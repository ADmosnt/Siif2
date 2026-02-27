<!-- resources/js/components/Reportes/PanelDual.vue -->
<template>
  <div class="flex flex-col md:flex-row md:space-x-6">
    <!-- Panel Izquierdo -->
    <div
      class="flex-1 flex flex-col border rounded-xl shadow md:h-[600px] panel-container"
      :style="panelBgStyle"
    >
      <PanelHeader :title="getTitle(leftPanel.title)" />
      <PanelList
        :items="leftPanel.items || []"
        :selected-item="selectedItem"
        @item-selected="handleItemSelected"
      />
    </div>

    <!-- Panel Derecho -->
    <div
      class="flex-1 flex flex-col border rounded-xl shadow md:h-[600px] panel-container"
      :style="panelBgStyle"
    >
      <PanelHeader :title="getTitle(rightPanel.title)" />
      <PanelDetail
        :item="selectedItem"
        :items="rightPanel.items || []"
        :detail-template="rightPanel.detailTemplate"
        :empty-message="rightPanel.emptyMessage"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, type ComputedRef } from 'vue'
import PanelHeader from './PanelHeader.vue'
import PanelList from './PanelList.vue'
import PanelDetail from './PanelDetail.vue'

export interface PanelItem {
  id: string | number
  [key: string]: any
}

export interface PanelConfig {
  title: string | ComputedRef<string>
  items?: PanelItem[]
  detailTemplate?: string
  emptyMessage?: string
}

interface Props {
  leftPanel: PanelConfig
  rightPanel: PanelConfig
  selectedItem?: PanelItem | null
}

interface Emits {
  (e: 'itemSelected', item: PanelItem): void
}

const props = withDefaults(defineProps<Props>(), {
  selectedItem: null
})

const emit = defineEmits<Emits>()

// Función para desenvolver títulos (string o ComputedRef)
const getTitle = (title: string | ComputedRef<string>): string => {
  return typeof title === 'string' ? title : title.value
}

// Lógica del fondo del panel (modo oscuro)
const panelBgColor = '#000000'
const isDark = computed(() => document.documentElement.classList.contains('dark'))
const panelBgStyle = computed(() => ({
  backgroundColor: isDark.value ? panelBgColor : '#ffffff'
}))

const handleItemSelected = (item: PanelItem) => {
  console.log('PanelDual: Emitiendo itemSelected:', item.id)
  emit('itemSelected', item)
}

// Watch para depuración
import { watch } from 'vue'

watch(() => props.selectedItem, (newVal) => {
  console.log('PanelDual: selectedItem prop actualizado:', newVal?.id)
}, { immediate: true })
</script>

<style scoped>
/* estilos de scrollbar existentes */
.panel-container {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e0 #ffffff;
}

.panel-container::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.panel-container::-webkit-scrollbar-track {
  background: #ffffff;
  border-radius: 4px;
}

.panel-container::-webkit-scrollbar-thumb {
  background-color: #cbd5e0;
  border-radius: 9999px;
  border: 2px solid #ffffff;
}

.panel-container::-webkit-scrollbar-thumb:hover {
  background-color: #a0aec0;
}

.dark .panel-container {
  scrollbar-color: #4b5563 #000000;
}

.dark .panel-container::-webkit-scrollbar-track {
  background: #000000;
}

.dark .panel-container::-webkit-scrollbar-thumb {
  background-color: #4b5563;
  border: 2px solid #000000;
}

.dark .panel-container::-webkit-scrollbar-thumb:hover {
  background-color: #6b7280;
}
</style>