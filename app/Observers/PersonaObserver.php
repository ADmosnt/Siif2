<?php

namespace App\Observers;

use App\Models\TPersona;
use App\Models\TCategoria;
use App\Models\TEstatusOrdene;
use App\Models\TTipoActividade;
use App\Models\TTipoIncidente;
use App\Models\TFrecuenciaVisita;
use App\Models\TRankingCliente;
use App\Models\TCiclo;

class PersonaObserver
{
    /**
     * Se ejecuta DESPUÉS de que la persona se guardó en la DB.
     */
    public function created(TPersona $persona)
    {
        // Solo actuamos si el que se acaba de crear es un FABRICANTE (una empresa nueva)
        if ($persona->idgrupo_persona === 'FABR') {
            $this->inicializarNuevaEmpresa($persona);
        }
    }

    private function inicializarNuevaEmpresa(TPersona $fabricante)
    {
        $idOp = $fabricante->idOperador;
        $idFab = $fabricante->idPersona; // Para el fabricante, su ID de persona es su ID de fabricante

        // Inserts requeridos cuando el grupo es FABR
        TTipoActividade::insert([
            ['idOperador' => $idOp, 'idFabricante' => $idFab, 'descripcion_tipo_actividades' => 'VISITA'],
            ['idOperador' => $idOp, 'idFabricante' => $idFab, 'descripcion_tipo_actividades' => 'ENTREGA MATERIALES'],
        ]);

        TTipoIncidente::create([
            'idOperador' => $idOp, 'idFabricante' => $idFab, 'descripcion_tipo_incidentes' =>'SIN INCIDENTES'
        ]);

        TCategoria::insert([
            ['idOperador' => $idOp, 'idFabricante' => $idFab, 'idcategorias' =>'MUES', 'NombreCategorias' => 'Muestras'],
            ['idOperador' => $idOp, 'idFabricante' => $idFab, 'idcategorias' =>'PROD', 'NombreCategorias' => 'Productos'],
        ]);

        // Obtener el próximo idestatus disponible para este idOperador
        $maxIdEstatus = \App\Models\TEstatusOrdene::where('idOperador', $idOp)->max('idestatus') ?? 0;
        $nextIdEstatus = $maxIdEstatus + 1;

        TEstatusOrdene::insert([
            ['idOperador' => $idOp, 'idFabricante' => $idFab,'descripcion' => 'Registrada'],
            ['idOperador' => $idOp, 'idFabricante' => $idFab,'descripcion' => 'Generada'],
            ['idOperador' => $idOp, 'idFabricante' => $idFab,'descripcion' => 'Facturada'],
            ['idOperador' => $idOp, 'idFabricante' => $idFab,'descripcion' => 'Anulada'],
            ['idOperador' => $idOp, 'idFabricante' => $idFab,'descripcion' => 'Enviada'],
            ['idOperador' => $idOp, 'idFabricante' => $idFab,'descripcion' => 'Confirmada'],
        ]);

        TFrecuenciaVisita::create([
            'idOperador' => $idOp, 'idFabricante' => $idFab, 'descripcion_frecuencia_visitas' => '2 veces al mes'
        ]);

        TRankingCliente::create([ 
            'idOperador' => $idOp, 'idFabricante' => $idFab, 'descripcion_ranking_cliente' => 'Alto'
        ]);

        TCiclo::create([
            'idOperador' => $idOp, 'idFabricante' => $idFab, 'descripcion_ciclos' => 'mensual'
        ]);

    }
}