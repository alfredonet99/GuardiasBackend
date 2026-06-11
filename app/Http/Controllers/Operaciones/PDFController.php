<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\Tickets;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PDFController extends Controller
{
    public function pdfticket(Request $request)
    {
        $validated = $request->validate([
            'date_mode'   => ['required', Rule::in(['range', 'month'])],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date'],
            'month'       => ['nullable', 'date_format:Y-m'],
            'statuses'    => ['required'],
            'statuses.*'  => ['nullable', 'integer', Rule::in([1, 2, 3])],
            'search'      => ['nullable', 'string', 'max:255'],
        ]);

        $dateMode  = $validated['date_mode'];
        $startDate = $validated['start_date'] ?? null;
        $endDate   = $validated['end_date'] ?? null;
        $month     = $validated['month'] ?? null;
        $statuses  = $validated['statuses'];
        $search    = trim((string) ($validated['search'] ?? ''));

        if ($dateMode === 'range') {
            if (!$startDate || !$endDate) {
                return response()->json([
                    'message' => 'Para exportar por rango debes enviar fecha inicial y fecha final.'
                ], 422);
            }

            if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
                return response()->json([
                    'message' => 'La fecha inicial no puede ser mayor a la fecha final.'
                ], 422);
            }
        }

        if ($dateMode === 'month' && !$month) {
            return response()->json([
                'message' => 'Para exportar por mes debes enviar el mes.'
            ], 422);
        }

        $query = Tickets::query()
            ->with([
                'creator:id,name',
                'assignedUser:id,name',
            ]);

        // filtro por fecha
        if ($dateMode === 'range') {
            $start = Carbon::parse($startDate)->startOfDay();
            $end   = Carbon::parse($endDate)->endOfDay();

            $query->whereBetween('created_at', [$start, $end]);
        }

        if ($dateMode === 'month') {
            $monthDate = Carbon::createFromFormat('Y-m', $month);

            $query->whereYear('created_at', $monthDate->year)
                ->whereMonth('created_at', $monthDate->month);
        }

        // filtro por estatus
        if ($statuses !== 'all') {
            $statusesArray = is_array($statuses)
                ? array_values(array_unique(array_map('intval', $statuses)))
                : [];

            if (!empty($statusesArray)) {
                $query->whereIn('status', $statusesArray);
            }
        }

        // búsqueda opcional
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('numTicket', 'like', "%{$search}%")
                    ->orWhere('numTicketNoct', 'like', "%{$search}%")
                    ->orWhere('titleTicket', 'like', "%{$search}%")
                    ->orWhere('descriptionTicket', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('assignedUser', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $tickets = $query
            ->orderByDesc('created_at')
            ->get();

        $statusMap = [
            1 => 'Abierto',
            2 => 'En proceso',
            3 => 'Anulado',
        ];

        $rows = $tickets->map(function ($ticket) use ($statusMap) {
            return [
                'id'                   => $ticket->id,
                'numTicket'            => $ticket->numTicket,
                'numTicketNoct'        => $ticket->numTicketNoct,
                'creado_por'           => $ticket->creator?->name ?? '—',
                'titulo'               => $ticket->titleTicket ?? '—',
                'descripcion'          => $ticket->descriptionTicket ?? '—',
                'creado'               => optional($ticket->created_at)?->format('Y-m-d H:i:s'),
                'actualizado'          => optional($ticket->updated_at)?->format('Y-m-d H:i:s'),
                'status'               => $ticket->status,
                'status_label'         => $statusMap[$ticket->status] ?? '—',
                'actualizo'            => $ticket->assignedUser?->name ?? '—',
            ];
        })->values();

        $filters = [
            'date_mode'  => $dateMode,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'month'      => $month,
            'statuses'   => $statuses,
            'search'     => $search,
        ];

        return response()->json([
            'message' => 'Reporte PDF de tickets generado correctamente.',
            'filters' => $filters,
            'total'   => $rows->count(),
            'rows'    => $rows,
        ]);
    }
}