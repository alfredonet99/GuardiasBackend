<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role as SpatieRole;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        SpatieRole::updating(function (SpatieRole $role) {
            // Si el campo "name" cambió...
            if ($role->isDirty('name')) {
                // Opción fuerte: lanzar excepción y no guardar
                throw new \RuntimeException('Los roles no pueden ser renombrados.');

                // Opción alternativa (sin excepción, solo ignora el cambio):
                // $role->name = $role->getOriginal('name');
            }
        });
    }
}
