<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Operaciones\Monitoreos;
use App\Models\Operaciones\ClienteVeeam;
use App\Models\Operaciones\Guardias;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MonitoreoController extends Controller
{

    protected array $statusMonit;

    public function __construct()
    {
        $this->statusMonit = [
            1 => 'Abierto',
            2 => 'Concluido',
            3 => 'Anulado',
        ];
    }
   public function index(Request $request)
{
    $search   = trim((string) $request->query('search', ''));
    $site     = trim((string) $request->query('site', ''));     
    $status   = trim((string) $request->query('status', ''));   
    $concluido = $request->query('concluido', null);

    $perPage = 100;

    $query = DB::table('monitoreos as m')
        ->join('app_service as a', 'a.id', '=', 'm.siteApp')
        ->leftJoin('users as uc', 'uc.id', '=', 'm.user_Cre')
        ->leftJoin('users as uu', 'uu.id', '=', 'm.user_Upd')
        ->leftJoin('c_veeam as cv', function ($join) {
            $join->on('cv.id', '=', 'm.client_id');
        })
        ->select([
            'm.id',
            'm.siteApp',
            'a.nameService as siteApp_name',

            DB::raw("
                CASE
                    WHEN a.nameService LIKE '%Veeam%' THEN 'veeam'
                    WHEN a.nameService LIKE '%Site24%' OR a.nameService LIKE '%Site24x7%' THEN 'site24'
                    WHEN a.nameService LIKE '%Sophos%' THEN 'sophos'
                    ELSE NULL
                END as site
            "),

            'm.client_id',
            'cv.numCV as client_code',
            'cv.nameCV as client_name',
            'cv.backup as client_backup',
            'cv.jobs as client_jobs',

            'm.dateRest',
            'm.estatus',
            'm.observacion',
            'm.concluido',
            'm.id_guard',

            'm.user_Cre',
            'uc.name as user_cre_name',

            'm.user_Upd',
            'uu.name as user_upd_name',

            'm.created_at',
            'm.updated_at',
        ]);

    if ($site !== '') {
        if ($site === 'veeam') {
            $query->where('a.nameService', 'like', '%Veeam%');
        } elseif ($site === 'site24') {
            $query->where(function ($w) {
                $w->where('a.nameService', 'like', '%Site24%')
                  ->orWhere('a.nameService', 'like', '%Site24x7%');
            });
        } elseif ($site === 'sophos') {
            $query->where('a.nameService', 'like', '%Sophos%');
        }
    }

    if ($status !== '') {
        if ($status === 'open') {
            $query->where('m.concluido', 1);
        } elseif ($status === 'done') {
            $query->where('m.concluido', 2);
        } elseif ($status === 'canceled') {
            $query->where('m.concluido', 3);
        }
    } else {
        if ($concluido !== null && $concluido !== '') {
            $query->where('m.concluido', (int) $concluido);
        }
    }

    if ($search !== '') {
        $query->where(function ($q) use ($search) {

            if (ctype_digit($search)) {
                $n = (int) $search;

                $q->where('m.id', $n)
                  ->orWhere('m.client_id', $n)
                  ->orWhere('m.siteApp', $n);

                return; 
            }


            $q->where('a.nameService', 'like', "%{$search}%")
              ->orWhere('m.observacion', 'like', "%{$search}%")
              ->orWhere('cv.numCV', 'like', "%{$search}%")
              ->orWhere('cv.nameCV', 'like', "%{$search}%")
              ->orWhere('cv.backup', 'like', "%{$search}%")
              ->orWhere('cv.jobs', 'like', "%{$search}%")
              ->orWhere('uc.name', 'like', "%{$search}%")
              ->orWhere('uu.name', 'like', "%{$search}%");
        });
    }

    $p = $query
        ->orderByDesc('m.created_at')
        ->orderByDesc('m.id')
        ->paginate($perPage);

    $items = collect($p->items())->map(function ($row) {
        $row = (array) $row;

        $row['concluido_label'] =
            $this->statusMonit[(int) ($row['concluido'] ?? 0)] ?? '—';

        $row['client_label'] = trim(
            ($row['client_code'] ?? '') . ' - ' . ($row['client_name'] ?? '')
        );

        if ($row['client_label'] === '-' || $row['client_label'] === '') {
            $row['client_label'] = '—';
        }

        return $row;
    })->values();

    return response()->json([
        'data' => $items,
        'meta' => [
            'current_page' => $p->currentPage(),
            'per_page'     => $p->perPage(),
            'total'        => $p->total(),
            'last_page'    => $p->lastPage(),
        ],
    ], 200);
}



 public function store(Request $request)
{
    $authUser = $request->user();
    if (! $authUser) {
        return response()->json([
            'message' => 'No autenticado. Envía tu token.',
            'code'    => 'UNAUTHENTICATED',
        ], 401);
    }

    $authUserId = (int) $authUser->id;

    $validator = Validator::make($request->all(), [
        'site' => ['required', 'string', Rule::in(['veeam', 'site24', 'sophos'])],
        'rows' => ['required', 'array', 'min:1'],

        'rows.*.client_id'   => ['required', 'integer', 'min:1'],
        'rows.*.siteApp'     => ['nullable', 'integer', 'min:1'],

        'rows.*.estatus'     => ['required', 'string'],
        'rows.*.observacion' => ['nullable', 'string', 'max:5000'],

        // ✅ dateRest NO obligatorio, pero si viene debe ser YYYY-MM-DD
        'rows.*.dateRest'    => ['nullable', 'date_format:Y-m-d'],
    ], [
        'rows.required' => 'No se recibieron registros.',
        'rows.*.dateRest.date_format' => 'dateRest debe venir en formato YYYY-MM-DD.',
    ]);

    $validator->after(function ($v) use ($request) {
        $site = (string) $request->input('site', '');
        $rows = (array) $request->input('rows', []);

        if ($site === 'veeam') {
            $veeamAppIds = DB::table('app_service')
                ->where('nameService', 'like', '%Veeam%')
                ->pluck('id')
                ->map(fn ($x) => (int) $x)
                ->all();

            $clientIds = collect($rows)
                ->pluck('client_id')
                ->filter()
                ->map(fn ($x) => (int) $x)
                ->unique()
                ->values()
                ->all();

            $appsByClient = ClienteVeeam::query()
                ->whereIn('id', $clientIds)
                ->pluck('app', 'id') // [client_id => app]
                ->map(fn ($x) => (int) $x)
                ->all();

            foreach ($rows as $i => $row) {
                $clientId = (int) ($row['client_id'] ?? 0);
                $estatus  = (string) ($row['estatus'] ?? '');

                $realApp = (int) ($appsByClient[$clientId] ?? 0);

                if (! $realApp) {
                    $v->errors()->add("rows.$i.client_id", 'Cliente Veeam no encontrado o sin app asignada.');
                    continue;
                }

                if (! in_array($realApp, $veeamAppIds, true)) {
                    $v->errors()->add("rows.$i.client_id", 'El cliente tiene un app no válido para Veeam.');
                }

                if (! in_array($estatus, ['1','2','3','4','5','6'], true)) {
                    $v->errors()->add("rows.$i.estatus", 'Estatus inválido para Veeam (solo 1..6).');
                }

                if (!empty($row['siteApp'])) {
                    $sent = (int) $row['siteApp'];
                    if ($sent !== $realApp) {
                        $v->errors()->add("rows.$i.siteApp", 'siteApp enviado no coincide con el app real del cliente.');
                    }
                }
            }

            return;
        }

        foreach ($rows as $i => $row) {
            $siteApp = (int) ($row['siteApp'] ?? 0);
            if ($siteApp <= 0) {
                $v->errors()->add("rows.$i.siteApp", 'siteApp es obligatorio para este site.');
            }
        }
    });

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validación fallida.',
            'errors'  => $validator->errors(),
        ], 422);
    }

    $site = (string) $request->input('site');
    $rows = (array) $request->input('rows');

    // ✅ Detectar guardia activa del usuario autenticado
    $guardiaId = Guardias::where('id_user', $authUserId)
        ->where('status', 1)
        ->orderByDesc('dateInit')
        ->value('id'); // puede ser null

    try {
        DB::transaction(function () use ($rows, $site, $authUserId, $guardiaId) {
            $now = now();
            $insert = [];

            $appsByClient = [];
            if ($site === 'veeam') {
                $clientIds = collect($rows)
                    ->pluck('client_id')
                    ->filter()
                    ->map(fn ($x) => (int) $x)
                    ->unique()
                    ->values()
                    ->all();

                $appsByClient = ClienteVeeam::query()
                    ->whereIn('id', $clientIds)
                    ->pluck('app', 'id')
                    ->map(fn ($x) => (int) $x)
                    ->all();
            }

            foreach ($rows as $r) {
                $estatus = (string) ($r['estatus'] ?? '');
                $clientId = (int) ($r['client_id'] ?? 0);

                // ✅ Regla concluido según estatus (tu lógica)
                // 1-2 => 2 | 3-6 => 1
                $concluido = in_array($estatus, ['1', '2'], true) ? 2 : 1;

                // ✅ siteApp derivado para veeam, requerido del payload para otros
                $siteApp = $site === 'veeam'
                    ? (int) ($appsByClient[$clientId] ?? 0)
                    : (int) ($r['siteApp'] ?? 0);

                $insert[] = [
                    'siteApp'     => $siteApp,
                    'client_id'   => $clientId,
                    'dateRest'    => $r['dateRest'] ?? null,
                    'estatus'     => $estatus,
                    'observacion' => $r['observacion'] ?? null,
                    'concluido'   => $concluido,
                    'id_guard'    => $guardiaId,
                    'user_Cre'    => $authUserId,
                    'user_Upd'    => $authUserId,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            Monitoreos::insert($insert);
        });

        return response()->json([
            'message'           => 'Monitoreos guardados correctamente.',
            'count'             => count($rows),
            'guardia_detectada' => (bool) $guardiaId,
            'guardia_id'        => $guardiaId,
        ], 201);
    } catch (Throwable $e) {
        Log::error('Monitoreo store failed', [
            'error'      => $e->getMessage(),
            'file'       => $e->getFile(),
            'line'       => $e->getLine(),
            'rows_count' => count($rows),
            'user_id'    => $authUserId,
            'site'       => $site,
            'guardia_id' => $guardiaId,
        ]);

        return response()->json([
            'message' => 'Error al guardar monitoreos.',
            'code'    => 'SERVER_ERROR',
            'debug'   => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}




    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function statusMonitoreo(int $id, Request $request)
    {
        $data = $request->validate([
            'concluido' => ['required', 'integer', Rule::in([1, 3])],
        ]);

        $monitoreo = Monitoreos::find($id);

        if (! $monitoreo) {
            return response()->json([
                'success' => false,
                'message' => 'Monitoreo no encontrado.',
            ], 404);
        }

        if ((int) $monitoreo->concluido === 2) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar un monitoreo Concluido.',
            ], 422);
        }

        $current = (int) $monitoreo->concluido;
        $next    = (int) $data['concluido'];

        $allowed =
            ($current === 1 && $next === 3) ||
            ($current === 3 && $next === 1);

        if (! $allowed) {
            return response()->json([
                'success' => false,
                'message' => 'Transición de estatus no permitida. Solo se permite Abierto ⇄ Anulado.',
            ], 422);
        }

        $monitoreo->concluido = $next;
        $monitoreo->save();

        return response()->json([
            'success' => true,
            'message' => 'Estatus actualizado.',
            'data'    => $monitoreo,
            'status_label' => $this->statusMonit[$monitoreo->concluido] ?? $monitoreo->concluido,
        ]);
    }
}
