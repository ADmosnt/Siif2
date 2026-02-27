<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, onMounted, computed, watch } from 'vue'
import { type BreadcrumbItem } from '@/types'
import PanelDual from '@/components/Reportes/PanelDual.vue'
import type { PanelItem } from '@/components/Reportes/PanelDual.vue'
import { PedidoService, type Orden } from '@/services/pedidoService'
import GenericCombobox from '@/components/GenericCombobox.vue'
import SimpleDatePicker from '@/components/simpleDatePicker.vue'
import { type DateValue } from '@internationalized/date'
import axios from 'axios'

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF', href: '/dashboard' },
  { label: 'Pedidos' },
  { label: 'Seguimiento' },
]

interface SelectOption {
  value: string
  label: string
}

type OrdenConPanelItem = Orden & PanelItem

// Variables de fecha
const fechaInicio = ref<DateValue>();
const fechaFin = ref<DateValue>();

const ordenes = ref<OrdenConPanelItem[]>([])
const selectedOrden = ref<OrdenConPanelItem | null>(null)
const loading = ref(true)

const estatusOptions = ref<SelectOption[]>([])
const selectedEstatus = ref<SelectOption | null>(null)
const isLoadingEstatus = ref(false)
const estatusLoaded = ref(false)

const rfvOptions = ref<SelectOption[]>([])
const selectedRfv = ref<SelectOption | null>(null)
const isLoadingRfvs = ref(false)
const rfvsLoaded = ref(false)

const leftPanelConfig = {
  title: 'Órdenes',
  emptyMessage: 'No hay órdenes con los filtros seleccionados.'
}

const rightPanelConfig = {
  title: computed(() => 
    selectedOrden.value 
      ? `Orden #${selectedOrden.value.nOrden}` 
      : 'Seleccione una orden'
  ),
  detailTemplate: 'orden-detalle' as const,
  emptyMessage: 'Seleccione una orden para ver detalles.'
}

async function fetchData() {
  if (!estatusLoaded.value || !rfvsLoaded.value) return
  
  loading.value = true
  try {
    const params: any = {
        fecha_inicio: fechaInicio.value,
        fecha_fin: fechaFin.value
    }
    
    if (selectedEstatus.value) params.estatus_id = parseInt(selectedEstatus.value.value, 10)
    if (selectedRfv.value) params.rfv_id = selectedRfv.value.value
    
    const response = await PedidoService.getOrdenesFiltradas(params)
    const ordenesConDetalle: OrdenConPanelItem[] = []
    
    for (const orden of response.data) {
      try {
        const detalleCompleto = await PedidoService.getOrdenDetalle(orden.nOrden)
        ordenesConDetalle.push({
          ...detalleCompleto,
          id: detalleCompleto.nOrden.toString(),
          nombre: `Orden #${detalleCompleto.nOrden}`,
          subtext: `${detalleCompleto.cliente} - ${detalleCompleto.fecha}`
        })
      } catch (error) {
        ordenesConDetalle.push({
          ...orden,
          id: orden.nOrden.toString(),
          nombre: `Orden #${orden.nOrden}`,
          subtext: `${orden.cliente} - ${orden.fecha}`
        })
      }
    }
    
    ordenes.value = ordenesConDetalle
    selectedOrden.value = ordenes.value.length > 0 ? { ...ordenes.value[0] } : null
    
  } catch (error) {
    console.error('Error al cargar órdenes:', error)
    ordenes.value = []
    selectedOrden.value = null
  } finally {
    loading.value = false
  }
}

async function cargarEstatus() {
  isLoadingEstatus.value = true
  try {
    const estatusData = await PedidoService.getEstatusDisponibles()
    estatusOptions.value = estatusData.map((e: any) => ({
      value: e.idestatus.toString(),
      label: e.descripcion
    }))
    estatusLoaded.value = true
  } catch (error) {
    estatusLoaded.value = true
  } finally {
    isLoadingEstatus.value = false
  }
}

async function cargarRfvs() {
  isLoadingRfvs.value = true
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
    rfvsLoaded.value = true
  } finally {
    isLoadingRfvs.value = false
  }
}

// 4. Watcher actualizado para incluir las nuevas variables de fecha
watch([selectedEstatus, fechaInicio, fechaFin, selectedRfv], () => {
  fetchData()
})

onMounted(async () => {
  await cargarEstatus()
  await cargarRfvs()
  await fetchData()
})

const handleOrdenSelected = (item: PanelItem) => {
  const ordenCompleta = ordenes.value.find(o => o.nOrden.toString() === item.id.toString())
  if (ordenCompleta) selectedOrden.value = { ...ordenCompleta }
}

const ordenesFiltradas = computed(() => selectedOrden.value ? [selectedOrden.value] : [])
</script>

<template>
  <Head title="Seguimiento de Pedidos" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          Seguimiento de Pedidos
        </h2>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-end">
          
          <div class="flex flex-col">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estatus</label>
            <GenericCombobox
              v-model="selectedEstatus"
              :options="estatusOptions"
              :disabled="isLoadingEstatus"
              placeholder="Todos los estatus"
              :dynamic-search="false"
            />
          </div>

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

          <div class="lg:col-span-2 flex flex-col md:flex-row gap-4">
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
        Cargando órdenes...
      </div>

      <div v-else-if="ordenes.length === 0" class="text-center py-8 text-gray-600 dark:text-gray-400">
        No hay órdenes con los filtros seleccionados.
      </div>

      <PanelDual
        v-else
        :left-panel="{ ...leftPanelConfig, items: ordenes }"
        :right-panel="{ ...rightPanelConfig, items: ordenesFiltradas }"
        :selected-item="selectedOrden"
        @item-selected="handleOrdenSelected"
      />
    </div>
  </AppLayout>
</template>