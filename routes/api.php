<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AreaController;

Route::middleware(GuestMiddleware::class)->group(function(){
    Route::post('login',[AuthController::class,'login']);
});

//Route::middleware(AuthMiddleware::class)->group(function(){
Route::middleware(['auth:api','active.user', AuthMiddleware::class, 'module.permission'])->group(function () {

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile/update',[ProfileController::class,'update']);
    Route::post('/profile/update/password',[ProfileController::class,'updatePassword']);
    Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');


    Route::get('/user/permissions', function () {
        $user = auth()->user();
        return response()->json([
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    })->middleware('auth:api');


    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/crear', [RoleController::class, 'create']);
    Route::post('/roles/store', [RoleController::class, 'store']);
    Route::get('/roles/{id}/editar', [RoleController::class, 'edit']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::get('/roles/{id}/ver', [RoleController::class, 'show']);
    Route::delete('/roles/delete/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');


    Route::delete('/permissions/delete/{id}', [PermissionController::class, 'destroy'])->name('permisos.destroy');


    Route::get('/permissions', [PermissionController::class, 'index'])->name('permisos.index');
    Route::post('/permissions/crear', [PermissionController::class, 'storeIndividual']);
    Route::post('/permissions/crear-crud', [PermissionController::class, 'storeCrud']);
    
    Route::get('auth/check', function () { return response()->json(['valid' => true]); });

    Route::get('/system/logs', [SystemLogController::class, 'index']);
    
    //Route::get('profile',[AuthController::class,'profile']);

    Route::get('users',[UserController::class,'index'])->name('users.index');
    Route::get('/users/crear', [UserController::class, 'create'])->name('users.create');
    Route::post('/users/store',[UserController::class,'store'])->name('users.store');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}/update', [UserController::class, 'update'])->name('users.update');
    Route::get('/users/{id}/ver', [UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{id}/status', [UserController::class, 'updateStatus']);
    Route::get('/users/{id}/vpermission', [UserController::class, 'permissions']);
    Route::put('/users/{id}/permissionup', [UserController::class, 'updatePermissions']);
    Route::delete('/users/{id}/delete', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/stats', [UserController::class, 'stats'])->name('users.stats');
    Route::post('auth/refresh', [AuthController::class, 'refresh']);

    Route::get('areas',[AreaController::class,'index'])->name('area.index');
    Route::post('areas/store',[AreaController::class,'store'])->name('area.store');
    Route::patch('/areas/{id}/status', [AreaController::class, 'status']);
    Route::delete('/areas/{id}/delete', [AreaController::class, 'destroy'])->name('area.destroy');
});


Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
Route::get('/password/validate', [PasswordResetController::class, 'validateToken']);

/*Route::post('/paquetexpress/cotizar', [CotizadorController::class, 'cotizar']);
Route::post('/paquetexpress/cotizar', [CotizadorController::class, 'cotizar']);
Route::get('/trazabilidad/{rastreo}', [TrazabilidadController::class, 'show']);
Route::post('/paquetexpress/carta-porte', [PqExpressController::class, 'generarCartaPorte']);*/
