<?php
//app/Http/Controllers/ConsultaReporte/ConsultaReporteController.php
namespace App\Http\Controllers\ConsultaReporte;

use App\Http\Requests\ConsultaReporte\ConsultaOrdenRequest;
use App\Http\Requests\ConsultaReporte\ConsultaVisitaRequest;
use App\Services\ConsultaReporte\ConsultaOrdenService;
use App\Services\ConsultaReporte\ConsultaVisitaService;
use App\Services\ConsultaOptionsService;
use App\Services\CompanyContextService;
use App\Exports\ConsultaReporte\VisitasExport;
use App\Exports\ConsultaReporte\MuestrasExport;
use App\Exports\ConsultaReporte\OrdenesExport;
use App\Exports\ConsultaReporte\ProductosOrdenExport;
use App\Services\RepresentanteClienteService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ConsultaReporteController extends Controller
{
    public function __construct(
        protected ConsultaOrdenService   $ordenService,
        protected ConsultaVisitaService $reporteService,
        protected ConsultaOptionsService $optionsService,
        protected CompanyContextService  $contextService,
        protected RepresentanteClienteService $representanteService,
    ) {}

    /**
     * Renderiza la página principal del módulo de consultas.
     * Pasa al frontend las opciones estáticas necesarias para los filtros de cada sección.
     */
    public function index(): Response
    {
        return Inertia::render('ConsultaReportes', [
            'options' => $this->optionsService->getOptions(),
        ]);
    }

    /**
     * Consulta de reportes de visitas con filtros.
     */
    public function consultaVisita(ConsultaVisitaRequest $request): JsonResponse
    {
        $resultado = $this->reporteService->consultar($request->validated(), $request);
        return response()->json($resultado, 200);
    }

    /**
     * Consulta de órdenes con filtros.
     */
    public function consultaOrden(ConsultaOrdenRequest $request): JsonResponse
    {
        $resultado = $this->ordenService->consultar($request->validated(), $request);
        return response()->json($resultado, 200);
    }


    public function exportarVisitas(Request $request): mixed
    {
        $filters = [
            'fechaInicio'  => $request->input('fechaInicio'),
            'fechaFin'     => $request->input('fechaFin'),
            'rfv'          => $this->decodeParam($request->input('rfv')),
            'zona'         => $this->decodeParam($request->input('zona')),
            'brick'        => $this->decodeParam($request->input('brick')),
            'ranking'      => $this->decodeParam($request->input('ranking')),
            'especialidad' => $this->decodeParam($request->input('especialidad')),
            'actividad'    => $this->decodeParam($request->input('actividad')),
            'incidente'    => $this->decodeParam($request->input('incidente')),
        ];

        $tipo  = $request->input('tipo', 'excel');
        $tabla = $request->input('tabla', 'visitas');
        $data  = $this->reporteService->exportarTodo($filters);


        if ($tipo === 'pdf') {
            // PDF siempre incluye ambas tablas en un solo documento
            $pdf = Pdf::loadView('exports.visitas', [
                'visitas'    => $data['visitas'],
                'muestras'   => $data['muestras'],
                'fechaInicio' => $request->input('fechaInicio'),
                'fechaFin'    => $request->input('fechaFin'),
            ])->setPaper('a4', 'landscape');

            return $pdf->download('reporte_visitas.pdf');
        }

        $export   = $tabla === 'muestras'
            ? new MuestrasExport($data['muestras'])
            : new VisitasExport($data['visitas']);

        $filename = $tabla === 'muestras' ? 'muestras_entregadas' : 'reporte_visitas';

        return Excel::download($export, "$filename.xlsx");
    }

    public function exportarOrdenes(ConsultaOrdenRequest $request): mixed
    {
        $filters = [
            'fechaInicio' => $request->input('fechaInicio'),
            'fechaFin'    => $request->input('fechaFin'),
            'rfv'         => $this->decodeParam($request->input('rfv')),
            'estatus'     => $this->decodeParam($request->input('estatus')),
            'mayorista'   => $this->decodeParam($request->input('mayorista')),
        ];

        $tipo  = $request->input('tipo', 'excel');
        $tabla = $request->input('tabla', 'ordenes');
        $data  = $this->ordenService->exportarTodo($filters);

        $totalUnidades = collect($data['ordenes'])->sum('unidades');
        $montoTotal    = collect($data['ordenes'])->sum('totalOrden');

        if ($tipo === 'pdf') {
            // dompdf tiene que construir y layoutear TODO el HTML en memoria
            // antes de rasterizar; con datasets grandes agota memory_limit/
            // max_execution_time y el proceso simplemente muere (a diferencia
            // del Excel, que escribe filas sin necesitar un arbol DOM/CSS
            // completo). Se limita el PDF a un tamaño manejable en vez de
            // dejarlo fallar en silencio/timeout.
            $maxFilasPdf = 1000;
            if (count($data['ordenes']) > $maxFilasPdf) {
                return response()->json([
                    'error' => "El PDF admite hasta {$maxFilasPdf} órdenes por reporte. Acota el rango de fechas o los filtros (o usa la exportación a Excel, sin ese límite).",
                ], 422);
            }

            try {
                $pdf = Pdf::loadView('exports.ordenes', [
                    'ordenes'      => $data['ordenes'],
                    'productos'    => $data['productos'],
                    'totalUnidades' => $totalUnidades,
                    'montoTotal'    => $montoTotal,
                    'fechaInicio'   => $request->input('fechaInicio'),
                    'fechaFin'      => $request->input('fechaFin'),
                ])->setPaper('a4', 'landscape');

                return $pdf->download('reporte_ordenes.pdf');
            } catch (\Throwable $e) {
                Log::error('Error generando PDF de ordenes', [
                    'exception' => $e->getMessage(),
                    'total_ordenes' => count($data['ordenes']),
                ]);
                return response()->json(['error' => 'No se pudo generar el PDF. Intenta acotar el rango de fechas o los filtros.'], 500);
            }
        }

        $export   = $tabla === 'productos'
            ? new ProductosOrdenExport($data['productos'])
            : new OrdenesExport($data['ordenes']);

        $filename = $tabla === 'productos' ? 'estadisticas_productos' : 'reporte_ordenes';

        return Excel::download($export, "$filename.xlsx");
    }

    public function getRepresentantes(Request $request, RepresentanteClienteService $repService): JsonResponse
    {
        $data = $repService->getRepresentantesData($request);
        return response()->json($data);
    }
    private function decodeParam(?string $value): ?array
{
    if (!$value || $value === 'null') return null;
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : null;

    }
}
