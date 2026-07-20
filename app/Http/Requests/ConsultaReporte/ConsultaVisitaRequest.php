<?php
// app/Http/Requests/ConsultaReporte/ConsultaReporteRequest.php
namespace App\Http\Requests\ConsultaReporte;

use Illuminate\Foundation\Http\FormRequest;

class ConsultaVisitaRequest extends FormRequest
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
            'fechaInicio'                    => 'required|date|before:tomorrow',
            'fechaFin'                       => 'required|date',

            'rfv'                            => 'present|nullable|array',
            'rfv.*.idPersona'                => 'string',

            'zona'                           => 'present|nullable|array',
            'zona.*.idestado'                => 'string',

            'brick'                          => 'present|nullable|array',
            'brick.*.idciudad'               => 'string',

            'especialidad'                   => 'present|nullable|array',
            'especialidad.*.id'              => 'string',

            'ranking'                        => 'present|nullable|array',
            'ranking.*.id'                   => 'string',

            'actividad'                      => 'present|nullable|array',
            'actividad.*.value'              => 'string',

            'incidente'                      => 'present|nullable|array',
            'incidente.*.idtipo_incidentes'  => 'string',
        ];
    }
}