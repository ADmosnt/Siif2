<?php

// app/Http/Controllers/Gerencial/gerencialController.php
namespace App\Http\Controllers\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\TBrickRuta;
use App\Models\TCoberturaRv;
use App\Models\TPersona;
use App\Models\TZona;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GerencialExport;

class GerencialController extends Controller
{
        public function index(Request $request)
    {
        $supervisores = TPersona::typeSupervisor()->get(['idPersona', 'nombre_completo_razon_social']);
        $representantes = TPersona::typeRFV()->get(['idPersona', 'nombre_completo_razon_social']);
        $zonas = TZona::get(['idzona', 'descripcion_zona']);
        $bricks = TBrickRuta::get(['idbrick', 'Descripcion']);
        $size = $request->input('size', 15);

        $query = $this->applyFilters($request);
        
        $hasFilters = collect($request->only(['fechaIni', 'fechaFin', 'supervisores', 'rfv', 'zonas', 'rutas']))
                        ->filter()->isNotEmpty();

        $estadisticas = $hasFilters 
            ? $query->latest('fechaRegistro')->paginate($size)->withQueryString()
            : new LengthAwarePaginator([], 0, $size);

        return Inertia::render('ConsultaGerencial', [
            'estadisticas' => $estadisticas,
            'listasParaFiltros' => [
                'supervisores' => $supervisores,
                'representantes' => $representantes,
                'zonas' => $zonas,
                'bricks' => $bricks,
            ],
            'filtrosAplicados' => $request->all(),
        ]);
    }

    public function exportPdf(Request $request){
    $estadisticas = $this->applyFilters($request)->latest('fechaRegistro')->get();
    
    // ruta (
    $pdf = Pdf::loadView('pdf.gerencial', compact('estadisticas'))
                ->setPaper('a4', 'landscape');
                
    return $pdf->download('reporte-gerencial.pdf');
    }

    public function exportExcel(Request $request)
    {
        $estadisticas = $this->applyFilters($request)->latest('fechaRegistro')->get();
        return Excel::download(new GerencialExport($estadisticas), 'reporte-gerencial.xlsx');
    }
    /**
     * MÉTODO PRIVADO PARA FILTROS
     * Centraliza la lógica para index, PDF y Excel
     */
    private function applyFilters(Request $request)
    {
        $query = TCoberturaRv::withoutGlobalScopes();
        $query->with([
            'representante:idPersona,nombre_completo_razon_social',
            'supervisor:idPersona,nombre_completo_razon_social',
            'zona:idzona,descripcion_zona',
            'ruta:idbrick,Descripcion'
        ]);

        $filtros = $request->only(['fechaIni', 'fechaFin', 'supervisores', 'rfv', 'zonas', 'rutas']);

        // Filtro de fechas
        if (!empty($filtros['fechaIni']) && !empty($filtros['fechaFin'])) {
            $inicio = min($filtros['fechaIni'], $filtros['fechaFin']);
            $fin    = max($filtros['fechaIni'], $filtros['fechaFin']);

            $query->whereBetween('fechaRegistro', [$inicio, $fin]);
        }

        // Filtros condicionales
        $query->when($request->supervisores, fn($q, $v) => $q->whereIn('idsupervisor', $v));
        $query->when($request->rfv,          fn($q, $v) => $q->whereIn('idRFV', $v));
        $query->when($request->zonas,        fn($q, $v) => $q->whereIn('idzona', $v));
        $query->when($request->rutas,        fn($q, $v) => $q->whereIn('idruta', $v));

        return $query;
    }

}