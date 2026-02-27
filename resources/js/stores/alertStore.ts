// resources/js/stores/alertStore.ts
import { ref, reactive, computed } from 'vue'
import { defineStore } from 'pinia'

interface Alert {
  id: number
  type: 'success' | 'error' | 'warning' | 'info'
  message: string
  autoHide?: boolean
  duration?: number
  persistent?: boolean
}

interface ConfirmAlert extends Omit<Alert, 'type'> {
  type: 'warning'
  onConfirm: () => void
  onCancel?: () => void
  confirmText?: string
  cancelText?: string
}

export const useAlertStore = defineStore('alert', () => {
  const alerts = ref<Alert[]>([])
  let nextId = 1

  // Computed para alertas no persistentes (las que se auto-ocultan)
  const autoHideAlerts = computed(() => 
    alerts.value.filter(alert => alert.autoHide && !alert.persistent)
  )

  // Computed para alertas persistentes (requieren acción del usuario)
  const persistentAlerts = computed(() => 
    alerts.value.filter(alert => alert.persistent)
  )

  function addAlert(alert: Omit<Alert, 'id'>) {
    const id = nextId++
    const newAlert = { id, ...alert }
    
    alerts.value.push(newAlert)
    
    // Auto-ocultar si está configurado
    if (alert.autoHide && !alert.persistent) {
      setTimeout(() => {
        removeAlert(id)
      }, alert.duration || 5000)
    }
    
    return id
  }

  function removeAlert(id: number) {
    const index = alerts.value.findIndex(alert => alert.id === id)
    if (index !== -1) {
      alerts.value.splice(index, 1)
    }
  }

  function clearAlerts() {
    alerts.value = []
  }

    function showConfirm(message: string, options: Omit<ConfirmAlert, 'id' | 'type' | 'message'>) {
    const id = addAlert({
      type: 'warning',
      message,
      persistent: true,
      ...options
    })
    
    return id
  }

  function showSuccess(message: string, options: Partial<Omit<Alert, 'id' | 'type' | 'message'>> = {}) {
    return addAlert({
      type: 'success',
      message,
      autoHide: true,
      duration: 5000,
      ...options
    })
  }

  function showError(message: string, options: Partial<Omit<Alert, 'id' | 'type' | 'message'>> = {}) {
    return addAlert({
      type: 'error',
      message,
      autoHide: true,
      duration: 8000,
      ...options
    })
  }

  function showWarning(message: string, options: Partial<Omit<ConfirmAlert, 'id' | 'type' | 'message'>> = {}) {
    if (options.onConfirm) {
      return showConfirm(message, options as ConfirmAlert)
    } else {
      return addAlert({
        type: 'warning',
        message,
        autoHide: true,
        duration: 7000,
        ...options
      })
    }
  }

  function showInfo(message: string, options: Partial<Omit<Alert, 'id' | 'type' | 'message'>> = {}) {
    return addAlert({
      type: 'info',
      message,
      autoHide: true,
      duration: 4000,
      ...options
    })
  }

  return {
    alerts,
    autoHideAlerts,
    persistentAlerts,
    addAlert,
    removeAlert,
    clearAlerts,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    showConfirm
  }
})