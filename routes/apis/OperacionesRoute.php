<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Operaciones\NetSuiteController;
use App\Http\Controllers\Operaciones\ClienteVeeamController;
use App\Http\Controllers\Operaciones\AppController;

Route::prefix('operaciones')->middleware('area.access:1')->group(function () {
        Route::get('clientes/netsuite', [NetSuiteController::class, 'index']);
        Route::get('clientes/netsuite/{id}', [NetSuiteController::class, 'show']);

        Route::get('/clientes/veeam',[ClienteVeeamController::class,'index']);
        Route::patch('/clientes/veeam/{id}/client-deactivate', [ClienteVeeamController::class, 'ClientDeactivate']);

        Route::get('/app',[AppController::class,'index']);
    });