<?php

namespace App\Services\TmpPlanificador;

use App\Models\TTmpPlanificadore;
use App\Models\TPlanificadore;

    // =============================================================================
    // MÉTODOS DE CONSULTAS
    // =============================================================================
class QueryService
{
    public function __construct(
        protected FilterService $filterService)
    {}

    public function obtenerVisitasTemporales($year, $month, ?string $idRfvFiltro)
    {
        $query = TTmpPlanificadore::withoutGlobalScope(\App\Models\Scopes\OperadorFabricante::class)
            ->with(['rfv', 'cliente'])
            ->whereYear('Fecha', $year)
            ->whereMonth('Fecha', $month)
            ->where('estatus_visita', TTmpPlanificadore::ESTATUS_TEMPORAL);

        $this->filterService->aplicarFiltrosSeguridad($query, $idRfvFiltro);
        return $query->get();
    }

    public function obtenerVisitasPerdidas($year, $month, ?string $idRfvFiltro)
    {
        $query = TTmpPlanificadore::with(['rfv', 'cliente'])
            ->whereYear('Fecha', $year)
            ->whereMonth('Fecha', $month)
            ->where('estatus_visita', TTmpPlanificadore::ESTATUS_PERDIDA);

        $this->filterService->aplicarFiltrosSeguridad($query, $idRfvFiltro);

        return $query->get();
    }

    public function obtenerVisitasProcesadas($year, $month, ?string $idRfvFiltro)
    {
        $query = TPlanificadore::withoutGlobalScope('idestatus')
            ->with(['cliente' => function($query) {
                $query->select('idPersona', 'nombre_completo_razon_social');
            }])
            ->whereYear('fecha_agenda', $year)
            ->whereMonth('fecha_agenda', $month);

        $this->filterService->aplicarFiltrosSeguridad($query, $idRfvFiltro);

        return $query->get();
    }
}
