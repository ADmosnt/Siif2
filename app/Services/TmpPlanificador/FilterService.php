<?php

namespace App\Services\TmpPlanificador;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\CompanyContextService;

    // =============================================================================
    // MÉTODOS DE FILTROS
    // =============================================================================
class FilterService
{

    public function __construct(
        protected CompanyContextService $contextService
    ){}

    public function aplicarFiltrosFecha($query, ?string $year, ?string $month): void
    {
        if (!$year || !$month) return;

        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $table = $query->getModel()->getTable();
        $dateField = $table === 't_planificadores' ? 'fecha_agenda' : 'Fecha';

        $query->whereBetween($dateField, [$startOfMonth, $endOfMonth]);
    }

    public function aplicarFiltrosSeguridad($query, ?string $idRfvFiltro = null): void
    {
        $user = Auth::user();
        $activeFabricante = $this->contextService->getActiveId();

        if ($user->idgrupo_persona === 'RFV') {
            $query->where('idRFV', $user->idPersona);
            return;
        }

        if ($activeFabricante) {
            $query->where('idFabricante', $activeFabricante);

            if ($idRfvFiltro) {
                $query->where('idRFV', $idRfvFiltro);
            }
            return;
        }

        if ($user->idgrupo_persona === 'SIIF') {
            if ($idRfvFiltro) {
                $query->where('idRFV', $idRfvFiltro);
            }
            return;
        }

        $query->whereRaw('1 = 0');
    }

    public function aplicarFiltroBusqueda($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->whereHas('cliente', function ($q2) use ($search) {
                $q2->where('nombre_completo_razon_social', 'like', "%{$search}%");
            })
            ->orWhereHas('rfv', function ($q2) use ($search) {
                $q2->where('nombre_completo_razon_social', 'like', "%{$search}%");
            })
            ->orWhere('Fecha', 'like', "%{$search}%")
            ->orWhere('Hora', 'like', "%{$search}%");
        });
    }
}
