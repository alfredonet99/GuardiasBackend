<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use App\Rules\OnlyLettersSpaces;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Support\RoleAreaMapper;

class UserController extends Controller
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
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;
        $search = $request->input('search');

        $query = User::query() ->with([
            'roles:id,name',
            'area:id,name',
        ]);

       if (!$isAdmin) {
            $areaId = $authUser?->area_id;

            if (!$areaId) {
                return response()->json(
                    User::query()->whereRaw('1=0')->paginate(20),
                    200
                );
            }

            $query->where('area_id', $areaId);

            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Administrador');
            });
        }

        if ($search) {
            $search = trim($search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhereHas('roles', function ($r) use ($search) {
                    $r->where('name', 'LIKE', "%{$search}%");
                });
            });
        }

        $users = $query->orderBy('name')->paginate(20);

        return response()->json($users);
    }


    public function create()
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;
        $rolesQuery = Role::orderBy('name', 'asc')->select(['id', 'name']);

        if (!$isAdmin) {
            $areaId = (int) ($authUser->area_id ?? 0);
            if (!$areaId) {
                abort(403, 'Tu usuario no tiene un área asignada.');
            }

            $allowedRoleIds = RoleAreaMapper::roleIdsForArea($areaId);

            if (empty($allowedRoleIds)) {
                abort(403, 'No hay roles configurados para tu área.');
            }

            $rolesQuery->whereIn('id', $allowedRoleIds);

            $rolesQuery->where('name', '!=', 'Administrador');
        }

        $roles = $rolesQuery->get();

        return response()->json(['roles' => $roles,]);
    }

    public function store(Request $request)
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;

        $validated = $request->validate([
            'name'     => ['required', 'string', 'min:3', 'max:255', new OnlyLettersSpaces],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role'     => ['required', 'exists:roles,name'],
            'activo'   => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'El correo ya está registrado.',
        ]);

        if (!$isAdmin && ($validated['role'] ?? null) === 'Administrador') {
            abort(403, 'No tienes permiso para asignar el rol Administrador.');
        }

        $role = Role::select(['id', 'name'])->where('name', $validated['role'])->firstOrFail();
        $areaId = null;

         if ($role->name !== 'Administrador') {
            $areaId = RoleAreaMapper::areaIdFromRoleId((int) $role->id);
            if (!$areaId) {
                abort(422, 'No hay un área configurada para el rol seleccionado.');
            }

            if (!$isAdmin) {
            $myAreaId = (int) ($authUser->area_id ?? 0);
            if (!$myAreaId) abort(403, 'Tu usuario no tiene un área asignada.');

            if ((int)$areaId !== (int)$myAreaId) {
                abort(403, 'No puedes asignar roles de otra área.');
            }
            }

            $area = Area::select(['id', 'activo'])->findOrFail($areaId);
            
            if (!(bool) $area->activo) {
                abort(422, 'No puedes asignar un área inactiva.');
            }
        }

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'activo'   => $validated['activo'] ?? true,
            'area_id'  => $areaId,
        ]);

        $user->assignRole($validated['role']);

        return response()->json(['message' => 'Usuario creado correctamente', 'user' => $user->load(['roles:id,name','area:id,name']),], 201);
    }

    public function edit(string $id)
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;
        $user = User::with(['roles:id,name', 'area:id,name'])->findOrFail($id);
        
        if (!$isAdmin) {
            if ($user->hasRole('Administrador')) {
                abort(403, 'No tienes permiso para editar usuarios Administradores.');
            }

            // debe tener área asignada
            $myAreaId = (int) ($authUser->area_id ?? 0);
            if (!$myAreaId) {
                abort(403, 'Tu usuario no tiene un área asignada.');
            }

            // no puede editar usuarios de otra área
            if ((int) ($user->area_id ?? 0) !== $myAreaId) {
                abort(403, 'No tienes permiso para editar usuarios de otra área.');
            }
        }

        $rolesQuery = Role::query()
        ->orderBy('name', 'asc')
        ->select(['id', 'name']);

        if (!$isAdmin) {
        $allowedRoleIds = RoleAreaMapper::roleIdsForArea((int) $authUser->area_id);
        $rolesQuery->whereIn('id', $allowedRoleIds);
        $rolesQuery->where('name', '!=', 'Administrador');
    }

        $roles = $rolesQuery->get();

        return response()->json(['user'  => $user,'roles' => $roles,]);
    }


    public function update(Request $request, string $id)
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;

        $user = User::with(['roles:id,name', 'area:id,name'])->findOrFail($id);

        if (!$isAdmin) {
            if ($user->hasRole('Administrador')) { abort(403, 'No tienes permiso para editar usuarios Administradores.'); }

            $myAreaId = (int) ($authUser->area_id ?? 0);
            if (!$myAreaId) { abort(403, 'Tu usuario no tiene un área asignada.'); }

            if ((int) ($user->area_id ?? 0) !== $myAreaId) { abort(403, 'No tienes permiso para editar usuarios de otra área.'); }
        }

        $rules = [
            'name'     => ['required', 'string', 'min:3', 'max:255', new OnlyLettersSpaces],
            'role'     => ['required', 'exists:roles,name'],
            'activo'   => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ];

        if ($isAdmin) {
            $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];
        } else {
            if ($request->filled('email') && $request->input('email') !== $user->email) {
                abort(403, 'No tienes permiso para cambiar el correo.');
            }
        }

        $validated = $request->validate($rules, ['email.unique' => 'El correo ya está registrado.',]);

        if (!$isAdmin && ($validated['role'] ?? null) === 'Administrador') {
            abort(403, 'No tienes permiso para asignar el rol Administrador.');
        }

        $role = Role::select(['id', 'name'])
            ->where('name', $validated['role'])
            ->firstOrFail();

        $areaId = null;

        if ($role->name !== 'Administrador') {
            $areaId = RoleAreaMapper::areaIdFromRoleId((int) $role->id);

            if (!$areaId) { abort(422, 'No hay un área configurada para el rol seleccionado.'); }

            if (!$isAdmin) {
                $myAreaId = (int) ($authUser->area_id ?? 0);

                if ((int) $areaId !== (int) $myAreaId) { abort(403, 'No puedes asignar roles de otra área.'); }
            }

            $area = Area::select(['id', 'activo'])->findOrFail($areaId);
            if (!(bool) $area->activo) { abort(422, 'No puedes asignar un área inactiva.'); }
        }

        $user->name   = $validated['name'];
        $user->activo = $validated['activo'] ?? $user->activo;

        if ($isAdmin && array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->area_id = $areaId; 

        $user->save();

        $user->syncRoles([$role->name]);

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user'    => $user->fresh()->load(['roles:id,name', 'area:id,name']),
        ], 200);
    }


   public function show(string $id)
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;

        $user = User::with(['roles:id,name', 'area:id,name'])->findOrFail($id);

        // 1) Candado: NO admin no puede ver perfiles Administrador
        if (!$isAdmin && $user->hasRole('Administrador', 'api')) {
            abort(403, 'No tienes permiso para ver perfiles Administrador.');
        }

        if (!$isAdmin) {
            $myAreaId = (int) ($authUser->area_id ?? 0);
            if (!$myAreaId) {
                abort(403, 'Tu usuario no tiene un área asignada.');
            }

            $targetAreaId = (int) ($user->area_id ?? 0);
            if (!$targetAreaId) {
                abort(403, 'El usuario no tiene un área asignada.');
            }

            if ($targetAreaId !== $myAreaId) {
                abort(403, 'No puedes ver usuarios de otra área.');
            }
        }

        $payload = [
            'user'  => $user->only(['id','name','email','Activo','avatar','area_id']),
            'area'  => $user->area ? $user->area->only(['id','name']) : null,
            'roles' => $user->roles->pluck('name')->values(),
        ];

        // 3) Solo admin ve permisos
        if ($isAdmin) {
            $directPermNames = $user->permissions()->pluck('name')->unique()->values();

            $rolePermNames = $user->getPermissionsViaRoles()
                ->pluck('name')
                ->unique()
                ->values();

            $payload['direct_permissions'] = $directPermNames;
            $payload['role_permissions']   = $rolePermNames;

            $payload['all_permissions'] = $directPermNames
                ->merge($rolePermNames)
                ->unique()
                ->values();
        }

        return response()->json($payload, 200);
    }

    public function destroy(string $id)
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;

        if (!$isAdmin) {
            abort(403, 'No tienes permiso para eliminar usuarios.');
        }

        $user = User::with('roles')->findOrFail($id);

        if ((int) $authUser->id === (int) $user->id) {
            abort(422, 'No puedes eliminar tu propio usuario.');
        }

        if ($user->hasRole('Administrador', 'api')) {
            abort(403, 'No puedes eliminar usuarios Administradores.');
        }

        $user->syncPermissions([]);
        $user->syncRoles([]);

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente',], 200);
    }


    public function updateStatus(Request $request, string $id)
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;

        $user = User::with(['roles:id,name', 'area:id,name'])->findOrFail($id);

        if ($authUser && (string) $authUser->id === (string) $user->id) {
            abort(403, 'No puedes cambiar tu propio estatus.');
        }

        if (!$isAdmin) {
            $myAreaId = (int) ($authUser->area_id ?? 0);
            if (!$myAreaId) abort(403, 'Tu usuario no tiene un área asignada.');

            $targetAreaId = (int) ($user->area_id ?? 0);
            if (!$targetAreaId) abort(403, 'El usuario objetivo no tiene un área asignada.');

            if ($targetAreaId !== $myAreaId) {
                abort(403, 'No puedes cambiar el estatus de usuarios de otra área.');
            }

        }

        $validated = $request->validate([
            'activo' => ['required', 'boolean'],
        ]);

        $user->activo = $validated['activo'];
        $user->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'user'    => $user->fresh()->load(['roles:id,name', 'area:id,name']),
        ], 200);
    }


    public function permissions(string $id)
    {
        $authUser = auth('api')->user();
        if (!($authUser?->isAdmin() ?? false)) {
            abort(403, 'No tienes permiso para administrar permisos de usuarios.');
        }

        $user = User::with('roles:id,name')->findOrFail($id);

        $allPermissions = Permission::select(['id','name','description'])
            ->orderBy('name')
            ->get();

        $rolePermNames = $user->getPermissionsViaRoles()
            ->pluck('name')
            ->unique()
            ->values();

        $directPermNames = $user->permissions()
            ->pluck('name')
            ->unique()
            ->values();

        $availablePermissions = $allPermissions
            ->reject(fn ($p) => $rolePermNames->contains($p->name))
            ->values();

        return response()->json([
            'user' => $user->only(['id','name','email']),
            'roles' => $user->roles->pluck('name')->values(),
            'available_permissions' => $availablePermissions,
            'direct_permissions' => $directPermNames,
            // (opcional) debug
            //'role_permissions' => $rolePermNames,
        ]);
    }

    public function updatePermissions(Request $request, string $id)
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;

        if (!$isAdmin) {
            abort(403, 'No tienes permiso para administrar permisos de usuarios.');
        }

        $user = User::findOrFail($id);

        $incoming = $request->input('direct_permissions', null);
        if ($incoming === null) {
            $incoming = $request->input('permissions', null);
        }

        $data = $request->validate([
            'direct_permissions'   => ['nullable', 'array'],
            'direct_permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($request->has('permissions') && !$request->has('direct_permissions')) {
            $data['direct_permissions'] = $request->input('permissions', []);
        }

        $perms = $data['direct_permissions'] ?? [];

        $user->syncPermissions($perms);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user->load('permissions:id,name');

        return response()->json(['message' => 'Permisos directos actualizados correctamente', 'direct_permissions' => $user->permissions->pluck('name')->values(),]);
    }

    public function stats()
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;

        $base = User::query();

    
        if (!$isAdmin) {
            $base->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Administrador');
            });

            $myAreaId = (int) ($authUser->area_id ?? 0);
            if (!$myAreaId) {
                return response()->json([
                    'total'          => 0,
                    'activos'        => 0,
                    'inactivos'      => 0,
                    'ingresaron_hoy' => 0,
                ], 200);
            }

            $base->where('area_id', $myAreaId);
        }

        $total     = (clone $base)->count();
        $activos   = (clone $base)->where('Activo', 1)->count();
        $inactivos = (clone $base)->where('Activo', 0)->count();

        $tz    = config('app.timezone', 'America/Mexico_City');
        $start = now($tz)->startOfDay();
        $end   = now($tz)->endOfDay();

        $ingresaronHoy = (clone $base)
            ->whereNotNull('last_login_at')
            ->whereBetween('last_login_at', [$start, $end])
            ->count();

        return response()->json([
            'total'          => $total,
            'activos'        => $activos,
            'inactivos'      => $inactivos,
            'ingresaron_hoy' => $ingresaronHoy,
        ], 200);
    }



}
