<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import GlobalTable from '@/components/GlobalTable.vue'
import Button from "@/components/ui/button/Button.vue";
import GlobalSelect from '@/components/GlobalSelect.vue';

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF', href: '/dashboard' },
  { label: 'Consulta Gerencial'},
]

interface Pedidos {
  idRfv: number;      
  nombre: string;     
  ranking: string;     
  actividad: string;  
  diasVisita: string;  
  horarios: string;    
  visitas: number;     
}

const rowsPedidos = ref<Pedidos[]>([])

const pageA     = ref(1)
const pageSizeA = ref('15')

const TableColumns = [
  { key: 'idRfv',      label: 'ID RFV',      className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'nombre',     label: 'Nombre',      className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'ranking',    label: 'Ranking',     className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'actividad',  label: 'Actividad',   className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'diasVisita', label: 'Días Visita', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'horarios',   label: 'Horarios',    className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'visitas',    label: 'Visitas',     className: 'px-4 py-2 whitespace-nowrap text-left' }, // Alineado a la derecha por ser un número
];

const Estatus = ref([{ id: 1, nombre: 'Empresa A' }, { id: 2, nombre: 'Empresa B' }])

const EstatusSeleccionado = ref()


function deleteData(row: Pedidos) {
  const idx = rowsPedidos.value.findIndex(r => r.codigo === row.idRfv)
  if (idx !== -1) rowsPedidos.value.splice(idx, 1)
}

</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">

      <div class="min-w-0 grid grid-cols-2 gap-x-4 order-last md:order-none md:row-start-2 md:col-start-3">
        <GlobalSelect                               
            v-model="EstatusSeleccionado"     
            :options="Estatus"               
            placeholder="Seleccione un Estatus"            
        />
        <Button class="w-full">Seleccionar</Button>
      </div>

      <div class="overflow-x-auto">
        <div class="overflow-x-auto mb-6">
          <GlobalTable
            :columns="TableColumns"
            :rows="rowsPedidos"
            :total-records="rowsPedidos.length"  
            :current-page="pageA"                   
            :page-size="pageSizeA"                  
            :actions="[{ key: 'delete', handler: deleteData }]"
            :actions-column-label="'Dias Visita - Horarios'"
            autoAddActionsColumn
            @update:page="pageA = $event"
            @update:pageSize="pageSizeA = $event"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

