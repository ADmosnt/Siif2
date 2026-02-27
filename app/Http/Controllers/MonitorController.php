<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\TPersona;
use Illuminate\Support\Facades\Auth;


class MonitorController extends Controller{

    public function index()
    {
        $usuario = Auth::user(); 

        // Siempre obtenemos las empresas fabricantes
        $empresasFabricantes = TPersona::withoutGlobalScopes()
            ->where('idgrupo_persona', 'FABR')
            ->get(['idPersona', 'nombre_completo_razon_social', 'idFabricante']);

        // Filtrar según el rol del usuario
        if ($usuario->idgrupo_persona === 'GRT') {
            // Solo la empresa a la que pertenece (mismo idFabricante)
            $empresas = $empresasFabricantes->where('idFabricante', $usuario->idFabricante)->values();
        } else {
            // SIIF o cualquier otro rol con acceso total
            $empresas = $empresasFabricantes;
        }

        return Inertia::render('Monitor', [
            'empresas' => $empresas,
            'usuario' => [
                'idgrupo_persona' => $usuario->idgrupo_persona,
                'idFabricante' => $usuario->idFabricante,
            ]
        ]);
    }
}
