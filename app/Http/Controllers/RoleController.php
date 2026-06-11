<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;


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

    $allPermissions = Permission::orderBy('name')
        ->get(['id','name','description','guard_name','id_area']);

    return response()->json([
        'role' => [
            'id'   => null,
            'name' => '',
        ],
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
    Log::info('--- [RoleController@edit] INICIO ---', [
        'role_id_param' => $id,
        'guard' => 'api',
    ]);

    $authUser = auth('api')->user();

    Log::info('[RoleController@edit] auth_user', [
        'auth_exists' => (bool) $authUser,
        'auth_id' => $authUser->id ?? null,
        'auth_name' => $authUser->name ?? null,
        'auth_email' => $authUser->email ?? null,
        'auth_area_id' => $authUser->area_id ?? null,
        'auth_roles' => method_exists($authUser, 'roles') ? $authUser->roles->pluck('name')->values() : null,
    ]);

    $isAdmin  = $authUser?->isAdmin() ?? false;

    Log::info('[RoleController@edit] isAdmin', [
        'isAdmin' => $isAdmin,
    ]);

    $restricted = ['console.', 'permisos.', 'roles.', 'users.','area'];

    $isDeletePerm = fn (string $name) => str_ends_with($name, '.delete');

    // ✅ agregado id_area para poder filtrar asignados por área sin tocar la lógica
    $role = Role::with('permissions:id,name,id_area')->findOrFail($id);

    Log::info('[RoleController@edit] role_loaded', [
        'role_id' => $role->id,
        'role_name' => $role->name,
        'role_guard' => $role->guard_name ?? null,
        'role_permissions_count' => $role->permissions->count(),
        'role_permissions_sample' => $role->permissions->pluck('name')->take(15)->values(),
    ]);

    if (!$isAdmin && $role->name === 'Administrador') {
        Log::warning('[RoleController@edit] BLOQUEADO: intentando editar Administrador siendo no-admin', [
            'auth_id' => $authUser->id ?? null,
            'role_id' => $role->id,
        ]);
        abort(403, 'No tienes permiso para editar el rol Administrador.');
    }

    if (!$isAdmin) {
        $myAreaId = (int) ($authUser->area_id ?? 0);

        Log::info('[RoleController@edit] no-admin: area del usuario', [
            'myAreaId' => $myAreaId,
        ]);

        if (!$myAreaId) {
            Log::warning('[RoleController@edit] BLOQUEADO: usuario sin area asignada', [
                'auth_id' => $authUser->id ?? null,
            ]);
            abort(403, 'Tu usuario no tiene un área asignada.');
        }

        $roleAreaId = (int) ($this->areaIdFromRoleId((int) $role->id) ?? 0);

        Log::info('[RoleController@edit] no-admin: area del rol', [
            'roleAreaId' => $roleAreaId,
            'role_id' => $role->id,
        ]);

        if (!$roleAreaId) {
            Log::warning('[RoleController@edit] BLOQUEADO: rol sin area configurada', [
                'role_id' => $role->id,
            ]);
            abort(403, 'Este rol no tiene un área configurada.');
        }

        if ($roleAreaId !== $myAreaId) {
            Log::warning('[RoleController@edit] BLOQUEADO: rol de otra area', [
                'myAreaId' => $myAreaId,
                'roleAreaId' => $roleAreaId,
                'role_id' => $role->id,
            ]);
            abort(403, 'No puedes editar roles de otra área.');
        }
    }

    if ($isAdmin) {
    $allPermissions = Permission::orderBy('name')
        ->get(['id','name','description','guard_name','id_area']);

    Log::info('[RoleController@edit] admin: response', [
        'permissions_count' => $allPermissions->count(),
        'assigned_permissions_count' => $role->permissions->count(),
    ]);

    return response()->json([
        'role' => $role,
        'permissions' => $allPermissions,
        'assigned_permissions' => $role->permissions->pluck('name')->values(),
        'can_rename' => true,
    ], 200);
}


    // ✅ mismos filtros + agregado filtro por área
    $allPermissions = Permission::query()
        ->where('id_area', $roleAreaId)
        ->where(function ($q) use ($restricted) {
            foreach ($restricted as $prefix) {
                $q->where('name', 'NOT LIKE', $prefix . '%');
            }
        })
        ->where('name', 'NOT LIKE', '%.delete')
        ->orderBy('name')
        ->get();

    // ✅ mismo filtro + agregado filtro por área usando id_area ya cargado
    $assignedAllowed = $role->permissions
        ->filter(function ($perm) use ($restricted, $isDeletePerm, $roleAreaId) {
            if ((int)($perm->id_area ?? 0) !== (int)$roleAreaId) return false;
            if ($isDeletePerm($perm->name)) return false;
            foreach ($restricted as $prefix) {
                if (str_starts_with($perm->name, $prefix)) return false;
            }
            return true;
        })
        ->pluck('name')
        ->values();

    Log::info('[RoleController@edit] no-admin: response', [
        'permissions_count' => $allPermissions->count(),
        'assigned_allowed_count' => $assignedAllowed->count(),
        'assigned_allowed_sample' => $assignedAllowed->take(20)->values(),
    ]);

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

    // ✅ agregado id_area en permissions para poder filtrar por área
    $role = Role::with('permissions:id,name,id_area')->findOrFail($id);

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
    $allowedByArea = Permission::where('id_area', $roleAreaId)->pluck('name')->all();

    $isRestricted = function (string $name) use ($restricted) {
        foreach ($restricted as $prefix) {
            if (str_starts_with($name, $prefix)) return true;
        }
        return false;
    };

    $triedDelete = collect($incoming)->first(fn ($p) => $isDeletePerm($p));
    if ($triedDelete) {
        abort(403, 'No tienes permiso para asignar permisos de tipo delete.');
    }

    $triedRestricted = collect($incoming)->first(fn ($p) => $isRestricted($p));
    if ($triedRestricted) {
        abort(403, 'No tienes permiso para asignar permisos de administración del sistema.');
    }

    // conservar restricted/delete que el rol YA tiene
    $keepRestricted = $role->permissions->pluck('name')->filter(fn ($p) => $isRestricted($p))->values()->all();
    $keepDelete     = $role->permissions->pluck('name')->filter(fn ($p) => $isDeletePerm($p))->values()->all();

    // incoming seguro (sin restricted/delete)
    $allowedIncoming = collect($incoming)
        ->reject(fn ($p) => $isRestricted($p) || $isDeletePerm($p))
        ->values()
        ->all();

    // ✅ filtro por área SOLO para los incoming seguros
    $allowedIncoming = array_values(array_intersect($allowedIncoming, $allowedByArea));

    // ✅ armar final conservando restricted/delete sin volver a filtrarlos por área
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
