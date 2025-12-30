<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Operaciones\ClienteVeeam;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class ClienteVeeamController extends Controller
{
   
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
        $clienteVeeam = ClienteVeeam::with('AppCV')->find($id);

        if (!$clienteVeeam) {
            return response()->json([
                'message' => 'Cliente no encontrado.',
                'code' => 'NOT_FOUND',
            ], 404);
        }

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
}
