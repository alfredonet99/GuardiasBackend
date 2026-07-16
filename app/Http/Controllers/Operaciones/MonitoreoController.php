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
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;
use Carbon\Carbon;

class MonitoreoController extends Controller
{

    protected array $statusMonit;
    protected array $statusVe = [];
    public function __construct()
    {
        $this->statusMonit = [
            1 => 'Abierto',
            2 => 'Concluido',
            3 => 'Anulado',
        ];

        $this->statusVe = [
            '1' => 'Completado Exitoso - Backup finalizado sin errores', '2' => 'Completado con Advertencias - Backup terminado pero con observaciones menores', 
            '3' => 'Fallido - Backup no se completó correctamente', '4' => ' En Progreso - Backup actualmente en ejecución',
            '5' => ' Pausado - Backup detenido temporalmente', '6' => ' Pendiente - Programado pero no iniciado',
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
    $user = request()->user();
    if (! $user) {
        return response()->json([
            'success' => false,
            'message' => 'No autenticado.',
        ], 401);
    }

    $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('Administrador');

    $q = Monitoreos::query()
        ->where('id', $id)
        ->with([
            'Appmv',
            'Cvm',
            'userCreate',
            'userUpdate',
            'guardiaMonit',
        ]);

    // ✅ Solo no-admin se restringe a Abierto/Anulado
    if (! $isAdmin) {
        $q->whereIn('concluido', [1, 3]);
    }

    $monitoreo = $q->first();

    if (! $monitoreo) {
        return response()->json([
            'success' => false,
            'message' => 'Monitoreo no disponible para edición.',
        ], 404);
    }

    return response()->json([
        'success'      => true,
        'message'      => 'Monitoreo listo para edición.',
        'data'         => $monitoreo,
        'is_admin'     => $isAdmin,
        'concluido_list' => $this->statusMonit,
        'estatus_list'   => $this->statusVe,
    ]);
}



    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, string $id)
{
    try {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No autenticado.'], 401);
        }

        $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('Administrador');
        $userId  = (int) $user->id;

        $data = $request->validate([
            'estatus'     => ['required', 'integer', Rule::in([1,2,3,4,5,6])],
            'dateRest'    => ['nullable', 'date'], // o date_format:Y-m-d
            'observacion' => ['nullable', 'string', 'max:2000'],
        ]);

        // ✅ Detectar guardia activa (solo no-admin)
        $guardiaId = null;
        if (! $isAdmin) {
            $guardiaId = Guardias::where('id_user', $userId)
                ->where('status', 1)
                ->orderByDesc('dateInit')
                ->value('id');
        }

        return DB::transaction(function () use ($id, $data, $isAdmin, $userId, $guardiaId) {

            $m = Monitoreos::whereKey($id)->lockForUpdate()->first();

            if (! $m) {
                return response()->json(['success' => false, 'message' => 'Monitoreo no encontrado.'], 404);
            }

            // ✅ No-admin: solo puede actualizar si está Abierto/Anulado
            if (! $isAdmin && ! in_array((int) $m->concluido, [1, 3], true)) {
                return response()->json(['success' => false, 'message' => 'Monitoreo no disponible para actualización.'], 403);
            }

            $estatus = (int) $data['estatus'];

            // ✅ REGLA: estatus 1-2 => concluido 2, estatus 3-6 => concluido 1
            $mappedConcluido = in_array($estatus, [1, 2], true) ? 2 : 1;

            // ✅ Actualiza campos
            $m->estatus     = $estatus;
            $m->dateRest    = $data['dateRest'] ?? null;
            $m->observacion = $data['observacion'] ?? null;

            // ✅ Siempre user_Upd
            if (array_key_exists('user_Upd', $m->getAttributes())) {
                $m->user_Upd = $userId;
            }

            // ✅ Si está ANULADO (3), lo conservamos como 3; si no, aplicamos mapeo
            if ((int) $m->concluido !== 3) {
                $m->concluido = $mappedConcluido;
            }

            // ✅ NUEVO: si hay guardia activa, FORZAR id_guard (aunque ya exista una registrada)
            if (! $isAdmin && $guardiaId) {
                $m->id_guard = (int) $guardiaId;
            }

            $m->save();

            return response()->json([
                'success' => true,
                'message' => 'Monitoreo actualizado.',
                'data'    => $m->fresh(),
            ]);
        });

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validación fallida.',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\Throwable $e) {
        Log::error('[Monitoreos.update] ERROR', [
            'id'    => $id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar.',
            'code'    => 'SERVER_ERROR',
        ], 500);
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
{
    try {
        $user = request()->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('Administrador');

        // Ajusta este nombre al permiso real que tengas registrado
        $canDelete = $user->can('monitoreos.delete');

        if (! $isAdmin && ! $canDelete) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar monitoreos.',
            ], 403);
        }

        $monitoreo = Monitoreos::find($id);

        if (! $monitoreo) {
            return response()->json([
                'success' => false,
                'message' => 'Monitoreo no encontrado.',
            ], 404);
        }

        // Usuario con permiso sí puede eliminar, pero no si ya está concluido
        if (! $isAdmin && (int) $monitoreo->concluido === 2) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar un monitoreo concluido.',
            ], 403);
        }

        $monitoreo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Monitoreo eliminado correctamente.',
        ], 200);

    } catch (Throwable $e) {
        Log::error('[Monitoreos.destroy] ERROR', [
            'id'    => $id,
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al eliminar monitoreo.',
            'code'    => 'SERVER_ERROR',
            'debug'   => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}

public function statusMonitoreo(int $id, Request $request)
{
    try {
        $data = $request->validate([
            'concluido' => ['required', 'integer', Rule::in([1, 3])],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $userId  = (int) $user->id;
        $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('Administrador');

        $guardiaId = $isAdmin ? null : Guardias::where('id_user', $userId)
            ->where('status', 1)
            ->orderByDesc('dateInit')
            ->value('id'); // puede ser null

        return DB::transaction(function () use ($id, $data, $guardiaId, $userId, $isAdmin) {

            $m = Monitoreos::whereKey($id)->lockForUpdate()->first();

            if (! $m) {
                return response()->json([
                    'success' => false,
                    'message' => 'Monitoreo no encontrado.',
                ], 404);
            }

            $current = (int) $m->concluido;
            $next    = (int) $data['concluido'];

            // No permitir si ya está concluido (2)
            if ($current === 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede modificar un monitoreo Concluido.',
                ], 422);
            }

            // Solo permitir 1 <-> 3
            $allowedTransitions = [
                1 => [3],
                3 => [1],
            ];

            if (! in_array($next, $allowedTransitions[$current] ?? [], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transición de estatus no permitida. Solo se permite 1 ⇄ 3.',
                ], 422);
            }

            // Siempre cambia concluido + user_Upd (si existe)
            $m->concluido = $next;

            if (array_key_exists('user_Upd', $m->getAttributes())) {
                $m->user_Upd = $userId;
            }

            // Solo no-admin con guardia detectada: reemplazar/asignar id_guard
            if (! $isAdmin && $guardiaId) {
                $curGuard = $m->id_guard ? (int) $m->id_guard : null;
                $newGuard = (int) $guardiaId;

                if ($curGuard !== $newGuard) {
                    $m->id_guard = $newGuard;
                }
            }

            $m->save();
            $fresh = $m->fresh();

            return response()->json([
                'success'           => true,
                'message'           => 'Estatus actualizado.',
                'data'              => $fresh,
                'status_label'      => $this->statusMonit[$fresh->concluido] ?? $fresh->concluido,
                'is_admin'          => $isAdmin,
                'guardia_detectada' => (! $isAdmin) && (bool) $guardiaId,
                'guardia_id'        => (! $isAdmin) ? $guardiaId : null,
            ]);
        });

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validación fallida.',
            'errors'  => $e->errors(),
        ], 422);

    } catch (Throwable $e) {
        Log::error('[statusMonitoreo] ERROR', [
            'id' => $id,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar estatus.',
            'code'    => 'SERVER_ERROR',
            'debug'   => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}



   public function pendientesVeeam()
{
    $veeamAppIds = DB::table('app_service')
        ->where('nameService', 'like', '%Veeam%')
        ->pluck('id')
        ->map(fn ($x) => (int) $x)
        ->all();

    $rows = DB::table('monitoreos as m')
        ->join('app_service as a', 'a.id', '=', 'm.siteApp')
        ->leftJoin('users as uc', 'uc.id', '=', 'm.user_Cre')
        ->leftJoin('c_veeam as cv', 'cv.id', '=', 'm.client_id')
        ->whereIn('m.siteApp', $veeamAppIds)
        ->where('m.concluido', 1)
        ->select([
            DB::raw('m.client_id as id'),
            DB::raw('m.id as monitoreo_id'),

            'm.id_guard',
            'm.siteApp',
            'a.nameService as siteApp_name',

            'cv.numCV as client_code',
            'cv.nameCV as client_name',
            'cv.backup as client_backup',
            'cv.jobs as client_jobs',

            'm.dateRest',
            'm.estatus',
            'm.observacion',
            'm.concluido',

            'm.user_Cre',
            'uc.name as user_cre_name',

            'm.created_at',
            'm.updated_at',
        ])
        ->orderByDesc('m.created_at')
        ->get()
        ->map(function ($r) {
            // ✅ estandariza nombres para el frontend (opcional pero útil)
            return [
                'id' => (int) $r->id, // client_id
                'monitoreo_id' => (int) $r->monitoreo_id,

                'id_guard' => $r->id_guard,
                'siteApp' => (int) $r->siteApp,
                'veeam_id' => (int) $r->siteApp,
                'veeam_name' => $r->siteApp_name,
                'site' => 'veeam',

                'numCV' => $r->client_code,
                'nameCV' => $r->client_name,
                'backup' => $r->client_backup,
                'jobs' => $r->client_jobs,

                'dateRest' => $r->dateRest,
                'estatus' => (string) $r->estatus,
                'observacion' => $r->observacion,
                'concluido' => (int) $r->concluido,

                'user_Cre' => (int) $r->user_Cre,
                'user_cre_name' => $r->user_cre_name,

                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ];
        })
        ->values();

    return response()->json([
        'items'  => $rows,
        'status' => $this->statusVe, // ✅ LO QUE TE FALTABA
        'source' => 'veeam',
    ], 200);
}

public function MonitGuardEditFromGuardia(Request $request, int $id)
{
    $authUser = $request->user();
    if (! $authUser) {
        return response()->json([
            'message' => 'No autenticado. Envía tu token.',
            'code'    => 'UNAUTHENTICATED',
        ], 401);
    }

    $authUserId = (int) $authUser->id;

    $isAdmin = method_exists($authUser, 'isAdmin')
        ? (bool) $authUser->isAdmin()
        : $authUser->roles()->where('name', 'Administrador')->exists();

    $guardia = Guardias::query()
        ->with('user:id,name,email')
        ->where('id', $id)
        ->where('status', 1)
        ->whereNull('dateFinish')
        ->first();

    if (! $guardia) {
        return response()->json([
            'message' => 'No se encontró una guardia activa válida.',
        ], 404);
    }

    $guardiaOwnerId = (int) $guardia->id_user;
    $isOwner = $guardiaOwnerId === $authUserId;

    if (! $isAdmin && ! $isOwner) {
        return response()->json([
            'message' => 'No tienes permisos para actualizar monitoreos de esta guardia.',
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'site' => ['required', 'string', Rule::in(['veeam', 'site24', 'sophos'])],
        'rows' => ['required', 'array', 'min:1'],

        // ✅ como son pendientes BD, aquí sí debe venir id
        'rows.*.id' => ['required', 'integer', 'min:1'],

        'rows.*.client_id'   => ['required', 'integer', 'min:1'],
        'rows.*.siteApp'     => ['nullable', 'integer', 'min:1'],
        'rows.*.estatus'     => ['required', 'string'],
        'rows.*.observacion' => ['nullable', 'string', 'max:5000'],
        'rows.*.dateRest'    => ['nullable', 'date_format:Y-m-d'],

        'sync' => ['nullable', 'boolean'],
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
                ->pluck('app', 'id')
                ->map(fn ($x) => (int) $x)
                ->all();

            foreach ($rows as $i => $row) {
                $clientId = (int) ($row['client_id'] ?? 0);
                $estatus  = trim((string) ($row['estatus'] ?? ''));

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

                if (! empty($row['siteApp'])) {
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
    $sync = (bool) $request->boolean('sync', false);

    Log::info('[MonitGuardEditFromGuardia] incoming', [
        'auth_user_id'        => $authUserId,
        'auth_is_admin'       => $isAdmin,
        'guardia_id'          => (int) $guardia->id,
        'guardia_owner_id'    => $guardiaOwnerId,
        'site'                => $site,
        'rows_n'              => count($rows),
        'sync'                => $sync,
        'rows_sample'         => array_slice($rows, 0, 3),
    ]);

    try {
        $result = DB::transaction(function () use ($rows, $site, $guardia, $guardiaOwnerId, $sync) {
            $now = now();

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

            $updated = 0;
            $notFoundIds = [];
            $skippedNotPending = 0;

            foreach ($rows as $idx => $r) {
                $id       = (int) ($r['id'] ?? 0);
                $estatus  = trim((string) ($r['estatus'] ?? ''));
                $clientId = (int) ($r['client_id'] ?? 0);

                $concluido = in_array($estatus, ['1','2'], true) ? 2 : 1;

                $siteApp = $site === 'veeam'
                    ? (int) ($appsByClient[$clientId] ?? 0)
                    : (int) ($r['siteApp'] ?? 0);

                $payload = [
                    'siteApp'     => $siteApp,
                    'client_id'   => $clientId,
                    'dateRest'    => $r['dateRest'] ?? null,
                    'estatus'     => $estatus,
                    'observacion' => $r['observacion'] ?? null,
                    'concluido'   => $concluido,

                    // ✅ REGLA DE GUARDIA
                    'id_guard'    => (int) $guardia->id,
                    'user_Upd'    => $guardiaOwnerId,

                    'updated_at'  => $now,
                ];

                $q = Monitoreos::query()
                    ->where('id', $id)
                    ->where('concluido', 1);

                $affected = $q->update($payload);

                if ($affected > 0) {
                    $updated += $affected;
                } else {
                    $exists = Monitoreos::query()->where('id', $id)->exists();

                    if (! $exists) {
                        $notFoundIds[] = $id;

                        Log::warning('[MonitGuardEditFromGuardia] update_no_rows_not_found', [
                            'idx'       => $idx,
                            'id'        => $id,
                            'client_id' => $clientId,
                            'estatus'   => $estatus,
                            'siteApp'   => $siteApp,
                        ]);
                    } else {
                        $skippedNotPending++;

                        Log::info('[MonitGuardEditFromGuardia] update_skip_not_pending', [
                            'idx'       => $idx,
                            'id'        => $id,
                            'client_id' => $clientId,
                            'estatus'   => $estatus,
                        ]);
                    }
                }
            }

            $deleted = 0;
            if ($sync) {
                // ⚠️ mantener apagado hasta definir alcance exacto
            }

            return [
                'updated_n'             => $updated,
                'not_found_ids'         => $notFoundIds,
                'skipped_not_pending_n' => $skippedNotPending,
                'deleted_n'             => $deleted,
            ];
        });

        Log::info('[MonitGuardEditFromGuardia] tx_result', [
            'auth_user_id'           => $authUserId,
            'guardia_id'             => (int) $guardia->id,
            'guardia_owner_id'       => $guardiaOwnerId,
            'site'                   => $site,
            'rows_received'          => count($rows),
            'updated_n'              => $result['updated_n'],
            'not_found_ids'          => $result['not_found_ids'],
            'skipped_not_pending_n'  => $result['skipped_not_pending_n'],
            'deleted_n'              => $result['deleted_n'],
            'sync'                   => $sync,
        ]);

        return response()->json([
            'message'          => 'Monitoreos pendientes actualizados correctamente.',
            'count'            => count($rows),
            'updated'          => $result['updated_n'],
            'skipped_not_pending' => $result['skipped_not_pending_n'],
            'not_found_ids'    => $result['not_found_ids'],
            'guardia_id'       => (int) $guardia->id,
            'guardia_owner_id' => $guardiaOwnerId,
            'sync'             => $sync,
        ], 200);

    } catch (Throwable $e) {
        Log::error('MonitGuardEditFromGuardia failed', [
            'error'            => $e->getMessage(),
            'file'             => $e->getFile(),
            'line'             => $e->getLine(),
            'rows_count'       => count($rows),
            'auth_user_id'     => $authUserId,
            'guardia_id'       => (int) $guardia->id,
            'guardia_owner_id' => $guardiaOwnerId,
            'site'             => $site,
        ]);

        return response()->json([
            'message' => 'Error al actualizar monitoreos de la guardia.',
            'code'    => 'SERVER_ERROR',
            'debug'   => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}

public function MonitDash(Request $request)
{
    $user = $request->user();

    if (! $user) {
        return response()->json([
            'message' => 'No autenticado. Envía tu token.',
            'code'    => 'UNAUTHENTICATED',
        ], 401);
    }

    $userId = (int) $user->id;

    /*
    |--------------------------------------------------------------------------
    | Periodos
    |--------------------------------------------------------------------------
    */

    $startDay = Carbon::now()
        ->startOfDay();

    $endDay = Carbon::now()
        ->endOfDay();

    $startWeek = Carbon::now()
        ->startOfWeek(Carbon::MONDAY)
        ->startOfDay();

    $endWeek = Carbon::now()
        ->endOfWeek(Carbon::SUNDAY)
        ->endOfDay();

    $startMonth = Carbon::now()
        ->startOfMonth()
        ->startOfDay();

    $endMonth = Carbon::now()
        ->endOfMonth()
        ->endOfDay();

    /*
    |--------------------------------------------------------------------------
    | Conteos semanales y actuales
    |--------------------------------------------------------------------------
    */

    // Pendientes globales, sin importar quién los creó.
    $pendingAll = DB::table('monitoreos')
        ->where('concluido', 1)
        ->count();

    // Monitoreos creados por el usuario durante la semana actual.
    $createdWeekByUser = DB::table('monitoreos')
        ->where('user_Cre', $userId)
        ->whereBetween('created_at', [$startWeek, $endWeek])
        ->count();

 // Monitoreos concluidos por el usuario durante la semana actual.
    $concludedWeekByUser = DB::table('monitoreos')
        ->where('concluido', 2)
        ->where('user_Upd', $userId)
        ->whereBetween('updated_at', [$startWeek, $endWeek])
        ->count();

    // Monitoreos anulados por el usuario durante la semana actual.
    $annulledWeekByUser = DB::table('monitoreos')
    ->where('concluido', 3)
    ->where('user_Upd', $userId)
    ->whereBetween('updated_at', [$startWeek, $endWeek])
    ->count();    

    /*
    |--------------------------------------------------------------------------
    | Conteos mensuales
    |--------------------------------------------------------------------------
    */

    // Todos los monitoreos creados por el usuario durante el mes.
    $totalMonthByUser = DB::table('monitoreos')
        ->where('user_Cre', $userId)
        ->whereBetween('created_at', [$startMonth, $endMonth])
        ->count();

    // Monitoreos concluidos por el usuario durante el mes.
    $concludedMonthByUser = DB::table('monitoreos')
        ->where('concluido', 2)
        ->where('user_Upd', $userId)
        ->whereBetween('updated_at', [$startMonth, $endMonth])
        ->count();

    // Monitoreos anulados por el usuario durante el mes.
    $annulledMonthByUser = DB::table('monitoreos')
        ->where('concluido', 3)
        ->where('user_Upd', $userId)
        ->whereBetween('updated_at', [$startMonth, $endMonth])
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Tabla de monitoreos pendientes y anulados
    |--------------------------------------------------------------------------
    |
    | Se incluyen:
    | 1 = Pendiente
    | 3 = Anulado
    |
    | Los anulados permanecen visibles para poder reactivarlos.
    |
    */

    $pendingMonitoreos = DB::table('monitoreos as m')
        ->join('app_service as a', 'a.id', '=', 'm.siteApp')
        ->leftJoin('c_veeam as cv', 'cv.id', '=', 'm.client_id')
        ->select([
            'm.id',
            'm.client_id',
            'm.siteApp',
            'a.nameService as siteApp_name',
            'cv.numCV as client_code',
            'cv.nameCV as client_name',
            'm.dateRest',
            'm.estatus',
            'm.observacion',
            'm.concluido',
            'm.created_at',
            'm.updated_at',
        ])
        ->whereIn('m.concluido', [1, 3])
        ->orderByDesc('m.created_at')
        ->limit(50)
        ->get()
        ->map(function ($monitoreo) {
            $clientCode = trim((string) ($monitoreo->client_code ?? ''));
            $clientName = trim((string) ($monitoreo->client_name ?? ''));

            $clientLabel = trim(
                implode(' - ', array_filter([
                    $clientCode,
                    $clientName,
                ]))
            );

            if ($clientLabel === '') {
                $clientLabel = '—';
            }

            $status = (int) $monitoreo->concluido;

            return [
                'id'              => (int) $monitoreo->id,
                'client_id'       => $monitoreo->client_id !== null
                    ? (int) $monitoreo->client_id
                    : null,
                'siteApp'         => $monitoreo->siteApp !== null
                    ? (int) $monitoreo->siteApp
                    : null,
                'siteApp_name'    => $monitoreo->siteApp_name ?? '—',
                'client_label'    => $clientLabel,
                'dateRest'        => $monitoreo->dateRest,
                'estatus'         => (string) ($monitoreo->estatus ?? ''),
                'observacion'     => $monitoreo->observacion,
                'concluido'       => $status,
                'concluido_label' => $this->statusMonit[$status] ?? '—',
                'created_at'      => $monitoreo->created_at,
                'updated_at'      => $monitoreo->updated_at,
            ];
        })
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Respuesta
    |--------------------------------------------------------------------------
    */

    return response()->json([
        'counts' => [
            'pending_all'          => $pendingAll,
            'created_week_user'    => $createdWeekByUser,
           'concluded_week_user' => $concludedWeekByUser,
            'annulled_week_user'  => $annulledWeekByUser,
            'total_month_user'     => $totalMonthByUser,
            'concluded_month_user' => $concludedMonthByUser,
            'annulled_month_user'  => $annulledMonthByUser,
        ],

        'pending_count' => $pendingMonitoreos
            ->where('concluido', 1)
            ->count(),

        'pending_monitoreos' => $pendingMonitoreos,

        'week' => [
            'start' => $startWeek->toDateString(),
            'end'   => $endWeek->toDateString(),
        ],

        'month' => [
            'start' => $startMonth->toDateString(),
            'end'   => $endMonth->toDateString(),
        ],

        'meta' => [
            'today' => Carbon::now()->toDateString(),
            'month' => Carbon::now()->format('Y-m'),
            'user'  => [
                'id'   => $userId,
                'name' => $user->name ?? null,
            ],
        ],
    ], 200);
}


}
