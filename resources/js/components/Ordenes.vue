<script setup lang="ts">
import { ref, computed } from 'vue'
import GlobalTable from '@/components/GlobalTable.vue'
import { useFileDownload } from '@/composables/useFileDownload'

interface Producto {
  Cliente:          string
  Orden:            string | number
  Nombre:           string
  Mayorista:        string | null
  Solicitado:       number
  Monto_Solicitado: number
  Faltante:         number
  Monto_Faltante:   number
  Conciliado:       number
  Monto_Conciliado: number
  RFV:              string
}

interface PaginatedOrdenes {
  data:         any[]
  total:        number
  current_page: number
  per_page:     number
  last_page:    number
}

const props = defineProps<{
  ordenes:   PaginatedOrdenes
  productos: Producto[]
  totalUnidades: number
  montoTotal:    number
  filtrosActivos: Record<string, any>  
}>()

const fmt = new Intl.NumberFormat('es-VE', {
  minimumFractionDigits:  2,
  maximumFractionDigits:  2,
})

const emit = defineEmits<{
  'change-page': [page: number]
  'change-page-size': [size: number]
}>()

// ─── Paginación local productos ───────────────────────────────────────────────
const pageP        = ref(1)
const pageSizeP    = ref(15)
const totalP       = computed(() => props.productos.length)
const lastPageP    = computed(() => Math.max(1, Math.ceil(totalP.value / pageSizeP.value)))
const productosPage = computed(() => {
  const start = (pageP.value - 1) * pageSizeP.value
  return props.productos.slice(start, start + pageSizeP.value)
})

function onPageP(newPage: number) {
  pageP.value = Math.min(Math.max(1, newPage), lastPageP.value)
}

function onPageSizeP(newSize: string) {
  pageSizeP.value = parseInt(newSize, 10) || 15
  pageP.value     = 1
}

const { downloading, descargar: descargarArchivo } = useFileDownload()

function descargar(tabla: 'ordenes' | 'productos', tipo: 'excel' | 'pdf') {
  const params = new URLSearchParams({ tabla, tipo })
  Object.entries(props.filtrosActivos).forEach(([k, v]) => {
    if (v !== null && v !== undefined) {
      params.append(k, typeof v === 'object' ? JSON.stringify(v) : String(v))
    }
  })

  // El PDF siempre trae ambas tablas juntas; el Excel es por tabla.
  const filename = tipo === 'pdf'
    ? 'reporte_ordenes.pdf'
    : (tabla === 'productos' ? 'estadisticas_productos.xlsx' : 'reporte_ordenes.xlsx')

  descargarArchivo('/exportar/ordenes', params, filename)
}

// ─── Columnas ─────────────────────────────────────────────────────────────────
const columnsOrdenes = [
  { key: 'nOrden',     label: 'Nº Orden',   className: 'px-3 py-2 text-left w-24' },
  { key: 'rfv',    label: 'RFV',        className: 'px-3 py-2 text-left hidden sm:table-cell' },
  { key: 'cliente',    label: 'Cliente',    className: 'px-3 py-2 text-left max-w-[180px] truncate' },
  { key: 'fecha',      label: 'Fecha',      className: 'px-3 py-2 text-right w-28' },
  { key: 'totalOrden', label: 'Total',      className: 'px-3 py-2 text-right w-28' },
  { key: 'ciudad',     label: 'Ciudad',     className: 'px-3 py-2 text-right hidden md:table-cell' },
  { key: 'estado',     label: 'Estado',     className: 'px-3 py-2 text-right hidden lg:table-cell' },
  { key: 'mayoristas', label: 'Mayorista',  className: 'px-3 py-2 text-right hidden xl:table-cell' },
  { key: 'unidades',   label: 'Unidades',   className: 'px-3 py-2 text-right hidden sm:table-cell' },
  { key: 'estatus',    label: 'Estatus',    className: 'px-3 py-2 text-right hidden md:table-cell' },
  { key: 'comentario', label: 'Comentario', className: 'px-3 py-2 text-right hidden xl:table-cell' },
  { key: 'coordenadas_l',       label: 'Coord. Lat',   className: 'px-3 py-2 text-right hidden 2xl:table-cell' },
  { key: 'coordenadas_a',       label: 'Coord. Lng',   className: 'px-3 py-2 text-right hidden 2xl:table-cell' },
]

const columnsProductos = [
  { key: 'Orden',            label: 'Nº Orden',         className: 'px-3 py-2 text-left hidden sm:table-cell w-20' },
  { key: 'Cliente',          label: 'Cliente',          className: 'px-3 py-2 text-left hidden sm:table-cell' },
  { key: 'RFV',              label: 'RFV',              className: 'px-3 py-2 text-left hidden md:table-cell' },
  { key: 'Nombre',           label: 'Producto',        className: 'px-3 py-2 text-left max-w-[200px] truncate' },
  { key: 'Solicitado',       label: 'Solicitado',       className: 'px-3 py-2 text-right w-24' },
  { key: 'Monto_Solicitado', label: 'Total', className: 'px-3 py-2 text-right w-32' },
  { key: 'Mayorista',        label: 'Mayorista',        className: 'px-3 py-2 text-left hidden lg:table-cell' },
  { key: 'Conciliado',       label: 'Conciliado',       className: 'px-3 py-2 text-right hidden sm:table-cell' },
  { key: 'Faltante',         label: 'Faltante',         className: 'px-3 py-2 text-right hidden lg:table-cell' },
]
</script>

<template>
  <div
    v-if="downloading"
    class="flex items-center gap-2 text-sm text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg px-4 py-2 mb-4"
  >
    <div class="h-4 w-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
    <span>Generando archivo, esto puede tardar unos segundos…</span>
  </div>

  <div class="mt-6">
      <div class="flex flex-wrap gap-3 mb-3 px-1">
    <div class="flex items-center gap-2 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg px-4 py-2">
      <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Unidades totales:</span>
      <span class="text-sm font-bold text-blue-900 dark:text-blue-100">
        {{ totalUnidades.toLocaleString() }}
      </span>
    </div>
    <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg px-4 py-2">
      <span class="text-sm font-medium text-green-700 dark:text-green-300">Monto total:</span>
      <span class="text-sm font-bold text-green-900 dark:text-green-100">
        {{ fmt.format(montoTotal) }}
      </span>
    </div>
  </div>
    <h2 class="text-lg font-semibold mb-2 px-1">Reportes de órdenes</h2>
    <GlobalTable
      :columns="columnsOrdenes"
      :rows="ordenes.data"
      :total-records="ordenes.total"
      :current-page="ordenes.current_page"
      :page-size="String(ordenes.per_page)"
      showSubHeader
      :subHeaderProps="{ exportActions: [
        { key: 'excel', onClick: () => descargar('ordenes','excel') },
        { key: 'pdf', onClick: () => descargar('ordenes','pdf') },] }"
      @update:page="emit('change-page', $event)"
      @update:pageSize="emit('change-page-size', parseInt($event, 10))"
    />
  </div>

  <div class="mt-6">
    <h2 class="text-lg font-semibold mb-2 px-1">Estadísticas de productos</h2>
    <GlobalTable
      :columns="columnsProductos"
      :rows="productosPage"
      :total-records="totalP"
      :current-page="pageP"
      :page-size="String(pageSizeP)"
      showSubHeader
      :subHeaderProps="{ exportActions: [
        { key: 'excel', onClick: () => descargar('productos','excel') },
        { key: 'pdf', onClick: () => descargar('productos','pdf') },] }"
      @update:page="onPageP"
      @update:pageSize="onPageSizeP"
    />
  </div>
</template>