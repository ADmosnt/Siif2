/*
  Nombre: Facturas
  Proceso: Componente de consulta y visualización de reportes de facturas.
  Fecha creado: 14 de agosto del 2025
  Quien lo hizo: Bimodal - A.Lozada
  Ultima Modificacion:
  Ultima Modificacion por: 
*/

<script setup lang="ts">
import { ref, computed } from 'vue'
import GlobalTable from '@/components/GlobalTable.vue'
import Button from './ui/button/Button.vue'
import ComboSelect from './ComboSelect.vue'

function downloadPdf() { /* tu lógica */ }
function downloadExcel() { /* tu lógica */ }

/** Interfaz del registro de factura representado en la tabla/tarjetas. */
interface RowOrden {
  id: number
  numeroFactura: string
  rfv: string
  cliente: string
  fecha: string
  ciudad: string
  estado: string
  mayorista: string
  unidades: number
  valor: number
  estatus: string
  comentarios: string
  coordenadas: string
}

/** Datos de ejemplo.  */
const rowsFacturas = ref<RowOrden[]>([
  {
    id: 1,
    numeroFactura: 'F-001',
    rfv: 'RFV-001',
    cliente: 'Clínica Los Andes',
    fecha: '2025-07-05',
    ciudad: 'Mérida',
    estado: 'Mérida',
    mayorista: 'Droguería Andes',
    unidades: 120,
    valor: 5800,
    estatus: 'Pendiente',
    comentarios: 'En proceso de envío',
    coordenadas: '8.59, -71.14',
  },
  {
    id: 2,
    numeroFactura: 'F-002',
    rfv: 'RFV-002',
    cliente: 'Hospital Central',
    fecha: '2025-07-06',
    ciudad: 'Caracas',
    estado: 'Distrito Capital',
    mayorista: 'Droguería Central',
    unidades: 90,
    valor: 4100,
    estatus: 'Procesado',
    comentarios: 'Entregado sin novedad',
    coordenadas: '10.50, -66.91',
  },
])

// Filtros (placeholder)
const estatusFacturas = ref([
  { value: 'pendiente', label: 'Pendiente' },
  { value: 'procesado', label: 'Procesado' },
  { value: 'cancelado', label: 'Cancelado' },
])
const estatusFacturasSeleccionado = ref<string[]>([])

// Columnas (con clases responsivas)
const columnsFacturas = [
  { key: 'numeroFactura', label: 'N°',       className: 'px-4 py-2 whitespace-nowrap text-left w-24' },
  { key: 'rfv',           label: 'RFV',      className: 'px-4 py-2 whitespace-nowrap text-left w-28 hidden xs:table-cell' },
  { key: 'cliente',       label: 'Cliente',  className: 'px-4 py-2 whitespace-nowrap text-left max-w-[180px] truncate' },
  { key: 'fecha',         label: 'Fecha',    className: 'px-4 py-2 whitespace-nowrap text-right w-28' },
  { key: 'ciudad',        label: 'Ciudad',   className: 'px-4 py-2 whitespace-nowrap text-right hidden md:table-cell' },
  { key: 'estado',        label: 'Estado',   className: 'px-4 py-2 whitespace-nowrap text-right hidden lg:table-cell' },
  { key: 'unidades',      label: 'Unid.',    className: 'px-4 py-2 whitespace-nowrap text-right w-20 hidden sm:table-cell' },
  { key: 'valor',         label: 'Monto',    className: 'px-4 py-2 whitespace-nowrap text-right w-28' },
  { key: 'estatus',       label: 'Estatus',  className: 'px-4 py-2 whitespace-nowrap text-right hidden md:table-cell' },
]

// Paginación compartida
const pageFact = ref(1)
const pageSizeFact = ref('15') // GlobalTable espera string
const pageSizeNum = computed(() => parseInt(pageSizeFact.value, 10) || 10)
const totalFact = computed(() => rowsFacturas.value.length)
const totalPages = computed(() => Math.max(1, Math.ceil(totalFact.value / pageSizeNum.value)))

// Datos paginados para el modo móvil (tarjetas)
const rowsFacturasPage = computed(() => {
  const start = (pageFact.value - 1) * pageSizeNum.value
  return rowsFacturas.value.slice(start, start + pageSizeNum.value)
})

// Helpers UI (móvil)
function badgeClass(status: string) {
  const base = 'rounded-full text-xs px-2 py-0.5 font-medium'
  if (status.toLowerCase() === 'procesado') return base + ' bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-200'
  if (status.toLowerCase() === 'pendiente') return base + ' bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200'
  return base + ' bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200'
}

function formatCurrency(n: number) {
  try {
    return new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }).format(n)
  } catch {
    return `$${n.toFixed(2)}`
  }
}
function formatDate(s: string) {
  const d = new Date(s)
  return isNaN(d.getTime()) ? s : d.toLocaleDateString('es-VE')
}
function prevPage() { if (pageFact.value > 1) pageFact.value-- }
function nextPage() { if (pageFact.value < totalPages.value) pageFact.value++ }
</script>

<template>
  <!-- Filtros -->
  <div class="border rounded-lg p-4 mb-6">
    <h2 class="text-lg font-semibold mb-4">Reportes de facturas</h2>

    <!-- Grid responsivo en vez de flex para que se acomode mejor -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
      <ComboSelect
        v-model="estatusFacturasSeleccionado"
        :options="estatusFacturas"
        placeholder="Estatus"
      />
      <div class="sm:col-span-1">
        <Button class="w-full sm:w-auto bg-[#63c00d] text-white hover:bg-[#00aa39]">
          CONSULTAR <i class="fas fa-search ml-1"></i>
        </Button>
      </div>
    </div>
  </div>

  <!-- Desktop / tablet: tabla -->
  <div class="hidden sm:block rounded-b-lg shadow overflow-x-auto">
    <GlobalTable
      :columns="columnsFacturas"
      :rows="rowsFacturas"
      :total-records="totalFact"
      :current-page="pageFact"
      :page-size="pageSizeFact"
      showSubHeader
      :subHeaderProps="{
            exportActions: [
              { key: 'pdf',   onClick: downloadPdf },
              { key: 'excel', onClick: downloadExcel }
            ],
          }"
      @update:page="pageFact = $event"
      @update:pageSize="pageSizeFact = $event"
    />
  </div>

  <!-- Móvil: tarjetas -->
  <div class="sm:hidden space-y-3">
    <div
      v-for="row in rowsFacturasPage"
      :key="row.id"
      class="rounded-lg border border-neutral-200 dark:border-neutral-800 p-3 bg-white dark:bg-neutral-900"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="font-semibold">{{ row.numeroFactura }}</div>
        <span :class="badgeClass(row.estatus)">{{ row.estatus }}</span>
      </div>

      <div class="mt-2 text-sm grid grid-cols-2 gap-x-3 gap-y-1">
        <div class="text-neutral-500 dark:text-neutral-400">Cliente</div>
        <div class="text-right truncate">{{ row.cliente }}</div>

        <div class="text-neutral-500 dark:text-neutral-400">Fecha</div>
        <div class="text-right">{{ formatDate(row.fecha) }}</div>

        <div class="text-neutral-500 dark:text-neutral-400">Ciudad</div>
        <div class="text-right">{{ row.ciudad }}</div>

        <div class="text-neutral-500 dark:text-neutral-400">Monto</div>
        <div class="text-right font-medium">{{ formatCurrency(row.valor) }}</div>
      </div>
    </div>

    <!-- Paginador simple móvil -->
    <div class="flex items-center justify-between pt-2">
      <Button class="bg-neutral-200 dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
              :disabled="pageFact === 1"
              @click="prevPage">
        Anterior
      </Button>
      <div class="text-sm text-neutral-600 dark:text-neutral-300">
        Página {{ pageFact }} de {{ totalPages }}
      </div>
      <Button class="bg-neutral-200 dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
              :disabled="pageFact === totalPages"
              @click="nextPage">
        Siguiente
      </Button>
    </div>
  </div>
</template>
