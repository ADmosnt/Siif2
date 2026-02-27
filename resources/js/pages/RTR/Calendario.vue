<!-- resources/js/pages/Demo.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { ref } from 'vue'
import { useVisitasStore, type Visita } from '@/stores/calendarStore'
import CalendarView from '@/components/CalendarV2/CalendarView.vue'

// =============================================================================
// CONFIGURACIÓN DE LA PÁGINA
// =============================================================================

/**
 * Breadcrumbs de navegación
 */
const breadcrumbs: BreadcrumbItem[] = [
  { label: 'Dashboard', href: 'dashboard' },
  { label: 'Calendario de Visitas' }, // Nombre más específico
]

// =============================================================================
// ESTADO LOCAL SIMPLE
// =============================================================================

/**
 * Fecha actual - para sincronización si es necesaria
 */
const currentDate = ref(new Date())

// =============================================================================
// STORE DE VISITAS - REEMPLAZA EL STORE COMPLEJO
// =============================================================================

/**
 * Store específico para visitas - Sin overrides, sin complejidad
 */
const visitasStore = useVisitasStore()

// =============================================================================
// MANEJADORES DE EVENTOS SIMPLIFICADOS
// =============================================================================

/**
 * Maneja cuando se crea una nueva visita
 */
const handleVisitaCreada = (visita: Visita) => {
  console.log('✅ Nueva visita creada:', {
    id: visita.id,
    cliente: visita.metadata.nombre_cliente,
    fecha: visita.metadata.fecha_original,
    hora: visita.metadata.hora_original
  })
  
  // Aquí podrías:
  // - Mostrar notificación de éxito
  // - Actualizar analytics
  // - Enviar evento a servicio de tracking
}

/**
 * Maneja cuando se actualiza una visita existente
 */
const handleVisitaActualizada = (visita: Visita, nuevoInicio: string, nuevoFin: string) => {
  console.log('✏️ Visita actualizada:', {
    id: visita.id,
    cliente: visita.metadata.nombre_cliente,
    viejaFecha: visita.metadata.fecha_original,
    nuevaFecha: new Date(nuevoInicio).toLocaleDateString('es-ES'),
    nuevaHora: new Date(nuevoInicio).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
  })
  
  // Aquí podrías:
  // - Mostrar notificación
  // - Registrar el cambio en logs
}

/**
 * Maneja cuando se elimina una visita
 */
const handleVisitaEliminada = (visitaId: string) => {
  console.log('🗑️ Visita eliminada:', { id: visitaId })
  
  // Aquí podrías:
  // - Mostrar confirmación
  // - Actualizar contadores
}

/**
 * Maneja cuando se hace click en una visita
 */
const handleVisitaClick = (visita: Visita) => {
  console.log('👆 Click en visita:', {
    id: visita.id,
    cliente: visita.metadata.nombre_cliente,
    rfv: visita.metadata.nombre_rfv,
    puedeEditar: visita.metadata.puedeEditar,
    puedeEliminar: visita.metadata.puedeEliminar,
    puedeProcesar: visita.metadata.puedeProcesar
  })
  
  // Aquí podrías:
  // - Abrir un modal de detalles
  // - Mostrar opciones contextuales
  // - Navegar a vista de detalles
}

/**
 * Maneja cuando se procesa una visita (cuando se convierte en reporte)
 */
const handleVisitaProcesada = (visitaId: string) => {
  console.log('🔄 Visita procesada:', { id: visitaId })
  
  // Aquí podrías:
  // - Mostrar notificación de éxito
  // - Recargar las visitas para actualizar el estado
  // - Navegar a la vista de reportes
  
  // Ejemplo: Recargar visitas después de procesar
  visitasStore.cargarVisitasDelMes(currentDate.value)
}

/**
 * Maneja cuando cambia la fecha (navegación entre meses)
 */
const handleFechaCambiada = (nuevaFecha: Date) => {
  console.log('📅 Cambio de fecha:', { 
    mes: nuevaFecha.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
  })
  
  currentDate.value = nuevaFecha
  // El CalendarView ya se encarga de cargar las visitas del nuevo mes
  // pero aquí podrías hacer otras acciones como:
  // - Actualizar el título de la página
  // - Guardar la fecha en localStorage
  // - Sincronizar con otros componentes
}

/**
 * Maneja cuando se abre el modal de visitas
 */
const handleModalAbierto = (fecha: Date) => {
  console.log('📋 Modal abierto para fecha:', fecha.toLocaleDateString('es-ES'))
  
  // Aquí podrías:
  // - Registrar analytics
  // - Mostrar tutorial si es primera vez
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <!-- 
      CALENDARIO DE VISITAS - COMPONENTE PRINCIPAL
      
      PROPS ENVIADOS:
      - initialDate: Fecha inicial a mostrar
      - showControls: Mostrar navegación y filtros (true por defecto)
      - showEventButton: Mostrar botón crear visita (true por defecto)
      - height: Altura del componente (100% por defecto)
      
      EVENTOS MANEJADOS:
      - date-change: Cuando cambia el mes/año visible
      - visita-created: Cuando se crea una nueva visita
      - visita-updated: Cuando se actualiza una visita existente  
      - visita-deleted: Cuando se elimina una visita
      - visita-click: Cuando se hace click en una visita
      - visita-processed: Cuando una visita se marca como procesada
      - openVisitaModal: Cuando se abre el modal de gestión de visitas
    -->
    <CalendarView 
      :initial-date="currentDate"
      @date-change="handleFechaCambiada"
      @visita-created="handleVisitaCreada"
      @visita-updated="handleVisitaActualizada"
      @visita-deleted="handleVisitaEliminada"
      @visita-click="handleVisitaClick"
      @visita-processed="handleVisitaProcesada"
      @openVisitaModal="handleModalAbierto"
      class="border rounded-lg shadow-sm"
    />
  </AppLayout>
</template>

<style scoped>
/* Estilos mínimos - la mayoría del styling está en los componentes hijos */
</style>