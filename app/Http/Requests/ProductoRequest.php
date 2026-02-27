<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProductoRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'idfabricante' => 'required|string|exists:t_personas,idPersona',
            'idproducto' => 'required|string|unique:t_productos,idproducto',
            'nombre_producto' => 'required|string|max:255',
            'Precio_producto' => 'required|decimal|min:0',
            'cantidad_producto_existente' => 'required|integer|min:0',
            'lote' => 'required|string|max:50',
            'fechaVencimiento_producto' => 'nullable|date',
        ];
    }
}