<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\TActividadesRepresentante;
use Illuminate\Pagination\LengthAwarePaginator;

class ListaReporteService
{

    public function __construct(
        protected CompanyContextService $contextService)
    {}

    /**
     * Obtiene las actividades filtradas por contexto y rango de fechas.
     */
    public function getActivitiesByDateRange(
        string $startDate, 
        string $endDate, 
        int $perpage = 15, 
        int $page = 1, 
        ?string $idRfv = null
    ): LengthAwarePaginator {
        
        $user = Auth::user();
        $activeFabricante = $this->contextService->getActiveId();

        $query = TActividadesRepresentante::query();

        // 1. Aplicar Seguridad y Filtros de Contexto
        if ($user->idgrupo_persona === 'RFV') {
            // Si es RFV, ignoramos cualquier intento de filtrar otro ID
            $query->where('idPersona', $user->idPersona);
        } else {
            // Para SIIF, GRT o SUP: Filtramos por la empresa activa en el contexto
            // Usamos whereHas para asegurar que el representante pertenece al fabricante seleccionado
            $query->whereHas('representante', function($q) use ($activeFabricante, $idRfv) {
                $q->withoutGlobalScopes()
                  ->where('idFabricante', $activeFabricante);
                
                // Si el usuario seleccionó un RFV específico en el combo
                if ($idRfv) {
                    $q->where('idPersona', $idRfv);
                }
            });
        }
        
        // 2. Filtro de fechas y carga de relaciones necesarias
        $inicio = min($startDate, $endDate);
        $fin    = max($startDate, $endDate);

        return $query->with(['representante:idPersona,nombre_completo_razon_social'])
            ->whereBetween('fecha_actividad', [$inicio, $fin])
            ->orderBy('fecha_actividad', 'desc')
            ->paginate($perpage, ['*'], 'page', $page)
            ->withQueryString();
    }
}