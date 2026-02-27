//resources/js/stores/calendarStore.ts -->


import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export interface Visita {
  id: string
  title: string
  start: string
  end: string
  tailwindColor: 'yellow' | 'red' | 'green'
  metadata: {
    tipo: 'tmp_temporal' | 'tmp_perdida' | 'procesada'
    cliente_id: string
    rfv_id: string
    nombre_cliente: string
    nombre_rfv: string
    fecha_original: string
    hora_original: string
    puedeEditar: boolean
    puedeEliminar: boolean
    puedeProcesar: boolean
    id_original: string | number
  }
}

interface PermisosVisita {
  puedeEditar: boolean
  puedeEliminar: boolean
  puedeProcesar: boolean
}

export const useVisitasStore = defineStore('visitas', () => {
  // =============================================================================
  // ESTADO
  // =============================================================================
  
  const visitas = ref<Visita[]>([])
  const mesActual = ref(new Date())
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const rfvsDisponibles = ref<{value: string, label: string}[]>([])
  const clientesDisponibles = ref<{value: string, label: string}[]>([])
  const selectedRfvId = ref<string | null>(null)
  const userRole = ref<string | null>(null)

  // =============================================================================
  // COMPUTADAS
  // =============================================================================
  
  const visitasDelMes = computed(() => {
    const mes = mesActual.value.getMonth()
    const año = mesActual.value.getFullYear()
    
    return visitas.value.filter(visita => {
      const fechaVisita = new Date(visita.start)
      return fechaVisita.getMonth() === mes && fechaVisita.getFullYear() === año
    })
  })

  const estadisticasVisitas = computed(() => {
    const tipos = {
      total: visitas.value.length,
      temporales: visitas.value.filter(v => v.metadata.tipo === 'tmp_temporal').length,
      perdidas: visitas.value.filter(v => v.metadata.tipo === 'tmp_perdida').length,
      procesadas: visitas.value.filter(v => v.metadata.tipo === 'procesada').length
    }
    return tipos
  })

  // =============================================================================
  // UTILIDADES
  // =============================================================================
  
  function determinarPermisos(tipo: string): PermisosVisita {
    const permisos: Record<string, PermisosVisita> = {
      tmp_temporal: { puedeEditar: true, puedeEliminar: true, puedeProcesar: true },
      tmp_perdida: { puedeEditar: false, puedeEliminar: false, puedeProcesar: false },
      procesada: { puedeEditar: false, puedeEliminar: false, puedeProcesar: false }
    }
    return permisos[tipo] || permisos.tmp_temporal
  }

  function transformarEventoBackend(evento: any): Visita {
    const permisos = determinarPermisos(evento.metadata?.tipo)
    
    return {
      id: evento.id,
      title: evento.title || 'Cliente no especificado',
      start: evento.start,
      end: evento.end,
      tailwindColor: evento.tailwindColor || 'yellow',
      metadata: {
        tipo: evento.metadata?.tipo || 'tmp_temporal',
        cliente_id: evento.metadata?.cliente_id,
        rfv_id: evento.metadata?.rfv_id,
        nombre_cliente: evento.metadata?.nombre_cliente || evento.title,
        nombre_rfv: evento.metadata?.nombre_rfv || 'No encontrado',
        fecha_original: evento.metadata?.fecha_original,
        hora_original: evento.metadata?.hora_original,
        puedeEditar: permisos.puedeEditar,
        puedeEliminar: permisos.puedeEliminar,
        puedeProcesar: permisos.puedeProcesar,
        id_original: evento.metadata?.id_original || evento.id
      }
    }
  }

  // =============================================================================
  // ACCIONES PRINCIPALES
  // =============================================================================

  async function cargarVisitasDelMes(fecha?: Date) {
    isLoading.value = true
    error.value = null

    try {
      const targetDate = fecha || mesActual.value
      const año = targetDate.getFullYear()
      const mes = targetDate.getMonth() + 1

      const response = await axios.get(
        route('tmp_planificaciones.calendario.index'), 
        { 
          params: { 
            year: año, 
            month: mes,
            idRfv: selectedRfvId.value
          } 
        }
      )

      if (response.data.data?.[0]?.userRole) {
        userRole.value = response.data.data[0].userRole
      }

      visitas.value = response.data.map(transformarEventoBackend)
      mesActual.value = targetDate
      
      return visitas.value
    } catch (err) {
      error.value = 'Error al cargar las visitas del mes'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function cargarRfvsDisponibles() {
    try {
      const response = await axios.get(route('tmp_planificaciones.rfvs_disponibles'))
      rfvsDisponibles.value = response.data

      if (rfvsDisponibles.value.length === 1) {
        selectedRfvId.value = rfvsDisponibles.value[0].value
      } else {
        selectedRfvId.value = null
      }
    } catch (err) {
      rfvsDisponibles.value = []
    }
  }

  async function cargarClientesPorRfv(rfvId: string) {
    // Implementación para cuando sea necesario
  }

  async function cambiarRfvSeleccionado(nuevoRfvId: string | null) {
    selectedRfvId.value = nuevoRfvId
    
    if (nuevoRfvId) {
      await cargarClientesPorRfv(nuevoRfvId)
    } else {
      clientesDisponibles.value = []
    }
    
    await cargarVisitasDelMes()
  }

  async function crearVisita(datosVisita: {
    idCliente: string
    Fecha: string
    Hora: string
    idRFV?: string
  }) {
    isLoading.value = true

    try {
      const response = await axios.post(route('tmp_planificaciones.store'), datosVisita)
      
      const nuevaVisita: Visita = {
        id: `tmp_${response.data.data.Id}`,
        title: response.data.data.nombre_cliente || 'Nueva visita',
        start: new Date(`${response.data.data.Fecha}T${response.data.data.Hora}`).toISOString(),
        end: new Date(new Date(`${response.data.data.Fecha}T${response.data.data.Hora}`).getTime() + 3600000).toISOString(),
        tailwindColor: 'yellow',
        metadata: {
          tipo: 'tmp_temporal',
          cliente_id: response.data.data.idCliente,
          rfv_id: response.data.data.idRFV,
          nombre_cliente: response.data.data.nombre_cliente || 'Cliente',
          nombre_rfv: response.data.data.nombre_rfv || 'RFV',
          fecha_original: response.data.data.Fecha,
          hora_original: response.data.data.Hora,
          puedeEditar: true,
          puedeEliminar: true,
          puedeProcesar: true,
          id_original: response.data.data.Id
        }
      }

      visitas.value.push(nuevaVisita)
      return nuevaVisita
    } catch (err) {
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function actualizarVisita(id: string, cambios: {
    idCliente?: string
    Fecha?: string
    Hora?: string
    idRFV?: string
  }) {
    isLoading.value = true

    try {
      const idOriginal = id.replace('tmp_', '')
      const response = await axios.put(
        route('tmp_planificaciones.update', { id: idOriginal }),
        cambios
      )

      const index = visitas.value.findIndex(v => v.id === id)
      if (index !== -1) {
        const visitaActualizada: Visita = {
          ...visitas.value[index],
          title: response.data.data.nombre_cliente || visitas.value[index].title,
          start: new Date(`${response.data.data.Fecha || cambios.Fecha}T${response.data.data.Hora || cambios.Hora}`).toISOString(),
          end: new Date(new Date(`${response.data.data.Fecha || cambios.Fecha}T${response.data.data.Hora || cambios.Hora}`).getTime() + 3600000).toISOString(),
          metadata: {
            ...visitas.value[index].metadata,
            cliente_id: response.data.data.idCliente || cambios.idCliente,
            fecha_original: response.data.data.Fecha || cambios.Fecha,
            hora_original: response.data.data.Hora || cambios.Hora,
            nombre_cliente: response.data.data.nombre_cliente || visitas.value[index].metadata.nombre_cliente,
            nombre_rfv: response.data.data.nombre_rfv || visitas.value[index].metadata.nombre_rfv
          }
        }
        
        visitas.value.splice(index, 1, visitaActualizada)
      }

      return visitas.value[index]
    } catch (err) {
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function eliminarVisita(id: string) {
    isLoading.value = true

    try {
      const idOriginal = id.replace('tmp_', '')
      await axios.delete(route('tmp_planificaciones.destroy', { id: idOriginal }))
      visitas.value = visitas.value.filter(v => v.id !== id)
    } catch (err) {
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function procesarVisita(id: string) {
    try {
      const idOriginal = id.replace('tmp_', '')
      window.location.href = route('tmp_planificaciones.procesar', { id: idOriginal })
    } catch (err) {
      throw err
    }
  }

  function filtrarVisitasPorTipo(tipo: 'tmp_temporal' | 'tmp_perdida' | 'procesada' | 'todos') {
    if (tipo === 'todos') return visitas.value
    return visitas.value.filter(visita => visita.metadata.tipo === tipo)
  }

  function limpiarSelecciones() {
    selectedRfvId.value = null
    rfvsDisponibles.value = []
    clientesDisponibles.value = []
  }

  function clearError() {
    error.value = null
  }

  // =============================================================================
  // RETORNO
  // =============================================================================

  return {
    visitas,
    mesActual,
    isLoading,
    error,
    rfvsDisponibles,
    clientesDisponibles,
    selectedRfvId,
    userRole,
    visitasDelMes,
    estadisticasVisitas,
    cargarVisitasDelMes,
    crearVisita,
    actualizarVisita,
    eliminarVisita,
    procesarVisita,
    cargarRfvsDisponibles,
    cargarClientesPorRfv,
    cambiarRfvSeleccionado,
    limpiarSelecciones,
    filtrarVisitasPorTipo,
    clearError
  }
})

