<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import Button from '@/components/ui/button/Button.vue'
import { Input } from '@/components/ui/input'
import GlobalTable from '@/components/GlobalTable.vue'
import SimpleDatePicker from '@/components/simpleDatePicker.vue'
import { type BreadcrumbItem } from '@/types'
import { format } from 'date-fns'
import { getLocalTimeZone, type DateValue } from '@internationalized/date'

  const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Real Time Report (RTR)'},
    { label: 'Listado de Reportes' },
  ]
  
  interface Visitas {
    rfv: string
    cliente: string
    fecha: string
    actividad: string
    comentario: string
  }
  
const rowsVisitas  = ref<Visitas[]>([])

const fechaInicio = ref<DateValue>();
const fechaFin = ref<DateValue>();

const TableColumns = [
    // Ahora las 'key' apuntan a las propiedades planas que acabamos de crear
    { key: 'supervisor_nombre',   label: 'Supervisor',        className: 'px-4 py-2 whitespace-nowrap text-left' },
    { key: 'rfv_nombre',          label: 'RFV',               className: 'px-4 py-2 whitespace-nowrap text-left' },
    { key: 'zona_nombre',         label: 'Zona',              className: 'px-4 py-2 whitespace-nowrap text-left' },
    { key: 'brick_descripcion',   label: 'Brick',             className: 'px-4 py-2 whitespace-nowrap text-left' },
    { key: 'MesRegistro',         label: 'Mes/Año',           className: 'px-4 py-2 whitespace-nowrap text-left' },
    { key: 'porce_cobertura',     label: '% Cobertura',       className: 'px-4 py-2 whitespace-nowrap text-right' },
    { key: 'productoEsperado',    label: 'Cant. Esperada',    className: 'px-4 py-2 whitespace-nowrap text-right' },
    { key: 'monto_esperado',      label: 'Monto Esperado',    className: 'px-4 py-2 whitespace-nowrap text-right' },
    { key: 'productoFacturado',   label: 'Cant. Facturada',   className: 'px-4 py-2 whitespace-nowrap text-right' },
    { key: 'monto_facturado',     label: 'Monto Facturado',   className: 'px-4 py-2 whitespace-nowrap text-right' },
];

// Lógica para resúmenes y descargas
const summaries = ref([ /* ... */ ])

</script>

<template>
  <Head title="Reporte de Visitas" />
  <AppLayout :breadcrumbs="breadcrumbs" class="max-w-full overflow-x-hidden">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="flex flex-col md:flex-row gap-4 md:col-span-1">
            <div class="flex-1 min-w-0">
                <label class="text-sm font-medium text-gray-700 mb-1 block">Desde</label>
                <SimpleDatePicker v-model="fechaInicio"/>
            </div>
            <div class="flex-1 min-w-0">
                <label class="text-sm font-medium text-gray-700 mb-1 block">Hasta</label>
                <SimpleDatePicker v-model="fechaFin"/>
            </div>
        </div>
    
        <GlobalTable
          :columns="TableColumns"
          :rows="rowsVisitas"
          :total-records="rowsVisitas.length"  
          :current-page="pageA"                   
          :page-size="pageSizeA"                  
          :actions="[{ key: 'delete', handler: deleteMayorista }]"
          :subHeaderProps="{summaries: summaries}"
          showSubHeader
          autoAddActionsColumn
          @add="showMayoristaModal = true"
          @update:page="pageA = $event"
          @update:pageSize="pageSizeA = $event"
        />
      
    </div>
  </AppLayout>
</template>
