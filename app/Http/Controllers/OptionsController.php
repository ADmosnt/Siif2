<?php
// app/Http/Controllers/OptionsController.php

namespace App\Http\Controllers;

use App\Models\TPaise;
use App\Models\TEstado;
use App\Models\TCiudade;
use App\Models\TPersona;
use App\Models\TLineaProducto;
use App\Models\TTipoProducto;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;

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

            if ($estadoId) {
                $query->where('idestado', $estadoId);
            }
        } else {
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
}