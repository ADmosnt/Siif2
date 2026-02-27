<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetPedidosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estatus_id' => 'nullable|integer|exists:t_estatus_ordenes,idestatus',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}