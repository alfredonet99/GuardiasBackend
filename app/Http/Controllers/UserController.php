<?php

namespace App\Http\Controllers;
use App\Models\User;
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

class UserController extends Controller
{
   
    public function index(Request $request)
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;
        $search = $request->input('search');
        $query = User::query()->with('roles:id,name');

        if (!$isAdmin) {
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

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'activo'   => $validated['activo'] ?? true,
        ]);

        $user->assignRole($validated['role']);

        return response()->json(['message' => 'Usuario creado correctamente', 'user'    => $user->load('roles'),], 201);
    }

    public function edit(string $id)
    {
        $user = User::with('roles')->findOrFail($id);
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;
        $rolesQuery = Role::orderBy('name', 'asc')->select(['id', 'name']);

        if (!$isAdmin) {
            $rolesQuery->where('name', '!=', 'Administrador');
        }

        $roles = $rolesQuery->get();

        return response()->json(['user'  => $user,'roles' => $roles,]);
    }


    public function update(Request $request, string $id)
    {
        $user = User::with('roles')->findOrFail($id);
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;

        if (!$isAdmin && $user->hasRole('Administrador')) {
            if ($request->filled('role')) {
                abort(403, 'No tienes permiso para cambiar el rol de un Administrador.');
            }
        }

        $rules = [
            'name'     => ['required', 'string', 'min:3', 'max:255', new OnlyLettersSpaces],
            'role'     => ['required', 'exists:roles,name'],
            'activo'   => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ];

        if (!$isAdmin && $request->filled('email') && $request->input('email') !== $user->email) {
            abort(403, 'No tienes permiso para cambiar el correo.');
        }

        if ($isAdmin) {
            $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];
        }

        $validated = $request->validate($rules, ['email.unique' => 'El correo ya está registrado.',]);

        if (!$isAdmin && ($validated['role'] ?? null) === 'Administrador') {
            abort(403, 'No tienes permiso para asignar el rol Administrador.');
        }

        if (!$isAdmin && $user->hasRole('Administrador') && $validated['role'] !== 'Administrador') {
            abort(403, 'No tienes permiso para quitar el rol Administrador.');
        }

        $user->name   = $validated['name'];
        $user->activo = $validated['activo'] ?? $user->activo;

        if ($isAdmin && isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (!$isAdmin && $user->hasRole('Administrador')) {
            $user->syncRoles(['Administrador']);
        } else {
            $user->syncRoles([$validated['role']]);
        }

        return response()->json(['message' => 'Usuario actualizado correctamente','user'    => $user->load('roles'),], 200);
    }


    public function show(string $id)
    {
        $authUser = auth('api')->user();
        $isAdmin  = $authUser?->isAdmin() ?? false;
        $user = User::with('roles:id,name')->findOrFail($id);

        if (!$isAdmin && $user->hasRole('Administrador', 'api')) {
            abort(403, 'No tienes permiso para ver perfiles Administrador.');
        }

        $payload = [
            'user'  => $user->only(['id','name','email','Activo','avatar']),
            'roles' => $user->roles->pluck('name')->values(),
        ];

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
        $user = User::with('roles')->findOrFail($id);

        if (!$isAdmin) {
            abort(403, 'No tienes permiso para cambiar el estatus de usuarios.');
        }

        if ($authUser && (string)$authUser->id === (string)$user->id) {
            abort(403, 'No puedes cambiar tu propio estatus.');
        }

        $validated = $request->validate([
            'activo' => ['required', 'boolean'],
        ]);

        $user->activo = $validated['activo'];

        $user->save();

        return response()->json(['message' => 'Estado actualizado correctamente','user'    => $user->fresh()->load('roles:id,name'),], 200);
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
