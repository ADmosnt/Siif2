<!-- resources/js/components/Reportes/PanelList.vue -->
<template>
  <ul
    v-if="items.length"
    class="flex-1 divide-y divide-gray-200 dark:divide-gray-600 overflow-y-auto"
  >
    <li
      v-for="item in items"
      :key="item.id"
      class="flex justify-between items-center px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-200"
      :class="{
        'bg-blue-50 dark:bg-blue-900 border-r-4 border-blue-500': isSelected(item)
      }"
      @click="handleItemClick(item)"
    >
      <div class="flex-1">
        <p class="text-blue-600 dark:text-blue-400 font-medium">
          {{ item.nombre || item.cliente || item.idCliente }}
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1" v-if="item.rfv || item.ultimoRFV">
          <strong>Visitó:</strong> {{ item.rfv || item.ultimoRFV }}
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-300" v-if="item.fecha_actividad || item.ultimaFecha">
          <strong>Fecha:</strong> {{ formatDate(item.fecha_actividad || item.ultimaFecha) }}
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-300" v-if="item.cantidad !== undefined">
          {{ item.cantidad }} actividad{{ item.cantidad > 1 ? 'es' : '' }}
        </p>
      </div>
      
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 ml-2" viewBox="0 0 512 512" fill="currentColor">
        <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM216 336l24 0 0-64-24 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l48 0c13.3 0 24 10.7 24 24l0 88 8 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-80 0c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-208a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/>
      </svg>
    </li>
  </ul>
  
  <div v-else class="p-4 text-center text-gray-700 dark:text-gray-300">
    {{ emptyMessage || 'No hay elementos para mostrar.' }}
  </div>
</template>

<script setup lang="ts">
import type { PanelItem } from './PanelDual.vue'

interface Props {
  items: PanelItem[]
  selectedItem?: PanelItem | null
  emptyMessage?: string
}

interface Emits {
  (e: 'itemSelected', item: PanelItem): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Función reactiva para determinar si un item está seleccionado
const isSelected = (item: PanelItem) => {
  return props.selectedItem?.id === item.id
}

// Manejar click con más control
const handleItemClick = (item: PanelItem) => {
  console.log('PanelList: Item clickeado:', item.id)
  emit('itemSelected', item)
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A'
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('es-ES')
  } catch {
    return 'Fecha inválida'
  }
}

// Watch para depuración
import { watch } from 'vue'

watch(() => props.selectedItem, (newVal) => {
  console.log('PanelList: selectedItem actualizado:', newVal?.id)
}, { immediate: true })

watch(() => props.items, (newVal) => {
  console.log('PanelList: items actualizados:', newVal.length)
}, { immediate: true })
</script>