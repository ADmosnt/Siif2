<?php
// app/Http/Controllers/OptionsController.php

//una aclaratoria, el filtro pais es así, pais->estado->ciudad(que sería la capital del estado y ya)
//se hizo así porque bueno las rutas y bricks eran horribles y como no quería tocar mucho la base de datos se hizo un storeprocedure o como se escriba
//(es un simplemnte un scrip) que llena la tabla de ciudades con las capitales de cada estado

namespace App\Http\Controllers;

use App\Models\TPaise;
use App\Models\TEstado;
use App\Models\TCiudade;
use App\Models\TPersona;
use App\Models\TLineaProducto;
use App\Models\TTipoProducto;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OptionsController extends Controller
{
    public function __construct(
        protected CompanyContextService $contextService
    ) {}

    public function search(Request $request)
    {
        $field = $request->input('field');
        $term = $request->input('term', '');

        $filtersJson = $request->input('filters', '{}');
        $filters = json_decode($filtersJson, true) ?: [];
        
        $page = $request->input('page', 1);
        $perPage = 25;

        $results = match($field) {
            'pais' => $this->searchPaises($term, $page, $perPage),
            'estado' => $this->searchEstados($term, $filters, $page, $perPage),
            'ciudad' => $this->searchCiudades($term, $filters, $page, $perPage),
            'supervisor' => $this->searchSupervisores($term, $page, $perPage),
            'linea' => $this->searchLineas($term, $page, $perPage),
            'tipo_producto' => $this->searchTiposProducto($term, $page, $perPage),
            'mayorista' => $this->searchMayoristas($term, $page, $perPage),
            'estado_visita' => $this->searchEstadosVisita($term, $page, $perPage),
            'ciudad_visita' => $this->searchCiudadesVisita($term, $filters, $page, $perPage),
            'vendedor' => $this->searchVendedores($term, $page, $perPage),
            default => ['data' => [], 'has_more' => false]
        };

        return response()->json($results);
    }

    // Métodos de búsqueda para cada tipo de opción
    // Cada método recibe el término de búsqueda, los filtros aplicados, la página actual y el número de resultados por página
    private function searchPaises($term, $page, $perPage)
    {
        $query = TPaise::where('idestatus', 1)
            ->orderBy('nombrePais');

        if ($term) {
            $query->where('nombrePais', 'like', "%{$term}%");
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginated->items())->map(fn($p) => [
                'label' => $p->nombrePais,
                'value' => $p->idpais
            ]),
            'has_more' => $paginated->hasMorePages()
        ];
    }

    private function searchEstados($term, $filters, $page, $perPage)
    {
        $query = TEstado::orderBy('nombreCiudad');

        // Filtrar por país si está seleccionado
        if (!empty($filters['pais'])) {
            $paisId = is_array($filters['pais']) ? ($filters['pais']['value'] ?? null) : $filters['pais'];
        

            if ($paisId) {
            $query->where('idpais', $paisId);
            }
         }else {
            return ['data' => [], 'has_more' => false];
        }

        if ($term) {
            $query->where('nombreCiudad', 'like', "%{$term}%");
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginated->items())->map(fn($e) => [
                'label' => $e->nombreCiudad,
                'value' => $e->idestado
            ]),
            'has_more' => $paginated->hasMorePages()
        ];
    }

    private function searchCiudades($term, $filters, $page, $perPage)
    {
        $query = TCiudade::orderBy('nombreCiudad');  

        // Filtrar por estado si está seleccionado
        if (!empty($filters['estado'])) {
            $estadoId = is_array($filters['estado']) ? ($filters['estado']['value'] ?? null) : $filters['estado'];
            $query->where('idestado', $estadoId);
        }

            if ($estadoId) {
            $query->where('idestado', $estadoId);
            }
        else {
            return ['data' => [], 'has_more' => false];
        }
        if ($term) {
            $query->where('nombreCiudad', 'like', "%{$term}%");
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginated->items())->map(fn($c) => [
                'label' => $c->nombreCiudad,
                'value' => $c->idCiudad
            ]),
            'has_more' => $paginated->hasMorePages()
        ];
    }

    private function searchVendedores($term, $page, $perPage)
    {
        $idFabricante = $this->contextService->getActiveId();
        $user = auth()->user();

        $query = TPersona::where('idgrupo_persona', 'RFV')
            ->where('idFabricante', $idFabricante)
            ->where('idestatus', 1)
            ->orderBy('nombre_completo_razon_social');

        // FILTRO DE SEGURIDAD:
        // Si el usuario que está navegando es un RFV, solo puede verse a sí mismo
        if ($user->idgrupo_persona === 'RFV') {
            $query->where('idPersona', $user->idPersona);
        }

        // Búsqueda por término (nombre)
        if ($term) {
            $query->where('nombre_completo_razon_social', 'like', "%{$term}%");
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginated->items())->map(fn($v) => [
                'label' => trim($v->nombre_completo_razon_social) ?: 'Vendedor sin nombre',
                'value' => $v->idPersona
            ]),
            'has_more' => $paginated->hasMorePages()
        ];
    }
    
    private function searchSupervisores($term, $page, $perPage)
    {
        $idFabricante = $this->contextService->getActiveId();
        $query = TPersona::where('idgrupo_persona', 'SUP')
            ->where('idFabricante', $idFabricante)
            ->where('idestatus', 1)
            ->orderBy('nombre_completo_razon_social');

        if ($term) {
            $query->where(function($q) use ($term) {
                $q->where('nombre_completo_razon_social', 'like', "%{$term}%");
            });
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginated->items())->map(fn($s) => [
                'label' => trim($s->nombre_completo_razon_social) ?: 'Sin Nombre',
                'value' => $s->idPersona
            ]),
            'has_more' => $paginated->hasMorePages()
        ];
    }
    private function searchLineas($term, $page, $perPage)
    {
        $idFabricante = $this->contextService->getActiveId();
        
        $query = TLineaProducto::orderBy('descripcion_linea_producto')
            ->where('idfabricante', $idFabricante);

        if ($term) {
            $query->where('descripcion_linea_producto', 'like', "%{$term}%");
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginated->items())->map(fn($l) => [
                'label' => $l->descripcion_linea_producto,
                'value' => $l->id
            ]),
            'has_more' => $paginated->hasMorePages()
        ];
    }

    private function searchTiposProducto($term, $page, $perPage)
    {
        $idFabricante = $this->contextService->getActiveId();

        $query = TTipoProducto::withoutGlobalScopes()
            ->where('idFabricante', $idFabricante)
            ->orderBy('descripcion_tipo_producto');


        if ($term) {
            $query->where('descripcion_tipo_producto', 'like', "%{$term}%");
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginated->items())->map(fn($t) => [
                'label' => $t->descripcion_tipo_producto,
                'value' => $t->idtipo_producto
            ]),
            'has_more' => $paginated->hasMorePages()
        ];
    }

    private function searchMayoristas($term, $page, $perPage)
    {
        $idFabricante = $this->contextService->getActiveId();
        
        $query = TPersona::where('idgrupo_persona', 'MAY')
            ->where('idFabricante', $idFabricante)
            ->where('idestatus', 1)
            ->orderBy('nombre_completo_razon_social');

        if ($term) {
            $query->where('nombre_completo_razon_social', 'like', "%{$term}%");
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginated->items())->map(fn($m) => [
                'label' => $m->nombre_completo_razon_social,
                'value' => $m->idPersona
            ]),
            'has_more' => $paginated->hasMorePages()
        ];
    }

    //este query no es un error o redundacia, sino que este está pensando para buscar dinamicamente las rutas y zonas 
// que si tienen visitas asociadas, para no mostrar opciones que al final no arrojen resultados, 
// por eso se hace un join con actividades representante y personas
    private function searchEstadosVisita($term, $page, $perPage)
{
    $fabricante = $this->contextService->getActiveId();
    $operador   = $this->contextService->getActiveOperador();

    $query = DB::table('t_actividades_representante as act')
        ->join('t_personas as cli', 'act.idCliente', '=', 'cli.idPersona')
        ->join('t_estados as est', 'cli.idestado', '=', 'est.idestado')
        ->where('cli.idOperador', $operador)
        ->where('cli.idFabricante', $fabricante)
        ->select('est.idestado', 'est.nombreCiudad')
        ->distinct()
        ->orderBy('est.nombreCiudad');

    if ($term) {
        $query->where('est.nombreCiudad', 'like', "%{$term}%");
    }

    $paginated = $query->paginate($perPage, ['*'], 'page', $page);

    return [
        'data'     => collect($paginated->items())->map(fn($e) => [
            'label' => $e->nombreCiudad,
            'value' => $e->idestado,
        ]),
        'has_more' => $paginated->hasMorePages(),
    ];
}

//este metodo por ahora parece redudante o inutil ya que directamente no hay rutas como tal, o sea están pero vueltas un asco, 
// pero pueden dejar este metodo para cuando se corrija eso y lo puedan usar corretamente
private function searchCiudadesVisita($term, $filters, $page, $perPage)
{
    $fabricante = $this->contextService->getActiveId();
    $operador   = $this->contextService->getActiveOperador();

    $query = DB::table('t_actividades_representante as act')
        ->join('t_personas as cli', 'act.idCliente', '=', 'cli.idPersona')
        ->join('t_ciudades as ciu', 'cli.idciudad', '=', 'ciu.idCiudad')
        ->where('cli.idOperador', $operador)
        ->where('cli.idFabricante', $fabricante)
        ->select('ciu.idCiudad', 'ciu.nombreCiudad', 'ciu.idestado')
        ->distinct()
        ->orderBy('ciu.nombreCiudad');

    // Filtrar por estado si ya seleccionó uno
    if (!empty($filters['estado'])) {
        $estadoId = is_array($filters['estado'])
            ? ($filters['estado']['value'] ?? null)
            : $filters['estado'];

        if ($estadoId) {
            $query->where('ciu.idestado', $estadoId);
        }
    } else {
        return ['data' => [], 'has_more' => false];
    }

    if ($term) {
        $query->where('ciu.nombreCiudad', 'like', "%{$term}%");
    }

    $paginated = $query->paginate($perPage, ['*'], 'page', $page);

    return [
        'data'     => collect($paginated->items())->map(fn($c) => [
            'label' => $c->nombreCiudad,
            'value' => $c->idCiudad,
        ]),
        'has_more' => $paginated->hasMorePages(),
    ];
}
}