<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActividadDetalleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idreporte' => $this->idreporte,
            'idCliente' => $this->idCliente,
            'cliente' => $this->cliente?->nombre_completo_razon_social,
            'idRFV' => $this->idRFV,
            'representante' => $this->representante?->nombre_completo_razon_social,
            'tipo_actividad' => $this->tipoActividad?->descripcion_tipo_actividades,
            'idtipo_actividades' => $this->idtipo_actividades,
            'incidente' => $this->incidente?->descripcion_tipo_incidentes,
            'idtipo_incidentes' => $this->idtipo_incidentes,
            'fecha_actividad' => $this->fecha_actividad,
            'observaciones' => $this->observaciones_cliente,
            'coordenadas_l' => $this->coordenadas_l,
            'coordenadas_a' => $this->coordenadas_a,
            'firma' => $this->Firma_cliente ? true : false,
            'idestatus' => $this->idestatus,
            'muestras' => $this->Muestras->map(fn ($m) => [
                'idproducto' => $m->idproducto,
                'nombre' => $m->nombre_producto,
                'cantidad' => $m->pivot->cantidad,
            ]),
        ];
    }
}
