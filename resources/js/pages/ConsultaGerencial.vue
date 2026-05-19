<!-- resources/js/pages/ConsultaGerencial.vue -->
<script setup lang="ts">
import { ref, computed, reactive, watch, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { getLocalTimeZone, type DateValue } from '@internationalized/date'
import { format } from 'date-fns'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import Button from "@/components/ui/button/Button.vue"
import ComboSelect from "@/components/ComboSelect.vue"
import GlobalTable from '@/components/GlobalTable.vue'
import SimpleDatePicker from '@/components/simpleDatePicker.vue'
import GpsModal from '@/components/GPS/GpsModal.vue'
import {Tooltip,TooltipContent,TooltipTrigger,} from '@/components/ui/tooltip'
import { type PaginatedData } from '@/types/pagination';

// =================================================================
// INTERFACES
// =================================================================
interface EstadisticaItem {
  MesRegistro: string
  porce_cobertura: number
  productoEsperado: number
  monto_esperado: number
  productoFacturado: number
  monto_facturado: number
  rfv_nombre?: string | null
  supervisor_nombre?: string | null
  zona_nombre?: string | null
  ruta_descripcion?: string | null
  supervisor?: { nombre_completo_razon_social: string } | null
  representante?: { nombre_completo_razon_social: string } | null
  zona?: { descripcion_zona: string } | null
  ruta?: { Descripcion: string } | null
}

interface FiltrosAplicados {
  supervisores?: string[]
  rfv?: string[]
  zonas?: string[]
  rutas?: string[]
  fechaIni?: string
  fechaFin?: string
}

interface Totales {
  total_producto_esperado:  number
  total_monto_esperado:     number
  total_producto_facturado: number
  total_monto_facturado:    number
}

interface Props {
  estadisticas: PaginatedData<EstadisticaItem>
  totales: Totales
  filtrosAplicados: FiltrosAplicados
}

interface SelectOption {
  value: string
  label: string
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF', href: '/dashboard' },
  { label: 'Consulta Gerencial' },
]

// =================================================================
// ESTADO DEL FORMULARIO
// =================================================================
const form = reactive({
  supervisores: props.filtrosAplicados.supervisores || [],
  rfv:          props.filtrosAplicados.rfv          || [],
  zonas:        props.filtrosAplicados.zonas         || [],
  rutas:        props.filtrosAplicados.rutas         || [],
})

const fechaInicio = ref<DateValue>()
const fechaFin    = ref<DateValue>()
const isGpsModalOpen = ref(false)

// =================================================================
//  ESTADO DE LOS COMBOS DINÁMICOS
// =================================================================

const zonaOptions     = ref<SelectOption[]>([])
const zonaLoading     = ref(false)
const zonaHasMore     = ref(false)
let   zonaPage        = 1


const rutaOptions     = ref<SelectOption[]>([])
const rutaLoading     = ref(false)
const rutaHasMore     = ref(false)
let   rutaPage        = 1

const supervisorOptions  = ref<SelectOption[]>([])
const supervisorLoading  = ref(false)
const supervisorHasMore  = ref(false)
let   supervisorPage     = 1

const rfvOptions     = ref<SelectOption[]>([])
const rfvLoading     = ref(false)
const rfvHasMore     = ref(false)
let   rfvPage        = 1

// =================================================================
// HELPERS PARA LLAMAR AL OptionsController
// =================================================================

async function fetchOptions(
  field: string,
  term: string,
  page: number,
  filters: Record<string, any> = {}
): Promise<{ data: SelectOption[]; has_more: boolean }> {
  const response = await axios.get('/options/search', {
    params: {
      field,
      term,
      page,
      filters: JSON.stringify(filters),
    },
  })
  return response.data
}

// =================================================================
// HANDLERS DE BÚSQUEDA DINÁMICA (emitidos por ComboSelect)
// =================================================================

// ── Zona ────────────────────────────────────────────────────────
async function onZonaSearch(term: string) {
  zonaPage    = 1
  zonaLoading.value = true
  try {
    const res = await fetchOptions('estado_visita', term, zonaPage)
    zonaOptions.value  = res.data
    zonaHasMore.value  = res.has_more
  } finally {
    zonaLoading.value = false
  }
}

// ── Ruta ─────────────────────────────────────────────────────────
async function onRutaSearch(term: string) {
  if (form.zonas.length === 0) return

  rutaPage    = 1
  rutaLoading.value = true
  try {
    const res = await fetchOptions('ciudad_visita', term, rutaPage, {
      estado: form.zonas[0],
    })
    rutaOptions.value  = res.data
    rutaHasMore.value  = res.has_more
  } finally {
    rutaLoading.value = false
  }
}

// ── Supervisor ───────────────────────────────────────────────────
async function onSupervisorSearch(term: string) {
  supervisorPage    = 1
  supervisorLoading.value = true
  try {
    const res = await fetchOptions('supervisor', term, supervisorPage)
    supervisorOptions.value  = res.data
    supervisorHasMore.value  = res.has_more
  } finally {
    supervisorLoading.value = false
  }
}

// ── RFV ──────────────────────────────────────────────────────────
async function onRfvSearch(term: string) {
  rfvPage    = 1
  rfvLoading.value = true
  try {
    const response = await axios.get(route('gerencial.representantes'), {
      params: { term, page: rfvPage },
    })
    const PaginatedData = response.data
    rfvOptions.value  = PaginatedData.data.map((r: any) => ({
      value: String(r.idPersona ?? r.id),
      label: r.nombre_completo_razon_social ?? r.nombre,
    }))
    rfvHasMore.value  = !!PaginatedData.next_page_url
  } finally {
    rfvLoading.value = false
  }
}

// =================================================================
// HIDRATACIÓN: tiene este nombre todo feo pero es nada más para que cuando pases de pagina no se pierdan los labels de los filtros
// =================================================================
onMounted(async () => {
  const f = props.filtrosAplicados

  if ((f as any).fechaIni) {
    const { parseDate } = await import('@internationalized/date')
    fechaInicio.value = parseDate((f as any).fechaIni)
  }
  if ((f as any).fechaFin) {
    const { parseDate } = await import('@internationalized/date')
    fechaFin.value = parseDate((f as any).fechaFin)
  }

  // Zonas
  if (f.zonas && f.zonas.length > 0) {
    zonaLoading.value = true
    try {
      const res = await fetchOptions('estado_visita', '', 1)
      zonaOptions.value = res.data
      zonaHasMore.value = res.has_more
    } finally {
      zonaLoading.value = false
    }
  }

  // Rutas
  if (f.rutas && f.rutas.length > 0 && f.zonas && f.zonas.length > 0) {
    rutaLoading.value = true
    try {
      const res = await fetchOptions('ciudad_visita', '', 1, { estado: f.zonas[0] })
      rutaOptions.value = res.data
      rutaHasMore.value = res.has_more
    } finally {
      rutaLoading.value = false
    }
  }

  // Supervisores
  if (f.supervisores && f.supervisores.length > 0) {
    supervisorLoading.value = true
    try {
      const res = await fetchOptions('supervisor', '', 1)
      supervisorOptions.value = res.data
      supervisorHasMore.value = res.has_more
    } finally {
      supervisorLoading.value = false
    }
  }

  // RFV
  if (f.rfv && f.rfv.length > 0) {
    rfvLoading.value = true
    try {
      const response = await axios.get(route('gerencial.representantes'), {
        params: { term: '', page: 1 },
      })
      const PaginatedData = response.data
      rfvOptions.value = PaginatedData.data.map((r: any) => ({
        value: String(r.idPersona ?? r.id),
        label: r.nombre_completo_razon_social ?? r.nombre,
      }))
      rfvHasMore.value = !!PaginatedData.next_page_url
    } finally {
      rfvLoading.value = false
    }
  }
})

// =================================================================
// CASCADA: limpiar rutas cuando el usuario cambia la zona
// =================================================================
let hydrationDone = false
onMounted(() => { setTimeout(() => { hydrationDone = true }, 100) })

watch(
  () => [...form.zonas],
  () => {
    if (!hydrationDone) return
    form.rutas        = []
    rutaOptions.value = []
  }
)

// =================================================================
// CONSULTA PRINCIPAL
// =================================================================

// Extrae el payload común de fechas + filtros
function buildPayload(extra: Record<string, any> = {}): Record<string, any> {
  const payload: any = { ...form, ...extra }
  if (fechaInicio.value) {
    payload.fechaIni = format(fechaInicio.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd')
  }
  if (fechaFin.value) {
    payload.fechaFin = format(fechaFin.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd')
  }
  return payload
}

function consultar() {
  router.get(route('gerencial.index'), buildPayload(), {
    preserveState:  true,
    preserveScroll: true,
    replace:        true,
  })
}

function handlePageSizeChange(newSize: string) {
  router.get(route('gerencial.index'), buildPayload({ size: newSize }), {
    preserveState:  true,
    preserveScroll: true,
    replace:        true,
  })
}

// =================================================================
// TABLA
// =================================================================
const rowsData = computed(() => {
  if (!props.estadisticas.data) return []
  return props.estadisticas.data.map((row: any) => ({
    ...row,
    supervisor_nombre: row.supervisor_nombre
      ?? row.supervisor?.nombre_completo_razon_social
      ?? '—',
    rfv_nombre: row.rfv_nombre
      ?? row.representante?.nombre_completo_razon_social
      ?? '—',
    zona_nombre: row.zona_nombre
      ?? row.zona?.descripcion_zona
      ?? '—',
    brick_descripcion: row.ruta_descripcion
      ?? row.ruta?.Descripcion
      ?? '—',
  }))
})

const TableColumns = [
  { key: 'supervisor_nombre',  label: 'Supervisor',      className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'rfv_nombre',         label: 'RFV',             className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'zona_nombre',        label: 'Zona',            className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'brick_descripcion',  label: 'Brick',           className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'MesRegistro',        label: 'Mes/Año',         className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'porce_cobertura',    label: '% Cobertura',     className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'productoEsperado',   label: 'Cant. Esperada',  className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'monto_esperado',     label: 'Monto Esperado',  className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'productoFacturado',  label: 'Cant. Facturada', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'monto_facturado',    label: 'Monto Facturado', className: 'px-4 py-2 whitespace-nowrap text-right' },
]

// Sumarios globales — vienen calculados desde el backend (toda la consulta,
// no solo la página actual). Se formatean con separador de miles.
const fmt = (n: number) =>
  new Intl.NumberFormat('es-VE', { maximumFractionDigits: 2 }).format(n ?? 0)

const summaries = computed(() => [
  { label: 'Cant. Esperada',   value: fmt(props.totales.total_producto_esperado)  },
  { label: 'Monto Esperado',   value: fmt(props.totales.total_monto_esperado)     },
  { label: 'Cant. Facturada',  value: fmt(props.totales.total_producto_facturado) },
  { label: 'Monto Facturado',  value: fmt(props.totales.total_monto_facturado)    },
])

// =================================================================
// EXPORTACIÓN
// =================================================================
const downloadReport = (type: 'pdf' | 'excel') => {
  const routeName = type === 'pdf' ? 'gerencial.export.pdf' : 'gerencial.export.excel'
  const params = new URLSearchParams()

  Object.entries(form).forEach(([key, value]) => {
    if (Array.isArray(value) && value.length > 0) {
      value.forEach(item => params.append(`${key}[]`, item))
    }
  })

  if (fechaInicio.value) {
    params.append('fechaIni', format(fechaInicio.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd'))
  }
  if (fechaFin.value) {
    params.append('fechaFin', format(fechaFin.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd'))
  }

  window.location.href = `${route(routeName)}?${params.toString()}`
}

const downloadPdf   = () => downloadReport('pdf')
const downloadExcel = () => downloadReport('excel')

// =================================================================
// GPS
// =================================================================
const formattedFechaInicio = computed(() =>
  fechaInicio.value ? format(fechaInicio.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd') : null
)
const formattedFechaFin = computed(() =>
  fechaFin.value ? format(fechaFin.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd') : null
)

const selectedRfvForGps = computed(() =>
  form.rfv && form.rfv.length === 1 ? form.rfv[0] : null
)

const isGpsButtonDisabled = computed(() =>
  !selectedRfvForGps.value || !formattedFechaInicio.value || !formattedFechaFin.value
)
</script>

<template>
  <Head title="Gerencial" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 min-w-0">

        <!-- RANGO DE FECHAS -->
        <div class="flex flex-col md:flex-row gap-4 md:col-span-1">
          <div class="flex-1 min-w-0">
            <label class="text-sm font-medium text-gray-700 mb-1 block">Desde</label>
            <SimpleDatePicker v-model="fechaInicio" />
          </div>
          <div class="flex-1 min-w-0">
            <label class="text-sm font-medium text-gray-700 mb-1 block">Hasta</label>
            <SimpleDatePicker v-model="fechaFin" />
          </div>
        </div>

        <!-- ZONA (searchEstadosVisita) -->
        <div class="min-w-0">
          <label class="text-sm font-medium text-gray-700 mb-1 block">Zona</label>
          <ComboSelect
            v-model="form.zonas"
            :options="zonaOptions"
            placeholder="Buscar zona..."
            :dynamic-search="true"
            :dynamic-loading="zonaLoading"
            :has-more="zonaHasMore"
            @dynamic-search="onZonaSearch"
          />
        </div>

        <!-- RUTA (searchCiudadesVisita) — deshabilitada hasta tener zona -->
        <div class="min-w-0">
          <label class="text-sm font-medium text-gray-700 mb-1 block">
            Ruta
            <span v-if="form.zonas.length === 0" class="text-xs text-gray-400 font-normal ml-1">
              (seleccione una zona primero)
            </span>
          </label>
          <ComboSelect
            v-model="form.rutas"
            :options="rutaOptions"
            placeholder="Buscar ruta..."
            :dynamic-search="true"
            :dynamic-loading="rutaLoading"
            :has-more="rutaHasMore"
            :disabled="form.zonas.length === 0"
            @dynamic-search="onRutaSearch"
          />
        </div>

        <!-- SUPERVISOR (searchSupervisores) -->
        <div class="min-w-0">
          <label class="text-sm font-medium text-gray-700 mb-1 block">Supervisor</label>
          <ComboSelect
            v-model="form.supervisores"
            :options="supervisorOptions"
            placeholder="Buscar supervisor..."
            :dynamic-search="true"
            :dynamic-loading="supervisorLoading"
            :has-more="supervisorHasMore"
            @dynamic-search="onSupervisorSearch"
          />
        </div>

        <!-- RFV (getRepresentantesData) -->
        <div class="min-w-0">
          <label class="text-sm font-medium text-gray-700 mb-1 block">RFV</label>
          <ComboSelect
            v-model="form.rfv"
            :options="rfvOptions"
            placeholder="Buscar RFV..."
            :dynamic-search="true"
            :dynamic-loading="rfvLoading"
            :has-more="rfvHasMore"
            @dynamic-search="onRfvSearch"
          />
        </div>

        <!-- BOTONES -->
        <div class="grid grid-cols-2 items-end min-w-0 space-x-2">
          <Button @click="consultar">Consultar</Button>

          <Tooltip :delay-duration="100">
            <TooltipTrigger as-child>
              <span class="inline-block w-full" :class="{ 'cursor-not-allowed': isGpsButtonDisabled }">
                <Button
                  class="w-full"
                  @click="isGpsModalOpen = true"
                  :disabled="isGpsButtonDisabled"
                >
                  GPS
                </Button>
              </span>
            </TooltipTrigger>
            <TooltipContent v-if="isGpsButtonDisabled">
              <p>Para activar, seleccione un (1) único RFV y un rango de fechas.</p>
            </TooltipContent>
          </Tooltip>
        </div>
      </div>

      <!-- TABLA -->
      <div class="rounded-b-lg shadow overflow-x-auto mt-4">
        <GlobalTable
          :columns="TableColumns"
          :rows="rowsData"
          :links="estadisticas.links"
          :total-records="estadisticas.total"
          :current-page="estadisticas.current_page"
          @update:pageSize="handlePageSizeChange"
          :page-size="String(estadisticas.per_page)"
          showSubHeader
          :subHeaderProps="{
            exportActions: [
              { key: 'pdf',   onClick: downloadPdf },
              { key: 'excel', onClick: downloadExcel }
            ],
            summaries: summaries
          }"
        />
      </div>
    </div>

    <GpsModal
      v-model="isGpsModalOpen"
      :rfv="selectedRfvForGps"
      :fecha-desde="formattedFechaInicio"
      :fecha-hasta="formattedFechaFin"
    />
  </AppLayout>
</template>