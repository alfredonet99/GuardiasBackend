<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $next($request);
        }

        if ((int) $user->Activo !== 1) {
            try {
                auth('api')->logout();
            } catch (\Throwable $e) {}

            return response()->json([
                'message' => 'Tu usuario está desactivado. Contacta a Sistemas.',
                'reason'  => 'inactive',
                'valid'   => false,
            ], 403);
        }

        return $next($request);
    }
}
