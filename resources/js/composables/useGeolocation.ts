import { ref, onMounted } from 'vue'

export interface GeoCoords {
  lat: number
  lon: number
}

export function useGeolocation() {
  const coords = ref<GeoCoords | null>(null)
  const error = ref<string | null>(null)
  const loading = ref(false)
  const permissionDenied = ref(false)

  async function requestLocation(): Promise<GeoCoords | null> {
    if (!('geolocation' in navigator)) {
      error.value = 'Tu dispositivo no soporta geolocalizacion.'
      return null
    }

    loading.value = true
    error.value = null

    try {
      const position = await new Promise<GeolocationPosition>((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: true,
          timeout: 30000,
          maximumAge: 60000,
        })
      })

      const result: GeoCoords = {
        lat: position.coords.latitude,
        lon: position.coords.longitude,
      }
      coords.value = result
      permissionDenied.value = false
      return result
    } catch (err: any) {
      if (err.code === 1) {
        permissionDenied.value = true
        error.value = 'Debes habilitar la ubicacion para continuar. Activa el GPS en la configuracion de tu navegador.'
      } else if (err.code === 2) {
        error.value = 'No se pudo determinar tu ubicacion. Verifica que el GPS este activado.'
      } else if (err.code === 3) {
        error.value = 'La solicitud de ubicacion tardo demasiado. Intentalo de nuevo.'
      }
      return null
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    if ('permissions' in navigator) {
      navigator.permissions.query({ name: 'geolocation' }).then((result) => {
        if (result.state === 'denied') {
          permissionDenied.value = true
          error.value = 'Debes habilitar la ubicacion para continuar. Activa el GPS en la configuracion de tu navegador.'
        }
      }).catch(() => {})
    }
  })

  return {
    coords,
    error,
    loading,
    permissionDenied,
    requestLocation,
  }
}
