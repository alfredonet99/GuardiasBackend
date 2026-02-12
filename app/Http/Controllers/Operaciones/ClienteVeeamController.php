<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\ClienteVeeam;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;
use Illuminate\Support\Facades\DB;
use App\Models\Operaciones\Monitoreos;
use Illuminate\Support\Facades\Log;

class ClienteVeeamController extends Controller
{

    private array $statusVe = [];
    public function __construct() {
        $this->statusVe = [
            '1' => 'Completado Exitoso - Backup finalizado sin errores', '2' => 'Completado con Advertencias - Backup terminado pero con observaciones menores', 
            '3' => 'Fallido - Backup no se completó correctamente', '4' => ' En Progreso - Backup actualmente en ejecución',
            '5' => ' Pausado - Backup detenido temporalmente', '6' => ' Pendiente - Programado pero no iniciado',
        ];
    }
   
    public function index(Request $request)
    {
        $search   = trim((string) $request->query('search', ''));
        $inactive = $request->query('inactive', null); 
        $perPage  = 30;

        $query = ClienteVeeam::query()->with('AppCV');

        if ($inactive === '0') {
            $query->where('activo', 1);
        } elseif ($inactive === '1') {
            $query->where('activo', 0);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {

                // numCV exacto si es número
                if (ctype_digit($search)) {
                    $q->orWhere('numCV', (int) $search);
                }

                $q->orWhere('numCV', 'like', "%{$search}%")
                ->orWhere('nameCV', 'like', "%{$search}%")
                ->orWhere('backup', 'like', "%{$search}%");

                $q->orWhereHas('AppCV', function ($sub) use ($search) {
                    $sub->where('nameService', 'like', "%{$search}%");
                });
            });
        }

        $clientes = $query
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json($clientes);
    }


  
    public function store(Request $request)
    {
        $numCV = trim((string) $request->input('numCV', ''));
        if ($numCV === '') {
            $numCV = 'NO IDENTIFICADO';
        }

        $data = $request->validate([
            'numCV'  => ['nullable', 'string', 'max:255'],
            'nameCV' => ['required', 'string', 'max:255'],

            'app' => [
                'required',
                'integer',
                Rule::exists('app_service', 'id')->where(function ($q) {
                    $q->where('nameService', 'like', '%Veeam%');
                }),
            ],

            'backup' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $v = trim(preg_replace('/\s+/', ' ', (string) $value));
                    if (!preg_match('/^\d+(\.\d+)?\s+(GB|TB)$/i', $v)) {
                        $fail('El almacenamiento debe tener número y unidad (GB o TB). Ej: "256.5 GB" o "1 TB".');
                    }
                },
            ],

            'jobs'   => ['nullable', 'integer', 'min:0', 'max:300'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'app.exists' => 'El aplicativo seleccionado no es válido o no pertenece al catálogo de Veeam.',
        ]);

        $data['numCV'] = $numCV;

        $data['backup'] = trim(preg_replace('/\s+/', ' ', $data['backup']));
        $data['backup'] = preg_replace_callback('/\b(gb|tb)\b/i', fn ($m) => strtoupper($m[0]), $data['backup']);

        if (!array_key_exists('activo', $data)) {
            $data['activo'] = 1;
        }

        $exists = ClienteVeeam::where('numCV', $data['numCV'])
            ->where('nameCV', $data['nameCV'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un cliente con el mismo ID y nombre.',
                'code'    => 'DUPLICATE',
            ], 409);
        }

        try {
            $cliente = ClienteVeeam::create($data);

            return response()->json([
                'message' => 'Cliente Veeam creado correctamente.',
                'data'    => $cliente,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al crear el cliente Veeam.',
                'code'    => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function edit(string $id)
    {
        $cliente = ClienteVeeam::find($id);
        return response()->json([
            'data' => $cliente,
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $cliente = ClienteVeeam::find($id);
        $numCV = trim((string) $request->input('numCV', ''));
        if ($numCV === '') {
            $numCV = 'NO IDENTIFICADO';
        }

        $data = $request->validate([
            'numCV'  => ['nullable', 'string', 'max:255'],
            'nameCV' => ['required', 'string', 'max:255'],

            'app' => [
                'required',
                'integer',
                Rule::exists('app_service', 'id')->where(function ($q) {
                    $q->where('nameService', 'like', '%Veeam%');
                }),
            ],

            'backup' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $v = trim(preg_replace('/\s+/', ' ', (string) $value));
                    if (!preg_match('/^\d+(\.\d+)?\s+(GB|TB)$/i', $v)) {
                        $fail('El almacenamiento debe tener número y unidad (GB o TB). Ej: "256.5 GB" o "1 TB".');
                    }
                },
            ],

            'jobs'   => ['nullable', 'integer', 'min:0', 'max:300'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'app.exists' => 'El aplicativo seleccionado no es válido o no pertenece al catálogo de Veeam.',
        ]);

        // fuerza el numCV normalizado
        $data['numCV'] = $numCV;

        // normaliza backup (espacios y unidad en mayúscula)
        $data['backup'] = trim(preg_replace('/\s+/', ' ', $data['backup']));
        $data['backup'] = preg_replace_callback(
            '/\b(gb|tb)\b/i',
            fn ($m) => strtoupper($m[0]),
            $data['backup']
        );

        // activo: si no viene, conserva el actual
        if (!array_key_exists('activo', $data)) {
            $data['activo'] = (int) ($cliente->activo ?? 1);
        }

        // ✅ DUPLICADO (numCV + nameCV) excluyendo este registro
        $exists = ClienteVeeam::where('numCV', $data['numCV'])
            ->where('nameCV', $data['nameCV'])
            ->where('id', '!=', $cliente->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un cliente con el mismo ID y nombre.',
                'code'    => 'DUPLICATE',
            ], 409);
        }

        try {
            $cliente->update($data);

            return response()->json([
                'message' => 'Cliente Veeam actualizado correctamente.',
                'data'    => $cliente->fresh(),
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar el cliente Veeam.',
                'code'    => 'SERVER_ERROR',
            ], 500);
        }
    }

    public function show($id)
{
    $clienteVeeam = ClienteVeeam::with([
  'AppCV',
  'MonitV' => function ($q) {
    $q->select('client_id', 'dateRest')
      ->whereNotNull('dateRest')
      ->groupBy('client_id', 'dateRest')
      ->orderByDesc('dateRest');
  },
])->find($id);

    if (!$clienteVeeam) {
        return response()->json([
            'message' => 'Cliente no encontrado.',
            'code' => 'NOT_FOUND',
        ], 404);
    }

    Log::info($clienteVeeam);

    return response()->json([
        'data' => $clienteVeeam,
    ], 200);
}



   public function destroy($id)
    {
        try {
            $cliente = ClienteVeeam::find($id);

            if (!$cliente) {
                return response()->json([
                    'message' => 'El cliente no existe o ya fue eliminado.',
                    'code'    => 'NOT_FOUND',
                ], 404);
            }

            $cliente->delete();

            return response()->json([
                'message' => 'Cliente Veeam eliminado correctamente.',
                'code'    => 'DELETED',
                'data'    => ['id' => $id],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al eliminar el cliente Veeam.',
                'code'    => 'SERVER_ERROR',
            ], 500);
        }
    }


    public function ClientDeactivate(int $id, Request $request)
    {
        $request->validate([
            'activo' => ['required', 'boolean'],
        ]);

        $cliente = ClienteVeeam::find($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado.',
            ], 404);
        }

        $cliente->activo = (int) $request->boolean('activo');
        $cliente->save();

        return response()->json([
            'success' => true,
            'message' => 'Estatus actualizado.',
            'data'    => $cliente,
        ]);
    }

  public function SelectVeeam()
{
    // ✅ Id “genérico” para Veeam (si lo ocupas en UI)
    $siteAppId = (int) DB::table('app_service')
        ->where('nameService', 'like', '%Veeam%')
        ->orderBy('id')
        ->value('id');

    if (!$siteAppId) {
        return response()->json([
            'message' => 'No existe app_service configurado para Veeam.',
            'code'    => 'VEEAM_APP_SERVICE_NOT_FOUND',
        ], 422);
    }

    $last = DB::table('monitoreos')
        ->selectRaw('client_id, siteApp, MAX(dateRest) as last_dateRest')
        ->whereNotNull('dateRest')
        ->groupBy('client_id', 'siteApp');

    $active = DB::table('monitoreos')
        ->selectRaw('client_id, siteApp, 1 as has_active')
        ->where('concluido', 1)
        ->groupBy('client_id', 'siteApp');

    $items = ClienteVeeam::query()
        ->from('c_veeam')
        ->where('c_veeam.activo', 1)

        // ✅ Join para saber “a qué veeam (app_service) pertenece”
        ->leftJoin('app_service as aps', 'aps.id', '=', 'c_veeam.app')

        // last_dateRest
        ->leftJoinSub($last, 'lr', function ($join) {
            $join->on('c_veeam.id', '=', 'lr.client_id')
                 ->on('c_veeam.app', '=', 'lr.siteApp');
        })

        // activos
        ->leftJoinSub($active, 'ac', function ($join) {
            $join->on('c_veeam.id', '=', 'ac.client_id')
                 ->on('c_veeam.app', '=', 'ac.siteApp');
        })

        // excluir los que tienen activo
        ->whereNull('ac.has_active')

        ->orderBy('c_veeam.nameCV')
        ->get([
            'c_veeam.id',
            'c_veeam.numCV',
            'c_veeam.nameCV',
            'c_veeam.backup',
            'c_veeam.jobs',
            'c_veeam.app', // ✅ id app_service (esto identifica qué veeam es)
            DB::raw('aps.nameService as veeam_name'), // ✅ nombre del veeam
            DB::raw('lr.last_dateRest as last_dateRest'),
        ])
        ->map(fn ($c) => [
            'id'            => (int) $c->id,
            'numCV'         => $c->numCV,
            'nameCV'        => $c->nameCV,
            'backup'        => $c->backup,
            'jobs'          => $c->jobs,
            'last_dateRest' => $c->last_dateRest,

            // ✅ NUEVO: a qué “Veeam” pertenece (app_service)
            'veeam_id'      => (int) $c->app,
            'veeam_name'    => $c->veeam_name ?? null,

            // ✅ NUEVO: site para que el front no lo invente
            'site'          => 'veeam',

            // label UI
            'label'         => trim($c->numCV.' - '.$c->nameCV),
        ])
        ->values();

    return response()->json([
        'items'     => $items,
        'status'    => $this->statusVe,
        'source'    => 'veeam',
        'siteAppId' => $siteAppId,
    ], 200);
}



}
