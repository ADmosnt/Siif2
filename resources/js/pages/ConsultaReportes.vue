<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import { type DateValue } from '@internationalized/date'
import { type BreadcrumbItem } from '@/types'
import AppLayout        from '@/layouts/AppLayout.vue'
import SimpleDatePicker from '@/components/simpleDatePicker.vue'
import SIIF_Info_cons01 from '@/components/SIIF_Info_cons01.vue'
import GenericCombobox, { type SelectOption } from '@/components/GenericCombobox.vue'
import Visitas from '@/components/Visitas.vue'
import Ordenes from '@/components/Ordenes.vue'
import type { RepresentanteSelect } from '@/types/agenda'
import { usePage } from '@inertiajs/vue3'

const page  = usePage()
const props = defineProps<{
  options: {
    visitas: {
      ranking:      SelectOption[]
      especialidad: SelectOption[]
      actividad:    SelectOption[]
      evento:       SelectOption[]
    }
    ordenes: {
      estatus: SelectOption[]
    }
  }
}>()
const user = computed(() => page.props.auth.user)

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF',                href: '/dashboard'        },
  { label: 'Consulta - Reportes', href: '/consulta-reporte' },
]

// ─ Sección activa ─
type Seccion = 'visitas' | 'ordenes'
const seccionActiva = ref<Seccion | null>(null)

function toggleSeccion(nombre: Seccion) {
  if (seccionActiva.value !== nombre) resultados.value = null
  seccionActiva.value = seccionActiva.value === nombre ? null : nombre
}

// ─ Fechas ─
const fechaInicio = ref<DateValue | undefined>()
const fechaFin    = ref<DateValue | undefined>()

// ─ Paginación ─
const ordenesPerPage = ref(15)
const visitasPerPage = ref(15)

// ─ RFV global ─
const rfvOptions      = ref<RepresentanteSelect[]>([])
const rfvLoading      = ref(false)
const rfvSeleccionado = ref<RepresentanteSelect | null>(null)

// ─ Filtros VISITAS ─
const zonaOptions  = ref<SelectOption[]>([])
const zonaLoading  = ref(false)
const brickOptions = ref<SelectOption[]>([])
const brickLoading = ref(false)

const visitaFiltros = ref({
  zona:         null as SelectOption | null,
  brick:        null as SelectOption | null,
  ranking:      null as SelectOption | null,
  especialidad: null as SelectOption | null,
  actividad:    null as SelectOption | null,
  evento:       null as SelectOption | null,
})

async function cargarRfvs() {
  if (rfvOptions.value.length > 0) return
  rfvLoading.value = true
  try {
    const response = await axios.get('/get-data-rfv')
    rfvOptions.value = response.data.data.map((r: any) => ({
      value:        r.id.toString(),
      label:        r.nombre,
      idFabricante: r.idFabricante,
      empresa:      r.empresa,
    }))
  } catch (error) {
    console.error('Error cargando RFVs:', error)
  } finally {
    rfvLoading.value = false
  }
}

const isRfvComboboxDisabled = computed(() =>
  rfvLoading.value || (user.value.idgrupo_persona === 'RFV' && rfvOptions.value.length === 1)
)

async function buscarZonas(term: string) {
  zonaLoading.value = true
  try {
    const { data } = await axios.get('/options/search', { params: { field: 'estado_visita', term } })
    zonaOptions.value = data.data ?? []
  } catch { zonaOptions.value = [] }
  finally  { zonaLoading.value = false }
}

async function buscarBricks(term: string) {
  if (!visitaFiltros.value.zona) { brickOptions.value = []; return }
  brickLoading.value = true
  try {
    const { data } = await axios.get('/options/search', {
      params: {
        field:   'ciudad_visita',
        term,
        filters: JSON.stringify({ estado: visitaFiltros.value.zona }),
      },
    })
    brickOptions.value = data.data ?? []
  } catch { brickOptions.value = [] }
  finally  { brickLoading.value = false }
}

function onZonaChange(val: SelectOption | null) {
  visitaFiltros.value.zona  = val
  visitaFiltros.value.brick = null
  brickOptions.value        = []
}

// ─ Filtros ORDENES ─
const mayoristaOptions = ref<SelectOption[]>([])
const mayoristaLoading = ref(false)

const ordenFiltros = ref({
  norden:    '',
  estatus:   null as SelectOption | null,
  mayorista: null as SelectOption | null,
})

async function buscarMayoristas(term: string) {
  mayoristaLoading.value = true
  try {
    const { data } = await axios.get('/options/search', { params: { field: 'mayorista', term } })
    mayoristaOptions.value = data.data ?? []
  } catch { mayoristaOptions.value = [] }
  finally  { mayoristaLoading.value = false }
}

// ─ Resultados y estado ─
const loading    = ref(false)
const resultados = ref<any>(null)
const error      = ref<string | null>(null)

// ─ Consulta central ─
async function consultar(page = 1) {
  if (!seccionActiva.value) return
  if (!fechaInicio.value || !fechaFin.value) {
    error.value = 'Debe seleccionar un rango de fechas.'
    return
  }

  loading.value = true
  error.value   = null

  const rfvPayload = rfvSeleccionado.value
    ? [{ idPersona: rfvSeleccionado.value.value }]
    : null

  const fi = fechaInicio.value.toString()
  const ff = fechaFin.value.toString()

  const payloads: Record<Seccion, object> = {
    visitas: {
      fechaInicio:  fi,
      fechaFin:     ff,
      rfv:          rfvPayload,
      zona:         visitaFiltros.value.zona        ? [{ idestado:          String(visitaFiltros.value.zona.value)        }] : null,
      brick:        visitaFiltros.value.brick       ? [{ idciudad:          String(visitaFiltros.value.brick.value)       }] : null,
      ranking:      visitaFiltros.value.ranking     ? [{ id:                String(visitaFiltros.value.ranking.value)     }] : null,
      especialidad: visitaFiltros.value.especialidad? [{ id:                String(visitaFiltros.value.especialidad.value)}] : null,
      actividad:    visitaFiltros.value.actividad   ? [{ value:             String(visitaFiltros.value.actividad.value)   }] : null,
      incidente:    visitaFiltros.value.evento      ? [{ idtipo_incidentes: String(visitaFiltros.value.evento.value)      }] : null,
      per_page:     visitasPerPage.value,
    },
    ordenes: {
      fechaInicio: fi,
      fechaFin:    ff,
      rfv:         rfvPayload,
      estatus:     ordenFiltros.value.estatus   ? [{ idestatus: String(ordenFiltros.value.estatus.value)   }] : null,
      mayorista:   ordenFiltros.value.mayorista ? [{ idPersona: ordenFiltros.value.mayorista.value         }] : null,
      per_page:    ordenesPerPage.value,
    },
  }

  const endpoints: Record<Seccion, string> = {
    visitas: '/consulta/visita',
    ordenes: '/consulta/ordenes',
  }

  try {
    const { data } = await axios.post(
      `${endpoints[seccionActiva.value]}?page=${page}`,
      payloads[seccionActiva.value],
    )

    const paginated = (rows: any[]) => ({
      data:         rows ?? [],
      total:        data.pagination?.total        ?? 0,
      current_page: data.pagination?.current_page ?? 1,
      per_page:     data.pagination?.per_page     ?? 15,
      last_page:    data.pagination?.last_page    ?? 1,
    })

    resultados.value = {
      visitas:  paginated(data.visitas  ?? []),
      ordenes:  paginated(data.ordenes  ?? []),
      productos: data.productos ?? [],
      totalUnidades: data.totalUnidades ?? 0,
      montoTotal:    data.montoTotal    ?? 0,
    }
  } catch (e: any) {
    error.value      = e?.response?.data?.error ?? e?.response?.data?.message ?? 'Ocurrió un error al realizar la consulta.'
    resultados.value = null
  } finally {
    loading.value = false
  }
}

const payloadVisitas = computed(() => ({
  fechaInicio:  fechaInicio.value?.toString() ?? '',
  fechaFin:     fechaFin.value?.toString()    ?? '',
  rfv:          rfvSeleccionado.value ? [{ idPersona: rfvSeleccionado.value.value }] : null,
  zona:         visitaFiltros.value.zona        ? [{ idestado: String(visitaFiltros.value.zona.value)        }] : null,
  brick:        visitaFiltros.value.brick       ? [{ idciudad: String(visitaFiltros.value.brick.value)       }] : null,
  ranking:      visitaFiltros.value.ranking     ? [{ id:       String(visitaFiltros.value.ranking.value)     }] : null,
  especialidad: visitaFiltros.value.especialidad? [{ id:       String(visitaFiltros.value.especialidad.value)}] : null,
  actividad:    visitaFiltros.value.actividad   ? [{ value:    String(visitaFiltros.value.actividad.value)   }] : null,
  incidente:    visitaFiltros.value.evento      ? [{ idtipo_incidentes: String(visitaFiltros.value.evento.value) }] : null,
}))

const payloadOrdenes = computed(() => ({
  fechaInicio: fechaInicio.value?.toString() ?? '',
  fechaFin:    fechaFin.value?.toString()    ?? '',
  rfv:         rfvSeleccionado.value ? [{ idPersona: rfvSeleccionado.value.value }] : null,
  estatus:     ordenFiltros.value.estatus   ? [{ idestatus: String(ordenFiltros.value.estatus.value)   }] : null,
  mayorista:   ordenFiltros.value.mayorista ? [{ idPersona: ordenFiltros.value.mayorista.value         }] : null,
}))

onMounted(async () => {
  await cargarRfvs()
  if (user.value.idgrupo_persona === 'RFV' && rfvOptions.value.length === 1) {
    rfvSeleccionado.value = rfvOptions.value[0]
  }
})

function handleVisitasPage(page: number) { consultar(page) }
function handleOrdenesPage(page: number) { consultar(page) }

function handleVisitasPageSize(size: number) { visitasPerPage.value = size; consultar(1) }
function handleOrdenesPageSize(size: number) { ordenesPerPage.value = size; consultar(1) }
</script>

<template>
  <Head title="Consulta - Reportes" />

  <AppLayout :breadcrumbs="breadcrumbs" class="max-w-full overflow-x-hidden">
    <div class="flex-1 flex flex-col p-4">
      <div class="grid grid-cols-1 gap-y-6">

        <!-- ── Fila superior ──────────────────────────────────────────────── -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

          <!-- Col 1: Fechas + RFV + Botón -->
          <div class="flex flex-col gap-4">
            <div class="grid grid-cols-2 gap-3">
              <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Desde</label>
                <SimpleDatePicker v-model="fechaInicio" />
              </div>
              <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Hasta</label>
                <SimpleDatePicker v-model="fechaFin" />
              </div>
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">RFV</label>
              <GenericCombobox
                v-model="rfvSeleccionado"
                :options="rfvOptions"
                :dynamic-loading="rfvLoading"
                :disabled="isRfvComboboxDisabled"
                :placeholder="user.idgrupo_persona === 'RFV' ? 'Vendedor asignado' : '-- Todos los RFV --'"
              />
            </div>

            <button
              class="w-full bg-[#63c00d] hover:bg-[#00aa39] text-white font-semibold py-2 px-4 rounded-md shadow transition-colors disabled:opacity-50"
              :disabled="!seccionActiva || loading"
              @click="consultar()"
            >
              {{ loading ? 'Consultando…' : 'CONSULTAR' }}
            </button>

            <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>
          </div>

          <!-- Col 2-3: Info card -->
          <div class="min-w-0 sm:col-span-2">
            <SIIF_Info_cons01
              message="Las consultas corresponden a reportes de visitas y órdenes.
Seleccione la sección deseada, configure los filtros y presione CONSULTAR.
Si no selecciona un filtro, la consulta será genérica para ese campo."
            />
          </div>
        </div>

        <!-- ── Botones de sección ─────────────────────────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
          <button
            v-for="sec in (['visitas', 'ordenes'] as const)"
            :key="sec"
            :class="[
              'w-full text-white font-semibold rounded-full px-6 py-2 shadow flex items-center justify-center gap-2 transition',
              seccionActiva === sec ? 'bg-[#014a99]' : 'bg-[#015ab6] hover:bg-[#014a99]'
            ]"
            @click="toggleSeccion(sec)"
          >
            {{ sec.toUpperCase() }}
            <i :class="seccionActiva === sec ? 'fas fa-eye-slash' : 'fas fa-eye'" />
          </button>
        </div>

        <!-- ── Filtros adicionales por sección ───────────────────────────── -->

        <!-- VISITAS -->
        <div v-if="seccionActiva === 'visitas'" class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Zona</label>
            <GenericCombobox
              :model-value="visitaFiltros.zona"
              :options="zonaOptions"
              :dynamic-search="true"
              :dynamic-loading="zonaLoading"
              placeholder="-- Zona --"
              @update:model-value="onZonaChange"
              @dynamic-search="buscarZonas"
            />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Ruta</label>
            <GenericCombobox
              v-model="visitaFiltros.brick"
              :options="brickOptions"
              :dynamic-search="true"
              :dynamic-loading="brickLoading"
              :disabled="!visitaFiltros.zona"
              placeholder="-- Ruta --"
              @dynamic-search="buscarBricks"
            />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Ranking</label>
            <GenericCombobox v-model="visitaFiltros.ranking" :options="options.visitas.ranking" placeholder="-- Ranking --" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Especialidad</label>
            <GenericCombobox v-model="visitaFiltros.especialidad" :options="options.visitas.especialidad" placeholder="-- Especialidad --" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Actividad</label>
            <GenericCombobox v-model="visitaFiltros.actividad" :options="options.visitas.actividad" placeholder="-- Actividad --" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Evento</label>
            <GenericCombobox v-model="visitaFiltros.evento" :options="options.visitas.evento" placeholder="-- Evento --" />
          </div>
        </div>

        <!-- ORDENES -->
        <div v-if="seccionActiva === 'ordenes'" class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Estatus</label>
            <GenericCombobox
              v-model="ordenFiltros.estatus"
              :options="options.ordenes.estatus"
              placeholder="-- Estatus --"
            />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Mayorista</label>
            <GenericCombobox
              v-model="ordenFiltros.mayorista"
              :options="mayoristaOptions"
              :dynamic-search="true"
              :dynamic-loading="mayoristaLoading"
              placeholder="-- Mayorista --"
              @dynamic-search="buscarMayoristas"
            />
          </div>
        </div>

        <!-- ── Resultados ─────────────────────────────────────────────────── -->
        <Visitas
          v-if="seccionActiva === 'visitas' && resultados"
          :visitas="resultados.visitas"
          :materiales="resultados.productos"
          :filtros-activos="payloadVisitas"
          @change-page="handleVisitasPage"
          @change-page-size="handleVisitasPageSize"
        />
        <Ordenes
          v-if="seccionActiva === 'ordenes' && resultados"
          :ordenes="resultados.ordenes"
          :productos="resultados.productos"
          :total-unidades="resultados.totalUnidades"
          :monto-total="resultados.montoTotal"
          :filtros-activos="payloadOrdenes"
          @change-page="handleOrdenesPage"
          @change-page-size="handleOrdenesPageSize"
        />
      </div>
    </div>
  </AppLayout>
</template>