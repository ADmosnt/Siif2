// resources/js/services/agendaService.ts
import { router } from '@inertiajs/vue3'

export interface FiltrosAgenda {
  search?: string
  size?: string
  page?: number
  idRfv?: string
  idCliente?: string
}

export class AgendaService {
  /**
   * Actualiza los filtros y recarga la página con Inertia
   */
  static actualizarFiltros(filtros: FiltrosAgenda) {
    const params: Record<string, any> = {}
    
    if (filtros.search) params.search = filtros.search
    if (filtros.size) params.size = filtros.size
    if (filtros.page) params.page = filtros.page
    if (filtros.idRfv) params.idRfv = filtros.idRfv
    if (filtros.idCliente) params.idCliente = filtros.idCliente
    
    router.get('/agenda', params, {
      preserveState: true,
      preserveScroll: true,
      only: ['clientes', 'estadisticas', 'filtros']
    })
  }

  /**
   * Reinicia todos los filtros
   */
  static reiniciarFiltros() {
    router.get('/agenda', {}, {
      preserveState: false,
      preserveScroll: false
    })
  }

  /**
   * Exporta los datos a Excel
   * @param idRfv - ID del RFV (opcional)
   * @param idCliente - ID del Cliente (opcional)
   */
  static async exportarExcel(idRfv?: string, idCliente?: string) {
    try {
      const params = new URLSearchParams()
      
      if (idRfv) params.append('idRfv', idRfv)
      if (idCliente) params.append('idCliente', idCliente)
      
      const url = `/agenda/exportar?${params.toString()}`
      
      // Abrir en nueva ventana o descargar directamente
      window.location.href = url
    } catch (error) {
      console.error('Error al exportar Excel:', error)
      throw error
    }
  }
}