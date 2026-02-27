<?php

use App\Http\Controllers\Gerencial\gps\gpsController; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reportes\RepresentantesController;
use App\Http\Controllers\Reportes\ClientesController;
use App\Http\Controllers\Reportes\ActividadesController;
use App\Http\Controllers\Reportes\EventosController;
use App\Http\Controllers\Reportes\ProductosController;
use App\Http\Controllers\Reportes\ProcesarReporteController;
use App\Http\Controllers\Auth\ApiAuthController;


Route::post('/api-login', [ApiAuthController::class, 'login']);
Route::get('/gerencial/gps/ruta', [gpsController::class, 'obtenerRuta'])->name('gps.obtenerRuta');
Route::middleware(['auth:sanctum', 'report.access'])->group(function () {

        //Route::get('/representantes', [RepresentantesController::class, 'index']);        
        //Route::get('/clientes/{idRfv}', [ClientesController::class, 'getByRfv']);
        //Route::get('/actividades', [ActividadesController::class, 'index']);
        //Route::get('/eventos', [EventosController::class, 'index']);
        //Route::get('/productos', [ProductosController::class, 'index']);
        //Route::post('/reportes/nuevo', [ProcesarReporteController::class, 'new']);
            

});



/*
Route::get('/gerencial/filtros', [gerencialController::class, 'obtenerFiltrosDashboard']);
Route::get('/gerencial/datos', [gerencialController::class, 'obtenerDatosGerenciales']);
// Rutas para el módulo de GPS
Route::get('/gps/representantes', [gpsController::class, 'listarRepresentantesConActividad']);
Route::get('/gps/ruta', [gpsController::class, 'obtenerRuta']);

*/