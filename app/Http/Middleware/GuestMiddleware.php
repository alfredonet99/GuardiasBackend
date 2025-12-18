<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
class GuestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
{
    try {
        // Intenta autenticar el token
        $user = JWTAuth::parseToken()->authenticate();

        if ($user) {
            return response()->json(['message' => 'Ya tienes una sesión activa'], 409);
        }

    } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
        // Token inválido: lo tratamos como "no autenticado"
    } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
        // Token expirado: permitir nuevo login
    } catch (\Exception $e) {
        // No hay token: permitir login
    }

    return $next($request);
}

}
