<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TmpCitaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->Id,
            'idCliente' => $this->idCliente,
            'cliente' => $this->cliente?->nombre_completo_razon_social,
            'idRFV' => $this->idRFV,
            'rfv' => $this->rfv?->nombre_completo_razon_social,
            'fecha' => $this->Fecha,
            'hora' => $this->Hora,
            'idstatus' => $this->idstatus,
            'idCreador' => $this->idCreador,
            'estatus_visita' => $this->estatus_visita,
        ];
    }
}
