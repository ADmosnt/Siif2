<!-- resources/js/icons/GlobalTable.vue -->
<script setup lang="ts">
import { ref, computed, watch, defineProps, defineEmits, PropType, nextTick, onMounted, defineExpose } from 'vue'

import {EditIcon, DeleteIcon, AddIcon, ClockIcon, CalendarIcon, PdfIcon, ExcelIcon} from '@/components/icons'

import {
    Table,
    TableHeader,
    TableRow,
    TableHead,
    TableBody,
    TableCell
} from '@/components/ui/table'
import TableFootTdp from '@/components/TablePagination.vue'
import SubHeader from '@/components/SubHeader.vue'
import type { ExportAction, SummaryItem } from '@/components/SubHeader.vue'

/** Iconos y clases por defecto para cada acción */
const DEFAULT_ACTIONS: Record<string, { icon: any; btnClass: string }> = {
    edit:   { icon: EditIcon,   btnClass: 'p-1 text-yellow-600 hover:text-yellow-800' },
    delete: { icon: DeleteIcon, btnClass: 'p-1 text-red-600   hover:text-red-800'  },
    add:    { icon: AddIcon,    btnClass: 'p-1 text-blue-600  hover:text-blue-800'  },
    clock:    { icon: ClockIcon,    btnClass: 'p-1 text-blue-600  hover:text-blue-800'  },
    calendar:    { icon: CalendarIcon,    btnClass: 'p-1 text-blue-600  hover:text-blue-800'  },
}

/** Descripción de cada columna */
interface Column {
  key: string
  label: string
  className?: string
  /** Opcional: si defines esto, GlobalTable usará este componente en lugar de texto */
  cellComponent?: any
  /** Props a pasarle al componente de celda */
  cellProps?: Record<string, any>
  /** Opcional: componente para el header de la columna */
  headerComponent?: any
}

/** Descripción de cada acción que se pasará desde el padre */
interface ActionDef<Row> {
    key: string
    handler: (row: Row) => void
  /** Si quieres usar un icono/custom vs el default */
    icon?: any
  /** Si quieres customizar la clase Tailwind */
    btnClass?: string
}

const props = defineProps({

  /** Columnas de la tabla */
    columns: {
        type: Array as PropType<Column[]>,
        required: true
    },
  /** Filas completas a mostrar */
    rows: {
        type: Array as PropType<Record<string, any>[]>,
        required: true
    },
  /** Lista de acciones (edit, delete, add, etc.) */
    actions: {
        type: Array as PropType<ActionDef<any>[]>,
        default: () => []
    },
  /** Si añadimos columna de acciones automáticamente */
    autoAddActionsColumn: {
        type: Boolean,
        default: false
    },
  /** Footer "Agregar …" */
    showAddButton: {
        type: Boolean,
        default: false
    },
    addButtonLabel: {
        type: String,
        default: 'Add'
    },
    addButtonLabelShort: {
        type: String,
        default: 'Add'
    },
    initialPage: {
        type: Number,
        default: 1
    },
    initialPageSize: {
        type: String,
        default: '15'
    },
    showSubHeader: {
        type: Boolean,
        default: false
    },
    /** ▶ Props que pasaremos a SubHeader */
    subHeaderProps: {
        type: Object as PropType<{
        exportActions?: ExportAction[]
        summaries?:    SummaryItem[]
        }>,
        default: () => ({})
    },  
    HiddenTableFoot: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits<{
    (e: 'update:page', value: number): void
    (e: 'update:pageSize', value: string): void
    (e: 'add'): void
}>()

// Paginación interna
const currentPage = ref(props.initialPage)
const pageSize    = ref(props.initialPageSize)

const totalRecords = computed(() => props.rows.length)
const perPage = computed(() =>
    pageSize.value === 'all' ? props.rows.length : Number(pageSize.value)
)
const totalPages = computed(() =>
    pageSize.value === 'all'
    ? 1
    : Math.ceil(props.rows.length / perPage.value)
)
const pagedRows = computed(() => {
    if (pageSize.value === 'all') return props.rows
    const start = (currentPage.value - 1) * perPage.value
    return props.rows.slice(start, start + perPage.value)
})

// Función para obtener las filas de la página actual (expuesta para GenericTable)
function getCurrentPageRows() {
    return pagedRows.value
}

// Exponer la función para que GenericTable pueda acceder a ella
defineExpose({
    getCurrentPageRows
})

// Emitir cambios de paginación
watch(pageSize, (v) => {
    currentPage.value = 1
    emit('update:pageSize', v)
    emit('update:page', 1)
})
watch(currentPage, (v) => emit('update:page', v))

// Acción de "Agregar …" en footer
function handleAdd() {
    emit('add')
}

// Merge entre DEFAULT_ACTIONS y lo pasado por props
const mergedActions = computed(() => {
    return props.actions.map(a => {
        const def = DEFAULT_ACTIONS[a.key] || {}
        return {
        key:      a.key,
        handler:  a.handler,
        icon:     a.icon     || def.icon,
        btnClass: a.btnClass || def.btnClass
        }
    })
})

// Referencia al contenedor scrollable
const wrapper = ref<HTMLElement|null>(null)

onMounted(() => {
    // Medimos altura inicial del wrapper (incluyendo header + body)
    // y luego fijamos SOLO la zona de filas (wrapper) a esa altura
    nextTick(() => {
        if (wrapper.value) {
        const fullHeight = wrapper.value.getBoundingClientRect().height
        // Ajusta 1rem si quieres descontar el header
        wrapper.value.style.height = `${fullHeight}px`
        }
    })
})


</script>

<template>

    <div>

        <!-- FOOTER / PAGINACIÓN -->
    
        <TableFootTdp
            v-if ="!props.HiddenTableFoot"
            :showAddButton="props.showAddButton"
            :addButtonLabel="props.addButtonLabel"
            :addButtonLabelShort="props.addButtonLabelShort"
            @add="handleAdd"
            :total-records="totalRecords"
            @update:page="(p) => (currentPage = p)"
            @update:pageSize="(sz) => (pageSize = sz)"
        />

         <SubHeader
           v-if="props.showSubHeader"
           v-bind="props.subHeaderProps"
         />        

        <!-- TABLA PRINCIPAL -->
        <Table class="w-full">
            <!-- 1) Encabezado -->
            <TableHeader>
                <TableRow>
                <TableHead
                    v-for="col in props.columns"
                    :key="col.key"
                    :class="col.className || 'px-4 py-2 whitespace-nowrap text-left font-medium'"
                >
                    <!-- Si tiene headerComponent, lo renderizamos -->
                    <template v-if="col.headerComponent">
                        <component :is="col.headerComponent" />
                    </template>
                    <!-- Si no, mostramos el label normal -->
                    <template v-else>
                        {{ col.label }}
                    </template>
                </TableHead>

                <!-- Columna extra de "Actions" -->
                <TableHead
                    v-if="props.autoAddActionsColumn"
                    class="px-4 py-2 whitespace-nowrap text-center font-medium"
                >
                    Acción
                </TableHead>
                </TableRow>
            </TableHeader>

            <!-- 2) Cuerpo -->
            <TableBody>
                <TableRow v-for="row in pagedRows" :key="row.id">
                    <!-- Columnas dinámicas -->
                    <TableCell
                        v-for="col in props.columns"
                        :key="col.key"
                        :class="col.className || 'px-4 py-2 whitespace-nowrap'"
                    >   
                    <!-- 1) Slot nombrado: -->
                    <template v-if="$slots[`cell-${col.key}`]">
                        <slot :name="`cell-${col.key}`" :row="row" />
                    </template>

                    <!-- 2) Componente de celda (si en la columna definiste `cellComponent`): -->
                    <template v-else-if="col.cellComponent">
                        <component
                            :is="col.cellComponent"
                            v-bind="typeof col.cellProps === 'function'
                            ? col.cellProps(row)
                            : (col.cellProps || {})"
                        />
                    </template>

                    <!-- 3) Fallback: solo mostrar el valor -->
                    <template v-else>
                        {{ row[col.key] }}
                    </template>
                    </TableCell>

                    <!-- Columna de acciones automática (sin tocar): -->
                    <TableCell
                        v-if="props.autoAddActionsColumn"
                        class="px-4 py-2 whitespace-nowrap text-center"
                    >
                        <template v-for="action in mergedActions" :key="action.key">
                            <button
                                :class="action.btnClass"
                                @click="() => action.handler(row)"
                            >
                                <component :is="action.icon" />
                            </button>
                        </template>
                    </TableCell>
                </TableRow>

                <!-- Mensaje "No hay datos" -->
                <TableRow v-if="pagedRows.length === 0">
                    <TableCell
                        :colspan="props.columns.length + (props.autoAddActionsColumn ? 1 : 0)"
                        class="px-4 py-2 text-center text-gray-500"
                    >
                        No hay datos para mostrar.
                    </TableCell>
                </TableRow>
            </TableBody>

        </Table>

    </div>

</template>