<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idorden' => $this->idorden,
            'idPersona' => $this->idPersona,
            'idMayorista' => $this->idMayorista,
            'mayorista' => $this->mayorista?->nombre_completo_razon_social,
            'idPersona_solicitante' => $this->idPersona_solicitante,
            'cliente' => $this->cliente?->nombre_completo_razon_social,
            'costoTotal' => $this->costoTotal,
            'impuesto' => $this->impuesto,
            'descuento' => $this->descuento,
            'fechaOrden' => $this->fechaOrden,
            'TotalUnidades' => $this->TotalUnidades,
            'estatus' => $this->estatus?->descripcion,
            'idestatus' => $this->idestatus,
            'comentario' => $this->comentario_entrega,
        ];
    }
}
