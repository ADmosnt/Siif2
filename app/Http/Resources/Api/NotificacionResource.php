<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idNotificacion' => $this->idNotificacion,
            'descripcion' => $this->descripcion_notoficacion,
            'tipo' => $this->tipo?->descripcion ?? null,
            'idtipo' => $this->idtipo,
            'fecha_registro' => $this->fecha_registro,
            'idestatus' => $this->idestatus,
        ];
    }
}
