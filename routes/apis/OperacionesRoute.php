<?php

use App\Http\Controllers\Operaciones\GuardiasController;
use App\Http\Controllers\Operaciones\MonitoreoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Operaciones\NetSuiteController;
use App\Http\Controllers\Operaciones\ClienteVeeamController;
use App\Http\Controllers\Operaciones\AppController;
use App\Http\Controllers\Operaciones\TicketsController;

Route::prefix('operaciones')->middleware('area.access:1')->group(function () {
        Route::get('clientes/netsuite', [NetSuiteController::class, 'index']);
        Route::get('clientes/netsuite/{id}', [NetSuiteController::class, 'show']);

        Route::get('/clientes/veeam',[ClienteVeeamController::class,'index'])->name('clientveeam.index');
        Route::post('/cliente-veeam/store',[ClienteVeeamController::class,'store'])->name('clientveeam.store');
        Route::get('/cliente-veeam/editar/{id}',[ClienteVeeamController::class,'edit'])->name('clientveeam.edit');
        Route::put('/cliente-veeam/update/{id}',[ClienteVeeamController::class,'update'])->name('clientveeam.update');
        Route::get('/cliente-veeam/show/{id}',[ClienteVeeamController::class,'show'])->name('clientveeam.show');
        Route::delete('/cliente-veeam/{id}/delete',[ClienteVeeamController::class,'destroy']);
        Route::patch('/clientes/veeam/{id}/client-deactivate', [ClienteVeeamController::class, 'ClientDeactivate']);
        Route::get('/obtener/lista-veeam',[ClienteVeeamController::class,'SelectVeeam']);

        Route::get('/app',[AppController::class,'index']);
        Route::patch('/app/{id}/app-deactivate', [AppController::class, 'toggleActivo'])->name('appclient.status');
        Route::post('/app/store',[AppController::class, 'store']);
        Route::get('/app/{id}/editar',[AppController::class,'edit']);
        Route::put('/app/{id}/update',[AppController::class,'update']);
        Route::delete('app/{id}/delete',[AppController::class,'destroy']);
        Route::get('/listaVeeam',[AppController::class,'ListVeeam']);

        Route::get('/guardias',[GuardiasController::class,'index']);
        Route::post("/guardias/store",[GuardiasController::class, 'store']);
        Route::get('/guardias/active', [GuardiasController::class, 'active']);
        Route::get('/guardias/close', [GuardiasController::class, 'closeData']);
        Route::patch('/guardias/close/tickets', [GuardiasController::class, 'updateCloseTicket']);


        Route::get('/tickets',[TicketsController::class,'index']);
        Route::post('/tickets/crear',[TicketsController::class,'store']);
        Route::get('/tickets/{id}/editar',[TicketsController::class,'edit']);
        Route::put('/tickets/{id}/update',[TicketsController::class,'update']);
        Route::get('/tickets/{id}/ver-ticket',[TicketsController::class,'show']);
        Route::patch('/tickets/{id}/status', [TicketsController::class, 'StatusTicket']);

        Route::get('/monitoreos',[MonitoreoController::class,'index']);
        Route::post('/monitoreos/store',[MonitoreoController::class,'store']);
        Route::patch('/monitoreos/{id}/status',[MonitoreoController::class,'statusMonitoreo']);

    });