<?php

// app/Http/Resources/ProductoResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->idproducto,
            'producto' => $this->nombre_producto,
            'precio' => $this->Precio_producto,
            'descuento' => $this->Descuento_producto,
            'mayorista' => $this->idMayorista,
            'linea' => $this->lineaProducto->descripcion_linea_producto ?? 'Sin línea',
            'existencia' => $this->cantidad_producto_existente,
            'lote' => $this->lote ?? 'Sin lote',
            // Formateamos a YYYY-MM-DD, esto se puede hacer desde el modelo, pero no lo hago ahí ya que me rompia otras cosas, entonces lo hago aquí
            'fecha_registro' => $this->fechaRegistro_producto 
                ? \Carbon\Carbon::parse($this->fechaRegistro_producto)->format('Y-m-d') 
                : null,
                
            'fecha_vencimiento' => $this->fechaVencimiento_producto 
                ? \Carbon\Carbon::parse($this->fechaVencimiento_producto)->format('Y-m-d') 
                : null,

            'metadata' => [
                'id' => $this->idproducto,
                'producto' => $this->nombre_producto,
                'existencia' => $this->cantidad_producto_existente,
                'linea' => $this->idlinea_producto,
                'tipo_producto' => $this->idtipo_producto,
                'mayorista' => $this->idMayorista,
                'fecha_registro' => $this->fechaRegistro_producto,
                'fecha_vencimiento' => $this->fechaVencimiento_producto,
                'precio' => $this->Precio_producto,
                'descuento' => $this->Descuento_producto,
                'lote' => $this->lote ?? 'Sin lote',
                'presentacion' => $this->presentacion ?? 'Sin presentación',

            ],
            'fabricante' => $this->fabricante ? [
                'id' => $this->fabricante->idPersona,
                'nombre' => $this->fabricante->nombre_completo_razon_social,
            ] : null,
            'estatus' => $this->estatus_producto,
            'descripcion' => $this->descripcion_producto,
        ];
    }
}