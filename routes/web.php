<?php

use Illuminate\Support\Facades\Route;
use App\Services\NetSuiteService;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/netsuite/test', function (NetSuiteService $ns) {
    return $ns->query('SELECT id, entityid FROM customer');
});