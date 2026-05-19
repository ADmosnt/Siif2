<?php
//app/Http/Controllers/Gerencial/gerencialController.php
namespace App\Http\Controllers\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\TCoberturaRv;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\RepresentanteClienteService;
use App\Services\CompanyContextService;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Gerencial\GerencialExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GerencialController extends Controller
{

    public function __construct(
        protected RepresentanteClienteService $representanteClienteService,
        protected CompanyContextService $contextService
    ) {}
  public function index(Request $request)
    {
        $size = $request->input('size', 15);

        $hasFilters = collect($request->only([
            'fechaIni', 'fechaFin', 'supervisores', 'rfv', 'zonas', 'rutas',
        ]))->filter()->isNotEmpty();

        if ($hasFilters) {
            $query = $this->applyFilters($request);

            // Paginación de filas
            $estadisticas = $query
                ->latest('cob.fechaRegistro')
                ->paginate($size)
                ->withQueryString();
            $totales = $this->applyFilters($request)
                ->select(DB::raw('
                    COALESCE(SUM(cob.productoEsperado),  0) as total_producto_esperado,
                    COALESCE(SUM(cob.monto_esperado),    0) as total_monto_esperado,
                    COALESCE(SUM(cob.productoFacturado), 0) as total_producto_facturado,
                    COALESCE(SUM(cob.monto_facturado),   0) as total_monto_facturado
                '))
                ->first();
        } else {
            $estadisticas = new LengthAwarePaginator([], 0, $size);
            $totales = (object) [
                'total_producto_esperado'  => 0,
                'total_monto_esperado'     => 0,
                'total_producto_facturado' => 0,
                'total_monto_facturado'    => 0,
            ];
        }

        return Inertia::render('ConsultaGerencial', [
            'estadisticas'    => $estadisticas,
            'totales'         => $totales,
            'filtrosAplicados' => $request->all(),
        ]);
    }

    // =====================================================================
    // applyFilters — query base reutilizable
    // =====================================================================
    private function applyFilters(Request $request)
    {
        $idFabricante = $this->contextService->getActiveId();
        $idOperador   = $this->contextService->getActiveOperador();

        $query = TCoberturaRv::withoutGlobalScopes()
            ->from('t_cobertura_rfv as cob')
            ->leftJoin('t_personas as rfv',   'cob.idRFV',        '=', 'rfv.idPersona')
            ->leftJoin('t_personas as sup',   'cob.idsupervisor',  '=', 'sup.idPersona')
            ->leftJoin('t_zonas as zon',      'cob.idzona',        '=', 'zon.idzona')
            ->leftJoin('t_brick_rutas as rut', 'cob.idruta',        '=', 'rut.idbrick')
            ->select([
                'cob.cod_cobertura_rfv',
                'cob.MesRegistro',
                'cob.porce_cobertura',
                'cob.productoEsperado',
                'cob.monto_esperado',
                'cob.productoFacturado',
                'cob.monto_facturado',
                'rfv.nombre_completo_razon_social as rfv_nombre',
                'sup.nombre_completo_razon_social as supervisor_nombre',
                'zon.descripcion_zona             as zona_nombre',
                'rut.Descripcion                  as ruta_descripcion',
            ])
            ->where('cob.idFabricante', $idFabricante)
            ->where('cob.idOperador',   $idOperador);

        // ── Filtro de fechas ─────────────────────────────────────────────
        $filtros = $request->only(['fechaIni', 'fechaFin', 'supervisores', 'rfv', 'zonas', 'rutas']);

        if (!empty($filtros['fechaIni']) && !empty($filtros['fechaFin'])) {
            $inicio = min($filtros['fechaIni'], $filtros['fechaFin']);
            $fin    = max($filtros['fechaIni'], $filtros['fechaFin']);
            $query->whereBetween('cob.fechaRegistro', [$inicio, $fin]);
        }

        // ── Filtros de combos ────────────────────────────────────────────
        $query->when($request->supervisores, fn($q, $v) => $q->whereIn('cob.idsupervisor', $v));
        $query->when($request->rfv,          fn($q, $v) => $q->whereIn('cob.idRFV',        $v));
        $query->when($request->zonas,        fn($q, $v) => $q->whereIn('cob.idzona',        $v));
        $query->when($request->rutas,        fn($q, $v) => $q->whereIn('cob.idruta',        $v));

        return $query;
    }

    // =====================================================================
    // getRepresentantesData — endpoint para el combo RFV
    // =====================================================================
    public function getRepresentantesData(Request $request): JsonResponse
    {
        try {
            $mappedRequest = $request->duplicate();
            $mappedRequest->merge([
                'search' => $request->input('term', ''),
            ]);

            $paginator = $this->representanteClienteService->getRepresentantesData($mappedRequest);
            $data = collect($paginator->items())->map(fn($r) => [
                'idPersona'                    => $r['id']     ?? $r->idPersona,
                'nombre_completo_razon_social' => $r['nombre'] ?? $r->nombre_completo_razon_social,
            ]);

            return response()->json([
                'data'          => $data,
                'current_page'  => $paginator->currentPage(),
                'per_page'      => $paginator->perPage(),
                'total'         => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo representantes en gerencial:', ['message' => $e->getMessage()]);

            return response()->json([
                'data'          => [],
                'current_page'  => 1,
                'per_page'      => 25,
                'total'         => 0,
                'next_page_url' => null,
            ]);
        }
    }

    public function exportPdf(Request $request){
    $estadisticas = $this->applyFilters($request)->latest('fechaRegistro')->get();
    
    $pdf = Pdf::loadView('pdf.gerencial', compact('estadisticas'))
                ->setPaper('a4', 'landscape');
                
    return $pdf->download('reporte-gerencial.pdf');
    }

    public function exportExcel(Request $request)
    {
        $estadisticas = $this->applyFilters($request)
    ->with(['supervisor', 'representante', 'zona', 'ruta'])
    ->latest('fechaRegistro')
    ->get();
        return Excel::download(new GerencialExport($estadisticas), 'reporte-gerencial.xlsx');
    }
}

