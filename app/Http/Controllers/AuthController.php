<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function Login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $dbUser = User::where('email', $credentials['email'])->first();

        if ($dbUser && (int) $dbUser->Activo !== 1) {
            Log::warning('LOGIN BLOCKED: usuario desactivado', ['user_id' => $dbUser->id,'email'   => $dbUser->email,]);
            return response()->json(['message' => "Tu usuario está desactivado.\nContacta a tu Administrador.",], 403);
        }

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => "Credenciales inválidas.\nVerifica tu correo y tu contraseña."], 401);
        }

        $user = auth('api')->user();
        $user->forceFill(['last_login_at' => now()])->save();
        $user->load([
            'roles:id,name',
            'area:id,name',
        ]);

        Log::info($token);

        return response()->json([
            'token'       => $token,
            'token_type'  => 'bearer',
            'expires_in'  => auth('api')->factory()->getTTL() * 60,
            'user'        => $user,
        ], 200);
    }


    public function refresh()
    {
        try {
            $newToken = auth('api')->refresh();

            Log::info("🔄 TOKEN REFRESCADO", ['user_id' => auth('api')->id(),]);

            return response()->json([
                'token'      => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ], 200);

        } catch (\Throwable $e) {
            Log::warning("❌ NO SE PUDO REFRESCAR TOKEN", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo refrescar el token',
                'error'   => $e->getMessage(),
            ], 401);
        }
    }

    /*public function profile()
    {
        $user = auth('api')->user();

        return response()->json([
            'user' => $user->load([
                'roles:id,name',
                'area:id,name',
            ])->only(['id','name','email','avatar','area_id']) + [
                // opcional: si quieres devolver area ya cargada (por load)
            ],
        ], 200);
    }*/

    public function me(Request $request)
    {
        $user = auth('api')->user();

        $user->load([
            'roles:id,name',
            'area:id,name',
        ]);

        $includePerms = $request->boolean('perms', false);

        return response()->json([
            'valid'   => true,
            'user'    => $user,
            'isAdmin' => $user->hasRole('Administrador'),
            'roles'   => $user->getRoleNames(),

            // 👇 Solo si lo pides
            'perms'   => $includePerms
                ? $user->getAllPermissions()->pluck('name')->values()
                : [],
        ], 200);
    }


}
