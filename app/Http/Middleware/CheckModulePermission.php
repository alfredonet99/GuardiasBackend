<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckModulePermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $route = $request->route();
        $routeName = $route?->getName(); 

        if (! $routeName) {
            return $next($request);
        }

        $parts = explode('.', $routeName);

        if (count($parts) < 2) {
            return $next($request);
        }

        [$module, $action] = $parts;

        $map = [
            'index'   => 'browse',
            'read'   =>  'show', 
            'create'  => 'create',
            'store'   => 'create',
            'edit'    => 'edit',
            'update'  => 'edit',
            'destroy' => 'delete',
            'delete'  => 'delete',
            'stats'   => 'stats',
        ];

        if (! isset($map[$action])) {
            return $next($request);
        }

        $permAction = $map[$action];
        $requiredPermission = "{$module}.{$permAction}";

        if (! $user->can($requiredPermission)) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        return $next($request);
    }
}
