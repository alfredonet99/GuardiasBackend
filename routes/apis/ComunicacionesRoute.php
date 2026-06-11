<?php

use App\Http\Controllers\Comunicaciones\SucursalController;
use App\Http\Controllers\Comunicaciones\MicrosoftController;
use App\Http\Controllers\Comunicaciones\MonitAAController;
use Illuminate\Support\Facades\Route;

Route::prefix('comunicaciones')->middleware('area.access:4')->group(function () {

    Route::get('/list/plataform',[SucursalController::class,'ListPlat']);
    Route::get('/list/sucursalesCreate',[SucursalController::class,'ListSucursales']);
    Route::get('/list/sucursales',[SucursalController::class,'index']);
    Route::get('/sucursales/{id}/editar',[SucursalController::class,'edit']);
    Route::put('/sucursales/{id}', [SucursalController::class, 'update']);
    Route::post('/crear/sucursal',[SucursalController::class,'store']);
    Route::post('/sucursales/import', [SucursalController::class, 'ImportDataSucursales']);
    Route::delete('/delete/sucursal/{id}',[SucursalController::class,'destroy']);

    Route::get('/microsoft/list',[MicrosoftController::class,'index']); 
    Route::get('/microsoft/create',[MicrosoftController::class,'create']);
    Route::post('/microsoft/store',[MicrosoftController::class,'store']);
    
    Route::get('/monitAA/create',[MonitAAController::class,'create']);
    Route::post('/monitAA/store',[MonitAAController::class,'store']);

});
