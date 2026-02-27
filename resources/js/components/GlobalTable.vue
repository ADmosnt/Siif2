<!-- resources/js/components/GlobalTable.vue -->
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
import TablePagination from '@/components/TablePagination.vue'
import SubHeader from '@/components/SubHeader.vue'
import type { ExportAction, SummaryItem } from '@/components/SubHeader.vue'

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

/** Iconos y clases por defecto para cada acción */
const DEFAULT_ACTIONS: Record<string, { icon: any; btnClass: string }> = {
    edit:   { 
        icon: EditIcon,  
        btnClass: [
            'inline-flex items-center justify-center',  
            'p-2 leading-none',                         
            'rounded-full',
            'bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-800 dark:hover:bg-yellow-800',
            'text-yellow-600 dark:text-yellow-300'           
        ].join(' ')         
        // btnClass: 'p-1 text-yellow-600 hover:text-yellow-800' 
    },
    delete: {
        icon: DeleteIcon,
        btnClass: [
        'inline-flex items-center justify-center',  // inline-flex para evitar issues de line-height
        'p-2 leading-none',                         // padding + leading-none para centrar perfectamente
        'rounded-full',
        'bg-red-100 hover:bg-red-200 dark:bg-red-800 dark:hover:bg-red-700',
        'text-red-600 dark:text-red-300'            // color via currentColor
        ].join(' ')
    },
    add:    {
         icon: AddIcon,    
         btnClass: [
            'inline-flex items-center justify-center',  
            'p-2 leading-none',                         
            'rounded-full',
            'bg-blue-100 hover:bg-blue-200 dark:bg-blue-800 dark:hover:bg-blue-800',
            'text-blue-600 dark:text-blue-300'           
        ].join(' ')    
    },
    clock:    { icon: ClockIcon,    btnClass: 'p-1 text-blue-600  hover:text-blue-800'  },
    calendar:    { icon: CalendarIcon,    btnClass: 'p-1 text-blue-600  hover:text-blue-800'  },
}

const props = defineProps({
    columns: { type: Array as PropType<Column[]>, required: true },
    rows: { type: Array as PropType<Record<string, any>[]>, required: true },
    actions: { type: Array as PropType<ActionDef<any>[]>, default: () => [] },

    links: { type: Array as PropType<Array<{ url: string | null; label: string; active: boolean }>> },
    currentPage: { type: Number },

    // Props de paginación que vienen del padre
    totalRecords: { type: Number, required: true },
    pageSize: { type: String, required: true },

    // Props de configuración
    autoAddActionsColumn: { type: Boolean, default: false },
    actionsColumnLabel: { type: String, default: 'Acción' },
    showAddButton: { type: Boolean, default: false },
    addButtonLabel: { type: String, default: 'Add' },
    addButtonLabelShort: { type: String, default: 'Add' },
    showSubHeader: { type: Boolean, default: false },
    subHeaderProps: { type: Object, default: () => ({}) },  
    showPagination: { type: Boolean, default: true }
})

const emit = defineEmits<{
    (e: 'update:page', value: number): void
    (e: 'update:pageSize', value: string): void
    (e: 'add'): void
}>()

function handleAdd() {
    emit('add')
}

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

// const wrapper = ref<HTMLElement|null>(null)

// onMounted(() => {
//     nextTick(() => {
//         if (wrapper.value) {
//         const fullHeight = wrapper.value.getBoundingClientRect().height
//         wrapper.value.style.height = `${fullHeight}px`
//         }
//     })
// })

</script>

<template>

    <div>

        <!-- FOOTER / PAGINACIÓN -->
    
        <TablePagination
            v-if="props.showPagination"
            :showAddButton="props.showAddButton"
            :addButtonLabel="props.addButtonLabel"
            :addButtonLabelShort="props.addButtonLabelShort"
            @add="handleAdd"          

            :links="props.links"
            :total-records="props.totalRecords"
            :current-page="props.currentPage"
            :page-size="props.pageSize"
            
            @update:page="(newPage) => emit('update:page', newPage)"
            @update:pageSize="(newSize) => emit('update:pageSize', String(newSize))"
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
                    {{ actionsColumnLabel }}
                </TableHead>
                </TableRow>
            </TableHeader>

            <!-- 2) Cuerpo -->
            <TableBody>
                <TableRow v-for="row in props.rows" :key="row.id">
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
                            v-model="row[col.key]" 
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
                        <div class="flex items-center justify-center space-x-2">
                            <template v-for="action in mergedActions" :key="action.key">
                                <button
                                    :class="action.btnClass"
                                    @click="() => action.handler(row)"
                                >
                                    <component :is="action.icon" />
                                </button>
                            </template>
                        </div>
                    </TableCell>
                </TableRow>

                <!-- Mensaje "No hay datos" -->
                <TableRow v-if="props.rows.length === 0">
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

<!-- flex items-center justify-center rounded-full w-8 h-8 bg-red-100 hover:bg-red-200 dark:bg-red-800 dark:hover:bg-red-700 text-red-600 dark:text-red-300' -->