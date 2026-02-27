// resources/js/services/pedidoService.ts

export interface Orden {
  nOrden: number;
  estatus: string;
  cliente: string;
  persona: string;
  mayoristas?: Array<{
    id: number;
    nombre: string;
    descuento: number;
  }>;
  productos?: Array<{
    id: number;
    nombre: string;
    cantidad: number;
    precio: number;
    unidades: string;
    descuento: number;
    conciliada: number;
    cantidad_mostrar: number;
  }>;
  registrado_por?: string;
  fecha: string;
  total: number;
  impuesto?: number;
  cantidad_unidades: number;
  comentario?: string;
  factura?: {
    idfactura: string;
    fechaFactura: string;
  } | null;
  estaFacturada?: boolean;
  coordenadas?: {
    latitud: string;
    longitud: string;
  };
  ubicacion_cliente?: {
    estado: string;
    ciudad: string;
  };
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
}

export class PedidoService {
  /**
   * Obtiene órdenes filtradas por estatus, RFV y fechas (para el listado)
   */
  static async getOrdenesFiltradas(params: {
    estatus_id?: number;
    rfv_id?: number;
    fecha_inicio?: string;
    fecha_fin?: string;
    page?: number;
    per_page?: number;
  }): Promise<PaginatedResponse<Orden>> {
    try {
      const queryParams = new URLSearchParams();
      
      if (params.estatus_id) {
        queryParams.append('estatus_id', params.estatus_id.toString());
      }
      
      // Agregar filtro por RFV si existe
      if (params.rfv_id) {
        queryParams.append('rfv_id', params.rfv_id.toString());
      }
      
      if (params.fecha_inicio) {
        queryParams.append('fecha_inicio', params.fecha_inicio);
      }
      if (params.fecha_fin) {
        queryParams.append('fecha_fin', params.fecha_fin);
      }
      if (params.page) {
        queryParams.append('page', params.page.toString());
      }
      if (params.per_page) {
        queryParams.append('per_page', params.per_page.toString());
      }

      const url = `/pedidos?${queryParams.toString()}`;
      
      console.log('📞 Fetching órdenes desde:', url);

      const response = await fetch(url, {
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`Error al obtener órdenes: ${response.status} - ${errorText}`);
      }

      const data = await response.json();
      

      return data;
    } catch (error) {
      console.error('Error en getOrdenesFiltradas:', error);
      throw error;
    }
  }

  /**
   * Obtiene el detalle COMPLETO de una orden (con mayoristas, productos, coordenadas, etc.)
   */
  static async getOrdenDetalle(id: number): Promise<Orden> {
    try {

      const response = await fetch(`/pedidos/${id}`, {
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Error response:', errorText);
        throw new Error(`Error al obtener detalle de orden: ${response.status} - ${errorText}`);
      }

      const responseData = await response.json();
      
      
      // El backend devuelve { data: {...} }
      const data = responseData.data || responseData;

      return data;
    } catch (error) {
      console.error('Error en getOrdenDetalle:', error);
      throw error;
    }
  }

  /**
   * Obtiene los estatus disponibles
   */
  static async getEstatusDisponibles(): Promise<Array<{idestatus: number, descripcion: string}>> {
    try {
      const response = await fetch('/estatus-ordenes', {
        credentials: 'include',
        headers: {
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`Error al obtener estatus: ${response.status} - ${errorText}`);
      }

      return await response.json();
    } catch (error) {
      console.error('Error en getEstatusDisponibles:', error);
      throw error;
    }
  }

  /**
   * OPCIONAL: agregar un método para obtener RFVs específicos para pedidos
   * aunque estamos usando el mismo endpoint que ReporteAgenda
   */
  static async getRfvsDisponibles(): Promise<Array<{id: number, nombre: string}>> {
    try {
      const response = await fetch('/representantes-data-pedidos', {
        credentials: 'include',
        headers: {
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`Error al obtener RFVs: ${response.status} - ${errorText}`);
      }

      const data = await response.json();
      return data.data || [];
    } catch (error) {
      console.error('Error en getRfvsDisponibles:', error);
      throw error;
    }
  }
}