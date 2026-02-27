<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\TPersona;
use Illuminate\Support\Facades\Auth;
use App\Services\CompanyContextService;


class DashboardController extends Controller{

    public function __construct( 
        protected CompanyContextService $contextService
    ) {}

    public function index()
    {
        $usuario = Auth::user(); 

// Obtenemos las empresas fabricantes
        $empresasQuery = TPersona::withoutGlobalScopes()
            ->where('idgrupo_persona', 'FABR');

        // Filtrar según el rol
        if (in_array($usuario->idgrupo_persona, ['GRT', 'RFV', 'SUP'])) {
            $empresasQuery->where('idFabricante', $usuario->idFabricante);
        }

        $empresas = $empresasQuery->get(['idPersona', 'nombre_completo_razon_social', 'idFabricante']);

        return Inertia::render('Dashboard', [
            'empresas' => $empresas,
            'activeCompanyId' => $this->contextService->getActiveId(), // Puede ser null
            'usuario' => [
                'idgrupo_persona' => $usuario->idgrupo_persona,
                'nombre' => $usuario->nombre_completo_razon_social,
            ]
        ]);
    }
}
