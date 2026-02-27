<script setup lang="ts">
// ... La sección <script> se mantiene exactamente igual que en la respuesta anterior ...
import { computed } from 'vue'
import type { PropType } from 'vue'
import PdfIcon from '@/components/icons/PdfIcon.vue'
import ExcelIcon from '@/components/icons/ExcelIcon.vue'

export interface ExportAction {
  key: string
  title?: string
  onClick: () => void
  icon?: any
  btnClass?: string
}

export interface SummaryItem {
  label: string
  value: string | number
  className?: string
}

const DEFAULT_EXPORT_ACTIONS: Record<string, ExportAction> = {
  pdf: {
    key: 'pdf',
    title: 'Descargar PDF',
    onClick: () => {},
    icon: PdfIcon,
    btnClass:
      'flex items-center justify-center rounded-full w-8 h-8 ' +
      'bg-red-100 hover:bg-red-200 dark:bg-red-800 dark:hover:bg-red-700 ' +
      'text-red-600 dark:text-red-300'
  },
  excel: {
    key: 'excel',
    title: 'Descargar Excel',
    onClick: () => {},
    icon: ExcelIcon,
    btnClass:
      'flex items-center justify-center rounded-full w-8 h-8 ' +
      'bg-green-100 hover:bg-green-200 dark:bg-green-800 dark:hover:bg-green-700 ' +
      'text-green-600 dark:text-green-300'
  }
}

const props = defineProps({
  showExport: {
    type: Boolean,
    default: true
  },
  exportLabel: {
    type: String,
    default: 'Descargar como:'
  },
  exportActions: {
    type: Array as PropType<ExportAction[]>,
    default: () => [
      { key: 'pdf', onClick: () => console.log('PDF clicked') },
      { key: 'excel', onClick: () => console.log('Excel clicked') }
    ]
  },
  summaries: {
    type: Array as PropType<SummaryItem[]>,
    default: () => []
  }
})

const mergedExportActions = computed(() =>
  props.exportActions.map((action) => {
    const def = DEFAULT_EXPORT_ACTIONS[action.key] || {}
    return {
      ...def,
      ...action,
      title: action.title || def.title,
      icon: action.icon || def.icon,
      btnClass: action.btnClass || def.btnClass
    }
  })
)
</script>


<template>
  <div
    class="flex flex-col sm:flex-row items-start sm:items-center w-full gap-4 p-4"
    :class="{ 'justify-between': showExport && exportActions.length > 0 }"
  >
    <div
      v-if="showExport && exportActions.length > 0"
      class="flex items-center gap-2 flex-shrink-0"
    >
      <span class="text-sm font-medium">{{ exportLabel }}</span>
      <button
        v-for="(act, i) in mergedExportActions"
        :key="i"
        :title="act.title"
        :class="['flex items-center justify-center rounded-full w-8 h-8 text-current', act.btnClass]"
        @click="act.onClick"
      >
        <component :is="act.icon" class="w-4 h-4" />
      </button>
    </div>

    <div
      v-if="summaries.length > 0"
      :class="[
        'text-sm',
        showExport && exportActions.length > 0
          ? 'grid grid-cols-2 md:grid-cols-4 gap-4'
          : 'w-full flex flex-wrap items-center justify-around gap-4'
      ]"
    >
      <div v-for="(s, i) in summaries" :key="i" class="whitespace-nowrap">
        <span class="font-medium">{{ s.label }}:</span> {{ s.value }}
      </div>
    </div>
  </div>
</template>