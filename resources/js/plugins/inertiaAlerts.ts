// resources/js/plugins/inertiaAlerts.ts
import { router } from '@inertiajs/vue3'
import { useAlertStore } from '@/stores/alertStore'

export function setupInertiaAlerts() {
  // Interceptar visitas de Inertia para manejar flash messages
  router.on('success', (event) => {
    const alertStore = useAlertStore()
    
    // Limpiar alertas anteriores
    alertStore.clearAlerts()
    
    // Procesar flash messages de la página actual
    const page = event.detail.page.props
    
    if (page.flash) {
      const { flash } = page
      
      if (flash.success) {
        alertStore.showSuccess(flash.success)
      }
      
      if (flash.error) {
        alertStore.showError(flash.error)
      }
      
      if (flash.warning) {
        alertStore.showWarning(flash.warning)
      }
      
      if (flash.info) {
        alertStore.showInfo(flash.info)
      }
    }
    
    // Procesar errores de validación
if (page.errors && Object.keys(page.errors).length > 0) {
      Object.values(page.errors).forEach((error: any) => {
        if (Array.isArray(error)) {
          error.forEach((msg: string) => alertStore.showError(msg))
        } else {
          alertStore.showError(error)
        }
      })
    }
  })
}