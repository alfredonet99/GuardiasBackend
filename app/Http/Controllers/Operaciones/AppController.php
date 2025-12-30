<?php

namespace App\Http\Controllers\Operaciones;

use App\Models\Operaciones\AppService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppController extends Controller
{
    private function requireAdmin(): void
    {
        $user = auth('api')->user();
        if (!($user?->isAdmin() ?? false)) {
            abort(403, 'No tienes acceso.');
        }
    }
    public function index()
    {
        $appService = AppService::orderByDesc('id')->get();

        return response()->json($appService);
    }

    public function store(Request $request)
    {
         $data = $request->validate([
        'nameService'        => ['required', 'string', 'max:255'],
        'descriptionService' => ['nullable', 'string'],
        'activo'             => ['nullable', 'boolean'],
        ]);

        if (!array_key_exists('activo', $data)) {
            $data['activo'] = 1;
        }

        $appService = AppService::create($data);

        return response()->json([
            'message' => 'Servicio creado correctamente.',
            'appService' => $appService,
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
        public function edit(string $id)
    {
        $appService = AppService::findOrFail($id);

        return response()->json([
            'appService' => $appService,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $appService = AppService::findOrFail($id);

        $data = $request->validate([
            'nameService'        => ['required', 'string', 'max:255'],
            'descriptionService' => ['nullable', 'string'],
            'activo'             => ['nullable', 'boolean'],
        ]);

        if (!array_key_exists('activo', $data)) {
            $data['activo'] = $appService->activo ?? 1; 
        }

        $appService->update($data);

        return response()->json([
            'message' => 'Servicio actualizado correctamente.',
            'appService' => $appService->fresh(),
        ], 200);
    }

    public function destroy(string $id)
    {
        $this->requireAdmin();

        $appService = AppService::findOrFail($id);
        $appService->delete();

        return response()->json([
            'message' => 'App eliminado correctamente.',
        ], 200);
    }


    public function toggleActivo(Request $request, string $id)
    {
        $app = AppService::findOrFail($id);

        $data = $request->validate([
            'activo' => ['nullable', 'boolean'],
        ]);

        $next = array_key_exists('activo', $data)
            ? (bool) $data['activo']
            : !$app->activo;

        $app->activo = $next;
        $app->save();

        return response()->json([
            'success' => true,
            'data' => $app,
        ]);
    }

    public function ListVeeam()
    {
        $appService = AppService::query()
            ->where('nameService', 'like', '%Veeam%')
            ->orderByDesc('id')
            ->get();

        return response()->json($appService);
    }

}
