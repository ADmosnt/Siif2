// resources/js/services/actividadService.ts

export interface Actividad {
  id: number
  idreporte: number
  idCliente: string
  fecha_actividad: string
  observaciones_cliente: string
  Firma_cliente: string | null
  rfv?: string
}

export interface ClienteConActividades {
  id: string
  nombre: string
  cantidad: number
  actividades: Actividad[]
  ultimaFecha?: string
  ultimoRFV?: string
}

export class ActividadService {
  /**
   * Obtiene actividades filtradas por fecha y opcionalmente por RFV
   */
  static async getActividadesPorFecha(
    fechaInicio: string, 
    fechaFin: string, 
    idRfv?: string
  ): Promise<Actividad[]> {
    try {
      // URL con parámetros correctos
      const params = new URLSearchParams({
        fechaInicio,
        fechaFin
      })

      // Solo agregar idRfv si existe y no es undefined/null/empty
      if (idRfv && idRfv.trim() !== '') {
        params.append('idRfv', idRfv)
      }

      const url = `/reportes?${params.toString()}`
      
      console.log('Fetching actividades desde:', url)

      const response = await fetch(url, {
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })

      if (!response.ok) {
        const errorText = await response.text()
        console.error('Error response:', errorText)
        throw new Error(`Error al obtener actividades: ${response.status} - ${errorText}`)
      }

      const data = await response.json()
      
      console.log('Respuesta del backend:', {
        total: data.meta?.total || 0,
        items: data.data?.length || 0
      })

      // Mapear los datos asegurando que tengan ID único
      const actividades = (data.data || []).map((actividad: any, index: number) => ({
        id: actividad.idreporte || index,
        idreporte: actividad.idreporte,
        idCliente: actividad.idCliente,
        fecha_actividad: actividad.fecha_actividad,
        observaciones_cliente: actividad.observaciones_cliente,
        Firma_cliente: actividad.Firma_cliente,
        rfv: actividad.rfv
      }))

      return actividades
    } catch (error) {
      console.error('Error en getActividadesPorFecha:', error)
      throw error
    }
  }

  /**
   * Agrupa actividades por cliente
   */
  static agruparPorCliente(actividades: Actividad[]): ClienteConActividades[] {
    const agrupadas = actividades.reduce((acc, actividad) => {
      if (!acc[actividad.idCliente]) {
        acc[actividad.idCliente] = {
          id: actividad.idCliente,
          nombre: actividad.idCliente,
          cantidad: 0,
          actividades: [],
          ultimaFecha: '',
          ultimoRFV: ''
        }
      }

      acc[actividad.idCliente].actividades.push(actividad)
      acc[actividad.idCliente].cantidad++

      // Mantener la fecha más reciente y el último RFV
      const fechaActividad = new Date(actividad.fecha_actividad)
      const fechaActual = new Date(acc[actividad.idCliente].ultimaFecha || 0)
      
      if (fechaActividad > fechaActual) {
        acc[actividad.idCliente].ultimaFecha = actividad.fecha_actividad
        acc[actividad.idCliente].ultimoRFV = actividad.rfv || ''
      }

      return acc
    }, {} as Record<string, ClienteConActividades>)

    return Object.values(agrupadas).sort((a, b) => 
      a.nombre.localeCompare(b.nombre)
    )
  }
}