<?php

namespace App\Http\Controllers;

use App\Models\TPersona;
use App\Models\TTipoNotificacion;
use App\Services\CompanyContextService;
use App\Services\RepresentanteClienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificacionWebController extends Controller
{
    public function __construct(
        protected CompanyContextService $contextService,
        protected RepresentanteClienteService $representanteService
    ) {}

    /**
     * Muestra la vista de notificaciones con los datos necesarios.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $activeFabricante = $this->contextService->getActiveId();

        // Empresas (solo SIIF puede ver todas)
        $empresasQuery = TPersona::withoutGlobalScopes()
            ->where('idgrupo_persona', 'FABR');

        if (in_array($user->idgrupo_persona, ['GRT', 'RFV', 'SUP'])) {
            $empresasQuery->where('idFabricante', $user->idFabricante);
        }

        $empresas = $empresasQuery->get(['idPersona', 'nombre_completo_razon_social', 'idFabricante']);

        // Representantes (RFVs) del contexto activo
        $representantes = $this->representanteService->getRepresentantesData($request);

        // Tipos de notificación
        $tiposQuery = TTipoNotificacion::where('idestatus', 1);
        if ($activeFabricante) {
            $tiposQuery->where('idOperador', $this->contextService->getActiveOperador());
        }
        $tipos = $tiposQuery->get(['id', 'titulo']);

        return Inertia::render('Notificacion', [
            'empresas' => $empresas,
            'representantes' => $representantes,
            'tipos' => $tipos,
            'activeCompanyId' => $activeFabricante,
            'usuario' => [
                'idgrupo_persona' => $user->idgrupo_persona,
                'nombre' => $user->nombre_completo_razon_social,
            ],
        ]);
    }
}
