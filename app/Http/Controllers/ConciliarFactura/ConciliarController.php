<?php

namespace App\Http\Controllers\ConciliarFactura;

use App\Http\Controllers\Controller;
use App\Services\ConciliarFactService;
use App\Services\CompanyContextService;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ConciliarController extends Controller
{
    public function __construct(
        protected ConciliarFactService $conciliarFactService,
        protected CompanyContextService $contextService,
        protected AccessControlService $accessControl
    ) {}

    public function index(Request $request)
    {
        if (!$this->accessControl->hasAnyRole(['SIIF', 'GRT', 'SUP'])) {
            $this->accessControl->logUnauthorizedAccess('Conciliación de Facturas');
            
            return redirect()->route('dashboard.index')->with('error', 'No tienes permisos para acceder a este módulo.');
        }
        try {
            
            $idFabricante = $this->contextService->getActiveId();

            $empresas = $this->conciliarFactService->getEmpresasMonitor();
            
            // Órdenes (solo si hay un fabricante identificado)
            $ordenesPaginadas = $this->conciliarFactService->getOrdenesParaSelect($idFabricante, $request);

            $ordenInfo = null;
            $productosPaginados = null;

            // Carga de detalle de orden
            if ($request->filled('orden')) {
                $detalle = $this->conciliarFactService->getOrdenConProductos($request->orden, $request);
                if ($detalle) {
                    $ordenInfo = $detalle['orden'];
                    $productosPaginados = $detalle['productos'];
                }
            }

            return Inertia::render('Consolidar', [
                'empresas' => $empresas,
                'formasDePago' => $this->conciliarFactService->getFormasDePago(),
                'ordenes' => $ordenesPaginadas,
                'ordenInfo' => $ordenInfo,
                'productos' => $productosPaginados,
                'selectedFabricante' => $idFabricante, // Para que el combo sepa qué empresa está activa
                'filters' => $request->only(['search', 'orden', 'page'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en ConciliarController:', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Error al cargar los datos.');
        }
    }
}