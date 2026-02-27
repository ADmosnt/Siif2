<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ObtenerRutaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {return true;}

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'idRFV'         => 'required|string|exists:t_personas,idPersona',
            'fechaDesde'    => 'required|date',
            'fechaHasta'    => 'required|date|after_or_equal:fechaDesde',
        ];
    }
}
