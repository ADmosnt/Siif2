<?php
// app/Http/Requests/ConsultaReporte/ConsultaOrdenRequest.php
namespace App\Http\Requests\ConsultaReporte;

use Illuminate\Foundation\Http\FormRequest;

class ConsultaOrdenRequest extends FormRequest
{
    public function authorize(): bool { return true; }

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
            'fechaInicio'              => 'required|date|before:tomorrow',
            'fechaFin'                 => 'required|date',

            'rfv'                      => 'nullable|array',
            'rfv.*.idPersona'          => 'string',

            'norden'                   => 'nullable|string',

            'estatus'                  => 'nullable|array',
            'estatus.*.idestatus'      => 'string',

            'mayorista'                => 'nullable|array',
            'mayorista.*.idPersona'    => 'string',
        ];
    }
}
