import { ref } from 'vue'
import axios, { isAxiosError } from 'axios'
import { useAlertStore } from '@/stores/alertStore'

async function extraerMensajeError(error: unknown): Promise<string> {
  if (isAxiosError(error) && error.response?.data instanceof Blob) {
    try {
      const text = await error.response.data.text()
      const parsed = JSON.parse(text)
      return parsed.error || parsed.message || 'No se pudo generar el archivo.'
    } catch {
      // El cuerpo del error no era JSON (p.ej. una pagina de error HTML)
    }
  }
  return 'No se pudo generar el archivo. Intenta acotar el rango de fechas o los filtros.'
}

// Descarga PDF/Excel via GET con axios en vez de window.location.href: eso
// navega el documento entero y no da forma de mostrar un estado de carga
// mientras dompdf/Excel arman el archivo en el servidor (puede tomar varios
// segundos con datasets grandes). Con blob + un <a download> sintetico se
// puede mostrar un spinner ("Generando archivo...") durante la espera.
export function useFileDownload() {
  const downloading = ref(false)

  async function descargar(url: string, params: URLSearchParams, filename: string) {
    downloading.value = true
    try {
      const response = await axios.get(`${url}?${params}`, {
        responseType: 'blob',
        // El interceptor global ya muestra su propio alert generico por
        // status code; se suprime aca porque este composable ya da un
        // mensaje mas especifico (leido del JSON de error real).
        _suppressAlert: true,
      } as any)

      const blobUrl = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = blobUrl
      link.setAttribute('download', filename)
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(blobUrl)
    } catch (error) {
      console.error('Error al generar el archivo:', error)
      useAlertStore().showError(await extraerMensajeError(error))
    } finally {
      downloading.value = false
    }
  }

  return { downloading, descargar }
}
