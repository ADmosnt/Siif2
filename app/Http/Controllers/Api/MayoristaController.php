<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TPersona;
use Illuminate\Support\Facades\Auth;

class MayoristaController extends Controller
{
    /**
     * Lista todos los mayoristas/distribuidores.
     */
    public function index()
    {
        $user = Auth::user();

        $mayoristas = TPersona::where('idgrupo_persona', 'MAY')
            ->where('idFabricante', $user->idFabricante)
            ->where('idestatus', 1)
            ->get(['idPersona', 'nombre_completo_razon_social', 'descuento']);

        return response()->json(['mayoristas' => $mayoristas]);
    }
}
