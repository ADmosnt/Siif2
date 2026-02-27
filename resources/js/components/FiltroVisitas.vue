/*
  Nombre: FiltroVisitas
  Proceso: El componente tiene la logica para que los filtros de visitas funcionen en el modulo de reporte -visitas
            filtra la informacion por: ranking, especialidad, actividad, eventos, zona y ruta
  Fecha creado: 8 de julio del 2025
  Quien lo hizo: Bimodal - A.Lozada
  Ultima Modificacion:
  Ultima Modificacion por: 
*/

<script setup lang="ts">
import { ref } from 'vue'
import ComboSelect from '@/components/ComboSelect.vue'

// Define las props que llegan desde el componente padre (listas de opciones para cada filtro)
defineProps<{
  rankingOptions: { value: string; label: string }[]
  especialidadOptions: { value: string; label: string }[]
  actividadOptions: { value: string; label: string }[]
  eventoOptions: { value: string; label: string }[]
  zonaOptions: { value: string; label: string }[]
  rutaOptions: { value: string; label: string }[]
}>()

// Define el evento 'consultar' que se emitirá al hacer clic en el botón
const emit = defineEmits(['consultar'])

// Variables reactivas que almacenan las selecciones del usuario
const selectedRanking = ref<string[]>([])
const selectedEspecialidad = ref<string[]>([])
const selectedActividad = ref<string[]>([])
const selectedEvento = ref<string[]>([])
const selectedZona = ref<string[]>([])
const selectedRuta = ref<string[]>([])

/**
 * Llama al método aplicarFiltro() al presionar el botón.
 * Este método emite un objeto con todos los filtros seleccionados
 * al componente padre, que puede procesarlos para filtrar los datos.
 */
function aplicarFiltro() {
  emit('consultar', { 
    ranking: selectedRanking.value,
    especialidad: selectedEspecialidad.value,
    actividad: selectedActividad.value,
    evento: selectedEvento.value,
    zona: selectedZona.value,
    ruta: selectedRuta.value
  })
}
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-4">
   <ComboSelect
      v-model="selectedRanking"
      :options="rankingOptions"
      placeholder="Ranking"
    />
    <ComboSelect
      v-model="selectedEspecialidad"
      :options="especialidadOptions"
      placeholder="Especialidad"
    />
   <ComboSelect
      v-model="selectedActividad"
      :options="actividadOptions" 
      placeholder="Actividad"
   />
    <ComboSelect
      v-model="selectedEvento"
       :options="eventoOptions"
        placeholder="Evento"
   />
    <ComboSelect
     v-model="selectedZona"
      :options="zonaOptions"
      placeholder="Zona"
  />
   <ComboSelect
      v-model="selectedRuta"
       :options="rutaOptions"
       placeholder="Ruta"
     />
   <div class="col-span-1">
     <button
      class="bg-[#63c00d] hover:bg-[#00aa39] text-white px-4 py-2 rounded font-bold"
       @click="aplicarFiltro"
       >
       CONSULTAR
     </button>
    </div>
  </div>
</template>