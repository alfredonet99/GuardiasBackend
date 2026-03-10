<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Operaciones\Tickets;
use App\Models\Operaciones\Guardias;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Database\QueryException;
use Throwable;
use Illuminate\Support\Carbon;

class TicketsController extends Controller
{

    protected array $statusTicket;

    public function __construct()
    {
        $this->statusTicket = [
            1 => 'Abierto',
            2 => 'Concluido',
            3 => 'Anulado',
        ];
    }
   public function index(Request $request)
{
    $user    = $request->user();
    $search  = trim((string) $request->query('search', ''));
    $status  = trim((string) $request->query('status', ''));
    $perPage = 20;

    // ✅ Ahora todos pueden ver todos los tickets
    $canSeeAll = true;

    $months = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5,
        'junio' => 6, 'julio' => 7, 'agosto' => 8, 'septiembre' => 9,
        'setiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
    ];

    $days = [
        'domingo' => 1, 'lunes' => 2, 'martes' => 3,
        'miércoles' => 4, 'miercoles' => 4,
        'jueves' => 5, 'viernes' => 6,
        'sábado' => 7, 'sabado' => 7,
    ];

    $query = Tickets::query()
        ->with(['creator', 'assignedUser']);

    // ✅ FILTRO POR STATUS
    if ($status !== '' && ctype_digit($status)) {
        $st = (int) $status;
        if (in_array($st, [1, 2, 3], true)) {
            $query->where('status', $st);
        }
    }

    if ($search !== '') {
        $qRaw = $search;
        $q = mb_strtolower($search, 'UTF-8');
        $qNoAccents = Str::of($q)->ascii()->toString();

        $query->where(function ($w) use ($qRaw, $q, $qNoAccents, $months, $days) {

            // 1) NÚMEROS: id / status / numTicket
            if (ctype_digit($qNoAccents)) {
                $num = (int) $qNoAccents;

                $w->orWhere('id', $num)
                  ->orWhere('status', $num);

                $w->orWhere('numTicket', 'like', "%{$qNoAccents}%");
            }

            // 2) USUARIO CREADOR (nombre)
            $w->orWhereHas('creator', function ($sub) use ($qRaw) {
                $sub->where('name', 'like', "%{$qRaw}%");
            });

            // 3) STATUS (texto)
            if (str_contains($qNoAccents, 'abierto')) {
                $w->orWhere('status', 1);
            }
            if (str_contains($qNoAccents, 'concluido') || str_contains($qNoAccents, 'concluído')) {
                $w->orWhere('status', 2);
            }
            if (str_contains($qNoAccents, 'anulado')) {
                $w->orWhere('status', 3);
            }

            // 4) FECHAS (created_at)
            $weekdayValue = null;
            foreach ($days as $dayName => $dayValue) {
                $dn = Str::of($dayName)->ascii()->toString();
                if (str_contains($qNoAccents, $dn)) {
                    $weekdayValue = $dayValue;
                    break;
                }
            }

            $dayOfMonth = null;
            if (preg_match('/\b([1-9]|[12]\d|3[01])\b/', $qNoAccents, $dm)) {
                $dayOfMonth = (int) $dm[1];
            }

            if ($weekdayValue !== null) {
                if ($dayOfMonth !== null) {
                    $w->orWhereRaw('DAYOFWEEK(created_at) = ? AND DAY(created_at) = ?', [$weekdayValue, $dayOfMonth]);
                } else {
                    $w->orWhereRaw('DAYOFWEEK(created_at) = ?', [$weekdayValue]);
                }
            }

            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})$/', $qNoAccents, $mShort)) {
                $day = (int) $mShort[1];
                $mon = (int) $mShort[2];

                if ($day >= 1 && $day <= 31 && $mon >= 1 && $mon <= 12) {
                    $w->orWhere(function ($qq) use ($day, $mon) {
                        $qq->whereDay('created_at', $day)
                           ->whereMonth('created_at', $mon);
                    });
                }
            }

            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2}|\d{4})$/', $qNoAccents, $m)) {
                $day  = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $mon  = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $yr   = $m[3];
                $year = strlen($yr) === 2 ? ('20' . $yr) : $yr;

                $date = "{$year}-{$mon}-{$day}";
                $w->orWhereDate('created_at', $date);
            }

            if (preg_match('/\b(\d{1,2})\s+de\s+([a-záéíóú]+)\s+(?:del|de)\s+(\d{4})\b/u', $q, $m2)) {
                $day       = str_pad($m2[1], 2, '0', STR_PAD_LEFT);
                $monthName = Str::of($m2[2])->ascii()->toString();
                $year      = $m2[3];

                if (isset($months[$monthName])) {
                    $mon  = str_pad((string) $months[$monthName], 2, '0', STR_PAD_LEFT);
                    $date = "{$year}-{$mon}-{$day}";
                    $w->orWhereDate('created_at', $date);
                }
            }

            if (preg_match('/\b([a-záéíóú]+)\s+(?:del|de)\s+(20\d{2})\b/u', $q, $m3)) {
                $monthName = Str::of($m3[1])->ascii()->toString();
                $year      = (int) $m3[2];

                if (isset($months[$monthName])) {
                    $mon = (int) $months[$monthName];
                    $w->orWhere(function ($qq) use ($mon, $year) {
                        $qq->whereMonth('created_at', $mon)->whereYear('created_at', $year);
                    });
                }
            }

            foreach ($months as $monthName => $mon) {
                if (str_contains($qNoAccents, $monthName)) {
                    $w->orWhereMonth('created_at', $mon);
                    break;
                }
            }

            if (preg_match('/^20\d{2}$/', $qNoAccents)) {
                $year = (int) $qNoAccents;
                $w->orWhereYear('created_at', $year);
            }
        });
    }

    $tickets = $query
    ->orderByDesc('updated_at')
    ->orderByDesc('created_at')
    ->paginate($perPage)
    ->withQueryString();

    return response()->json([
        'tickets'      => $tickets,
        'statusTicket' => $this->statusTicket,
        'canSeeAll'    => $canSeeAll,
    ]);
}



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }




 public function store(Request $request)
{
    $authUser   = $request->user();
    $authUserId = (int) $authUser->id;

    $isAdmin = method_exists($authUser, 'isAdmin')
        ? (bool) $authUser->isAdmin()
        : $authUser->roles()->where('name', 'Administrador')->exists();

    $data = $request->validate([
        'numTicket' => [
            'required',
            'integer',
            Rule::unique('tickets', 'numTicket'),
        ],
        'numTicketNoct' => ['nullable', 'integer'],
        'titleTicket' => ['required', 'string', 'max:100'],
        'descriptionTicket' => ['required', 'string', 'max:2000'],
        'creator_user_id' => [
            'nullable',
            'integer',
            Rule::exists('users', 'id')->where(fn ($q) => $q->where('Activo', 1)),
        ],
    ]);

    // ✅ CREADOR DEL TICKET
    // Usuario normal: siempre él mismo
    // Admin: puede decidir otro creador
    $creatorUserId = $authUserId;
    if ($isAdmin && !empty($data['creator_user_id'])) {
        $creatorUserId = (int) $data['creator_user_id'];
    }

    // ✅ USUARIO ASIGNADO
    // Al crear, debe quedar igual al usuario seleccionado como creador
    $assignedUserId = $creatorUserId;

    // ✅ GUARDIA
    // Se toma del mismo usuario seleccionado como creador/asignado
    $guardiaId = Guardias::query()
        ->where('id_user', $creatorUserId)
        ->where('status', 1)
        ->whereNull('dateFinish')
        ->orderByDesc('dateInit')
        ->value('id');

    $ticket = Tickets::create([
        'numTicket'          => (int) $data['numTicket'],
        'numTicketNoct'      => $data['numTicketNoct'] !== null ? (int) $data['numTicketNoct'] : null,
        'user_create_ticket' => $creatorUserId,
        'assigned_user_id'   => $assignedUserId,
        'titleTicket'        => $data['titleTicket'],
        'descriptionTicket'  => $data['descriptionTicket'],
        'status'             => 1,
        'id_guardia'         => $guardiaId,
    ]);

    return response()->json([
        'ticket' => $ticket,
        'guardia_detectada' => (bool) $guardiaId,
        'guardia_id' => $guardiaId,
        'message' => $guardiaId
            ? 'Ticket creado (guardia activa detectada).'
            : 'Ticket creado (sin guardia activa).',
    ], 201);
}

   public function edit(string $id, Request $request)
{
    $ticket = Tickets::query()
        ->with(['creator', 'assignedUser'])
        ->whereKey($id)
        ->first();

    if (! $ticket) {
        return response()->json([
            'success' => false,
            'message' => 'Ticket no encontrado.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'ticket'  => $ticket,
    ]);
}



    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, string $id)
{
    $user = $request->user();
    $uid  = (int) $user->id;

    $ticket = Tickets::query()
        ->with(['creator', 'assignedUser'])
        ->whereKey($id)
        ->first();

    if (! $ticket) {
        return response()->json([
            'success' => false,
            'message' => 'Ticket no encontrado.',
        ], 404);
    }

    $data = $request->validate([
        'numTicket' => ['sometimes', 'required', 'integer'],
        'numTicketNoct' => ['sometimes', 'nullable', 'integer'],
        'titleTicket' => ['sometimes', 'required', 'string', 'max:100'],
        'descriptionTicket' => ['sometimes', 'required', 'string', 'max:2000'],
        'status' => ['sometimes', 'required', 'integer', Rule::in([1, 2, 3])],
    ]);

    if (array_key_exists('numTicket', $data)) {
        $ticket->numTicket = (int) $data['numTicket'];
    }

    if (array_key_exists('numTicketNoct', $data)) {
        $ticket->numTicketNoct = $data['numTicketNoct'] !== null
            ? (int) $data['numTicketNoct']
            : null;
    }

    if (array_key_exists('titleTicket', $data)) {
        $ticket->titleTicket = $data['titleTicket'];
    }

    if (array_key_exists('descriptionTicket', $data)) {
        $ticket->descriptionTicket = $data['descriptionTicket'];
    }

    if (array_key_exists('status', $data)) {
        $ticket->status = (int) $data['status'];
    }

    // ✅ Siempre se reasigna al usuario que está modificando
    $ticket->assigned_user_id = $uid;

    // ✅ Buscar guardia activa del usuario que está modificando
    $activeGuardiaId = Guardias::query()
        ->where('id_user', $uid)
        ->where('status', 1)
        ->whereNull('dateFinish')
        ->orderByDesc('dateInit')
        ->value('id');

    // ✅ Si existe guardia activa, se actualiza en el ticket
    if ($activeGuardiaId) {
        $ticket->id_guardia = (int) $activeGuardiaId;
    }

    $ticket->save();
    $ticket->load(['creator', 'assignedUser']);

    return response()->json([
        'success' => true,
        'ticket'  => $ticket,
        'guardia_detectada' => (bool) $activeGuardiaId,
        'guardia_id' => $activeGuardiaId,
        'message' => $activeGuardiaId
            ? 'Ticket actualizado correctamente (guardia activa detectada).'
            : 'Ticket actualizado correctamente (sin guardia activa).',
    ], 200);
}
public function updateCloseTickets(Request $request)
{
    $traceId = (string) Str::uuid();

    try {
        Log::info('[updateCloseTickets] START', [
            'trace_id' => $traceId,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_id' => optional($request->user())->id,
        ]);

        $authUser = $request->user();
        if (! $authUser) {
            Log::warning('[updateCloseTickets] No autenticado', ['trace_id' => $traceId]);
            abort(401, 'No autenticado.');
        }

        $authUserId = (int) $authUser->id;

        $isAdmin = method_exists($authUser, 'isAdmin')
            ? (bool) $authUser->isAdmin()
            : $authUser->roles()->where('name', 'Administrador')->exists();

        Log::info('[updateCloseTickets] Auth context', [
            'trace_id' => $traceId,
            'auth_user_id' => $authUserId,
            'is_admin' => $isAdmin,
        ]);

        Log::info('[updateCloseTickets] Incoming payload summary', [
            'trace_id' => $traceId,
            'guardia_id' => $request->input('guardia_id'),
            'tickets_count' => is_array($request->input('tickets')) ? count($request->input('tickets')) : null,
            'first_ticket' => $request->input('tickets.0'),
        ]);

        $data = $request->validate([
            'guardia_id' => ['required', 'integer', Rule::exists('info_guard', 'id')],

            'tickets' => ['required', 'array', 'min:1'],
            'tickets.*.id' => ['required', 'integer', Rule::exists('tickets', 'id')],
            'tickets.*.numTicket' => ['required', 'integer'],
            'tickets.*.numTicketNoct' => ['nullable', 'integer'],
            'tickets.*.titleTicket' => ['required', 'string', 'max:100'],
            'tickets.*.descriptionTicket' => ['required', 'string', 'max:2000'],
            'tickets.*.status' => ['required', 'integer', Rule::in([1, 2])],
        ]);

        $guardia = Guardias::query()
            ->where('id', (int) $data['guardia_id'])
            ->where('status', 1)
            ->whereNull('dateFinish')
            ->first();

        Log::info('[updateCloseTickets] Target guardia lookup', [
            'trace_id' => $traceId,
            'guardia_id' => (int) ($data['guardia_id'] ?? 0),
            'guardia_found' => (bool) $guardia,
        ]);

        if (! $guardia) {
            Log::warning('[updateCloseTickets] No hay guardia activa objetivo', [
                'trace_id' => $traceId,
                'requested_guardia_id' => (int) ($data['guardia_id'] ?? 0),
            ]);

            abort(422, 'No hay guardia activa para procesar.');
        }

        $guardiaId = (int) $guardia->id;
        $guardiaOwnerUserId = (int) $guardia->id_user;

        // ✅ Permiso sobre la guardia objetivo
        if (! $isAdmin && $guardiaOwnerUserId !== $authUserId) {
            Log::warning('[updateCloseTickets] Intento no autorizado sobre guardia ajena', [
                'trace_id' => $traceId,
                'auth_user_id' => $authUserId,
                'guardia_id' => $guardiaId,
                'guardia_owner_user_id' => $guardiaOwnerUserId,
            ]);

            abort(403, 'No puedes actualizar tickets de una guardia que no te pertenece.');
        }

        return DB::transaction(function () use ($data, $traceId, $guardiaId, $guardiaOwnerUserId, $authUserId, $isAdmin) {
            Log::info('[updateCloseTickets] TX START', [
                'trace_id' => $traceId,
                'guardia_id' => $guardiaId,
                'guardia_owner_user_id' => $guardiaOwnerUserId,
                'closed_by_user_id' => $authUserId,
                'closed_by_is_admin' => $isAdmin,
            ]);

            $ids = collect($data['tickets'])
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $ticketsDb = Tickets::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            Log::info('[updateCloseTickets] Tickets loaded & locked', [
                'trace_id' => $traceId,
                'db_count' => $ticketsDb->count(),
            ]);

            $updatedIds = [];

            foreach ($data['tickets'] as $row) {
                $id = (int) $row['id'];

                /** @var Tickets $ticket */
                $ticket = $ticketsDb->get($id);
                if (! $ticket) {
                    abort(404, "Ticket no encontrado: {$id}");
                }

                $nextStatus = (int) $row['status'];

                $ticket->numTicket = (int) $row['numTicket'];
                $ticket->numTicketNoct = $row['numTicketNoct'] !== null
                    ? (int) $row['numTicketNoct']
                    : null;
                $ticket->titleTicket = $row['titleTicket'];
                $ticket->descriptionTicket = $row['descriptionTicket'];
                $ticket->status = $nextStatus;

                // ✅ Backend decide el asignado:
                // siempre queda con el usuario dueño de la guardia
                $ticket->assigned_user_id = $guardiaOwnerUserId;

                // ✅ Liga los tickets a la guardia objetivo real
                $ticket->id_guardia = $guardiaId;

                $ticket->save();
                $updatedIds[] = $ticket->id;
            }

            $ticketsToSend = Tickets::query()
                ->where('id_guardia', $guardiaId)
                ->with(['creator:id,name,email', 'assignedUser:id,name,email'])
                ->orderBy('id')
                ->get();

            Log::info('[updateCloseTickets] TX OK', [
                'trace_id' => $traceId,
                'guardia_id' => $guardiaId,
                'guardia_owner_user_id' => $guardiaOwnerUserId,
                'closed_by_user_id' => $authUserId,
                'closed_by_is_admin' => $isAdmin,
                'updated_ticket_ids' => $updatedIds,
                'tickets_to_send_count' => $ticketsToSend->count(),
            ]);

            return response()->json([
                'trace_id' => $traceId,
                'message' => 'Tickets actualizados correctamente.',
                'guardia_id' => $guardiaId,
                'guardia_owner_user_id' => $guardiaOwnerUserId,
                'updated_ticket_ids' => $updatedIds,
                'tickets' => $ticketsToSend,
            ]);
        });

    } catch (ValidationException $e) {
        return response()->json([
            'trace_id' => $traceId,
            'message' => 'Validación fallida.',
            'errors' => $e->errors(),
        ], 422);

    } catch (HttpExceptionInterface $e) {
        return response()->json([
            'trace_id' => $traceId,
            'message' => $e->getMessage(),
        ], $e->getStatusCode());

    } catch (QueryException $e) {
        Log::error('[updateCloseTickets] DB QUERY ERROR', [
            'trace_id' => $traceId,
            'message' => $e->getMessage(),
            'sql' => $e->getSql(),
            'bindings' => $e->getBindings(),
        ]);

        return response()->json([
            'trace_id' => $traceId,
            'message' => 'Error de base de datos.',
        ], 500);

    } catch (Throwable $e) {
        Log::error('[updateCloseTickets] UNEXPECTED ERROR', [
            'trace_id' => $traceId,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'trace_id' => $traceId,
            'message' => 'Error inesperado al actualizar tickets.',
        ], 500);
    }
}

    public function show(string $id, Request $request)
    {
        $user = $request->user();
        $uid  = (int) $user->id;

        $canViewAll = $user->roles()
            ->whereIn('name', ['Administrador', 'Service Support Cloud Coordinator'])
            ->exists();

        // 1) Primero: existe?
        $ticket = Tickets::query()
            ->with(['creator', 'assignedUser'])
            ->whereKey($id)
            ->first();

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket no encontrado.',
            ], 404);
        }

        // 2) Luego: permiso (solo dueño o asignado, si no es admin/coordinator)
        if (! $canViewAll) {
            $isOwnerOrAssigned =
                ((int) $ticket->user_create_ticket === $uid) ||
                ((int) $ticket->assigned_user_id === $uid);

            if (! $isOwnerOrAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver este ticket.',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'ticket'  => $ticket,
            'statusTicket' => $this->statusTicket,
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request)
{
    $user = $request->user();

    if (! $user) {
        return response()->json([
            'success' => false,
            'message' => 'No autenticado.',
        ], 401);
    }

    // ✅ SOLO estos roles pueden eliminar (sin excepciones)
    $canDelete = $user->roles()
        ->whereIn('name', ['Administrador', 'Service Support Cloud Coordinator'])
        ->exists();

    if (! $canDelete) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para eliminar tickets.',
        ], 403);
    }

    $ticket = Tickets::query()->whereKey($id)->first();

    if (! $ticket) {
        return response()->json([
            'success' => false,
            'message' => 'Ticket no encontrado.',
        ], 404);
    }

    // ✅ Regla: si está Concluido (2) NO se elimina
    if ((int) $ticket->status === 2) {
        return response()->json([
            'success' => false,
            'message' => 'No se puede eliminar un ticket Concluido.',
        ], 422);
    }

    try {
        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ticket eliminado correctamente.',
        ], 200);

    } catch (Throwable $e) {
        Log::error('[Tickets.destroy] Error al eliminar ticket', [
            'ticket_id' => $id,
            'user_id'   => (int) $user->id,
            'message'   => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'No se pudo eliminar el ticket. Intenta de nuevo.',
        ], 500);
    }
}


    public function StatusTicket(int $id, Request $request)
    {
        $data = $request->validate([
            'status' => ['required', 'integer', Rule::in([1, 3])],
        ]);

        $ticket = Tickets::find($id);

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket no encontrado.',
            ], 404);
        }

        // ✅ Si está Concluido (2), no se puede cambiar
        if ((int) $ticket->status === 2) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar un ticket Concluido.',
            ], 422);
        }

        $current = (int) $ticket->status;
        $next    = (int) $data['status'];

        // ✅ Solo transiciones 1 <-> 3
        $allowed =
            ($current === 1 && $next === 3) ||
            ($current === 3 && $next === 1);

        if (! $allowed) {
            return response()->json([
                'success' => false,
                'message' => 'Transición de estatus no permitida. Solo se permite Abierto ⇄ Anulado.',
            ], 422);
        }

        $ticket->status = $next;
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'Estatus actualizado.',
            'data'    => $ticket,
            'status_label' => $this->statusTicket[$ticket->status] ?? $ticket->status,
        ]);
    }


    public function CloseTicket(int $id, Request $request)
{
    // ✅ solo permite concluir
    $data = $request->validate([
        'status' => ['required', 'integer', Rule::in([2])],
    ]);

    $ticket = Tickets::find($id);

    if (! $ticket) {
        return response()->json([
            'success' => false,
            'message' => 'Ticket no encontrado.',
        ], 404);
    }

    $current = (int) $ticket->status;

    // ✅ si ya está concluido, no hagas nada (o puedes regresar 422 si prefieres)
    if ($current === 2) {
        return response()->json([
            'success' => true,
            'message' => 'El ticket ya está Concluido.',
            'data'    => $ticket,
            'status_label' => $this->statusTicket[$ticket->status] ?? $ticket->status,
        ]);
    }

    // ✅ NO permitir concluir si está anulado
    if ($current === 3) {
        return response()->json([
            'success' => false,
            'message' => 'No se puede concluir un ticket Anulado. Reactívalo primero.',
        ], 422);
    }

    // ✅ solo transición 1 -> 2
    if ($current !== 1) {
        return response()->json([
            'success' => false,
            'message' => 'Transición de estatus no permitida. Solo se permite Abierto → Concluido.',
        ], 422);
    }

    $ticket->status = 2;
    $ticket->save();

    return response()->json([
        'success' => true,
        'message' => 'Ticket concluido.',
        'data'    => $ticket,
        'status_label' => $this->statusTicket[$ticket->status] ?? $ticket->status,
    ]);
}


public function dashboardTickets(Request $request)
{
    $user = $request->user('api') ?? $request->user();

    if (! $user) {
        return response()->json(['message' => 'No autenticado'], 401);
    }

    $uid = (int) $user->id;

    // Semana actual (Lun-Dom)
    $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
    $weekEnd   = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

    // Mes actual
    $monthStart = Carbon::now()->startOfMonth()->startOfDay();
    $monthEnd   = Carbon::now()->endOfMonth()->endOfDay();

    // ✅ Query base por ASIGNADO
    $assignedQuery = Tickets::query()->where('assigned_user_id', $uid);

    // ✅ Query base por CREADOR
    $creatorQuery = Tickets::query()->where('user_create_ticket', $uid);

    // 1) Pendientes esta semana (ASIGNADOS)
    $ticketsPendientesSemana = (clone $assignedQuery)
        ->where('status', 1)
        ->whereBetween('created_at', [$weekStart, $weekEnd])
        ->count();

    // 2) Creados esta semana (CREADOS por el usuario)
    $ticketsCreadosSemana = (clone $creatorQuery)
        ->whereBetween('created_at', [$weekStart, $weekEnd])
        ->count();

    // 3) Total creados mes (CREADOS por el usuario)
    $totalTicketsCreadosMes = (clone $creatorQuery)
        ->whereBetween('created_at', [$monthStart, $monthEnd])
        ->count();

    // ✅ 4) Total concluidos mes (CONCLUIDOS por el usuario)
    // usamos assigned + updated_at porque concluir ocurre en la actualización
    $totalTicketsConcluidosMes = (clone $assignedQuery)
        ->where('status', 2)
        ->whereBetween('updated_at', [$monthStart, $monthEnd])
        ->count();

    // 5) Total anulados mes (ANULADOS por el usuario)
    // ✅ ASIGNADOS + updated_at (porque el anulado ocurre al actualizar)
    $totalTicketsAnuladosMes = (clone $assignedQuery)
        ->where('status', 3)
        ->whereBetween('updated_at', [$monthStart, $monthEnd])
        ->count();

    // ✅ Tabla: ABIERTOS + ANULADOS (para que NO desaparezcan al anular)
    $pendingTickets = (clone $assignedQuery)
        ->with([
            'creator:id,name',
            'assignedUser:id,name',
        ])
        ->whereIn('status', [1, 3])
        ->orderByDesc('created_at')
        ->get([
            'id',
            'numTicket',
            'numTicketNoct',
            'titleTicket',
            'status',
            'created_at',
            'updated_at',
            'user_create_ticket',
            'assigned_user_id',
        ]);

    return response()->json([
        'week' => [
            'start' => $weekStart->toDateString(),
            'end'   => $weekEnd->toDateString(),
        ],
        'month' => [
            'start' => $monthStart->toDateString(),
            'end'   => $monthEnd->toDateString(),
        ],
        'counts' => [
            'pending_week'     => $ticketsPendientesSemana,
            'created_week'     => $ticketsCreadosSemana,
            'total_created'    => $totalTicketsCreadosMes,
            'total_concluded'  => $totalTicketsConcluidosMes,
            'total_annulled'   => $totalTicketsAnuladosMes,
        ],
        'pending_count' => $pendingTickets->count(),
        'pending_tickets' => $pendingTickets,
        'user' => [
            'id' => $uid,
            'name' => $user->name ?? null,
        ],
    ]);
}


}
