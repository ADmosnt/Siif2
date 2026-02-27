<!-- resources/js/components/GenericTable.vue -->
<script setup lang="ts">
import { ref, computed, defineProps, defineEmits, PropType, h } from 'vue'
import GlobalTable from '@/components/GlobalTable.vue'
import type { Column } from '@/components/GlobalTable.vue'
import Button from './ui/button/Button.vue'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import Input from './ui/input/Input.vue'
import { RefreshCw } from 'lucide-vue-next'

// props de configuración
const props = defineProps({

  columns:    { type: Array as PropType<Column[]>, required: true },
  rows:       { type: Array as PropType<Record<string,any>[]>, required: true },

  selectable: { type: Boolean, default: false },
  modelValue: { type: Array as PropType<any[]>, default: () => [] },

  /** Search box encima */
  showFilter:         { type: Boolean, default: false },
  filterPlaceholder:  { type: String,  default: 'Buscar...' },

  /** Refresh button */
  showRefresh:        { type: Boolean, default: false },
  onRefresh:          { type: Function as PropType<() => void> },

  /** Columna de fecha (asume que la columna ya está en `columns`) */
  showDateColumn:     { type: Boolean, default: false },

  /** Select + botón para acciones */
  showSelectAction:   { type: Boolean, default: false },
  selectOptions:      { type: Array as PropType<{ label:string; value:any }[]>, default: () => [] },
  onSelect:           { type: Function as PropType<(val:any) => void> },
  showSaveButton:     { type: Boolean, default: false },
})

const emit = defineEmits<{ (e: 'update:modelValue', val: any[]): void }>()

// estado interno del filtro
const search = ref('')
const selectedValue = ref<any>(null)

// Referencia a GlobalTable para acceder a las filas paginadas
const globalTableRef = ref<InstanceType<typeof GlobalTable> | null>(null)

// filtrar rows si showFilter
const filtered = computed(() => {
  if (!props.showFilter || !search.value) return props.rows
  return props.rows.filter(r =>
    Object.values(r).some(v =>
      String(v).toLowerCase().includes(search.value.toLowerCase())
    )
  )
})

// Simulamos la paginación localmente para evitar dependencias circulares
const currentPage = ref(1)
const pageSize = ref('15')

// Calculamos las filas de la página actual localmente
const currentPageRows = computed(() => {
  const rows = filtered.value
  if (pageSize.value === 'all') return rows
  const perPage = Number(pageSize.value)
  const start = (currentPage.value - 1) * perPage
  return rows.slice(start, start + perPage)
})

// función de ayuda para saber si ya está en el selected
const isRowSelected = (row: any) =>
  props.modelValue.some(item => item.id === row.id)

// toggleRow y toggleSelectAll que ya tienes corregidos:
function toggleRow(row: any, checked: boolean) {
  const arr = [...props.modelValue]
  if (checked) {
    if (!arr.some(item => item.id === row.id)) arr.push(row)
  } else {
    const idx = arr.findIndex(item => item.id === row.id)
    if (idx > -1) arr.splice(idx, 1)
  }
  emit('update:modelValue', arr)
}

function toggleSelectAll(selected: boolean) {
  const arr = [...props.modelValue]
  if (selected) {
    currentPageRows.value.forEach(row => {
      if (!arr.some(item => item.id === row.id)) arr.push(row)
    })
  } else {
    currentPageRows.value.forEach(row => {
      const idx = arr.findIndex(item => item.id === row.id)
      if (idx > -1) arr.splice(idx, 1)
    })
  }
  emit('update:modelValue', arr)
}

// Computed para saber si todos los registros de la página actual están seleccionados
const allCurrentPageSelected = computed(() => {
  if (currentPageRows.value.length === 0) return false
  return currentPageRows.value.every(row => props.modelValue.includes(row))
})

// Computed para saber si algunos (pero no todos) están seleccionados
const someCurrentPageSelected = computed(() => {
  if (currentPageRows.value.length === 0) return false
  const selectedCount = currentPageRows.value.filter(row => props.modelValue.includes(row)).length
  return selectedCount > 0 && selectedCount < currentPageRows.value.length
})

// … en el <script setup> de GenericTable.vue …

// …después de definir toggleRow, toggleSelectAll, currentPageRows, etc…

// 1) Definimos nuestro checkbox de fila
const CheckboxCell = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  setup(p, { emit }) {
    return () =>
      h('input', {
        type: 'checkbox',
        checked: p.modelValue,
        onChange: (e: any) => emit('update:modelValue', e.target.checked),
        class: 'form-checkbox'
      })
  }
}

// 2) Definimos el checkbox de cabecera (select all)
const SelectAllCheckbox = {
  setup() {
    return () =>
      h('input', {
        type: 'checkbox',
        checked: allCurrentPageSelected.value,
        ref: (el: HTMLInputElement|null) => {
          if (el) el.indeterminate = someCurrentPageSelected.value
        },
        onChange: (e: any) => toggleSelectAll(e.target.checked),
        class: 'form-checkbox'
      })
  }
}

const tableColumns = computed<Column[]>(() => [
  ...(props.selectable
     ? [{
         key: '__sel',
         label: '',
         className: 'w-10',               // ancho a tu gusto
         headerComponent: SelectAllCheckbox
       }]
     : []),
  ...props.columns
])

// Manejar selección del select
function handleSelectChange(value: any) {
  selectedValue.value = value
}

// Ejecutar acción con valor seleccionado
function executeAction() {
  if (selectedValue.value && props.onSelect) {
    props.onSelect(selectedValue.value)
  }
}

</script>

<template>
  <div class="flex flex-col gap-4">
    <!-- ■ Toolbar -->
    <div class="flex items-center gap-2">
      <slot name="toolbar-before" />

      <!-- Filtro de búsqueda -->
      <div class="flex-1">
        <Input
          v-if="showFilter"
          v-model="search"
          :placeholder="filterPlaceholder"
          class="max-w-sm"
        />
      </div>

      <!-- Botón refresh -->
      <Button
        v-if="showRefresh"
        @click="onRefresh"
        variant="outline"
        size="sm"
      >
        <RefreshCw class="h-4 w-4" />
      </Button>

      <slot name="toolbar-after" />
    </div>

    <!-- ■ Select + Acción -->
    <div v-if="showSelectAction" class="flex items-center gap-2">
      <Select @update:modelValue="handleSelectChange">
        <SelectTrigger class="w-48">
          <SelectValue placeholder="Seleccionar opción..." />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup>
            <SelectItem 
              v-for="opt in selectOptions" 
              :key="opt.value" 
              :value="opt.value"
            >
              {{ opt.label }}
            </SelectItem>
          </SelectGroup>
        </SelectContent>
      </Select>
      
      <Button 
        @click="executeAction"
        :disabled="!selectedValue"
        size="sm"
      >
        Aplicar
      </Button>

      <Button 
        v-if="showSaveButton"
        @click="() => {}"
        variant="outline"
        size="sm"
      >
        Guardar
      </Button>
    </div>

    <GlobalTable
      ref="globalTableRef"
      :columns="tableColumns"
      :rows="filtered"
      :actions="[]"
      :autoAddActionsColumn="false"
      @update:page="currentPage = $event"
      @update:pageSize="pageSize = $event"
      v-bind="$attrs"
    >
      <!-- Slot para cada celda __sel -->
      <template #cell-__sel="{ row }">
        <input
          type="checkbox"
          :checked="isRowSelected(row)"
          @change="toggleRow(row, $event.target.checked)"
          class="form-checkbox"
        />
      </template>
    </GlobalTable>

    <!-- ■ Paginación / add-button via slots -->
    <slot name="footer" />
  </div>
</template>