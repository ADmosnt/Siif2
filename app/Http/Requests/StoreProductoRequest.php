<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idfabricante' => 'required|string|exists:t_personas,idPersona',
            'id' => 'required|string|unique:t_productos,idproducto',
            'idlinea_producto' => 'required|exists:t_linea_productos,id',
            'idtipo_producto' => 'required|exists:t_tipo_productos,idtipo_producto',
            'nombre_producto' => 'required|string',
            'Precio_producto' => 'required|numeric|min:0',
            'Descuento_producto' => 'required|numeric|between:0,100',
            'idcategorias' => 'required|string',
            'cantidad_producto_existente' => 'required|integer|min:0',
            'fechaExpedicion_producto' => 'nullable|date',
            'fechaVencimiento_producto' => 'nullable|date',
            'idMayorista' => 'nullable|string|exists:t_personas,idPersona',
        ];
    }
}