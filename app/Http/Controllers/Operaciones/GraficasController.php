<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GraficasController extends Controller
{
    public function MonitWeeklyChart(Request $request)
    {
        $user = $request->user('api') ?? $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'No autenticado. Envía tu token.',
                'code'    => 'UNAUTHENTICATED',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Validar semana solicitada
        |--------------------------------------------------------------------------
        |
        | week_start es opcional.
        |
        | Si no se envía:
        | - Se utiliza la semana actual.
        |
        | Si se envía:
        | - Se toma cualquier fecha de esa semana.
        | - El backend encuentra el lunes correspondiente.
        | - El backend calcula automáticamente el domingo.
        |
        */

        $validator = Validator::make($request->all(), [
            'week_start' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'La semana seleccionada no es válida.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $userId = (int) $user->id;

        /*
        |--------------------------------------------------------------------------
        | Determinar semana solicitada
        |--------------------------------------------------------------------------
        */

        if ($request->filled('week_start')) {
            $requestedDate = Carbon::createFromFormat(
                'Y-m-d',
                $request->input('week_start')
            );

            $weekStart = $requestedDate
                ->copy()
                ->startOfWeek(Carbon::MONDAY)
                ->startOfDay();
        } else {
            $weekStart = Carbon::now()
                ->startOfWeek(Carbon::MONDAY)
                ->startOfDay();
        }

        $weekEnd = $weekStart
            ->copy()
            ->endOfWeek(Carbon::SUNDAY)
            ->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Comprobar si corresponde a la semana actual
        |--------------------------------------------------------------------------
        */

        $currentWeekStart = Carbon::now()
            ->startOfWeek(Carbon::MONDAY)
            ->startOfDay();

        $isCurrentWeek = $weekStart->isSameDay($currentWeekStart);

        /*
        |--------------------------------------------------------------------------
        | Monitoreos creados por día
        |--------------------------------------------------------------------------
        |
        | Se consideran:
        | - Usuario creador: user_Cre.
        | - Fecha de creación: created_at.
        |
        */

        $createdByDay = DB::table('monitoreos')
            ->selectRaw(
                'DATE(created_at) AS activity_date, COUNT(*) AS total'
            )
            ->where('user_Cre', $userId)
            ->whereBetween('created_at', [
                $weekStart,
                $weekEnd,
            ])
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'activity_date');

        /*
        |--------------------------------------------------------------------------
        | Monitoreos concluidos por día
        |--------------------------------------------------------------------------
        |
        | Se consideran:
        | - Estado concluido = 2.
        | - Usuario que actualizó: user_Upd.
        | - Fecha de actualización: updated_at.
        |
        */

        $concludedByDay = DB::table('monitoreos')
            ->selectRaw(
                'DATE(updated_at) AS activity_date, COUNT(*) AS total'
            )
            ->where('concluido', 2)
            ->where('user_Upd', $userId)
            ->whereBetween('updated_at', [
                $weekStart,
                $weekEnd,
            ])
            ->groupByRaw('DATE(updated_at)')
            ->pluck('total', 'activity_date');

        /*
        |--------------------------------------------------------------------------
        | Monitoreos anulados por día
        |--------------------------------------------------------------------------
        |
        | Se consideran:
        | - Estado anulado = 3.
        | - Usuario que actualizó: user_Upd.
        | - Fecha de actualización: updated_at.
        |
        */

        $annulledByDay = DB::table('monitoreos')
            ->selectRaw(
                'DATE(updated_at) AS activity_date, COUNT(*) AS total'
            )
            ->where('concluido', 3)
            ->where('user_Upd', $userId)
            ->whereBetween('updated_at', [
                $weekStart,
                $weekEnd,
            ])
            ->groupByRaw('DATE(updated_at)')
            ->pluck('total', 'activity_date');

        /*
        |--------------------------------------------------------------------------
        | Etiquetas de los días
        |--------------------------------------------------------------------------
        */

        $dayLabels = [
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mié',
            4 => 'Jue',
            5 => 'Vie',
            6 => 'Sáb',
            7 => 'Dom',
        ];

        /*
        |--------------------------------------------------------------------------
        | Construir los siete días de la gráfica
        |--------------------------------------------------------------------------
        |
        | Aunque un día no tenga actividad, se devuelve con valores en cero.
        |
        */

        $weeklyChart = collect(
            CarbonPeriod::create(
                $weekStart->copy()->startOfDay(),
                $weekEnd->copy()->startOfDay()
            )
        )->map(function (Carbon $date) use (
            $dayLabels,
            $createdByDay,
            $concludedByDay,
            $annulledByDay
        ) {
            $dateKey = $date->toDateString();

            return [
                'day'       => $dayLabels[$date->isoWeekday()],
                'date'      => $dateKey,
                'created'   => (int) ($createdByDay[$dateKey] ?? 0),
                'updated'   => 0,
                'concluded' => (int) ($concludedByDay[$dateKey] ?? 0),
                'annulled'  => (int) ($annulledByDay[$dateKey] ?? 0),
            ];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | Respuesta
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'week' => [
                'id' => $weekStart->toDateString(),

                'label' => $weekStart->format('d/m/Y')
                    . ' al '
                    . $weekEnd->format('d/m/Y'),

                'start' => $weekStart->toDateString(),
                'end'   => $weekEnd->toDateString(),

                'is_current' => $isCurrentWeek,
            ],

            'weekly_chart' => $weeklyChart,
        ], 200);
    }
}