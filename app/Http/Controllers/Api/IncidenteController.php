<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TTipoIncidente;
use Illuminate\Support\Facades\Auth;

class IncidenteController extends Controller
{
    /**
     * Lista todos los tipos de incidentes.
     */
    public function index()
    {
        $user = Auth::user();

        $incidentes = TTipoIncidente::where('idestatus', 1)
            ->where('idFabricante', $user->idFabricante)
            ->get(['idtipo_incidentes', 'descripcion_tipo_incidentes']);

        return response()->json(['incidentes' => $incidentes]);
    }
}
