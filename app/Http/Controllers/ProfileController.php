<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class ProfileController extends Controller
{
    // ===============================
    // GET /profile
    // ===============================
    public function show(Request $request)
    {
        $user = $request->user();

    Log::info('🟩 [PROFILE:SHOW] HIT desde front', [
        'user_id'   => $user?->id,
        'email'     => $user?->email,
        'area_id'   => $user?->area_id,
        'ip'        => $request->ip(),
        'method'    => $request->method(),
        'url'       => $request->fullUrl(),
        'ua'        => substr((string) $request->userAgent(), 0, 180),
        'has_token' => $request->bearerToken() ? true : false,
    ]);

    return response()->json(
        $user->load(['area:id,name'])
    );
    }

     public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'url'],
        ]);

        $user->name = $request->name;

        if ($request->filled('avatar')) {
            $user->avatar = $request->avatar;
        }

        $user->save();

        $user->load([
            'roles:id,name',
            'area:id,name',
        ]);

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user'    => $user,
        ]);
    }

     public function updatePassword(Request $request)
    {

        $request->validate([
            'current_password' => ['required'],
            'new_password'     => ['required', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'error' => 'La contraseña actual es incorrecta',
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Contraseña actualizada correctamente',]);
    }

}
