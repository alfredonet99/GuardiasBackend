<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
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
    info("📌 [ROLES] Listando todos los roles...");

    $authUser = auth('api')->user();
    $isAdmin  = $authUser?->isAdmin() ?? false;

    $roles = Role::query()
        ->when(!$isAdmin, function ($q) {
            $q->where('name', '!=', 'Administrador');
        })
        ->orderBy('name')
        ->get();

    info("📌 [ROLES] Total roles encontrados: " . $roles->count());

    return response()->json($roles);
}


     public function create()
    {
        $this->requireAdmin();
        $allPermissions = Permission::orderBy('name')->get(['id','name','description','guard_name']);

        return response()->json([
            'role' => ['id'   => null,'name' => '',],
            'permissions' => $allPermissions,
            'assigned_permissions' => [],
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin();

        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $roleName = $data['name'];

        if (Role::where('name', $roleName)->exists()) {
            info("⚠️ [ROLES] Intento de crear rol duplicado: {$roleName}");

            return response()->json(['message' => 'El nombre del rol ya existe.',], 422);
        }

        $role = Role::create(['name'       => $roleName,'guard_name' => 'api',]);

        $permissions = $data['permissions'] ?? [];
        $role->syncPermissions($permissions);

        info("✅ [ROLES] Rol creado", [
            'role'     => $role->name,
            'permisos' => $permissions,
        ]);

        return response()->json(['message' => 'Rol creado correctamente','role'    => $role->only(['id', 'name', 'guard_name']),], 201);
    }



    public function edit($id)
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;
        $restricted = ['console.', 'permisos.', 'roles.', 'users.'];

        $role = Role::with('permissions:id,name')->findOrFail($id);

        if ($isAdmin) {
            $allPermissions = Permission::orderBy('name')->get();

            return response()->json([
                'role' => $role,
                'permissions' => $allPermissions,
                'assigned_permissions' => $role->permissions->pluck('name')->values(),
                'can_rename' => true,
            ], 200);
        }

        $allPermissions = Permission::query()
            ->where(function ($q) use ($restricted) {
                foreach ($restricted as $prefix) {
                    $q->where('name', 'NOT LIKE', $prefix . '%');
                }
            })
            ->orderBy('name')
            ->get();

        $assignedAllowed = $role->permissions
            ->pluck('name')
            ->filter(function ($name) use ($restricted) {
                foreach ($restricted as $prefix) {
                    if (str_starts_with($name, $prefix)) return false;
                }
                return true;
            })
            ->values();

        return response()->json([
            'role' => $role->only(['id','name','guard_name']),
            'permissions' => $allPermissions,
            'assigned_permissions' => $assignedAllowed,
            'can_rename' => false,
        ], 200);
    }

    public function update(Request $request, $id)
    {

        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;

        $restricted = ['console.', 'permisos.', 'roles.', 'users.'];

        $role = Role::with('permissions:id,name')->findOrFail($id);

        if ($request->has('name')) {
            abort(403, 'No tienes permiso para renombrar roles.');
        }

        $data = $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $permissions = $data['permissions'] ?? [];

        if (!$isAdmin) {

            $isRestricted = function (string $name) use ($restricted) {
                foreach ($restricted as $prefix) {
                    if (str_starts_with($name, $prefix)) return true;
                }
                return false;
            };

            $triedRestricted = collect($permissions)->first(fn ($p) => $isRestricted($p));
            if ($triedRestricted) {
                abort(403, 'No tienes permiso para asignar permisos de administración del sistema.');
            }

            $keepRestricted = $role->permissions
                ->pluck('name')
                ->filter(fn ($p) => $isRestricted($p))
                ->values()
                ->all();

            $allowedIncoming = collect($permissions)
                ->reject(fn ($p) => $isRestricted($p))
                ->values()
                ->all();

            $permissions = collect($keepRestricted)
                ->merge($allowedIncoming)
                ->unique()
                ->values()
                ->all();
        }

        $role->syncPermissions($permissions);

        info("🔄 [ROLES] Permisos sincronizados", ['role'     => $role->name,'permisos' => $permissions,]);

        return response()->json(['message' => 'Rol actualizado correctamente','role'    => $role,]);
    }


    public function show($id)
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;

        $role = Role::with('permissions')->findOrFail($id);

        if (!$isAdmin && $role->name === 'Administrador') {
            abort(403, 'No tienes permiso para ver el rol Administrador.');
        }

        $roleData = $role->only(['id', 'name', 'guard_name']);

        $assignedPermissions = $role->permissions->map(function ($perm) {
            return [
                'id'          => $perm->id,
                'name'        => $perm->name,
                'description' => $perm->description,
            ];
        });

        return response()->json(['role' => $roleData,'permissions' => $assignedPermissions,], 200);
    }


    public function destroy($id)
    {
        $this->requireAdmin();

        $role = Role::with(['permissions', 'users'])->findOrFail($id);

        if ($role->name === 'Administrador') {
            info("⛔ [ROLES] Intento de eliminar rol protegido: {$role->name}");

            return response()->json([
                'message' => 'No está permitido eliminar el rol Administrador.',
            ], 403);
        }

        $roleName   = $role->name;
        $permsNames = $role->permissions->pluck('name')->toArray();
        $userIds    = $role->users->pluck('id')->toArray();

        $role->permissions()->detach();
        $role->users()->detach();

        $role->delete();

        info("[ROLES] Rol eliminado", [
            'role'                 => $roleName,
            'permissions_detached' => $permsNames,
            'users_detached'       => $userIds,
        ]);

        return response()->json([
            'message'              => 'Rol eliminado correctamente',
            'role'                 => $roleName,
            'permissions_detached' => $permsNames,
            'users_detached'       => $userIds,
        ]);
    }





}
