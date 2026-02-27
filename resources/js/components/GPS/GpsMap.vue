<script setup lang="ts">
import { ref, watch, nextTick, onUnmounted, shallowRef } from 'vue'
import L from 'leaflet'
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';

import 'leaflet/dist/leaflet.css'
import icon from 'leaflet/dist/images/marker-icon.png'
import iconShadow from 'leaflet/dist/images/marker-shadow.png'
import axios from 'axios'
import { type RutaPoint, type LeyendaItem } from '@/types/gps'

// =================================================================
// PROPS
// =================================================================
const props = defineProps<{
  rfv: string | null
  fechaDesde: string | null
  fechaHasta: string | null
}>()

L.Icon.Default.mergeOptions({
  iconUrl: icon,
  shadowUrl: iconShadow,
})

// =================================================================
// ESTADO INTERNO
// =================================================================
const mapContainer = ref<HTMLElement | null>(null)
let map: L.Map | null = null
const routeLayerGroup = shallowRef<L.FeatureGroup | null>(null)
let markerClusterGroup: L.MarkerClusterGroup | null = null;

const ruta = ref<RutaPoint[]>([])
const leyenda = ref<LeyendaItem[]>([])
const totalVisitas = ref(0)
const isLoading = ref(false)
const error = ref<string | null>(null)

// =================================================================
// LÓGICA PRINCIPAL
// =================================================================

function initializeMap() {
  if (mapContainer.value && !map) {
    // --- IMPLEMENTACIÓN DEL ENFOQUE C ---
    // Se define un punto de partida genérico y un zoom alejado.
    const initialCoords: L.LatLngTuple = [8.0, -66.0]; // Centro de Venezuela
    const initialZoom = 6;

    map = L.map(mapContainer.value, {
        attributionControl: false, 
    }).setView(initialCoords, initialZoom); // El mapa inicia en esta vista general

    // Se eliminó la lógica de navigator.geolocation para un comportamiento consistente.

    const corner1 = L.latLng(-90, -180);
    const corner2 = L.latLng(90, 180);
    const bounds = L.latLngBounds(corner1, corner2);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      noWrap: true,
      bounds: bounds,
      minZoom: 2,
      maxZoom: 19,
    }).addTo(map)

    map.setMaxBounds(bounds);

    L.control.attribution({
        position: 'bottomright',
        prefix: ''
    }).addAttribution('© OpenStreetMap').addTo(map);
    
    markerClusterGroup = L.markerClusterGroup({
        disableClusteringAtZoom: 18,
        spiderfyOnMaxZoom: true,
        iconCreateFunction: function(cluster) {
            const markers = cluster.getAllChildMarkers();
            const activityCounts: { [key: string]: number } = {};
            markers.forEach(marker => {
                const activityName = marker.options.title || '';
                if (activityName) {
                    activityCounts[activityName] = (activityCounts[activityName] || 0) + 1;
                }
            });

            let dominantActivity = '';
            let maxCount = 0;
            for (const activity in activityCounts) {
                if (activityCounts[activity] > maxCount) {
                    maxCount = activityCounts[activity];
                    dominantActivity = activity;
                }
            }

            const colorMap = new Map(leyenda.value.map(item => [item.actividad, item.color]));
            const clusterColor = colorMap.get(dominantActivity) || '#757575';

            const childCount = cluster.getChildCount();

            const iconHtml = `
              <div style="background-color: ${clusterColor}; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);">
                <span>${childCount}</span>
              </div>
            `;
            
            return L.divIcon({
                html: iconHtml,
                className: '',
                iconSize: [40, 40]
            });
        }
    });
    map.addLayer(markerClusterGroup);

    routeLayerGroup.value = L.featureGroup().addTo(map)
    
    buscarRuta()
  }
}

const buscarRuta = async () => {
  if (!props.rfv || !props.fechaDesde || !props.fechaHasta) {
    return
  }
  if (!map || !markerClusterGroup) {
    return
  }

  isLoading.value = true
  error.value = null
  limpiarMapa()

  try {
    const response = await axios.get(route('gps.obtenerRuta'), {
      params: {
        idRFV: props.rfv,
        fechaDesde: props.fechaDesde,
        fechaHasta: props.fechaHasta,
      },
    })
    
    ruta.value = response.data.ruta
    leyenda.value = response.data.leyenda
    totalVisitas.value = response.data.totalVisitas

    await dibujarRutaEnMapa()

  } catch (err: any) {
    console.error("Error al obtener la ruta:", err)
    error.value = err.response?.data?.message || "No se pudo obtener la ruta. Verifique los filtros e intente de nuevo."
  } finally {
    isLoading.value = false
  }
}

const dibujarRutaEnMapa = async () => {
  await nextTick();

  if (!map || !markerClusterGroup || ruta.value.length === 0) return

  const colorMap = new Map(leyenda.value.map(item => [item.actividad, item.color]))
  const markers: L.Marker[] = [];

  ruta.value.forEach(point => {
    const lat = parseFloat(point.latitud)
    const lng = parseFloat(point.longitud)
    
    const color = colorMap.get(point.nombre_actividad) || '#757575'
    
    const customIcon = L.divIcon({
      html: `<svg viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="${color}" stroke="white" stroke-width="2"/></svg>`,
      className: '',
      iconSize: [24, 24],
      iconAnchor: [12, 12],
      popupAnchor: [0, -12]
    });

    const popupContent = `
    <div class="font-sans text-sm leading-normal">
        <strong class="text-base">${point.nombre_cliente}</strong><br>
        <div class="mt-2 space-y-1">
            <div class="flex">
                <strong class="font-semibold w-24 flex-shrink-0">Actividad:</strong>
                <span>${point.nombre_actividad}</span>
            </div>
            <div class="flex">
                <strong class="font-semibold w-24 flex-shrink-0">Especialidad:</strong>
                <span>${point.nombre_especialidad}</span>
            </div>
            <div class="flex">
                <strong class="font-semibold w-24 flex-shrink-0">Fecha:</strong>
                <span>${new Date(point.fecha_actividad).toLocaleString()}</span>
            </div>
            <div class="flex items-start">
                <strong class="font-semibold w-24 flex-shrink-0">Obs:</strong>
                <span class="whitespace-pre-wrap">${point.observaciones_cliente || 'N/A'}</span>
            </div>
        </div>
    </div>
    `
    const marker = L.marker([lat, lng], { 
        icon: customIcon,
        title: point.nombre_actividad
    }).bindPopup(popupContent);
    markers.push(marker);
  })
  
  markerClusterGroup.addLayers(markers);

  // La función fitBounds se encarga de hacer el "salto visual" al área de los datos.
  map.fitBounds(markerClusterGroup.getBounds(), { padding: [50, 50] });
}

const limpiarMapa = () => {
  markerClusterGroup?.clearLayers();
}

// =================================================================
// CICLO DE VIDA Y REACTIVIDAD
// =================================================================
watch(() => [props.rfv, props.fechaDesde, props.fechaHasta], async () => {
  await nextTick()
  if (!map) {
    initializeMap()
  } else {
    buscarRuta()
  }
}, { immediate: true })

onUnmounted(() => {
  if (map) {
    map.remove()
    map = null
  }
})
</script>

<template>
  <div class="flex flex-col lg:flex-row gap-4 p-1">
    <div class="w-full lg:w-3/4 h-[60vh] rounded-xl overflow-hidden shadow-md border">
      <div ref="mapContainer" class="w-full h-full bg-gray-200"></div>
    </div>

    <div class="w-full lg:w-1/4 flex flex-col items-start space-y-4">
      <div v-if="isLoading" class="w-full p-4 text-center text-gray-600 font-medium">
        <p>Buscando ruta...</p>
      </div>
      
      <div v-if="error" class="w-full p-3 text-sm text-red-700 bg-red-100 rounded-lg">
        <p class="font-bold">Error</p>
        <p>{{ error }}</p>
      </div>

      <div v-if="!isLoading && !error && ruta.length > 0" class="w-full p-4 border rounded-xl shadow-sm bg-white space-y-4 text-gray-700">
        <div>
          <h3 class="font-bold text-lg text-gray-800">Resultados</h3>
          <p class="text-sm font-medium">
            Total de Visitas: <span class="font-bold text-gray-900">{{ totalVisitas }}</span>
          </p>
        </div>
        
        <hr class="border-gray-200" />
        
        <div>
          <h4 class="font-semibold text-md text-gray-800 mb-3">Interpretación del Mapa</h4>
          
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold border-2 border-white shadow-md">
                <span>{{ totalVisitas }}</span>
              </div>
            </div>
            <div>
              <p class="font-semibold text-sm">Agrupaciones</p>
              <p class="text-xs text-gray-500">
                Los grupos son clickeables. Además, el color indica la actividad más frecuente y el número es el total de visitas.
              </p>
            </div>
          </div>
          
          <hr class="my-4 border-gray-200" />

          <div>
            <p class="font-semibold text-sm mb-2">Puntos Individuales</p>
            <ul class="space-y-1.5">
              <li v-for="item in leyenda" :key="item.actividad" class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                  <svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" :fill="item.color" stroke="white" stroke-width="2"/></svg>
                  <span class="truncate" :title="item.actividad">{{ item.actividad }}</span>
                </div>
                <span class="font-bold bg-gray-100 px-2 py-0.5 rounded-full text-xs">{{ item.cantidad }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div v-if="!isLoading && !error && ruta.length === 0" class="w-full p-4 text-center text-gray-500">
        <p>No se encontraron actividades para los filtros seleccionados.</p>
      </div>
    </div>
  </div>
</template>

