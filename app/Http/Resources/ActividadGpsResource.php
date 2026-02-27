<?php
// app/Http/Resources/ActividadGpsResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActividadGpsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'idCliente'             => $this->idCliente,
            'fecha_actividad'       => $this->fecha_actividad->toDateTimeString(), // Formato estándar
            'observaciones_cliente' => $this->observaciones_cliente,
            
            // Relaciones cargadas de forma segura
            'nombre_cliente'        => $this->whenLoaded('cliente', $this->cliente->nombre_completo_razon_social),
            
            // =================================================================
            // CORRECCIÓN CLAVE: Lógica explícita y robusta
            // =================================================================
            // Nos aseguramos de que la relación 'especialidad' en el 'cliente' exista
            // antes de intentar acceder a su propiedad. Si no, devolvemos null.
            'nombre_especialidad'   => $this->whenLoaded('cliente', function () {
                return $this->cliente && $this->cliente->especialidad 
                    ? $this->cliente->especialidad->descripcion_especialidad 
                    : null;
            }),

            'nombre_actividad'      => $this->whenLoaded('tipoActividad', function () {
                return $this->tipoActividad 
                    ? $this->tipoActividad->descripcion_tipo_actividades 
                    : null;
            }),

            'latitud'               => $this->coordenadas_l,
            'longitud'              => $this->coordenadas_a,
        ];
    }
}
