<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PersonaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        //Y esta informacón se muestra en la tabla principal de cada modulo de administrar personas (Clientes, Representantes, Mayoristas, etc.)
        //tal vez se use en otro lado pero no tengo ni idea
        $ubicacionFallback = $this->ciudad?->nombreCiudad ?? 'Sin dirección';
        $telefonoFallback = $this->movil_persona ?? 'Sin teléfono';
        return [
            'id'                => $this->idPersona,
            'nombre_completo'   => $this->nombre_completo_razon_social,
            'nombre'            => $this->nombre_persona,
            'apellido'          => $this->apellido_persona,
            'email'             => $this->email ?? 'Sin email',
            'grupo'             => $this->idgrupo_persona,
            'telefono'          => $this->telefono_persona ?? $telefonoFallback,
            'direccion'         => $this->direccion_domicilio ?? $ubicacionFallback,
            'es_cliente'        => $this->esCliente(),
            'descuento_especial'=> $this->when($this->esMayorista(), $this->descuento), 
            'created_at'        => $this->fecha_nacimiento_registro,
            'documento' => $this->documento_identidad ?? 'Sin documento',
            'estatus' => $this->idestatus == 1 ? 'Activo' : 'Inactivo',
            'idFabricante' => $this->idFabricante,

            'metadata' => [
                'nombre'            => $this->nombre_persona,
                'apellido'          => $this->apellido_persona,
                'tipo_doc'   => $this->cod_tipo_persona,
                'documento' => $this->documento_identidad ?? 'Sin documento',
                'telefono'          => $this->telefono_persona ?? $telefonoFallback,
                'direccion'         => $this->direccion_domicilio ?? $ubicacionFallback,
                'genero' => $this->sexo_genero_persona,
                'especialidad'      => $this->idespecialidad,
                'clase'     => $this->idclase_persona,
                'ranking'           => $this->idranking,
                'frecuencia'        => $this->idfrecuencia,
                'ciclo'             => $this->idciclos,
                'pais'              => $this->idpais,
                'estado'            => $this->idestado,
                'ciudad'            => $this->idciudad,
                'supervisor'        => $this->idsupervisor,
                'username'          => $this->name,
                'descuento'         => $this->descuento ?? 0,
            ],
        ];
    }
}
