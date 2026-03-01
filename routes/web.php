<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\Gerencial\gerencialController;
use App\Http\Controllers\Reportes\ProcesarReporteController;
use App\Http\Controllers\Reportes\ReporteController;
use App\Http\Controllers\Ordenes\ProcesarOrdenController;
use App\Http\Controllers\ConciliarFactura\ConciliarController;
use App\Http\Controllers\ConciliarFactura\ConciliarFacturaController;
use App\Http\Controllers\Agenda\TmpPlanificadorController;
use App\Http\Controllers\ListaReportes\ListaReporteController;
use App\Http\Controllers\MonitorUp\PersonaUploadController;
use App\Http\Controllers\MonitorUp\ProductoUploadController;
use App\Http\Controllers\MonitorDown\PersonaDownloadController;
use App\Http\Controllers\MonitorDown\ProductoDownloadController;
use App\Http\Controllers\SeguimientoPedido\PedidoController;
use App\Http\Controllers\ListaCliente\AgendaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContextController;
use App\Http\Controllers\Admin\PersonaAdminController;
use App\Http\Controllers\Admin\ProductoAdminController;
use App\Http\Controllers\OptionsController;
use App\Http\Controllers\ContactController;

// ================================
// 1. RUTA DE AUTENTICACIÓN Y CSRF
// ================================
Route::get('/get-csrf-token', function() {
    return response()->json(['csrf_token' => csrf_token()]);
});

Route::get('/', fn() => Inertia::render('auth/Login'))->name('login');

// ================================
// 2. RUTAS PROTEGIDAS (AUTH)
// ================================
Route::middleware(['auth',])->group(function () {
    
    // Este controlador contiene lo que sería el filtro para paises, estados, ciudades y supervisores
    Route::get('/options/search', [OptionsController::class, 'search'])->name('options.search');

    //Este controlador es muy simple porque su único motivo de existir es recibir la orden de Vue y delegar en el servicio
    Route::post('/context/switch', [ContextController::class, 'switchFabricante'])->name('context.switch');

    // --- DASHBOARD Y MONITOR ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/monitor', [MonitorController::class, 'index'])->name('monitor.index');

    // RUTAS DE MENÚS GENÉRICOS (Selección de entidad)
    Route::get('/personas', function () {return Inertia::render('Administrar/MenuGen', ['tipo' => 'personas']);})->name('menu.personas');
    Route::get('/productos', function () {return Inertia::render('Administrar/MenuGen', ['tipo' => 'productos']);})->name('menu.productos');

    // ============================================================
    //  RUTAS GENÉRICAS FLEXIBLES
    // ============================================================
    // Define aquí la URL y el tipo de configuración que cargará
    // Formato: 'url_del_navegador' => 'tipo_en_index_ts'
    $rutasGenericasPersonas = [
        'clientes'       => 'clientes',
        'representantes' => 'representantes',
        'mayoristas'     => 'mayoristas',
        'supervisores'   => 'supervisores',
        'gerentes'       => 'gerentes',
    ];

    foreach ($rutasGenericasPersonas as $url => $tipo) {
        Route::group(['prefix' => $url, 'as' => "gen.$tipo."], function () use ($tipo) {
            Route::get('/', [PersonaAdminController::class, 'index'])->defaults('tipo', $tipo);
            Route::post('/', [PersonaAdminController::class, 'store'])->defaults('tipo', $tipo);
            Route::put('/{id}', [PersonaAdminController::class, 'update'])->defaults('tipo', $tipo);
            Route::delete('/{id}', [PersonaAdminController::class, 'destroy'])->defaults('tipo', $tipo);
        });
    }

    $rutasGenericasProductos = [
        'muestras'       => 'muestras',
        'productos-lista'=> 'productos',
    ];

        foreach ($rutasGenericasProductos as $url => $tipo) {
        Route::group(['prefix' => $url, 'as' => "gen.$tipo."], function () use ($tipo) {
            Route::get('/', [ProductoAdminController::class, 'index'])->defaults('tipo', $tipo);
            Route::post('/', [ProductoAdminController::class, 'store'])->defaults('tipo', $tipo);
            Route::put('/{id}', [ProductoAdminController::class, 'update'])->defaults('tipo', $tipo);
            Route::delete('/{id}', [ProductoAdminController::class, 'destroy'])->defaults('tipo', $tipo);
        });
    }
    // --- REPORTES (RTR) ---
    Route::post('/reportes/nuevo', [ProcesarReporteController::class, 'new'])->name('reportes.procesar');
    Route::get('/nuevo-reporte', [ReporteController::class, 'index'])->name('nuevo-reporte.index');
    Route::get('/reporte-agenda', fn() => Inertia::render('RTR/ReporteAgenda'))->name('ReporteAgenda');
    Route::get('/consulta-reporte', fn() => Inertia::render('ConsultaReportes'))->name('ConsultaReporte');

    // --- LISTA DE CLIENTES ---
    Route::get('/agenda', [AgendaController::class, 'index'])->name('rtr.lista-clientes');
    Route::get('/agenda/exportar', [AgendaController::class, 'exportarExcel'])->name('agenda.exportar');
    Route::get('/agenda-representantes-data-agenda', [AgendaController::class, 'getRepresentantes'])->name('agenda.representantes');
    Route::get('/agenda-clientes-list', [AgendaController::class, 'getClientes'])->name('agenda.clientes.list');

    // Rutas para listas de reportes y datos relacionados
    Route::get('/reportes', [ListaReporteController::class, 'getReports'])->name('reportes.lista');
    Route::get('/representantes-data', [ListaReporteController::class, 'getRepresentantes'])->name('reportes.representantes');

    // Búsqueda de clientes
    Route::get('/clientes/search', [ReporteController::class, 'searchClientes'])->name('clientes.search');

    // --- GERENCIAL ---
    Route::get('/consulta-gerencial', [gerencialController::class, 'index'])->name('gerencial.index');
    
    // --- SEGUIMIENTO DE PEDIDOS ---
    Route::get('/seguimiento', [PedidoController::class, 'seguimiento'])->name('pedidos.seguimiento');
    Route::get('/representantes-data-pedidos', [PedidoController::class, 'getRepresentantes'])->name('pedidos.representantes');
    Route::get('/pedidos', [PedidoController::class, 'getOrdenesFiltradas'])->name('pedidos.filtradas');
    Route::get('/pedidos/{id}', [PedidoController::class, 'getOrdenDetalle'])->name('pedidos.detalle');
    Route::get('/estatus-ordenes', [PedidoController::class, 'getEstatus'])->name('pedidos.estatus');

    // --- TOMA DE PEDIDOS (TDP) ---
    Route::get('/tdp', [ReporteController::class, 'index'])->name('toma-de-pedidos.index');
    Route::post('/toma-de-pedidos', [ProcesarOrdenController::class, 'store'])->name('toma-de-pedidos.procesar');

    // --- CONCILIACIÓN DE FACTURAS ---
    Route::get('/consolidar', [ConciliarController::class, 'index'])->name('consolidar.index');
    Route::post('/conciliar-factura', [ConciliarFacturaController::class, 'store'])->name('conciliar-factura.store');

    // --- PLANIFICADOR / CALENDARIO ---
    // API Json para el calendario
    Route::get('/tmp-planificaciones/rfvs-disponibles', [TmpPlanificadorController::class, 'getRfvsDisponibles'])->name('tmp_planificaciones.rfvs_disponibles');
    Route::get('/tmp-planificaciones/clientes-por-rfv/{rfvId}', [TmpPlanificadorController::class, 'getClientesPorRfv'])->name('tmp_planificaciones.clientes_por_rfv');
    Route::get('/tmp-planificaciones/calendario', [TmpPlanificadorController::class, 'getEventosParaCalendario'])->name('tmp_planificaciones.calendario.index');
    
    // CRUD Planificaciones
    Route::get('/tmp-planificaciones/{id}/procesar', [TmpPlanificadorController::class, 'showProcesarVisita'])->name('tmp_planificaciones.procesar');
    Route::get('/tmp-planificaciones', [TmpPlanificadorController::class, 'index'])->name('tmp_planificaciones.index');
    Route::post('/tmp-planificaciones', [TmpPlanificadorController::class, 'store'])->name('tmp_planificaciones.store');
    Route::put('/tmp-planificaciones/{id}', [TmpPlanificadorController::class, 'update'])->name('tmp_planificaciones.update');
    Route::delete('/tmp-planificaciones/{id}', [TmpPlanificadorController::class, 'destroy'])->name('tmp_planificaciones.destroy');

    // --- VISTAS ESTÁTICAS / SIN CONTROLADOR ---
    Route::get('/notificacion', fn() => Inertia::render('Notificacion'))->name('notificacion');
    Route::get('/desc', fn() => Inertia::render('Descuentos'))->name('descuentós');
    Route::get('/preguntas', fn() => Inertia::render('Preguntas'))->name('Preguntas');
    Route::get('/Contacto', fn() => Inertia::render('Contacto'))->name('Contacto');
    Route::get('/agregar-operadores', fn() => Inertia::render('Administrar/Operadores'))->name('operadores');

    // ========================================
    // RUTA PARA EL ENVIO DE COMENTARIO EN LA VISTA DE CONTACTO
    // ========================================
    Route::post('/contacto', [ContactController::class, 'send'])->name('contact.send');

    // --- UPLOAD ---
    Route::post('/upload/personas/personas', [PersonaUploadController::class, 'storeNewPersonas']);
    Route::post('/upload/personas/clientesRfv', [PersonaUploadController::class, 'storeClientesRfv']);
    Route::post('/upload/productos', [ProductoUploadController::class, 'storeProducto']);

    // --- EXPORT ---
    Route::post('/export/personas/personasDown', [PersonaDownloadController::class, 'PersonaDownload']);
    Route::post('/export/personas/clientesRfvDown', [PersonaDownloadController::class, 'ClientByRFVDownload']);
    Route::post('/export/productos/productoDown', [ProductoDownloadController::class, 'ProductoDownloadByFabricante']);

    // --- EXPORT PDF y EXCEL ---
    Route::get('/gerencial/export/pdf', [gerencialController::class, 'exportPdf'])->name('gerencial.export.pdf');
    Route::get('/gerencial/export/excel', [gerencialController::class, 'exportExcel'])->name('gerencial.export.excel');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';



