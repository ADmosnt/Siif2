<script setup lang="ts">
import GlobalTable from '@/components/GlobalTable.vue'
import { ref, computed } from 'vue'
import { useFileDownload } from '@/composables/useFileDownload'

interface Material {
  Reporte:  number | string
  Material: string
  Cantidad: number
  Cliente:     string | null
  RFV:     string | null
}

interface PaginatedVisitas {
  data:         any[]
  total:        number
  current_page: number
  per_page:     number
  last_page:    number
}

const props = defineProps<{
  visitas:    PaginatedVisitas
  materiales: Material[]
  filtrosActivos: Record<string, any>
}>()

const emit = defineEmits<{
  'change-page': [page: number]
  'change-page-size': [size: number]
}>()

// ─── Paginación local materiales ──────────────────────────────────────────────
const pageM         = ref(1)
const pageSizeM     = ref(15)
const totalM        = computed(() => props.materiales.length)
const lastPageM     = computed(() => Math.max(1, Math.ceil(totalM.value / pageSizeM.value)))
const materialesPage = computed(() => {
  const start = (pageM.value - 1) * pageSizeM.value
  return props.materiales.slice(start, start + pageSizeM.value)
})

function onPageM(newPage: number) {
  pageM.value = Math.min(Math.max(1, newPage), lastPageM.value)
}

function onPageSizeM(newSize: string) {
  pageSizeM.value = parseInt(newSize, 10) || 15
  pageM.value     = 1
}

const { downloading, descargar: descargarArchivo } = useFileDownload()

function descargar(tabla: 'visitas' | 'muestras', tipo: 'excel' | 'pdf') {
  const params = new URLSearchParams({ tabla, tipo })

  // Serializar filtros — arrays y objetos como JSON
  Object.entries(props.filtrosActivos).forEach(([k, v]) => {
    if (v !== null && v !== undefined) {
      params.append(k, typeof v === 'object' ? JSON.stringify(v) : String(v))
    }
  })

  // El PDF siempre trae ambas tablas juntas; el Excel es por tabla.
  const filename = tipo === 'pdf'
    ? 'reporte_visitas.pdf'
    : (tabla === 'muestras' ? 'muestras_entregadas.xlsx' : 'reporte_visitas.xlsx')

  descargarArchivo('/exportar/visitas', params, filename)
}

const columnsVisitas = [
  { key: 'reporte',             label: 'Nº Rep.',      className: 'px-3 py-2 text-left w-20' },
  { key: 'rfv',                 label: 'RFV',          className: 'px-3 py-2 text-left hidden sm:table-cell' },
  { key: 'cliente',             label: 'Cliente',      className: 'px-3 py-2 text-left max-w-[180px] truncate' },
  { key: 'ciudad',              label: 'Ciudad',       className: 'px-3 py-2 text-right hidden lg:table-cell' },
  { key: 'estado',              label: 'Estado',       className: 'px-3 py-2 text-right hidden lg:table-cell' },
  { key: 'fecha',               label: 'Fecha',        className: 'px-3 py-2 text-right w-28' },
  { key: 'actividad',           label: 'Actividad',    className: 'px-3 py-2 text-right hidden xl:table-cell' },
  { key: 'especialidad', label: 'Especialidad', className: 'px-3 py-2 text-right hidden xl:table-cell' },
  { key: 'ranking',      label: 'Ranking',      className: 'px-3 py-2 text-right hidden 2xl:table-cell' },
  { key: 'observaciones',       label: 'Comentarios',  className: 'px-3 py-2 text-right hidden 2xl:table-cell' },
  { key: 'coordenadas_l',       label: 'Coord. Lat',   className: 'px-3 py-2 text-right hidden 2xl:table-cell' },
  { key: 'coordenadas_a',       label: 'Coord. Lng',   className: 'px-3 py-2 text-right hidden 2xl:table-cell' },
]

const columnsMuestras = [
  { key: 'Reporte',  label: 'Nº Rep.',  className: 'px-3 py-2 text-left w-24' },
  { key: 'Cliente',     label: 'Cliente',     className: 'px-3 py-2 text-right hidden sm:table-cell' },
  { key: 'RFV',              label: 'RFV',              className: 'px-3 py-2 text-left hidden md:table-cell' },
  { key: 'Material', label: 'Producto', className: 'px-3 py-2 text-left max-w-[200px] truncate' },
  { key: 'Cantidad', label: 'Cantidad', className: 'px-3 py-2 text-right w-24' },
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
    <h2 class="text-lg font-semibold mb-2 px-1">Reportes de visitas</h2>
    <GlobalTable
      :columns="columnsVisitas"
      :rows="visitas.data"
      :total-records="visitas.total"
      :current-page="visitas.current_page"
      :page-size="String(visitas.per_page)"
      showSubHeader
      :subHeaderProps="{  exportActions: [
        { key: 'excel', onClick: () => descargar('visitas', 'excel') },
        { key: 'pdf',   onClick: () => descargar('visitas', 'pdf')   },]}"
      @update:page="emit('change-page', $event)"
      @update:pageSize="emit('change-page-size', parseInt($event, 10))"
    />
  </div>

  <div class="mt-6">
    <h2 class="text-lg font-semibold mb-2 px-1">Muestras entregadas</h2>
    <GlobalTable
      :columns="columnsMuestras"
      :rows="materialesPage"
      :total-records="totalM"
      :current-page="pageM"
      :page-size="String(pageSizeM)"
      showSubHeader
      :subHeaderProps="{   exportActions: [
        { key: 'excel', onClick: () => descargar('muestras', 'excel') },
        { key: 'pdf',   onClick: () => descargar('muestras', 'pdf')   },]}"
      @update:page="onPageM"
      @update:pageSize="onPageSizeM"
    />
  </div>
</template>