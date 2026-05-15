<?php

namespace App\Services\TmpPlanificador;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\RClienteRfv;
use App\Models\TTmpPlanificadore;
use Carbon\Carbon;

    // =============================================================================
    // MÉTODOS DE VALIDACIONES
    // =============================================================================
class ValidationService
{
    public function validarParametrosCalendario(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|date_format:Y',
            'month' => 'required|date_format:m',
            'idRfv' => 'nullable|string|exists:t_personas,idPersona',
        ]);

        if ($validator->fails()) {
            throw new \Exception('Error de validación: ' . $validator->errors()->first(), 422);
        }

        return $validator->validated();
    }

    public function validarDatosBasicos(array $data): void
    {
        if (empty($data['idCliente']) || empty($data['Fecha']) || empty($data['Hora'])) {
            throw ValidationException::withMessages([
                'idCliente' => ['El cliente es obligatorio.'],
                'Fecha' => ['La fecha es obligatoria.'],
                'Hora' => ['La hora es obligatoria.'],
            ]);
        }
    }

    public function validarFechaNoEsPasado(string $fecha, string $hora, bool $esActualizacion = false): void
    {
        $fechaVisita = Carbon::parse($fecha);
        if ($fechaVisita->isBefore(Carbon::today())) {
            throw ValidationException::withMessages([
                'Fecha' => ['La fecha no puede ser en el pasado.'],
            ]);
        }
    }

    public function validarAsociacionClienteRfv(string $idRfv, string $idCliente): void
    {
        if (!RClienteRfv::where('id_RFV', $idRfv)->where('id_cliente', $idCliente)->exists()) {
            throw ValidationException::withMessages([
                'idCliente' => ['El cliente no está asociado con el vendedor.'],
            ]);
        }
    }

    public function verificarDuplicado(string $idCliente, string $fecha, string $hora, ?int $excluirId = null): void
    {
        $query = TTmpPlanificadore::where('idCliente', $idCliente)
            ->where('Fecha', $fecha)
            ->where('Hora', $hora);

        if ($excluirId) {
            $query->where('Id', '!=', $excluirId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'duplicado' => ['Ya existe una visita para este cliente en la misma fecha y hora.'],
            ]);
        }
    }
}
