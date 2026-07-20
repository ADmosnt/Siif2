<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetListaReporteRequet extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true; // La autorización la maneja el servicio por roles
    }

    /**
     * Reglas de validación.
     */
    protected function prepareForValidation(): void
    {
        $fechaInicio = $this->input('fechaInicio');
        $fechaFin = $this->input('fechaFin');

        if ($fechaInicio && $fechaFin && strtotime($fechaInicio) > strtotime($fechaFin)) {
            $this->merge([
                'fechaInicio' => $fechaFin,
                'fechaFin' => $fechaInicio,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'fechaInicio' => [
                'required',
                'date',
                'date_format:Y-m-d',
            ],
            'fechaFin' => [
                'required',
                'date',
                'date_format:Y-m-d',
            ],
        
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100'
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1'
            ]
        ];
    }

}