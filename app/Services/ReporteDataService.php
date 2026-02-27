<?php

namespace App\Services;

use App\Models\TTipoActividade;
use App\Models\TTipoIncidente;
use App\Models\TProducto;
use App\Models\TEstatusOrdene;
use App\Models\TPersona;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Traits\FabricanteTrait;

class ReporteDataService
{
    use FabricanteTrait;
    /**
     * Obtiene todos los datos iniciales para el reporte.
     * Esta es la función principal que el controlador llamará.
     */

    public function __construct(
        protected RepresentanteClienteService $representanteClienteService, 
        protected CompanyContextService $contextService)
    {}

    public function obtenerEstadoPorFabricante(string $descripcion, string $idFabricante): ?TEstatusOrdene
    {
        try {
            return TEstatusOrdene::where('descripcion', $descripcion)
                                ->where('idFabricante', $idFabricante)
                                ->first();
        } catch (\Exception $e) {
            Log::error('Error obteniendo estado por fabricante:', ['message' => $e->getMessage(), 'descripcion' => $descripcion, 'idFabricante' => $idFabricante]);
            return null;
        }
    }

    public function obtenerIdFabricante(?string $idRfv): ?string
    {
        try {
            return $this->representanteClienteService->obtenerIdFabricante($idRfv);
        } catch (\Exception $e) {
            Log::error('Error obteniendo idFabricante desde representanteClienteService:', ['message' => $e->getMessage(), 'idRfv' => $idRfv]);
            return null;
        }
    }
    /**
     * Obtiene los representantes (RFV) basados en el rol del usuario.
     */
    public function getRepresentantesData(Request $request): LengthAwarePaginator
    {
        try {
            return $this->representanteClienteService->getRepresentantesData($request);
        } catch (\Exception $e) {
            Log::error('Error obteniendo representantes:', ['message' => $e->getMessage()]);
            $size = $request->input('size', 25);
            $page = $request->input('page', 1);
            return new LengthAwarePaginator([], 0, $size, $page);
        }
    }
    
    /**
     * Obtiene las actividades.
     */
    public function getActividadesData(Request $request, ?string $idRfv = null): array
    {
        try {
            $idRfv = $idRfv ?? $request->input('idRfv');
            $fabricanteId = $this->getEffectiveFabricanteId($idRfv);

            $query = TTipoActividade::where('idestatus', 1);
            
            if ($fabricanteId) {
                $query->where('idfabricante', $fabricanteId);
            }
            return $query->get(['idtipo_actividades', 'descripcion_tipo_actividades'])
                        ->map(fn ($item) => ['descripcionActividad' => $item->descripcion_tipo_actividades, 'idtipo_actividad' => $item->idtipo_actividades])
                        ->toArray();
        } catch (\Exception $e) {
            Log::error('Error obteniendo actividades:', ['message' => $e->getMessage()]);
            return [];
        }

    }
    
    /**
     * Obtiene los eventos para un RFV.
     */
    public function getEventosData(Request $request, ?string $idRfv = null): array
    {
        try {
            $idRfv = $idRfv ?? $request->input('idRfv');
            $fabricanteId = $this->getEffectiveFabricanteId($idRfv);

            $query = TTipoIncidente::where('idestatus', 1);

            if ($fabricanteId) {
                $query->where('idfabricante', $fabricanteId);
            }
            return $query->orderBy('descripcion_tipo_incidentes')
                        ->get(['descripcion_tipo_incidentes as descripcion', 'idtipo_incidentes'])
                        ->map(fn ($item) => ['estatus' => $item->descripcion, 'idtipo_incidentes' => $item->idtipo_incidentes, 'descripcionIncidente' => $item->descripcion])
                        ->toArray();
        } catch (\Exception $e) {
            Log::error('Error obteniendo eventos:', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtiene los productos para un RFV.
     */
    public function getProductosData(Request $request, ?string $idRfv = null): LengthAwarePaginator
    {
        try {
            // 1. Obtenemos el ID usando nuestro nuevo resolutor
            $idRfv = $idRfv ?? $request->input('idRfv');
            $fabricanteId = $this->getEffectiveFabricanteId($idRfv);

            $size = $request->input('size', 15);
            $page = $request->input('page', 1);
            $search = $request->input('search');

            // 2. Creamos la Query base
            $query = TProducto::where('estatus_producto', 1);

            // 3. Aplicamos el filtro de fabricante solo si existe
            // (Si el SIIF está en modo Global y no hay RFV, traerá todos los productos del sistema)
            if ($fabricanteId) {
                $query->where('idfabricante', $fabricanteId);
            }

            return $query->when($search, fn($q) => $q->where('nombre_producto', 'like', "%{$search}%"))
                ->orderBy('nombre_producto')
                ->paginate($size, ['*'], 'page', $page)
                ->through(fn ($p) => [
                    'codigo' => $p->idproducto,
                    'producto' => $p->nombre_producto,
                    'precio' => $p->Precio_producto
                ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo productos:', ['message' => $e->getMessage()]);
            $size = $request->input('size', 15);
            $page = $request->input('page', 1);
            return new LengthAwarePaginator([], 0, $size, $page);
        }
    }


    /**
     * Obtiene los mayoristas para un RFV.
     */
    public function getMayoristasData(Request $request, ?string $idRfv = null)
    {
        try {
            $idRfv = $idRfv ?? $request->input('idRfv');
            $fabricanteId = $this->getEffectiveFabricanteId($idRfv); // <--- Usar el nuevo resolutor

            $search = $request->input('search');
            $size = $request->input('size', 15);
            $page = $request->input('page', 1);

            $query = TPersona::where('idgrupo_persona', 'MAY');

            // Filtro flexible: si hay ID lo usa, si no (SIIF Global), trae todo.
            if ($fabricanteId) {
                $query->where('idFabricante', $fabricanteId);
            }

            return $query->when($search, function ($q, $search) {
                    return $q->where('nombre_completo_razon_social', 'like', "%{$search}%");
                })
                ->orderBy('nombre_completo_razon_social')
                ->paginate($size, ['*'], 'page', $page)
                ->withQueryString()
                ->through(fn ($may) => [
                    'codigo' => $may->idPersona, 
                    'mayorista' => $may->nombre_completo_razon_social
                ]);
                
        } catch (\Exception $e) {
            Log::error('Error obteniendo mayoristas:', ['message' => $e->getMessage()]);
            return new LengthAwarePaginator([], 0, 15, 1); // Siempre retorna un paginador para evitar errores en Vue
        }
    }

    //obtención del fabricante
    private function getEffectiveFabricanteId(?string $idRfv): ?string
    {
        try {
            // Prioridad 1: Si seleccionaron un representante (RFV), mandan sus datos
            if ($idRfv) {
                return $this->getFabricanteId($idRfv);
            }

            // Prioridad 2: Si no hay RFV, usamos el contexto global del Dashboard
            return $this->contextService->getActiveId();
        } catch (\Exception $e) {
            Log::error('Error resolviendo fabricante efectivo:', ['message' => $e->getMessage(), 'idRfv' => $idRfv]);
            return null;
        }
    }

    public function searchClientesData(Request $request): array
    {
        try {
            return $this->representanteClienteService->searchClientesData($request);
        } catch (\Exception $e) {
            Log::error('Error en searchClientesData delegando a representanteClienteService:', ['message' => $e->getMessage()]);
            return [];
        }
    }

}