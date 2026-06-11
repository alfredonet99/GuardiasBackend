<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Area;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    private function requireAdmin(): void
    {
        $user = auth('api')->user();
        if (!($user?->isAdmin() ?? false)) {
            abort(403, 'No tienes acceso.');
        }
    }

    public function index(Request $request)
    {
        $this->requireAdmin();
        $search = $request->input('search');

        $query = Permission::query()
        ->with(['area:id,name']);

        if ($search) {
            $search = trim($search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $permissions = $query
            ->orderBy('name')
            ->paginate(10);

        return response()->json($permissions);
    }

    public function options()
    {
        $this->requireAdmin();

        return Area::query()
            ->select('id','name')
            ->where('activo', 1)
            ->orderBy('name')
            ->get();
    }


   public function storeIndividual(Request $request)
{
    $this->requireAdmin();

    $request->validate([
        "id_area"     => "required|integer|exists:areas,id",
        "module"      => "required|string",
        "name"        => "required|string",
        "description" => "nullable|string"
    ]);

    $fullPermission = strtolower($request->module) . "." . strtolower($request->name);

    if (Permission::where("name", $fullPermission)->exists()) {
        return response()->json([
            "message" => "El permiso ya existe",
        ], 422);
    }

    $perm = Permission::create([
        "id_area"     => $request->id_area,
        "name"        => $fullPermission,
        "description" => $request->description,
        "guard_name"  => "api",
    ]);

    return response()->json([
        "message"    => "Permiso creado correctamente",
        "permission" => $perm,
    ], 201);
}

public function storeCrud(Request $request)
{
    $this->requireAdmin();

    $request->validate([
        "id_area" => "required|integer|exists:areas,id",
        "module"  => "required|string",
        "crud"    => "required|array"
    ]);

    $module = strtolower($request->module);
    $crudItems = $request->crud;

    $created = [];
    $skipped = [];

    foreach ($crudItems as $item) {
        $action = is_array($item) ? $item["action"] : $item;
        $description = is_array($item) ? ($item["description"] ?? null) : null;

        $permissionName = "$module.$action";

        if (Permission::where("name", $permissionName)->exists()) {
            $skipped[] = $permissionName;
            continue;
        }

        $perm = Permission::create([
            "id_area"     => $request->id_area,
            "name"        => $permissionName,
            "guard_name"  => "api",
            "description" => $description
        ]);

        $created[] = $perm->name;
    }

    if (count($skipped) > 0) {
        return response()->json([
            "message"   => "Algunos permisos ya existen y fueron omitidos.",
            "existing"  => $skipped,
            "created"   => $created
        ], 422);
    }

    return response()->json([
        "message" => "Permisos CRUD generados correctamente.",
        "created" => $created
    ], 201);
}


    public function destroy($id)
    {
        $this->requireAdmin();

        $permission = Permission::findOrFail($id);
        $permissionName = $permission->name;
        $roles = $permission->roles()->pluck('name')->toArray();

        $permission->roles()->detach();
        $permission->delete();

        Log::info('🗑 Permiso eliminado', ['permission' => $permissionName, 'roles_detached' => $roles,]);

        return response()->json([
            'message' => 'Permiso eliminado correctamente',
            'permission' => $permissionName,
            'detached_from_roles' => $roles,
        ]);
    }


}
