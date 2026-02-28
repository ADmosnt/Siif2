<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idproducto' => $this->idproducto,
            'nombre' => $this->nombre_producto,
            'descripcion' => $this->descripcion_producto,
            'precio' => $this->Precio_producto,
            'descuento' => $this->descuento_producto,
            'cantidad_existente' => $this->cantidad_producto_existente,
            'categoria' => $this->idcategorias,
            'linea' => $this->idlinea_producto,
            'tipo' => $this->idtipo_producto,
            'unidades' => $this->idunidades,
        ];
    }
}
