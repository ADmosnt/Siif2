<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyContextService
{
    /**
     * Establece la empresa que el administrador está monitoreando.
     */
    public function setContext(?string $idFabricante)
    {
        $user = Auth::user();
   // Solo el SIIF puede poner el contexto en NULL (Modo Global)
    if (is_null($idFabricante) && $user->idgrupo_persona !== 'SIIF') {
        // Si un usuario normal intenta poner null, se fuerza a seleccionar su propia empresa
        session()->put('active_fabricante_id', $user->idFabricante);
        return;
    }
    
        session()->put('active_fabricante_id', $idFabricante); 
    }

    /**
     * Obtiene el ID del fabricante actual según el contexto.
     */
    public function getActiveId(): ?string
    {
        $user = Auth::user();

        // Si es SIIF, se prioriza lo que seleccionó en el combo (Sesión)
        if ($user->idgrupo_persona === 'SIIF') {
            return Session::get('active_fabricante_id', $user->idFabricante);
        }

        // Para gerentes o RFVs, devuelve su fabricante por defecto
        return $user->idFabricante;
    }
    
    public function getActiveOperador(): ?string
    {
        $user = Auth::user();
        $activeFabricante = $this->getActiveId();

        // Si el fabricante activo es el mismo del usuario, se devuelve3 su operador
        if ($activeFabricante === $user->idFabricante) {
            return $user->idOperador;
        }

        // Si el SIIF cambió de empresa, buscamos el operador de esa empresa específica
        return DB::table('t_personas')
            ->where('idFabricante', $activeFabricante)
            ->value('idOperador') ?? '01'; 
    }
}