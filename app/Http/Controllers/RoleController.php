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
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;

        $query = Role::query()
            ->select(['id','name','guard_name'])
            ->where('guard_name', 'api')
            ->orderBy('name');

        if ($isAdmin) {
            $roles = $query->get();
            return response()->json($roles, 200);
        }

        $areaId = (int) ($authUser->area_id ?? 0);

        $roleIds = $this->roleIdsForArea($areaId);

        $roles = $query
            ->whereIn('id', $roleIds)
            ->where('name', '!=', 'Administrador') // candado extra
            ->get();

        return response()->json($roles, 200);
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

    $restricted = ['console.', 'permisos.', 'roles.', 'users.','area'];

    // ✅ Regla: no-admin NUNCA ve permisos *.delete
    $isDeletePerm = fn (string $name) => str_ends_with($name, '.delete');

    $role = Role::with('permissions:id,name')->findOrFail($id);

    if (!$isAdmin && $role->name === 'Administrador') {
        abort(403, 'No tienes permiso para editar el rol Administrador.');
    }

    if (!$isAdmin) {
        $myAreaId = (int) ($authUser->area_id ?? 0);
        if (!$myAreaId) abort(403, 'Tu usuario no tiene un área asignada.');

        $roleAreaId = (int) ($this->areaIdFromRoleId((int) $role->id) ?? 0);
        if (!$roleAreaId) abort(403, 'Este rol no tiene un área configurada.');

        if ($roleAreaId !== $myAreaId) {
            abort(403, 'No puedes editar roles de otra área.');
        }
    }

    if ($isAdmin) {
        $allPermissions = Permission::orderBy('name')->get();

        return response()->json([
            'role' => $role,
            'permissions' => $allPermissions,
            'assigned_permissions' => $role->permissions->pluck('name')->values(),
            'can_rename' => true,
        ], 200);
    }

    // ✅ No-admin: sin console/permisos/roles/users y sin *.delete
    $allPermissions = Permission::query()
        ->where(function ($q) use ($restricted) {
            foreach ($restricted as $prefix) {
                $q->where('name', 'NOT LIKE', $prefix . '%');
            }
        })
        ->where('name', 'NOT LIKE', '%.delete') // 👈 clave
        ->orderBy('name')
        ->get();

    $assignedAllowed = $role->permissions
        ->pluck('name')
        ->filter(function ($name) use ($restricted, $isDeletePerm) {
            if ($isDeletePerm($name)) return false; // 👈 clave
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
    $isDeletePerm = fn (string $name) => str_ends_with($name, '.delete');

    $role = Role::with('permissions:id,name')->findOrFail($id);

    if ($request->has('name')) {
        abort(403, 'No tienes permiso para renombrar roles.');
    }

    // ✅ mismo candado por área que en edit (para no-admin)
    if (!$isAdmin) {
        if ($role->name === 'Administrador') abort(403, 'No puedes editar el rol Administrador.');

        $myAreaId = (int) ($authUser->area_id ?? 0);
        if (!$myAreaId) abort(403, 'Tu usuario no tiene un área asignada.');

        $roleAreaId = (int) ($this->areaIdFromRoleId((int) $role->id) ?? 0);
        if (!$roleAreaId) abort(403, 'Este rol no tiene un área configurada.');

        if ($roleAreaId !== $myAreaId) abort(403, 'No puedes editar roles de otra área.');
    }

    $data = $request->validate([
        'permissions'   => ['nullable', 'array'],
        'permissions.*' => ['string'],
    ]);

    $incoming = $data['permissions'] ?? [];

    if (!$isAdmin) {
        // 1) Prohibidos por prefijo (los tuyos)
        $isRestricted = function (string $name) use ($restricted) {
            foreach ($restricted as $prefix) {
                if (str_starts_with($name, $prefix)) return true;
            }
            return false;
        };

        // 2) Prohibido: *.delete (NUNCA asignable por no-admin)
        $triedDelete = collect($incoming)->first(fn ($p) => $isDeletePerm($p));
        if ($triedDelete) {
            abort(403, 'No tienes permiso para asignar permisos de tipo delete.');
        }

        $triedRestricted = collect($incoming)->first(fn ($p) => $isRestricted($p));
        if ($triedRestricted) {
            abort(403, 'No tienes permiso para asignar permisos de administración del sistema.');
        }

        // ✅ conservar los restricted y los delete que el rol YA tenga
        $keepRestricted = $role->permissions->pluck('name')->filter(fn ($p) => $isRestricted($p))->values()->all();
        $keepDelete     = $role->permissions->pluck('name')->filter(fn ($p) => $isDeletePerm($p))->values()->all();

        // ✅ permitir solo los incoming “seguros”
        $allowedIncoming = collect($incoming)
            ->reject(fn ($p) => $isRestricted($p) || $isDeletePerm($p))
            ->values()
            ->all();

        $incoming = collect($keepRestricted)
            ->merge($keepDelete)
            ->merge($allowedIncoming)
            ->unique()
            ->values()
            ->all();
    }

    $role->syncPermissions($incoming);

    return response()->json([
        'message' => 'Rol actualizado correctamente',
        'role'    => $role->fresh('permissions'),
    ]);
}




    public function show($id)
{
    $authUser = auth('api')->user();
    $isAdmin  = $authUser?->isAdmin() ?? false;

    $role = Role::with('permissions')->findOrFail($id);

    // 1) No-admin nunca puede ver Administrador
    if (!$isAdmin && $role->name === 'Administrador') {
        abort(403, 'No tienes permiso para ver el rol Administrador.');
    }

    // 2) No-admin: solo puede ver roles de su misma área
    if (!$isAdmin) {
        $myAreaId = (int) ($authUser->area_id ?? 0);
        if (!$myAreaId) abort(403, 'Tu usuario no tiene un área asignada.');

        $roleAreaId = (int) ($this->areaIdFromRoleId((int) $role->id) ?? 0);
        if (!$roleAreaId) abort(403, 'Este rol no tiene un área configurada.');

        if ($roleAreaId !== $myAreaId) {
            abort(403, 'No puedes ver roles de otra área.');
        }
    }

    $roleData = $role->only(['id', 'name', 'guard_name']);

    $assignedPermissions = $role->permissions->map(function ($perm) {
        return [
            'id'          => $perm->id,
            'name'        => $perm->name,
            'description' => $perm->description,
        ];
    });

    return response()->json([
        'role' => $roleData,
        'permissions' => $assignedPermissions,
    ], 200);
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
