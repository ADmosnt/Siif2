<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import GlobalTable from '@/components/GlobalTable.vue'
import GenericCombobox from '@/components/GenericCombobox.vue'
import { AgendaService } from '@/services/agendaService'
import axios from 'axios'
import type {
  AgendaPageProps,
  ClienteAgenda,
  DiaVisita,
  Horario,
  RepresentanteSelect,
  SelectOption
} from '@/types/agenda'

const page = usePage<AgendaPageProps>()
const props = computed(() => page.props)
const user = computed(() => props.value.auth.user)

// Estado principal
const selectedRfv = ref<RepresentanteSelect | null>(null)
const selectedCliente = ref<SelectOption | null>(null)
const rfvOptions = ref<RepresentanteSelect[]>([])
const clienteOptions = ref<SelectOption[]>([])
const isLoadingRfvs = ref(false)
const isLoadingClientes = ref(false)
const loading = ref(false)

// Flag para evitar sincronización circular
const isInternalUpdate = ref(false)

const TableColumns = [
  { key: 'idRfv',      label: 'ID RFV',      className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'nombre',     label: 'Nombre',      className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'ranking',    label: 'Ranking',     className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'actividad',  label: 'Actividad',   className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'diasVisita', label: 'Días Visita', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'horarios',   label: 'Horarios',    className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'visitas',    label: 'Visitas',     className: 'px-4 py-2 whitespace-nowrap text-right' },
]

const breadcrumbs = [
  { label: 'Real Time Report (RTR)' },
  { label: 'Lista de Clientes' },
]

const rowsData = computed(() => {
  const clientes = props.value.clientes?.data
  if (!Array.isArray(clientes)) return []

  return clientes.map((cliente: ClienteAgenda) => ({
    id: cliente.id,
    idRfv: cliente.idRFV,
    nombre: cliente.nombre,
    ranking: cliente.ranking || 'N/A',
    actividad: cliente.actividad || 'N/A',
    diasVisita: Array.isArray(cliente.dias_visita)
      ? cliente.dias_visita.map((dia: DiaVisita) => dia.descripcion).join(', ')
      : 'N/A',
    horarios: Array.isArray(cliente.horarios)
      ? cliente.horarios.map((horario: Horario) => horario.descripcion).join(', ')
      : 'N/A',
    visitas: `${cliente.visitado || 0} / ${cliente.frecuencia || 0}`
  }))
})

const summaries = computed(() => {
  const stats = props.value.estadisticas || {}

  const activeCompany = props.value.selectedFabricante
  if (user.value.idgrupo_persona === 'SIIF' && !selectedRfv.value && !activeCompany) {
    return []
  }

  return [
    {
      label: 'Visitas del Ciclo',
      value: stats.ciclo || 0,
      className: 'text-blue-600 font-bold'
    },
    {
      label: 'Días hábiles del Ciclo',
      value: stats.dhabiles || 0,
      className: 'text-green-600 font-bold'
    },
    {
      label: 'Visitas Diarias',
      value: stats.visitasDiarias ? stats.visitasDiarias.toFixed(2) : '0.00',
      className: 'text-purple-600 font-bold'
    },
    {
      label: 'Cobertura',
      value: `${(stats.cobertura || 0).toFixed(2)}%`,
      className: (stats.cobertura || 0) >= 80 ? 'text-green-600 font-bold' : 'text-red-600 font-bold'
    }
  ]
})

const paginationData = computed(() => {
  const clientes = props.value.clientes
  return {
    currentPage: clientes?.meta?.current_page || 1,
    pageSize: String(clientes?.meta?.per_page || 15),
    totalRecords: clientes?.meta?.total || 0,
    links: clientes?.links || []
  }
})

const rfvPlaceholder = computed(() => {
  if (isLoadingRfvs.value) return 'Cargando RFVs...'
  if (user.value.idgrupo_persona === 'RFV') return 'Vendedor asignado'
  return 'Seleccionar representante...'
})

const isRfvComboboxDisabled = computed(() => {
  return isLoadingRfvs.value || (user.value.idgrupo_persona === 'RFV' && rfvOptions.value.length === 1)
})

const clientePlaceholder = computed(() => {
  if (!selectedRfv.value) return 'Primero seleccione un RFV'
  if (isLoadingClientes.value) return 'Cargando clientes...'
  return 'Buscar cliente...'
})

const isClienteComboboxDisabled = computed(() => {
  return !selectedRfv.value || isLoadingClientes.value
})

const isExcelDisabled = computed(() => {
  if (user.value.idgrupo_persona === 'SIIF') {
    return !selectedRfv.value || loading.value
  }
  return loading.value
})

const mensajeInformativo = computed(() => {
  const rol = user.value.idgrupo_persona
  if (rol === 'SIIF' && !selectedRfv.value) {
    return 'Seleccione un representante de ventas para ver sus clientes y estadísticas.'
  }
  return null
})

/**
 * Carga inicial de RFVs disponibles según rol
 */
async function cargarRfvs() {
  if (rfvOptions.value.length > 0) return

  isLoadingRfvs.value = true
  try {
    const response = await axios.get('/representantes-data')
    rfvOptions.value = response.data.data.map((r: any) => ({
      value: r.id.toString(),
      label: r.nombre,
      idFabricante: r.idFabricante,
      empresa: r.empresa
    }))

    // Sincronizar con filtros de URL tras cargar opciones
    sincronizarConUrl()
  } catch (error) {
    console.error('Error cargando RFVs:', error)
  } finally {
    isLoadingRfvs.value = false
  }
}

/**
 * Carga clientes filtrados por RFV seleccionado
 */
async function cargarClientes(query: string = '') {
  if (!selectedRfv.value?.value) {
    clienteOptions.value = []
    return
  }

  isLoadingClientes.value = true
  try {
    const response = await axios.get('/agenda-clientes-list', {
      params: {
        idRfv: selectedRfv.value.value,
        search: query,
        size: 25
      }
    })
    clienteOptions.value = response.data || []
  } catch (error) {
    console.error('Error cargando clientes:', error)
    clienteOptions.value = []
  } finally {
    isLoadingClientes.value = false
  }
}

/**
 * Sincroniza estado local con parámetros de URL
 * Se ejecuta solo cuando se cargan las opciones o hay cambios en filtros del servidor
 */
async function sincronizarConUrl() {
  const filtros = props.value.filtros
  if (!filtros) return

  isInternalUpdate.value = true

  // Sincronizar RFV
  if (filtros.idRfv && rfvOptions.value.length > 0) {
    const rfvEncontrado = rfvOptions.value.find(r => r.value === String(filtros.idRfv))
    if (rfvEncontrado && selectedRfv.value?.value !== rfvEncontrado.value) {
      selectedRfv.value = rfvEncontrado
      await cargarClientes()
    }
  }

  // Sincronizar Cliente
  if (filtros.idCliente && clienteOptions.value.length > 0) {
    const clienteEncontrado = clienteOptions.value.find(c => c.value === String(filtros.idCliente))
    if (clienteEncontrado && selectedCliente.value?.value !== clienteEncontrado.value) {
      selectedCliente.value = clienteEncontrado
    }
  }

  isInternalUpdate.value = false
}

/**
 * Handler para cambio de RFV por el usuario
 */
const handleRfvChange = async (rfv: RepresentanteSelect | null) => {
  if (isInternalUpdate.value) return

  selectedRfv.value = rfv
  selectedCliente.value = null

  if (rfv) {
    await cargarClientes()
  } else {
    clienteOptions.value = []
  }

  AgendaService.actualizarFiltros({
    ...props.value.filtros,
    idRfv: rfv?.value || '',
    idCliente: '',
    page: 1
  })
}

/**
 * Handler para cambio de Cliente por el usuario
 */
const handleClienteChange = (cliente: SelectOption | null) => {
  if (isInternalUpdate.value) return

  selectedCliente.value = cliente

  AgendaService.actualizarFiltros({
    ...props.value.filtros,
    idCliente: cliente?.value || '',
    page: 1
  })
}

/**
 * Búsqueda dinámica de clientes con debounce implícito
 */
async function onClienteDynamicSearch(query: string) {
  if (!selectedRfv.value?.value) return
  await cargarClientes(query)
}

const handlePageChange = (page: number) => {
  AgendaService.actualizarFiltros({
    ...props.value.filtros,
    idRfv: selectedRfv.value?.value || props.value.filtros?.idRfv || '',
    idCliente: selectedCliente.value?.value || props.value.filtros?.idCliente || '',
    page: page
  })
}

const handlePageSizeChange = (newSize: string) => {
  AgendaService.actualizarFiltros({
    ...props.value.filtros,
    size: newSize,
    page: 1
  })
}

const downloadExcel = () => {
  loading.value = true

  // GRT/SUP: exportar datos generales sin filtro de RFV
  const idRfvExport = ['GRT', 'SUP'].includes(user.value.idgrupo_persona)
    ? undefined
    : selectedRfv.value?.value

  AgendaService.exportarExcel(idRfvExport, selectedCliente.value?.value)
    .catch((err) => {
      console.error('Error al exportar:', err)
      alert('Error al exportar los datos. Por favor, inténtelo nuevamente.')
    })
    .finally(() => {
      loading.value = false
    })
}

const reiniciarFiltros = () => {
  // RFV mantiene su selección automática
  if (user.value.idgrupo_persona === 'RFV' && rfvOptions.value.length === 1) {
    selectedRfv.value = rfvOptions.value[0]
  } else {
    selectedRfv.value = null
  }

  selectedCliente.value = null
  clienteOptions.value = []
  AgendaService.reiniciarFiltros()
}

onMounted(async () => {
  await cargarRfvs()

  // Setup inicial para RFV: auto-seleccionar si solo tiene un RFV.
  // No se recarga la página con AgendaService.actualizarFiltros: el
  // backend ya scopea "clientes" y "estadisticas" al RFV logueado sin
  // depender del query string, así que esa recarga era redundante con
  // la carga inicial y el navegador la cancelaba (NS_BINDING_ABORTED),
  // lo que el interceptor de axios mostraba como "Error de conexión".
  if (user.value.idgrupo_persona === 'RFV' &&
      rfvOptions.value.length === 1 &&
      !props.value.filtros?.idRfv) {
    selectedRfv.value = rfvOptions.value[0]
    await cargarClientes()
  }
})

/**
 * Watch simplificado: solo sincroniza cuando los filtros del servidor cambian
 * y no coinciden con el estado local (ej: navegación con botón atrás)
 */
watch(
  () => props.value.filtros,
  async (newFiltros, oldFiltros) => {
    // Ignorar cambios de página solamente
    if (newFiltros?.page !== oldFiltros?.page &&
        newFiltros?.idRfv === oldFiltros?.idRfv &&
        newFiltros?.idCliente === oldFiltros?.idCliente) {
      return
    }

    // Sincronizar si hay diferencias reales en filtros
    if (newFiltros?.idRfv !== selectedRfv.value?.value ||
        newFiltros?.idCliente !== selectedCliente.value?.value) {
      await sincronizarConUrl()
    }
  },
  { deep: true }
)
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">

      <!-- Estadísticas -->
      <div v-if="summaries.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="(stat, index) in summaries"
          :key="index"
          class="bg-card text-card-foreground rounded-lg border shadow-sm p-4 border-l-4 border-l-blue-500"
        >
          <p class="text-sm text-muted-foreground font-medium">{{ stat.label }}</p>
          <p :class="`text-2xl font-bold mt-1 ${stat.className}`">{{ stat.value }}</p>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-card text-card-foreground rounded-lg border shadow-sm p-4">
        <div class="flex flex-col md:flex-row items-end gap-4">

          <div class="w-full md:w-64">
            <label class="block text-sm font-medium mb-1.5 opacity-80">
              Representante de Ventas
            </label>
            <GenericCombobox
              v-model="selectedRfv"
              :options="rfvOptions"
              :disabled="isRfvComboboxDisabled"
              :placeholder="rfvPlaceholder"
              @update:model-value="handleRfvChange"
            />
          </div>

          <div class="w-full md:w-64">
            <label class="block text-sm font-medium mb-1.5 opacity-80">
              Cliente
            </label>
            <GenericCombobox
              v-model="selectedCliente"
              :options="clienteOptions"
              :disabled="isClienteComboboxDisabled"
              :placeholder="clientePlaceholder"
              :dynamic-search="true"
              :dynamic-loading="isLoadingClientes"
              @update:model-value="handleClienteChange"
              @dynamic-search="onClienteDynamicSearch"
            />
          </div>

          <div class="w-full md:w-auto">
            <button
              @click="downloadExcel"
              :disabled="isExcelDisabled"
              :class="[
                'px-4 py-2 h-[40px] text-white rounded-md transition-all text-sm font-medium shadow-sm flex items-center justify-center gap-2',
                isExcelDisabled
                  ? 'bg-green-600/50 cursor-not-allowed opacity-50'
                  : 'bg-green-600 hover:bg-green-700 active:scale-95'
              ]"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              {{ loading ? 'Exportando...' : 'Exportar Excel' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Mensaje informativo -->
      <div v-if="mensajeInformativo" class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
        <p class="text-sm text-blue-700">{{ mensajeInformativo }}</p>
      </div>

      <!-- Tabla -->
      <div class="rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden">
        <GlobalTable
          :columns="TableColumns"
          :rows="rowsData"
          :total-records="paginationData.totalRecords"
          :current-page="paginationData.currentPage"
          :page-size="paginationData.pageSize"
          :links="paginationData.links"
          :loading="loading"
          @update:page="handlePageChange"
          @update:pageSize="handlePageSizeChange"
          :showSubHeader="false"
        />
      </div>
    </div>
  </AppLayout>
</template>
