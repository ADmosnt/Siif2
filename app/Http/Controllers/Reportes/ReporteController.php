<?php
// app/Http/Controllers/Reportes/ReporteController.php 

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\ReporteDataService;
use App\Services\RepresentanteClienteService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

class ReporteController extends Controller
{
    protected $reporteDataService;
    protected $representanteClienteService;

    public function __construct(ReporteDataService $reporteDataService, RepresentanteClienteService $representanteClienteService)
    {
        $this->reporteDataService = $reporteDataService;
        $this->representanteClienteService = $representanteClienteService;
    }

    /**
     * Carga todos los datos iniciales para la página NuevoReporte.
     */
    public function index(Request $request)
    {
        try {
            Log::info('Iniciando carga de datos para NuevoReporte.', [
                'idRfv' => $request->idRfv,
                'user_id' => Auth::id(),
                'user_group' => Auth::user()->idgrupo_persona ?? 'No disponible',
                'params' => $request->all()
            ]);
            
            $view = match ($request->route()->getName()) {
                'toma-de-pedidos.index' => 'TomaDePedidos',
                'nuevo-reporte.index'   => 'RTR/NuevoReporte',
                default                 => 'Error',
            };

            $data = []; 

            // datos comunes para ambos reportes
            $data['representantes'] = $this->reporteDataService->getRepresentantesData($request);
            $data['productos'] = $this->reporteDataService->getProductosData($request);

            // datos específicos para cada reporte
            if ($view === 'TomaDePedidos') {
                $data['mayoristas'] = $this->reporteDataService->getMayoristasData($request);
            } elseif ($view === 'RTR/NuevoReporte') {
                $data['actividades'] = $this->reporteDataService->getActividadesData($request);
                $data['eventos'] = $this->reporteDataService->getEventosData($request);
            }

            //  TODOS LOS METADATOS DE PAGINACIÓN
            $data['filtros'] = $request->only([
                'search',
                'size',
                'page'
            ]);

            return Inertia::render($view, $data);

        } catch (\Exception $e) {
            Log::error('Error en la carga inicial de datos para NuevoReporte:', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'idRfv'   => $request->idRfv ?? 'No proporcionado',
                'params'  => $request->all()
            ]);

            return Inertia::render('Error', [
                'message' => 'Ocurrió un error al cargar los datos. Por favor, inténtelo de nuevo más tarde.'
            ]);
        }
    }

    public function searchClientes(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->input('search', '');
            $idRfv = $request->input('idRfv');

            // Validar idRfv si es obligatorio para la búsqueda
            if (!$idRfv) {
                return response()->json(['error' => 'idRfv es requerido'], 400);
            }

            // Llamar al servicio para obtener los clientes filtrados
            $clientes = $this->reporteDataService->searchClientesData($request);

            return response()->json($clientes, 200);

        } catch (\Exception $e) {
            Log::error('Error buscando clientes en el controlador:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => $request->all()
            ]);

            return response()->json(['message' => 'Ocurrió un error al buscar los clientes.'], 500);
        }
    }
}