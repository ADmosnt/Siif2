<!-- resources/js/components/GenericGlobalAlert.vue -->
<script setup lang="ts">
import { useAlertStore } from '@/stores/alertStore'
import { storeToRefs } from 'pinia'
import { computed } from 'vue'

const alertStore = useAlertStore()
const { alerts } = storeToRefs(alertStore)

// Agrupar alertas por tipo para mostrar múltiples
const groupedAlerts = computed(() => {
  const groups = {
    success: [] as Array<{id: number, message: string, onConfirm?: () => void}>,
    error: [] as Array<{id: number, message: string, onConfirm?: () => void}>,
    warning: [] as Array<{id: number, message: string, onConfirm?: () => void, confirmText?: string, cancelText?: string}>,
    info: [] as Array<{id: number, message: string, onConfirm?: () => void}>
  }
  
  alerts.value.forEach(alert => {
    const alertData = {
      id: alert.id,
      message: alert.message,
      onConfirm: (alert as any).onConfirm,
      confirmText: (alert as any).confirmText,
      cancelText: (alert as any).cancelText
    }
    
    groups[alert.type].push(alertData)
  })
  
  return groups
})

// Determinar el tipo principal de alerta a mostrar
const primaryAlertType = computed(() => {
  if (groupedAlerts.value.error.length > 0) return 'error'
  if (groupedAlerts.value.warning.length > 0) return 'warning'
  if (groupedAlerts.value.success.length > 0) return 'success'
  if (groupedAlerts.value.info.length > 0) return 'info'
  return null
})

// Mensaje compuesto para mostrar
const compositeMessage = computed(() => {
  const type = primaryAlertType.value
  if (!type) return ''
  
  const messages = groupedAlerts.value[type]
  if (messages.length === 1) return messages[0].message
  
  // Si hay múltiples mensajes del mismo tipo
  if (messages.length > 1) {
    return `${messages.length} ${type === 'error' ? 'errores' : type === 'warning' ? 'advertencias' : type + 's'}: ${messages[0].message} ${messages.length > 1 ? `(+${messages.length - 1} más)` : ''}`
  }
  
  return ''
})

// Clases de estilo
const bgColorClass = computed(() => {
  switch (primaryAlertType.value) {
    case 'success': return 'bg-green-50 border-green-200 text-green-800'
    case 'error': return 'bg-red-50 border-red-200 text-red-800'
    case 'warning': return 'bg-yellow-50 border-yellow-200 text-yellow-800'
    case 'info': return 'bg-blue-50 border-blue-200 text-blue-800'
    default: return ''
  }
})

const iconClass = computed(() => {
  switch (primaryAlertType.value) {
    case 'success': return 'text-green-400'
    case 'error': return 'text-red-400'
    case 'warning': return 'text-yellow-400'
    case 'info': return 'text-blue-400'
    default: return ''
  }
})

// Cerrar todas las alertas del tipo principal
const closeAllOfType = () => {
  const type = primaryAlertType.value
  if (type) {
    groupedAlerts.value[type].forEach(alert => {
      alertStore.removeAlert(alert.id)
    })
  }
}

// Función para manejar confirmación
const handleConfirm = (alert: any) => {
  if (alert.onConfirm) {
    alert.onConfirm()
    alertStore.removeAlert(alert.id)
  }
}

// Función para manejar cancelación
const handleCancel = (alert: any) => {
  if ((alert as any).onCancel) {
    (alert as any).onCancel()
  }
  alertStore.removeAlert(alert.id)
}
</script>

<template>

  <div class="fixed inset-0 flex justify-center items-start pointer-events-none z-[200]">
    <transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform translate-y-2 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform translate-y-2 opacity-0"
    >
      <div
        v-if="primaryAlertType"
        :class="['border rounded-lg p-4 mb-4 shadow-sm flex items-start pointer-events-auto', bgColorClass]"
        style="max-width: 90vw; min-width: 300px;"
      >
        <!-- Icono -->
        <div :class="['flex-shrink-0 mr-3', iconClass]">
          <svg v-if="primaryAlertType === 'success'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <svg v-else-if="primaryAlertType === 'error'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
          </svg>
          <svg v-else-if="primaryAlertType === 'warning'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          <svg v-else-if="primaryAlertType === 'info'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
          </svg>
        </div>
        
        <!-- Contenido -->
        <div class="flex-1">
          <p class="text-sm font-medium">{{ compositeMessage }}</p>
          
        <!-- Botones de confirmación para alertas de tipo warning con onConfirm -->
        <div v-if="primaryAlertType === 'warning' && groupedAlerts.warning[0]?.onConfirm" 
              class="mt-3 flex space-x-2">
          <button
            @click="handleConfirm(groupedAlerts.warning[0])"
            :class="[
              'px-3 py-1 text-sm rounded-md',
              'bg-yellow-600 text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2'
            ]"
          >
            {{ groupedAlerts.warning[0]?.confirmText || 'Confirmar' }}
          </button>
          <button
            @click="handleCancel(groupedAlerts.warning[0])"
            :class="[
              'px-3 py-1 text-sm rounded-md',
              'bg-gray-200 text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2'
            ]"
          >
            {{ groupedAlerts.warning[0]?.cancelText || 'Cancelar' }}
          </button>
        </div>
          <!-- Contador de múltiples alertas -->
          <div v-if="Object.values(groupedAlerts).flat().length > 1" class="mt-1 text-xs opacity-75">
            Mostrando {{ groupedAlerts[primaryAlertType].length }} de {{ Object.values(groupedAlerts).flat().length }} alertas
          </div>
        </div>
        
        <!-- Botón de cierre -->
        <button
          @click="closeAllOfType"
          class="flex-shrink-0 ml-3 text-gray-400 hover:text-gray-600 focus:outline-none"
          aria-label="Cerrar alerta"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </transition>
  </div>
</template>