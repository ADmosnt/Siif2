<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TPersona;
use Illuminate\Support\Facades\Auth;

class RepresentanteController extends Controller
{
    /**
     * Lista todos los representantes de ventas (RFV).
     */
    public function index()
    {
        $user = Auth::user();

        $representantes = TPersona::where('idgrupo_persona', 'RFV')
            ->where('idFabricante', $user->idFabricante)
            ->where('idestatus', 1)
            ->get(['idPersona', 'nombre_completo_razon_social']);

        if ($representantes->isEmpty()) {
            return response()->json(['error' => 'No hay representantes de ventas'], 404);
        }

        return response()->json(['representantes' => $representantes]);
    }
}
