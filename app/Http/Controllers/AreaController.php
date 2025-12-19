<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Area;

class AreaController extends Controller
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
        $this->requireAdmin();

        $areas = Area::query()
        ->orderBy('name')
        ->get(['id','name', 'activo']);
        return response()->json($areas, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->requireAdmin();

        $data = $request->validate([
            'name'   => ['required', 'string', 'min:3', 'max:100', 'unique:areas,name'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'name.unique' => 'El área ya existe.',
        ]);

        $area = Area::create([
            'name'   => trim($data['name']),
            'activo' => $data['activo'] ?? 1,
        ]);

        return response()->json([
            'message' => 'Área creada correctamente',
            'area'    => $area,
        ], 201);
    }


    /**
     * Display the specified resource.
     */
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

    public function status(Request $request, $id)
    {
        $this->requireAdmin();

        info("🔁 [AREAS] Cambiando estado del área ID: {$id}");

        $data = $request->validate(['activo' => ['required', 'boolean'],]);

        $area = Area::findOrFail($id);

        $area->activo = (bool) $data['activo'];
        $area->save();

        info("✅ [AREAS] Estado actualizado", [
            'id'     => $area->id,
            'name'   => $area->name,
            'activo' => $area->activo,
        ]);

        return response()->json([
            'message' => 'Estado del área actualizado correctamente',
            'area' => $area->only(['id', 'name', 'activo']),
        ], 200);
    }
}
