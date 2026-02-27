<!-- resources/js/components/Gps.vue -->

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { onMounted, onBeforeUnmount, ref } from 'vue'
import * as L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import icon from 'leaflet/dist/images/marker-icon.png'
import iconShadow from 'leaflet/dist/images/marker-shadow.png'
import DatePicker from '@/components/DatePicker.vue'
import SimpleDatePicker from '@/components/simpleDatePicker.vue'
import axios from 'axios' // 1. Importar axios
import { getLocalTimeZone, type DateValue } from '@internationalized/date'
// --- Interfaces para tipar la respuesta de la API (Buena práctica con TypeScript) ---
interface RutaPoint {
  latitud: string
  longitud: string
  fecha_actividad: string
  nombre_cliente: string
  nombre_especialidad: string
  nombre_actividad: string
  observaciones_cliente: string
}

interface LeyendaItem {
  actividad: string
  cantidad: number
  color: string
}

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF', href: '/dashboard' },
  { label: 'Consulta Gerencial'},
]

L.Icon.Default.mergeOptions({
  iconUrl: icon,
  shadowUrl: iconShadow,
})

// --- Variables Reactivas (Refs) para el estado ---
const mapContainer = ref<HTMLElement | null>(null)
let map: L.Map
const routeLayerGroup = ref<L.LayerGroup | null>(null) // Grupo de capas para marcadores y líneas

// Estado para los parámetros de la API
const idRFV = ref('SUBEROK') // Valor de ejemplo, podría venir de un prop, un selector, etc.
const fechaDesde = ref<DateValue>(); // Valor de ejemplo
const fechaHasta = ref<DateValue>(); // Valor de ejemplo





// Estado para la respuesta de la API
const ruta = ref<RutaPoint[]>([])
const leyenda = ref<LeyendaItem[]>([])
const totalVisitas = ref(0)

// Estado de la UI
const isLoading = ref(false)
const error = ref<string | null>(null)


// --- Ciclo de Vida del Componente ---
onMounted(() => {
  if (mapContainer.value) {
    map = L.map(mapContainer.value).setView([10.5, -66.9], 13)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
    }).addTo(map)

    // Inicializamos el grupo de capas y lo añadimos al mapa
    routeLayerGroup.value = L.layerGroup().addTo(map)
  }
})

onBeforeUnmount(() => {
  if (map) map.remove()
})


// --- Lógica de la Aplicación ---

/**
 * Función principal para obtener y mostrar la ruta.
 */
const buscarRuta = async () => {
  // Validaciones básicas
  if (!idRFV.value || !fechaDesde.value || !fechaHasta.value) {
    error.value = "Todos los campos son obligatorios."
    return
  }

  isLoading.value = true
  error.value = null
  limpiarMapa() // Limpia marcadores anteriores

  try {
    // 2. Hacer la llamada a la API con axios
    const response = await axios.get('/api/gerencial/gps/ruta', { // Asegúrate que esta URL sea correcta
      params: {
        idRFV: idRFV.value,
        fechaDesde: fechaDesde.value,
        fechaHasta: fechaHasta.value,
      },
    })
    
    // 3. Almacenar los datos de la respuesta en nuestras variables reactivas
    ruta.value = response.data.ruta
    leyenda.value = response.data.leyenda
    totalVisitas.value = response.data.totalVisitas

    // 4. Dibujar los nuevos datos en el mapa
    dibujarRutaEnMapa()

  } catch (err) {
    console.error("Error al obtener la ruta:", err)
    error.value = "No se pudo obtener la ruta. Por favor, intente de nuevo."
    // Aquí podrías manejar errores específicos, ej: err.response.status === 404
  } finally {
    isLoading.value = false
  }
}

/**
 * Dibuja los marcadores y la línea de la ruta en el mapa.
 */
const dibujarRutaEnMapa = () => {
  if (!routeLayerGroup.value || ruta.value.length === 0) return

  const latLngs: L.LatLngExpression[] = []
  
  // Obtenemos el mapa de colores de la leyenda para usarlo en los marcadores
  const colorMap = new Map(leyenda.value.map(item => [item.actividad, item.color]))

  ruta.value.forEach(point => {
    const lat = parseFloat(point.latitud)
    const lng = parseFloat(point.longitud)
    latLngs.push([lat, lng])
    
    // Crear un icono personalizado con el color de la leyenda
    const color = colorMap.get(point.nombre_actividad) || '#CCCCCC' // Color por defecto
    const customIcon = L.divIcon({
        html: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${color}" width="28" height="28"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>`,
        className: '', // Sin clase para no añadir estilos por defecto
        iconSize: [28, 28],
        iconAnchor: [14, 28], // La punta del marcador
        popupAnchor: [0, -28]
    });


    // Crear el contenido del Popup
    const popupContent = `
      <div class="font-sans">
        <strong class="text-base">${point.nombre_cliente}</strong><br>
        <strong>Actividad:</strong> ${point.nombre_actividad}<br>
        <strong>Especialidad:</strong> ${point.nombre_especialidad}<br>
        <strong>Fecha:</strong> ${new Date(point.fecha_actividad).toLocaleString()}<br>
        <strong>Obs:</strong> ${point.observaciones_cliente}
      </div>
    `
    // Añadir marcador al grupo de capas
    L.marker([lat, lng], { icon: customIcon }).addTo(routeLayerGroup.value).bindPopup(popupContent)
  })

  // 5. Dibujar una línea (Polyline) que conecte los puntos
  if (latLngs.length > 1) {
    L.polyline(latLngs, { color: '#003366', weight: 3, opacity: 0.7 }).addTo(routeLayerGroup.value)
  }

  // 6. Ajustar el zoom del mapa para que se vea toda la ruta
  if (routeLayerGroup.value) {
      map.fitBounds(routeLayerGroup.value.getBounds(), { padding: [50, 50] })
  }
}

/**
 * Limpia el grupo de capas del mapa.
 */
const limpiarMapa = () => {
  routeLayerGroup.value?.clearLayers()
  ruta.value = []
  leyenda.value = []
  totalVisitas.value = 0
}
</script>

<template>
  <Head title="GPS" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col lg:flex-row gap-4 p-4">
      
      <div class="w-full lg:w-3/4 h-[550px] rounded-xl overflow-hidden shadow-md border border-gray-450">
        <div ref="mapContainer" class="w-full h-full"></div>
      </div>

      <div class="w-full lg:w-1/4 flex flex-col items-start space-y-4">
        
        <div class="w-full p-4 border rounded-xl shadow-sm bg-white space-y-3">
            <h3 class="font-bold text-lg text-gray-800">Parámetros de Búsqueda</h3>
            
            <div>
                <label for="idRFV" class="block text-sm font-medium text-gray-700">ID Representante (RFV)</label>
                <input
                    type="text"
                    id="idRFV"
                    v-model="idRFV"
                    class="w-full mt-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-300 text-sm"
                />
            </div>

                <SimpleDatePicker v-model="fechaDesde"/>
                <SimpleDatePicker v-model="fechaHasta"/>

            <div v-if="error" class="text-sm text-red-600 bg-red-100 p-2 rounded">
              {{ error }}
            </div>

            <button
              @click="buscarRuta"
              :disabled="isLoading"
              class="w-full bg-gray-600 text-white font-medium py-2 px-4 rounded-xl hover:bg-gray-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <svg v-if="isLoading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isLoading ? 'Buscando...' : 'Buscar Ruta' }}
            </button>
        </div>

        <div v-if="ruta.length > 0 && !isLoading" class="w-full p-4 border rounded-xl shadow-sm bg-white space-y-3">
          <h3 class="font-bold text-lg text-gray-800">Resultados</h3>
          <p class="text-sm font-medium text-gray-600">Total de Visitas: <span class="font-bold text-gray-800">{{ totalVisitas }}</span></p>

          <hr>
          
          <h4 class="font-semibold text-md text-gray-700">Leyenda</h4>
          <ul class="space-y-2">
            <li v-for="item in leyenda" :key="item.actividad" class="flex items-center justify-between text-sm">
              <div class="flex items-center gap-2">
                <span class="block w-4 h-4 rounded-full" :style="{ backgroundColor: item.color }"></span>
                <span>{{ item.actividad }}</span>
              </div>
              <span class="font-bold bg-gray-200 px-2 py-0.5 rounded-full">{{ item.cantidad }}</span>
            </li>
          </ul>
        </div>

      </div>
    </div>
  </AppLayout>
</template>
