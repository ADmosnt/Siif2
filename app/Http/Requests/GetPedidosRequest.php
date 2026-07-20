<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetPedidosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $fechaInicio = $this->input('fecha_inicio');
        $fechaFin = $this->input('fecha_fin');

        if ($fechaInicio && $fechaFin && strtotime($fechaInicio) > strtotime($fechaFin)) {
            $this->merge([
                'fecha_inicio' => $fechaFin,
                'fecha_fin' => $fechaInicio,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'estatus_id' => 'nullable|integer|exists:t_estatus_ordenes,idestatus',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}