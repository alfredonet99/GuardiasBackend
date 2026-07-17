<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GraficasController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GRÁFICA SEMANAL DE MONITOREOS
    |--------------------------------------------------------------------------
    */

    public function MonitWeeklyChart(Request $request): JsonResponse
    {
        /*
         * Obtenemos autenticación, usuario y periodo semanal
         * desde la estructura global.
         */
        $prepared = $this->prepareWeeklyContext($request);

        if (! $prepared['success']) {
            return $prepared['response'];
        }

        $context = $prepared['context'];

        $userId   = $context['user_id'];
        $weekStart = $context['week_start'];
        $weekEnd   = $context['week_end'];

        /*
        |--------------------------------------------------------------------------
        | Monitoreos creados por día
        |--------------------------------------------------------------------------
        |
        | Creados por el usuario autenticado.
        | Se contabilizan usando created_at.
        |
        */

        $createdByDay = $this->countRecordsByDay(
            DB::table('monitoreos')
                ->where('user_Cre', $userId),
            'created_at',
            $weekStart,
            $weekEnd
        );

        /*
        |--------------------------------------------------------------------------
        | Monitoreos concluidos por día
        |--------------------------------------------------------------------------
        |
        | concluido = 2
        | user_Upd = usuario autenticado
        | Se contabilizan usando updated_at.
        |
        */

        $concludedByDay = $this->countRecordsByDay(
            DB::table('monitoreos')
                ->where('concluido', 2)
                ->where('user_Upd', $userId),
            'updated_at',
            $weekStart,
            $weekEnd
        );

        /*
        |--------------------------------------------------------------------------
        | Monitoreos anulados por día
        |--------------------------------------------------------------------------
        |
        | concluido = 3
        | user_Upd = usuario autenticado
        | Se contabilizan usando updated_at.
        |
        */

        $annulledByDay = $this->countRecordsByDay(
            DB::table('monitoreos')
                ->where('concluido', 3)
                ->where('user_Upd', $userId),
            'updated_at',
            $weekStart,
            $weekEnd
        );

        /*
        |--------------------------------------------------------------------------
        | Construcción global de los siete días
        |--------------------------------------------------------------------------
        |
        | La función recibe las series que esta gráfica necesita.
        | Para monitoreos:
        |
        | - created
        | - concluded
        | - annulled
        |
        */

        $weeklyChart = $this->buildWeeklyChart(
            $weekStart,
            $weekEnd,
            [
                'created'   => $createdByDay,
                'concluded' => $concludedByDay,
                'annulled'  => $annulledByDay,
            ]
        );

        /*
         * La respuesta también se construye con una función global.
         */
        return $this->weeklyChartResponse(
            $context,
            $weeklyChart
        );
    }

   public function TicketsWeekChart(Request $request): JsonResponse
{
    $prepared = $this->prepareWeeklyContext($request);

    if (! $prepared['success']) {
        return $prepared['response'];
    }

    $context = $prepared['context'];

    $userId   = $context['user_id'];
    $weekStart = $context['week_start'];
    $weekEnd   = $context['week_end'];

    // Tickets creados por día.
    $createdByDay = $this->countRecordsByDay(
        DB::table('tickets')
            ->where('user_create_ticket', $userId),
        'created_at',
        $weekStart,
        $weekEnd
    );

    // Tickets actualizados por día.
    $updatedByDay = $this->countRecordsByDay(
        DB::table('tickets')
            ->where('assigned_user_id', $userId)
            ->whereColumn('updated_at', '>', 'created_at'),
        'updated_at',
        $weekStart,
        $weekEnd
    );

    // Tickets concluidos por día.
    $concludedByDay = $this->countRecordsByDay(
        DB::table('tickets')
            ->where('status', 2)
            ->where('assigned_user_id', $userId),
        'updated_at',
        $weekStart,
        $weekEnd
    );

    // Tickets anulados por día.
    $annulledByDay = $this->countRecordsByDay(
        DB::table('tickets')
            ->where('status', 3)
            ->where('assigned_user_id', $userId),
        'updated_at',
        $weekStart,
        $weekEnd
    );

    $weeklyChart = $this->buildWeeklyChart(
        $weekStart,
        $weekEnd,
        [
            'created'   => $createdByDay,
            'updated'   => $updatedByDay,
            'concluded' => $concludedByDay,
            'annulled'  => $annulledByDay,
        ]
    );

    return $this->weeklyChartResponse(
        $context,
        $weeklyChart
    );
}

    /*
    |--------------------------------------------------------------------------
    | ESTRUCTURA GLOBAL: PREPARAR LA SEMANA
    |--------------------------------------------------------------------------
    |
    | Esta función se encarga de:
    |
    | - Validar el usuario.
    | - Validar week_start.
    | - Determinar lunes y domingo.
    | - Indicar si es la semana actual.
    |
    | Después podrá ser consumida por cualquier gráfica semanal.
    |
    */

    private function prepareWeeklyContext(Request $request): array
    {
        $user = $request->user('api') ?? $request->user();

        if (! $user) {
            return [
                'success' => false,

                'response' => response()->json([
                    'message' => 'No autenticado. Envía tu token.',
                    'code'    => 'UNAUTHENTICATED',
                ], 401),
            ];
        }

        $validator = Validator::make($request->all(), [
            'week_start' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,

                'response' => response()->json([
                    'message' => 'La semana seleccionada no es válida.',
                    'errors'  => $validator->errors(),
                ], 422),
            ];
        }

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

        $currentWeekStart = Carbon::now()
            ->startOfWeek(Carbon::MONDAY)
            ->startOfDay();

        return [
            'success' => true,

            'context' => [
                'user_id' => (int) $user->id,

                'user_name' => $user->name ?? null,

                'week_start' => $weekStart,

                'week_end' => $weekEnd,

                'is_current' => $weekStart->isSameDay(
                    $currentWeekStart
                ),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ESTRUCTURA GLOBAL: CONTAR REGISTROS POR DÍA
    |--------------------------------------------------------------------------
    |
    | Recibe:
    |
    | - Una consulta con sus filtros.
    | - La columna de fecha.
    | - Inicio y fin de semana.
    |
    | Devuelve:
    |
    | [
    |     '2026-07-13' => 3,
    |     '2026-07-14' => 5,
    | ]
    |
    */

    private function countRecordsByDay(
        $query,
        string $dateColumn,
        Carbon $weekStart,
        Carbon $weekEnd
    ): Collection {
        return $query
            ->selectRaw(
                "DATE({$dateColumn}) AS activity_date, COUNT(*) AS total"
            )
            ->whereBetween($dateColumn, [
                $weekStart,
                $weekEnd,
            ])
            ->groupByRaw("DATE({$dateColumn})")
            ->pluck('total', 'activity_date');
    }

    /*
    |--------------------------------------------------------------------------
    | ESTRUCTURA GLOBAL: CONSTRUIR LOS SIETE DÍAS
    |--------------------------------------------------------------------------
    |
    | Esta función no sabe si son monitoreos o tickets.
    |
    | Únicamente recibe las series:
    |
    | [
    |     'created' => Collection,
    |     'concluded' => Collection,
    |     'annulled' => Collection,
    | ]
    |
    */

    private function buildWeeklyChart(
        Carbon $weekStart,
        Carbon $weekEnd,
        array $series
    ): Collection {
        $dayLabels = [
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mié',
            4 => 'Jue',
            5 => 'Vie',
            6 => 'Sáb',
            7 => 'Dom',
        ];

        return collect(
            CarbonPeriod::create(
                $weekStart->copy()->startOfDay(),
                $weekEnd->copy()->startOfDay()
            )
        )
            ->map(function (Carbon $date) use (
                $dayLabels,
                $series
            ) {
                $dateKey = $date->toDateString();

                $row = [
                    'day'  => $dayLabels[$date->isoWeekday()],
                    'date' => $dateKey,
                ];

                /*
                 * Agrega dinámicamente cada serie recibida.
                 */
                foreach ($series as $seriesName => $valuesByDay) {
                    $row[$seriesName] = (int) (
                        $valuesByDay->get($dateKey, 0)
                    );
                }

                return $row;
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | ESTRUCTURA GLOBAL: RESPUESTA JSON
    |--------------------------------------------------------------------------
    */

    private function weeklyChartResponse(
        array $context,
        Collection $weeklyChart
    ): JsonResponse {
        $weekStart = $context['week_start'];
        $weekEnd   = $context['week_end'];

        return response()->json([
            'week' => [
                'id' => $weekStart->toDateString(),

                'label' => $weekStart->format('d/m/Y')
                    . ' al '
                    . $weekEnd->format('d/m/Y'),

                'start' => $weekStart->toDateString(),

                'end' => $weekEnd->toDateString(),

                'is_current' => $context['is_current'],
            ],

            'weekly_chart' => $weeklyChart,
        ], 200);
    }
}