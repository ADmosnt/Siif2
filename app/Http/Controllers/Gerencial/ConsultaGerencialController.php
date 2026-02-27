<?php

namespace App\Http\Controllers\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\TBrickRuta;
use App\Models\TCoberturaRv;
use App\Models\TPersona;
use App\Models\TZona;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

class GerencialController extends Controller
{
    /**
     * Muestra la vista de consulta gerencial con datos filtrados y paginados.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $supervisores = TPersona::typeSupervisor()->get(['idPersona', 'nombre_completo_razon_social']);
        $representantes = TPersona::typeRFV()->get(['idPersona', 'nombre_completo_razon_social']);
        $zonas = TZona::get(['idzona', 'descripcion_zona']);
        $bricks = TBrickRuta::get(['idbrick', 'Descripcion']);
        $size = $request->input('size', 15);

        $query = $this->applyFilters($request);
        
        // Solo paginar si hay algún filtro activo para no saturar
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
}