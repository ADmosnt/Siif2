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
                'after_or_equal:fechaInicio',
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

    /**
     * Mensajes de error personalizados (opcional pero útil).
     */
    public function messages(): array
    {
        return [
            'fechaInicio.required' => 'La fecha de inicio es obligatoria.',
            'fechaInicio.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD.',
            'fechaFin.required' => 'La fecha de fin es obligatoria.',
            'fechaFin.date_format' => 'La fecha de fin debe tener el formato AAAA-MM-DD.',
            'fechaFin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ];
    }
}