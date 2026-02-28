<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActividadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idreporte' => $this->idreporte,
            'idCliente' => $this->idCliente,
            'cliente' => $this->cliente?->nombre_completo_razon_social,
            'idRFV' => $this->idRFV,
            'tipo_actividad' => $this->tipoActividad?->descripcion_tipo_actividades,
            'idtipo_actividades' => $this->idtipo_actividades,
            'incidente' => $this->incidente?->descripcion_tipo_incidentes,
            'fecha_actividad' => $this->fecha_actividad,
            'observaciones' => $this->observaciones_cliente,
            'coordenadas_l' => $this->coordenadas_l,
            'coordenadas_a' => $this->coordenadas_a,
            'idestatus' => $this->idestatus,
        ];
    }
}
