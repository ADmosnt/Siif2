<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idAgenda' => $this->idAgenda,
            'idCliente' => $this->idCliente,
            'cliente' => $this->cliente?->nombre_completo_razon_social,
            'idRFV' => $this->idRFV,
            'fecha_agenda' => $this->fecha_agenda,
            'hora' => $this->hora,
            'observacion' => $this->observacion_agenda,
            'idestatus' => $this->idestatus,
            'idreporte' => $this->idreporte,
        ];
    }
}
