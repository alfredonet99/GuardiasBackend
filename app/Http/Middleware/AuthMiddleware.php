<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Validar token y autenticar usuario
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['message' => 'Token inválido'], 401);
            }

        } catch (TokenExpiredException $e) {

            // ⚠️ Si el token expiró PERO la ruta es /auth/refresh → permitir que pase
            if ($request->is('api/auth/refresh')) {
                return $next($request);
            }

            return response()->json([
                'message' => 'Token expirado',
            ], 401);

        } catch (TokenInvalidException $e) {

            return response()->json([
                'message' => 'Token inválido',
            ], 401);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Token ausente o no procesable',
            ], 401);
        }

        return $next($request);
    }
}
