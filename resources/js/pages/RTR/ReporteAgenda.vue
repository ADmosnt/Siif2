<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, onMounted, computed, watch } from 'vue'
import SimpleDatePicker from '@/components/simpleDatePicker.vue'
import { type DateValue } from '@internationalized/date'
import { type BreadcrumbItem } from '@/types'
import PanelDual from '@/components/Reportes/PanelDual.vue'
import type { PanelItem } from '@/components/Reportes/PanelDual.vue'
import { ActividadService, type Actividad, type ClienteConActividades } from '@/services/actividadService'
import GenericCombobox from '@/components/GenericCombobox.vue'
import axios from 'axios'

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF', href: '/dashboard' },
  { label: 'Visitas' },
]

interface SelectOption {
  value: string
  label: string
}

// 2. Nuevos estados para las fechas
const fechaInicio = ref<DateValue>();
const fechaFin = ref<DateValue>();

// Estado
const clientes = ref<ClienteConActividades[]>([])
const actividades = ref<Actividad[]>([])
const selectedCliente = ref<ClienteConActividades | null>(null)
const loading = ref(true)

const rfvOptions = ref<SelectOption[]>([])
const selectedRfv = ref<SelectOption | null>(null)
const isLoadingRfvs = ref(false)
const rfvsLoaded = ref(false) 

const rightPanelTitle = computed(() => 
  selectedCliente.value 
    ? `Actividades de ${selectedCliente.value.nombre}` 
    : 'Seleccione un cliente'
)

const leftPanelConfig = { title: 'Clientes', emptyMessage: 'No hay clientes con actividades.' }
const rightPanelConfig = {
  title: rightPanelTitle,
  detailTemplate: 'actividades-cliente' as const,
  emptyMessage: 'Seleccione un cliente para ver sus actividades.'
}

async function fetchData() {
  if (!rfvsLoaded.value) return
  
  loading.value = true
  try {
    const idRfvParam = selectedRfv.value?.value || undefined
    
    // Llamada directa usando los v-model de los pickers
    const actividadesData = await ActividadService.getActividadesPorFecha(
      fechaInicio.value ? fechaInicio.value.toString() : '', 
      fechaFin.value ? fechaFin.value.toString() : '', 
      idRfvParam
    )
    
    actividades.value = actividadesData
    clientes.value = ActividadService.agruparPorCliente(actividadesData)
    
    selectedCliente.value = clientes.value.length > 0 ? { ...clientes.value[0] } : null
  } catch (error) {
    console.error('Error al cargar actividades:', error)
    actividades.value = []
    clientes.value = []
    selectedCliente.value = null
  } finally {
    loading.value = false
  }
}

async function cargarRfvs() {
  isLoadingRfvs.value = true
  rfvsLoaded.value = false
  try {
    const response = await axios.get('/representantes-data') 
    const data = response.data.data.map((r: any) => ({
      value: r.id.toString(),
      label: r.nombre
    }))
    rfvOptions.value = data
    if (data.length === 1) selectedRfv.value = data[0]
    rfvsLoaded.value = true
  } catch (error) {
    console.error('Error cargando RFVs:', error)
    rfvsLoaded.value = true 
  } finally {
    isLoadingRfvs.value = false
  }
}

// Watchers para observar las dos fechas y el RFV
watch([fechaInicio, fechaFin, selectedRfv], () => {
  fetchData()
})

onMounted(async () => {
  await cargarRfvs()
  await fetchData()
})

const handleClienteSelected = (item: PanelItem) => {
  const clienteCompleto = clientes.value.find(c => c.id === item.id)
  selectedCliente.value = clienteCompleto ? { ...clienteCompleto } : (item as ClienteConActividades)
}

const actividadesFiltradas = computed(() => {
  if (!selectedCliente.value) return []
  return actividades.value.filter((actividad: Actividad) => 
    actividad.idCliente === selectedCliente.value!.id
  )
})
</script>

<template>
  <Head title="Visitas - Reporte de Agenda" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          Reporte de Actividades
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          
          <div class="flex flex-col">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vendedor</label>
            <GenericCombobox
              v-model="selectedRfv"
              :options="rfvOptions"
              :disabled="isLoadingRfvs || rfvOptions.length <= 1" 
              :placeholder="rfvOptions.length <= 1 ? 'Vendedor asignado' : 'Seleccionar Vendedor...'"
              :dynamic-search="true"
            />
          </div>

          <div class="md:col-span-2 flex flex-col md:flex-row gap-4">
            <div class="flex-1 min-w-0">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Desde</label>
                <SimpleDatePicker v-model="fechaInicio"/>
            </div>
            <div class="flex-1 min-w-0">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Hasta</label>
                <SimpleDatePicker v-model="fechaFin"/>
            </div>
          </div>

        </div>
      </div>

      <div v-if="loading" class="text-center py-8 text-gray-600 dark:text-gray-400">
        Cargando actividades...
      </div>

      <div v-else-if="clientes.length === 0" class="text-center py-8 text-gray-600 dark:text-gray-400">
        No hay clientes con actividades en el rango seleccionado.
      </div>

      <PanelDual
        v-else
        :left-panel="{ ...leftPanelConfig, items: clientes }"
        :right-panel="{ ...rightPanelConfig, items: actividadesFiltradas }"
        :selected-item="selectedCliente"
        @item-selected="handleClienteSelected"
      />
    </div>
  </AppLayout>
</template>