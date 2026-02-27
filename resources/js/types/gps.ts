/**
 * Define la estructura de un único punto geográfico en la ruta de un representante.
 * Corresponde a cada objeto dentro del array 'ruta' de la respuesta de la API.
 */
export interface RutaPoint {
  latitud: string;
  longitud: string;
  fecha_actividad: string;
  nombre_cliente: string;
  nombre_especialidad: string;
  nombre_actividad: string;
  observaciones_cliente: string;
}

/**
 * Define la estructura de un ítem en la leyenda del mapa.
 * Corresponde a cada objeto dentro del array 'leyenda' de la respuesta de la API.
 */
export interface LeyendaItem {
  actividad: string;
  cantidad: number;
  color: string;
}