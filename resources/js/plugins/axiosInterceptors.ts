// resources/js/plugins/axiosInterceptors.ts
import axios from 'axios'
import { useAlertStore } from '@/stores/alertStore'

export function setupAxiosInterceptors() {
  // Interceptor de respuesta para errores HTTP
  axios.interceptors.response.use(
    (response) => {
      if (response.config?._suppressAlert) return response

      if (response.data?.message && response.status >= 200 && response.status < 300) {
        const alertStore = useAlertStore()
        alertStore.showSuccess(response.data.message)
      }
      return response
    },
    (error) => {
      if (error.config?._suppressAlert) {
        return Promise.reject(error)
      }

      const alertStore = useAlertStore()

      if (error.response) {
        // Error del servidor
        const { status, data } = error.response
        
        switch (status) {
          case 422:
            // Validación fallida
            if (data.errors) {
              Object.values(data.errors).forEach((messages: any) => {
                messages.forEach((message: string) => {
                  alertStore.showError(message)
                })
              })
            } else if (data.message) {
              alertStore.showError(data.message)
            }
            break
            
          case 403:
            alertStore.showError(data.message || 'No tiene permisos para realizar esta acción')
            break
            
          case 404:
            alertStore.showError(data.message || 'Recurso no encontrado')
            break
            
          case 500:
            alertStore.showError('Error interno del servidor. Por favor, intente más tarde')
            break
            
          default:
            if (data.message) {
              alertStore.showError(data.message)
            } else {
              alertStore.showError(`Error ${status}: ${error.message}`)
            }
        }
      } else if (error.request) {
        // Error de red (sin respuesta)
        alertStore.showError('Error de conexión. Verifique su conexión a internet')
      } else {
        // Error en la configuración de la petición
        alertStore.showError('Error al configurar la petición')
      }
      
      return Promise.reject(error)
    }
  )
}