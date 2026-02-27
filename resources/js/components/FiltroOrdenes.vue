<script setup lang="ts">
import { ref } from 'vue'
import ComboSelect from './ComboSelect.vue'
import Button from './ui/button/Button.vue' // Asegúrate de importar el componente Button si no lo tienes

/**
 * Evento personalizado que se emitirá cuando el usuario haga clic en el botón CONSULTAR.
 */
const emit = defineEmits(['filtrar'])

/**
 * Opciones fijas para el filtro de Estatus.
 */
const estatusOptions = [
  { label: 'Pendiente', value: 'Pendiente' },
  { label: 'Procesado', value: 'Procesado' },
  { label: 'Cancelado', value: 'Cancelado' }
]

/**
 * Opciones fijas para el filtro de Mayorista.
 */
const mayoristaOptions = [
  { label: 'Mayorista A', value: 'Mayorista A' },
  { label: 'Mayorista B', value: 'Mayorista B' }
]

/**
 * Estados locales reactivos para almacenar los filtros seleccionados.
 */
const selectedEstatus = ref<string[]>([])
const selectedMayorista = ref<string[]>([])

/**
 * Función que se ejecuta al presionar el botón CONSULTAR.
 * Emite un objeto con los filtros seleccionados hacia el componente padre.
 */
function aplicarFiltro() {
  emit('filtrar', {
    estatus: selectedEstatus.value,
    mayorista: selectedMayorista.value
  })
}
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
    <ComboSelect
      v-model="selectedEstatus"
      :options="estatusOptions"
      placeholder="Estatus"
    />

    <ComboSelect
      v-model="selectedMayorista"
      :options="mayoristaOptions"
      placeholder="Mayorista"
    />

    <div class="sm:col-span-1">
      <Button
        @click="aplicarFiltro"
        class="w-full sm:w-auto bg-[#63c00d] text-white hover:bg-[#00aa39]"
      >
        CONSULTAR
        <i class="fas fa-search ml-1"></i>
      </Button>
    </div>
  </div>
</template>