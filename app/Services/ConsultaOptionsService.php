<?php

namespace App\Services;

use App\Models\TEspecialidade;
use App\Models\TRankingCliente;
use App\Models\TTipoActividade;
use App\Models\TTipoIncidente;
use App\Models\TEstatusOrdene;
use App\Models\Scopes\OperadorFabricante;
use App\Services\CompanyContextService;

/**
 * Provee las opciones estáticas para los filtros del módulo ConsultaReportes.
 *
 * ──────────────────────────────────────────────────────────────
 *  VISITAS
 *  · zona / ruta   → OptionsController (lazy via /options/search)
 *  · ranking       → getOptionsVisitas()
 *  · especialidad  → getOptionsVisitas()
 *  · actividad     → getOptionsVisitas()
 *  · evento/incidente → getOptionsVisitas()
 *
 *  ORDENES
 *  · mayorista     → OptionsController (lazy via /options/search)
 *  · estatus       → getOptionsOrdenes()
 *
 *  FACTURAS
 *  · (sin selects adicionales — solo fechas + RFV global)
 * ──────────────────────────────────────────────────────────────
 */
class ConsultaOptionsService
{
    public function __construct(
        protected CompanyContextService $contextService
    ) {}
    public function getOptions(): array
    {
        return [
            'visitas' => $this->getOptionsVisitas(),
            'ordenes' => $this->getOptionsOrdenes(),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  VISITAS
    // ─────────────────────────────────────────────────────────────

    private function getOptionsVisitas(): array
    {
        $idFabricante = $this->contextService->getActiveId();

        return [
            'ranking' => TRankingCliente::withoutGlobalScopes()
                ->where('idFabricante', $idFabricante)
                ->where('estatus', 1)
                ->orderBy('descripcion_ranking_cliente')
                ->get(['id', 'descripcion_ranking_cliente'])
                ->map(fn($r) => [
                    'label' => $r->descripcion_ranking_cliente,
                    'value' => $r->id,
                ])->toArray(),

            'especialidad' => TEspecialidade::where('estatus', 1)
                ->orderBy('descripcion_especialidad')
                ->get(['id', 'descripcion_especialidad'])
                ->map(fn($e) => [
                    'label' => $e->descripcion_especialidad,
                    'value' => $e->id,
                ])->toArray(),

            'actividad' => TTipoActividade::withoutGlobalScopes()
                ->where('idFabricante', $idFabricante)
                ->orderBy('descripcion_tipo_actividades')
                ->get(['idtipo_actividades', 'descripcion_tipo_actividades'])
                ->map(fn($a) => [
                    'label' => $a->descripcion_tipo_actividades,
                    'value' => $a->idtipo_actividades,
                ])->toArray(),

            'evento' => TTipoIncidente::withoutGlobalScopes()
                ->where('idFabricante', $idFabricante)
                ->orderBy('descripcion_tipo_incidentes')
                ->get(['idtipo_incidentes', 'descripcion_tipo_incidentes'])
                ->map(fn($i) => [
                    'label' => $i->descripcion_tipo_incidentes,
                    'value' => $i->idtipo_incidentes,
                ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  ORDENES
    // ─────────────────────────────────────────────────────────────

    private function getOptionsOrdenes(): array
    {
        $idFabricante = $this->contextService->getActiveId();

        $estatusQuery = TEstatusOrdene::withoutGlobalScope(OperadorFabricante::class);

        if ($idFabricante) {
            $estatusQuery->where('idFabricante', $idFabricante);
        }

        return [
            'estatus' => $estatusQuery
                ->orderBy('descripcion')
                ->get(['idestatus', 'descripcion'])
                ->map(fn($e) => [
                    'label' => $e->descripcion,
                    'value' => $e->idestatus,
                ])->toArray(),
        ];
    }
}