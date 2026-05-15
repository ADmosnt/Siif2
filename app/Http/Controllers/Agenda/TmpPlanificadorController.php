<?php

//app/Http/Controllers/Agenda/TmpPlanificadorController.php
/*
En este controlador se reutiliza el servicio de reporteDataService para obtener listas de actividades, eventos y productos.
porque al reutilizar la vista de NuevoReporte, se necesita esa información para llenar los selects correspondientes.
 */
namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Services\TmpPlanificadorService;
use App\Services\RepresentanteClienteService;
use App\Services\ReporteDataService;
use App\Services\AccessControlService;
use App\Imports\VisitasImports\VisitaMasivaImport;
use App\Exports\VisitasExports\PlantillaVisitasExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class TmpPlanificadorController extends Controller
{

    public function __construct(
        protected TmpPlanificadorService $tmpPlanificadorService,
        protected RepresentanteClienteService $representanteClienteService,
        protected ReporteDataService $reporteDataService,
        protected AccessControlService $accessControl
    ) {}

    /**
     * Muestra la vista principal de visitas
     */
    public function index(Request $request)
    {
        if (!$this->accessControl->hasAnyRole(['SIIF', 'GRT', 'SUP', 'RFV'])) {
            $this->accessControl->logUnauthorizedAccess('Conciliación de Facturas');
            
            return redirect()->route('dashboard.index')->with('error', 'No tienes permisos para acceder a este módulo.');
        }
        return Inertia::render('RTR/Calendario', [
            'filtros' => $request->only(['search', 'size', 'page', 'idRfv']),
        ]);
    }

    /**
     * Obtiene eventos para el calendario
     */
    public function getEventosParaCalendario(Request $request): JsonResponse
        {
            try{
            return response()->json($this->tmpPlanificadorService->getEventosParaCalendario($request));
        
            } catch (\Exception $e) {
                $statusCode = $e->getCode() === 403 || $e->getCode() === 404 ? $e->getCode() : 500;
                return response()->json([
                    'message' => $e->getMessage()
                ], $statusCode);
            }
        }

    /**
     * Almacena una nueva visita temporal
     */
    public function store(Request $request): JsonResponse
        {
            try{
            $nuevaVisita = $this->tmpPlanificadorService->crearVisitaTemporal($request->all());
            return response()->json([
                'message' => 'Creada', 
                'data' => $nuevaVisita
                ], 201);
            } catch (\Exception $e) {
                $statusCode = $e->getCode() === 403 || $e->getCode() === 404 ? $e->getCode() : 500;
                return response()->json([
                    'message' => $e->getMessage()
                ], $statusCode);
            }
        }

    /**
     * Actualiza una visita temporal
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $visitaActualizada = $this->tmpPlanificadorService->actualizarVisitaTemporal($id, $request->all());

            return response()->json([
                'message' => 'Visita temporal actualizada exitosamente.',
                'data' => $visitaActualizada
            ], 200);

        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 403 || $e->getCode() === 404 ? $e->getCode() : 500;
            return response()->json([
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Elimina una visita temporal
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->tmpPlanificadorService->eliminarVisitaTemporal($id);

            return response()->json([
                'message' => 'Visita temporal eliminada exitosamente.'
            ], 200);

        } catch (\Exception $e) {
            $statusCode = $e->getCode() === 403 || $e->getCode() === 404 ? $e->getCode() : 500;
            return response()->json([
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Muestra la vista para procesar una visita temporal
     */
    public function showProcesarVisita(Request $request, int $idVisitaTemporal)
    {
        try {
            $data = $this->tmpPlanificadorService->getDataParaProcesarVisita($idVisitaTemporal, $request);
            $data['actividades'] = $this->reporteDataService->getActividadesData($request);
            $data['eventos'] = $this->reporteDataService->getEventosData($request);
            $data['productos'] = $this->reporteDataService->getProductosData($request);
            return Inertia::render('RTR/NuevoReporte', $data);

        } catch (\Exception $e) {
            Log::error('Error cargando vista NuevoReporte para procesar visita temporal:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id_visita_temporal' => $idVisitaTemporal,
                'params' => $request->all()
            ]);

            $statusCode = $e->getCode() === 403 || $e->getCode() === 404 ? $e->getCode() : 500;
            
            return Inertia::render('Error', [
                'message' => $e->getMessage() ?: 'Ocurrió un error al cargar la vista para procesar la visita.'
            ]);
        }
    }

    /**
     * Obtiene RFVs disponibles
     */

    public function getRfvsDisponibles(Request $request): JsonResponse
    {
        try {

            // Esto devuelve solo RFVs, no GRTs
            $representantes = $this->representanteClienteService->getRepresentantesData($request);
            
            return response()->json($representantes->map(fn($r) => [
                        'value' => $r['id'],
                        'label' => $r['nombre']
            ]));
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo RFVs disponibles:', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function descargarPlantilla()
    {
        return Excel::download(new PlantillaVisitasExport(), 'plantilla_visitas.xlsx');
    }

    public function cargaMasiva(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $import = new VisitaMasivaImport();
            Excel::import($import, $request->file('archivo'));

            $importados = $import->getImportados();
            $errores = $import->getErrors();
            $failures = $import->getFailures();

            $mensaje = "{$importados} visita(s) creada(s) correctamente.";
            if (count($errores) > 0 || count($failures) > 0) {
                $mensaje .= ' Algunas filas tuvieron errores.';
            }

            return response()->json([
                'message' => $mensaje,
                'importados' => $importados,
                'errores' => $errores,
                'failures' => collect($failures)->map(fn($f) => [
                    'row' => $f->row(),
                    'attribute' => $f->attribute(),
                    'errors' => $f->errors(),
                ])->toArray(),
            ], $importados > 0 ? 200 : 422);
        } catch (\Exception $e) {
            Log::error('Error en carga masiva de visitas:', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Error al procesar el archivo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene clientes por RFV
     */
    public function getClientesPorRfv(Request $request, string $rfvId): JsonResponse
    {
        try {

$request->merge(['idRfv' => $rfvId]);
        return response()->json($this->representanteClienteService->searchClientesData($request));
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo clientes por RFV:', [
                'message' => $e->getMessage(),
                'rfv_id' => $rfvId,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([], 500);
        }
    }
}