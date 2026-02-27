<?php

//app/Traits/FabricanteTrait.php
namespace App\Traits;

use App\Models\TPersona;
use Illuminate\Support\Facades\Log;

trait FabricanteTrait
{
    /**
     * Obtiene el ID del fabricante asociado a un ID de persona (RFV).
     */
    protected function getFabricanteId(?string $idRfv): ?string
    {
        if (is_null($idRfv)) {
            Log::warning('ID de RFV es nulo, no se puede obtener el fabricante.');
            return null;
        }

        try {
            $persona = TPersona::where('idPersona', $idRfv)->first();
            return $persona ? $persona->idFabricante : null;
        } catch (\Exception $e) {
            Log::error('Error obteniendo fabricante para RFV', [
                'idRfv' => $idRfv,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

//esta vaina funciona como una herramienta de consulta técnica, mientras que el CompanyContextService se usa para la lógica de navegación del usuario y esas cosas no lo elimines