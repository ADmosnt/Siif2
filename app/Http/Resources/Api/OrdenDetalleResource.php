<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenDetalleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idorden' => $this->idorden,
            'idPersona' => $this->idPersona,
            'rfv' => $this->rfv?->nombre_completo_razon_social,
            'idMayorista' => $this->idMayorista,
            'mayorista' => $this->mayorista?->nombre_completo_razon_social,
            'idPersona_solicitante' => $this->idPersona_solicitante,
            'cliente' => $this->cliente?->nombre_completo_razon_social,
            'costoTotal' => $this->costoTotal,
            'impuesto' => $this->impuesto,
            'descuento' => $this->descuento,
            'fechaOrden' => $this->fechaOrden,
            'fecha_envio_orden' => $this->fecha_envio_orden,
            'TotalUnidades' => $this->TotalUnidades,
            'estatus' => $this->estatus?->descripcion,
            'idestatus' => $this->idestatus,
            'comentario' => $this->comentario_entrega,
            'coordenadas_l' => $this->coordenadas_l,
            'coordenadas_a' => $this->coordenadas_a,
            'productos' => $this->productos->map(fn ($p) => [
                'idproducto' => $p->idproducto,
                'nombre' => $p->pivot->nombreproducto,
                'cantidad_solicitada' => $p->pivot->cantidad_solicitada,
                'precio' => $p->pivot->item_price,
                'total' => $p->pivot->item_total,
                'descuento' => $p->pivot->item_descuento,
            ]),
        ];
    }
}
