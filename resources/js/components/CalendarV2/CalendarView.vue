<!-- resources/js/components/CalendarV2/CalendarView.vue -->
<script setup lang="ts">
import { ref, computed, watch, PropType, onMounted } from "vue"
import { useVisitasStore, type Visita } from "@/stores/calendarStore"
import CitasAgendadasModal from './CitasAgendadasModal.vue'
import CalendarMonthComponent from "./CalendarMonthComponent.vue"
import GenericCombobox from '@/components/GenericCombobox.vue'
import Button from "../ui/button/Button.vue"
import axios from 'axios'
import GenericGlobalAlert from '@/components/GenericGlobalAlert.vue'

// =============================================================================
// INTERFACES Y TIPOS
// =============================================================================

interface SelectOption {
  value: string
  label: string
}

// =============================================================================
// PROPS Y EMITS
// =============================================================================

const props = defineProps({
  height: { type: String, default: "100%" },
  initialDate: { type: Date, default: () => new Date() },
  timeFormat: { type: String as PropType<"12h" | "24h">, default: "24h" },
  showControls: { type: Boolean, default: true },
  showEventButton: { type: Boolean, default: true },
  customClasses: {
    type: Object as PropType<{
      container?: string
      header?: string
      controls?: string
    }>,
    default: () => ({})
  },
  locale: { type: String, default: 'es' },
  firstDayOfWeek: { type: Number, default: 1 }
})

const emit = defineEmits<{
  (e: "date-change", date: Date): void
  (e: "visita-created", visita: Visita): void
  (e: "visita-updated", visita: Visita, newStart: string, newEnd: string): void
  (e: "visita-deleted", visitaId: string): void
  (e: "openVisitaModal", date: Date): void
  (e: "visita-click", visita: Visita): void
  (e: "visita-processed", visitaId: string): void
}>()

// =============================================================================
// STORES Y ESTADO
// =============================================================================

const visitasStore = useVisitasStore()
const currentDate = ref<Date>(props.initialDate)

// =============================================================================
// ESTADO DE FILTROS
// =============================================================================

const rfvOptionsFilter = ref<SelectOption[]>([])
const clienteOptionsFilter = ref<SelectOption[]>([])
const isLoadingRfvsFilter = ref(false)
const isLoadingClientesFilter = ref(false)
const selectedCliente = ref<SelectOption | null>(null)
const selectedRfv = ref<SelectOption | null>(null)
const rfvsLoaded = ref(false)

// =============================================================================
// CARGA MASIVA
// =============================================================================

const isUploading = ref(false)
const rutaPlantilla = '/tmp-planificaciones/plantilla-descarga'

async function handleCargaMasiva(event: Event) {
  const input = event.target as HTMLInputElement
  if (!input.files?.length) return

  const file = input.files[0]
  isUploading.value = true

  try {
    const formData = new FormData()
    formData.append('archivo', file)

    const response = await axios.post('/tmp-planificaciones/carga-masiva', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    const data = response.data
    let mensaje = data.message || 'Carga completada.'
    if (data.errores?.length > 0) {
      mensaje += '\n\nErrores:\n' + data.errores.slice(0, 5).join('\n')
      if (data.errores.length > 5) mensaje += `\n...y ${data.errores.length - 5} errores mas.`
    }
    alert(mensaje)

    await visitasStore.cargarVisitasDelMes(currentDate.value)
  } catch (err: any) {
    const msg = err.response?.data?.message || 'Error al procesar el archivo.'
    alert(msg)
  } finally {
    isUploading.value = false
    input.value = ''
  }
}

// =============================================================================
// REFERENCIAS DE MODALES
// =============================================================================

const dayModalRef = ref<InstanceType<typeof CitasAgendadasModal> | null>(null)
const dayModalDate = ref<Date | null>(null)
const dayModalFocusEventId = ref<string | null>(null)

// =============================================================================
// COMPUTADAS
// =============================================================================

const headerDate = computed(() => {
  const formatter = new Intl.DateTimeFormat(props.locale, { month: 'long', year: 'numeric' })
  return formatter.format(currentDate.value)
})

const clienteOptionsFromVisitas = computed(() => {
  const clientes = new Map<string, SelectOption>()
  
  visitasStore.visitas.forEach(visita => {
    const { cliente_id, nombre_cliente } = visita.metadata
    if (cliente_id && nombre_cliente && !clientes.has(cliente_id)) {
      clientes.set(cliente_id, { value: cliente_id, label: nombre_cliente })
    }
  })
  
  return Array.from(clientes.values())
})

const rfvOptionsFromVisitas = computed(() => {
  const rfvs = new Map<string, SelectOption>()
  
  visitasStore.visitas.forEach(visita => {
    const { rfv_id, nombre_rfv } = visita.metadata
    if (rfv_id && nombre_rfv && !rfvs.has(rfv_id)) {
      rfvs.set(rfv_id, { value: rfv_id, label: `${rfv_id} - ${nombre_rfv}` })
    }
  })
  
  return Array.from(rfvs.values())
})

const visitaFilter = (visita: Visita) => {
  const clienteMatch = selectedCliente.value 
    ? visita.metadata.cliente_id === selectedCliente.value.value 
    : true
    
  const rfvMatch = selectedRfv.value 
    ? visita.metadata.rfv_id === selectedRfv.value.value 
    : true
    
  return clienteMatch && rfvMatch
}

// Computed para placeholder dinámico del RFV
const rfvPlaceholder = computed(() => {
  if (isLoadingRfvsFilter.value) return 'Cargando vendedores...'
  if (rfvOptionsFilter.value.length === 1) return 'Vendedor asignado'
  return 'Filtrar por RFV…'
})

// Computed para deshabilitar el combobox de RFV
const isRfvComboboxDisabled = computed(() => {
  return isLoadingRfvsFilter.value || visitasStore.isLoading || rfvOptionsFilter.value.length <= 1
})

// =============================================================================
// CARGA DE DATOS PARA FILTROS
// =============================================================================

async function cargarRfvsParaFiltro() {
  isLoadingRfvsFilter.value = true
  rfvsLoaded.value = false
  
  try {
    const response = await axios.get(route('tmp_planificaciones.rfvs_disponibles'))
    rfvOptionsFilter.value = response.data

    // LÓGICA DE AUTO-SELECCIÓN: Si hay un solo RFV, seleccionarlo automáticamente
    if (rfvOptionsFilter.value.length === 1) {
      selectedRfv.value = rfvOptionsFilter.value[0]      
      // Cargar clientes para ese RFV automáticamente
      await cargarClientesPorRfvParaFiltro(selectedRfv.value.value)
    }
    
    rfvsLoaded.value = true
  } catch (error) {
    console.error('Error cargando RFVs para filtro:', error)
    rfvOptionsFilter.value = []
    rfvsLoaded.value = true
  } finally {
    isLoadingRfvsFilter.value = false
  }
}

async function cargarClientesPorRfvParaFiltro(rfvId: string, query: string = '') {
  if (!rfvId) {
    clienteOptionsFilter.value = []
    return
  }
  
  isLoadingClientesFilter.value = true
  try {
    
    const response = await axios.get(
      route('tmp_planificaciones.clientes_por_rfv', { rfvId }), 
      { params: { search: query, size: 25, page: 1 } }
    )
    clienteOptionsFilter.value = response.data
    
  } catch (error) {
    console.error('Error cargando clientes:', error)
    clienteOptionsFilter.value = []
  } finally {
    isLoadingClientesFilter.value = false
  }
}

async function onClienteDynamicSearchFilter(query: string) {
  if (!selectedRfv.value?.value) {
    console.log('No hay RFV seleccionado para búsqueda de clientes')
    return
  }
  await cargarClientesPorRfvParaFiltro(selectedRfv.value.value, query)
}

// =============================================================================
// GESTIÓN DE MODALES
// =============================================================================

function openGlobalAgenda() {
  dayModalDate.value = null
  dayModalFocusEventId.value = null
  dayModalRef.value?.open({ 
    mode: 'global',
    clienteOptions: clienteOptionsFromVisitas.value,
    rfvOptions: rfvOptionsFromVisitas.value
  })
}

function openDayList(date: Date) {
  dayModalDate.value = date
  dayModalFocusEventId.value = null
  dayModalRef.value?.open({ 
    mode: 'day', 
    date,
    clienteOptions: clienteOptionsFromVisitas.value,
    rfvOptions: rfvOptionsFromVisitas.value
  })
}

function onVisitaClick(visita: Visita) {
  dayModalDate.value = new Date(visita.start)
  dayModalFocusEventId.value = visita.id
  dayModalRef.value?.open({ 
    mode: 'day', 
    date: dayModalDate.value, 
    focusEventId: visita.id,
    clienteOptions: clienteOptionsFromVisitas.value,
    rfvOptions: rfvOptionsFromVisitas.value
  })
  emit('visita-click', visita)
}

function addNewFromDayModal(date?: Date) {
  const d = date ? new Date(date) : new Date()
  d.setHours(9, 0, 0, 0)
  const mode = date ? 'day' : 'global'
  dayModalRef.value?.open({ 
    mode, 
    date: d, 
    create: true,
    clienteOptions: clienteOptionsFromVisitas.value,
    rfvOptions: rfvOptionsFromVisitas.value
  })
}

function toggleNewEventForm() {
  const date = new Date()
  dayModalRef.value?.open({ 
    mode: 'global', 
    date, 
    create: true,
    clienteOptions: clienteOptionsFromVisitas.value,
    rfvOptions: rfvOptionsFromVisitas.value
  })
  emit("openVisitaModal", date)
}

// =============================================================================
// NAVEGACIÓN
// =============================================================================

async function previousPeriod() {
  const newDate = new Date(currentDate.value)
  newDate.setMonth(newDate.getMonth() - 1)
  currentDate.value = newDate
  
  try {
    await visitasStore.cargarVisitasDelMes(newDate)
    emit("date-change", newDate)
  } catch (error) {
    console.error('Error al navegar al mes anterior:', error)
  }
}

async function nextPeriod() {
  const newDate = new Date(currentDate.value)
  newDate.setMonth(newDate.getMonth() + 1)
  currentDate.value = newDate
  
  try {
    await visitasStore.cargarVisitasDelMes(newDate)
    emit("date-change", newDate)
  } catch (error) {
    console.error('Error al navegar al mes siguiente:', error)
  }
}

function handleDateClick(date: Date) {
  const defaultTime = new Date(date)
  defaultTime.setHours(9, 0, 0, 0)
  dayModalRef.value?.open({ 
    mode: 'day', 
    date: defaultTime,
    clienteOptions: clienteOptionsFromVisitas.value,
    rfvOptions: rfvOptionsFromVisitas.value
  })
}

// =============================================================================
// HANDLERS DE EVENTOS
// =============================================================================

function handleVisitaProcessed(visitaId: string) {
  emit('visita-processed', visitaId)
}

async function handleVisitaActualizada() {
  try {
    await visitasStore.cargarVisitasDelMes(currentDate.value)
  } catch (error) {
    console.error('Error al actualizar visitas:', error)
  }
}

async function handleRfvUpdate(rfv: SelectOption | null) {
  selectedRfv.value = rfv
  selectedCliente.value = null
  
  visitasStore.selectedRfvId = rfv ? rfv.value : null

  if (rfv) {
    await cargarClientesPorRfvParaFiltro(rfv.value)
  } else {
    clienteOptionsFilter.value = []
  }
  await visitasStore.cargarVisitasDelMes(currentDate.value)
}

// =============================================================================
// WATCHERS Y CICLO DE VIDA
// =============================================================================

watch(currentDate, (newDate) => {
  emit("date-change", newDate)
})

onMounted(async () => {
  try {    
    // Cargar RFVs primero y esperar
    await cargarRfvsParaFiltro()
    
    // Luego cargar visitas del mes
    await visitasStore.cargarVisitasDelMes(props.initialDate)
    
    console.log('Datos iniciales cargados correctamente')
  } catch (error) {
    console.error('Error al cargar datos iniciales:', error)
  }
})

// =============================================================================
// EXPOSICIÓN DE MÉTODOS
// =============================================================================

defineExpose({
  toggleNewEventForm,
  previousPeriod,
  nextPeriod,
  clienteOptions: clienteOptionsFromVisitas,
  rfvOptions: rfvOptionsFromVisitas
})
</script>

<template>
  <div class="relative">
    <GenericGlobalAlert />
    
    <div
      :class="[
        'vc-calendar grow w-full flex flex-col',
        'bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden',
        'border border-gray-200 dark:border-gray-700',
        customClasses?.container,
      ]"
      :style="{ height: height }"
    >
      <!-- Cabecera -->
      <div
        v-if="props.showControls"
        :class="[
          'p-4 flex flex-col md:flex-row justify-between items-center gap-4 text-base/7',
          customClasses?.header || 'vc-calendar-header'
        ]"
      >
        <slot name="navigation" 
              :current-date="currentDate" 
              :previous-period="previousPeriod" 
              :next-period="nextPeriod" 
              :header-date="headerDate">
          
          <div class="flex items-center gap-2">
            <button 
              @click="previousPeriod" 
              class="px-2 hover:bg-gray-100 rounded" 
              aria-label="Previous month"
              :disabled="visitasStore.isLoading"
            >
              ←
            </button>
            <h2 class="text-base/7 font-bold whitespace-nowrap">
              {{ headerDate }}
              <span v-if="visitasStore.isLoading" class="text-sm text-gray-500">(Cargando...)</span>
            </h2>
            <button 
              @click="nextPeriod" 
              class="px-2 hover:bg-gray-100 rounded" 
              aria-label="Next month"
              :disabled="visitasStore.isLoading"
            >
              →
            </button>
          </div>
        </slot>

        <slot name="controls" 
              :current-date="currentDate" 
              :toggle-new-event-form="toggleNewEventForm">
          
          <!-- Layout mejorado para los controles -->
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
            <Button 
              @click="openGlobalAgenda" 
              variant="secondary"
              :disabled="visitasStore.isLoading"
              class="w-full sm:w-auto"
            >
              Todas las agendas
            </Button>
            
            <!-- Combobox de RFV con auto-selección -->
            <div class="w-full sm:w-[200px] flex-shrink-0">
              <GenericCombobox
                :model-value="selectedRfv"
                @update:model-value="handleRfvUpdate"
                :options="rfvOptionsFilter"
                :disabled="isRfvComboboxDisabled"
                :placeholder="rfvPlaceholder"
                :dynamic-search="false"
              />
            </div>
            
            <!-- Combobox de Cliente -->
            <div class="w-full sm:w-[200px] flex-shrink-0">
              <GenericCombobox
                v-model:modelValue="selectedCliente"
                :options="clienteOptionsFilter"
                :disabled="!selectedRfv || isLoadingClientesFilter || visitasStore.isLoading"
                :placeholder="
                  !selectedRfv 
                    ? 'Seleccione RFV primero' 
                    : isLoadingClientesFilter 
                      ? 'Cargando clientes...' 
                      : 'Filtrar por cliente…'
                "
                :dynamic-search="true"
                :dynamic-loading="isLoadingClientesFilter"
                @dynamic-search="onClienteDynamicSearchFilter"
              />
            </div>
            
            <div v-if="props.showEventButton" class="flex flex-wrap gap-2">
              <a
                :href="rutaPlantilla"
                class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Descargar Formato
              </a>
              <label
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                :class="{ 'opacity-50 pointer-events-none': isUploading }"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                {{ isUploading ? 'Cargando...' : 'Carga Masiva' }}
                <input
                  type="file"
                  accept=".xlsx,.xls,.csv"
                  class="hidden"
                  @change="handleCargaMasiva"
                  :disabled="isUploading"
                />
              </label>
            </div>
          </div>
        </slot>
      </div>

      <!-- Contenido principal -->
      <div class="flex-1 overflow-auto">
        <CalendarMonthComponent
          :current-date="currentDate"
          :locale="props.locale"
          :first-day-of-week="props.firstDayOfWeek"
          :event-filter="visitaFilter"
          :cliente-options="clienteOptionsFromVisitas"
          :rfv-options="rfvOptionsFromVisitas"
          :is-loading="visitasStore.isLoading"
          @date-clicked="handleDateClick"
          @eventClick="onVisitaClick"
          @open-day-list="openDayList"
        />
      </div>

      <!-- Modal de visitas -->
      <CitasAgendadasModal
        ref="dayModalRef"
        :mode="'day'"
        :date="dayModalDate"
        :focus-event-id="dayModalFocusEventId"
        :event-filter="visitaFilter"
        :cliente-options="clienteOptionsFromVisitas"
        :rfv-options="rfvOptionsFromVisitas"
        @close="() => { dayModalDate = null; dayModalFocusEventId = null }"
        @addNew="addNewFromDayModal"
        @processed="handleVisitaProcessed"
        @visita-actualizada="handleVisitaActualizada"
      />

      <!-- Errores -->
      <div 
        v-if="visitasStore.error" 
        class="m-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded"
      >
        {{ visitasStore.error }}
        <button 
          @click="visitasStore.clearError()" 
          class="float-right font-bold"
        >
          ×
        </button>
      </div>
    </div>
  </div>
</template>