<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TipoActividadResource;
use App\Models\TTipoActividade;
use Illuminate\Support\Facades\Auth;

class TipoActividadController extends Controller
{
    /**
     * Lista todos los tipos de actividades disponibles.
     */
    public function index()
    {
        $user = Auth::user();

        $actividades = TTipoActividade::where('idestatus', 1)
            ->where('idFabricante', $user->idFabricante)
            ->get();

        return TipoActividadResource::collection($actividades);
    }
}
