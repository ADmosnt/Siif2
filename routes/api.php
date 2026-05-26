<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\Api\ActividadController;
use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\OrdenController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\RepresentanteController;
use App\Http\Controllers\Api\MayoristaController;
use App\Http\Controllers\Api\TipoActividadController;
use App\Http\Controllers\Api\IncidenteController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\OfflineController;
use App\Http\Controllers\Gerencial\gps\gpsController;

/*
|--------------------------------------------------------------------------
| API Routes - App Móvil
|--------------------------------------------------------------------------
|
| Estas rutas son cargadas con el prefijo /api automáticamente.
| Usan autenticación Sanctum (tokens Bearer) para la app móvil.
|
| Autenticación: Enviar header "Authorization: Bearer <token>"
| Puerto: El mismo que la app web (definido en .env o docker)
|
*/

// ================================
// RUTAS PÚBLICAS (sin token)
// ================================

Route::post('/login', [ApiAuthController::class, 'login']);

// ================================
// RUTAS GPS (consultas externas)
// ================================

Route::get('/gerencial/gps/ruta', [gpsController::class, 'obtenerRuta'])->name('gps.obtenerRuta');

// ================================
// RUTAS PROTEGIDAS (requieren token Sanctum)
// ================================

Route::middleware('auth:sanctum')->group(function () {

    // --- AUTENTICACIÓN ---
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::get('/profile', [ApiAuthController::class, 'profile']);

    // --- TIPOS DE ACTIVIDADES ---
    Route::get('/actividades/tipos', [TipoActividadController::class, 'index']);

    // --- INCIDENTES ---
    Route::get('/incidentes', [IncidenteController::class, 'index']);

    // --- ACTIVIDADES / REPORTES ---
    Route::prefix('/actividad')->group(function () {
        Route::get('/', [ActividadController::class, 'index']);
        Route::post('/nueva', [ActividadController::class, 'store']);
        Route::post('/desde-agenda', [ActividadController::class, 'storeFromAgenda']);
        Route::post('/firma', [ActividadController::class, 'saveFirma']);
        Route::post('/detalle', [ActividadController::class, 'detalle']);
    });

    // --- AGENDA / PLANIFICACIÓN ---
    Route::prefix('/agenda')->group(function () {
        Route::get('/citas', [AgendaController::class, 'index']);
        Route::get('/pendientes', [AgendaController::class, 'citasPendientes']);
        Route::get('/clientes-disponibles', [AgendaController::class, 'clientesDisponibles']);
        Route::get('/rfvs', [AgendaController::class, 'rfvsDisponibles']);
        Route::post('/crear', [AgendaController::class, 'store']);
        Route::post('/actualizar', [AgendaController::class, 'update']);
        Route::post('/eliminar', [AgendaController::class, 'destroy']);
    });

    // --- ÓRDENES / PEDIDOS ---
    Route::prefix('/orden')->group(function () {
        Route::get('/', [OrdenController::class, 'index']);
        Route::post('/crear', [OrdenController::class, 'store']);
        Route::post('/detalle', [OrdenController::class, 'show']);
    });

    // --- CLIENTES ---
    Route::prefix('/clientes')->group(function () {
        Route::get('/', [ClienteController::class, 'index']);
        Route::post('/buscar-id', [ClienteController::class, 'searchById']);
        Route::post('/buscar-nombre', [ClienteController::class, 'searchByName']);
        Route::post('/materiales', [ClienteController::class, 'materiales']);
    });

    // --- PRODUCTOS ---
    Route::prefix('/producto')->group(function () {
        Route::get('/', [ProductoController::class, 'index']);
        Route::post('/crear', [ProductoController::class, 'store']);
        Route::post('/modificar', [ProductoController::class, 'update']);
    });

    // --- REPRESENTANTES ---
    Route::get('/representantes', [RepresentanteController::class, 'index']);

    // --- MAYORISTAS ---
    Route::get('/mayoristas', [MayoristaController::class, 'index']);

    // --- NOTIFICACIONES ---
    Route::prefix('/notificacion')->group(function () {
        Route::get('/', [NotificacionController::class, 'index']);
        Route::post('/crear', [NotificacionController::class, 'store']);
        Route::post('/actualizar', [NotificacionController::class, 'update']);
        Route::post('/eliminar', [NotificacionController::class, 'destroy']);
        Route::post('/vista', [NotificacionController::class, 'marcarVista']);
    });

    // --- PUSH SUBSCRIPTIONS ---
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store']);
    Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'destroy']);

    // --- OFFLINE / DATOS MAESTROS ---
    Route::get('/offline/master-data', [OfflineController::class, 'masterData']);
});