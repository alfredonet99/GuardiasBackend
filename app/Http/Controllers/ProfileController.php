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
        return response()->json($request->user()->load('roles'));
    }

     public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'url'],
        ]);

        // Solo actualizamos lo permitido
        $user->name = $request->name;

        if ($request->filled('avatar')) {
            $user->avatar = $request->avatar;
        }

        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user'    => $user->load('roles'),
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
