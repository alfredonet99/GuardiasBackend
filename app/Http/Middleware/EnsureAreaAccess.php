<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Support\RoleAreaMapper;

class EnsureAreaAccess
{
    public function handle(Request $request, Closure $next, ...$allowedAreas): Response
    {
        $user = $request->user('api') ?? $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        $roleId = optional($user->roles->first())->id;

        $userAreaId = RoleAreaMapper::areaIdFromRoleId($roleId);

        if (!$userAreaId) {
            return response()->json(['message' => 'Sin área asignada'], 403);
        }

        $allowedAreas = array_map('intval', $allowedAreas);

        if (!in_array((int)$userAreaId, $allowedAreas, true)) {
            return response()->json(['message' => 'No autorizado para esta área'], 403);
        }

        return $next($request);
    }
}
