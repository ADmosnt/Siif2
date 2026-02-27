<?php

namespace App\Services\TmpPlanificador;

use Illuminate\Validation\ValidationException;
use App\Models\TTmpPlanificadore;

class PreparationService
{
    public function prepararDatosCreacion(array $data, $user): array
    {
        $datosParaCrear = [
            'idOperador' => $user->idOperador,
            'idFabricante' => $user->idFabricante,
            'idCliente' => $data['idCliente'],
            'Fecha' => $data['Fecha'],
            'Hora' => $data['Hora'],
            'idstatus' => 1,
            'estatus_visita' => TTmpPlanificadore::ESTATUS_TEMPORAL,
            'idCreador' => $user->idPersona,
        ];

        if ($user->idgrupo_persona === 'RFV') {
            $datosParaCrear['idRFV'] = $user->idPersona;
            $datosParaCrear['idSupervisor'] = $user->idsupervisor;
        } elseif (in_array($user->idgrupo_persona, ['GRT', 'SUP'])) {
            $idRfvSeleccionado = $data['idRFV'] ?? null;

            if (!$idRfvSeleccionado) {
                throw ValidationException::withMessages([
                    'idRFV' => ['El vendedor es obligatorio.'],
                ]);
            }

            $datosParaCrear['idRFV'] = $idRfvSeleccionado;
            $datosParaCrear['idSupervisor'] = $user->idPersona;
        } else {
            throw ValidationException::withMessages([
                'rol' => ['Rol no autorizado para crear visitas'],
            ]);
        }

        return $datosParaCrear;
    }

    public function extraerCamposActualizables(array $data): array
    {
        $camposPermitidos = ['idCliente', 'Fecha', 'Hora'];
        $datosParaActualizar = [];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $datosParaActualizar[$campo] = $data[$campo];
            }
        }

        return $datosParaActualizar;
    }
}
