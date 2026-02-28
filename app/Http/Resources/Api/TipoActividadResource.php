<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoActividadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idtipo_actividades' => $this->idtipo_actividades,
            'descripcion' => $this->descripcion_tipo_actividades,
        ];
    }
}
