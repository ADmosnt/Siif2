<?php
//app/Http/Resources/PersonaResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PersonaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
        //Y esta informacón se muestra en la tabla principal de cada modulo de administrar personas (Clientes, Representantes, Mayoristas, etc.)
        //tal vez se use en otro lado pero no tengo ni idea

    public function toArray($request): array
    {
    $telefonoFallback = 'Sin teléfono';
    $ubicacionFallback = 'Dirección no especificada';
    
    // Detectamos el rol para las condicionales (funciona con Objeto o Array)
    $grupo = data_get($this, 'idgrupo_persona');
    $esMayorista = ($grupo === 'MAY');
    $esCliente = ($grupo === 'CLI');

    return [
        'id'                => data_get($this, 'idPersona') ?? data_get($this, 'id_cliente'),
        'nombre_completo'   => data_get($this, 'nombre_completo_razon_social'),
        'nombre'            => data_get($this, 'nombre_persona'),
        'apellido'          => data_get($this, 'apellido_persona'),
        'email'             => data_get($this, 'email') ?? 'Sin email',
        'grupo'             => $grupo,
        'telefono'          => data_get($this, 'telefono_persona') ?? $telefonoFallback,
        'direccion'         => data_get($this, 'direccion_domicilio') ?? $ubicacionFallback,
        'es_cliente'        => $esCliente,
        
        // El 'when' de Laravel funciona mejor así con data_get
        'descuento_especial'=> $esMayorista ? data_get($this, 'descuento') : null, 
        
        'created_at'        => data_get($this, 'fecha_nacimiento_registro'),
        'documento'         => data_get($this, 'documento_identidad') ?? 'Sin documento',
        'estatus'           => data_get($this, 'idestatus') == 1 ? 'Activo' : 'Inactivo',
        'idFabricante'      => data_get($this, 'idFabricante'),

        // Información para Modales de Vue
        'metadata' => [
            'nombre'            => data_get($this, 'nombre_persona'),
            'apellido'          => data_get($this, 'apellido_persona'),
            'tipo_doc'          => data_get($this, 'cod_tipo_persona'),
            'documento'         => data_get($this, 'documento_identidad') ?? 'Sin documento',
            'telefono'          => data_get($this, 'telefono_persona') ?? $telefonoFallback,
            'direccion'         => data_get($this, 'direccion_domicilio') ?? $ubicacionFallback,
            'genero'            => data_get($this, 'sexo_genero_persona'),
            'especialidad'      => data_get($this, 'idespecialidad'),
            'clase'             => data_get($this, 'idclase_persona'),
            'ranking'           => data_get($this, 'idranking'),
            'frecuencia'        => data_get($this, 'idfrecuencia'),
            'ciclo'             => data_get($this, 'idciclos'),
            'pais'              => data_get($this, 'idpais'),
            'estado'            => data_get($this, 'idestado'),
            'ciudad'            => data_get($this, 'idciudad'),
            'supervisor'        => data_get($this, 'idsupervisor'),
            'username'          => data_get($this, 'name'),
            'descuento'         => data_get($this, 'descuento') ?? 0,
        ],
    ];
    }
}
