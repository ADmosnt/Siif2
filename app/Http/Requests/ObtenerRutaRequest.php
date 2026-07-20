<?php
// app/Http/Requests/ObtenerRutaRequest.php
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
    public function prepareForValidation()
    {
        // if both dates are provided and hasta is before desde, swap them
        if ($this->filled(['fechaDesde', 'fechaHasta']) &&
            strtotime($this->fechaHasta) < strtotime($this->fechaDesde)) {
            $this->merge([
                'fechaDesde' => $this->fechaHasta,
                'fechaHasta' => $this->fechaDesde,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'idRFV'         => 'required|string|exists:t_personas,idPersona',
            'fechaDesde'    => 'required|date',
            'fechaHasta'    => 'required|date',
        ];
    }
}
