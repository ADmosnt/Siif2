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

const props = defineProps<{
  user_role: string
}>()

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

const fechaInicio = ref<DateValue>()
const fechaFin = ref<DateValue>()

const ordenes = ref<OrdenConPanelItem[]>([])
const selectedOrden = ref<OrdenConPanelItem | null>(null)
const loading = ref(true)
const updatingStatus = ref(false)

const estatusOptions = ref<SelectOption[]>([])
const selectedEstatus = ref<SelectOption | null>(null)
const isLoadingEstatus = ref(false)
const estatusLoaded = ref(false)

const rfvOptions = ref<SelectOption[]>([])
const selectedRfv = ref<SelectOption | null>(null)
const isLoadingRfvs = ref(false)
const rfvsLoaded = ref(false)

const puedeModificarEstatus = computed(() =>
  ['SIIF', 'GRT', 'SUP'].includes(props.user_role)
)

const leftPanelConfig = {
  title: 'Ordenes',
  emptyMessage: 'No hay ordenes con los filtros seleccionados.'
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
      } catch {
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
  } catch {
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
  } catch {
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
  } catch {
    rfvsLoaded.value = true
  } finally {
    isLoadingRfvs.value = false
  }
}

async function cambiarEstatus(ordenId: number, nuevoEstatus: number, descripcion: string) {
  if (!puedeModificarEstatus.value || updatingStatus.value) return

  updatingStatus.value = true
  try {
    await PedidoService.actualizarEstatus(ordenId, nuevoEstatus)

    if (selectedOrden.value && selectedOrden.value.nOrden === ordenId) {
      selectedOrden.value = { ...selectedOrden.value, estatus: descripcion }
    }

    const idx = ordenes.value.findIndex(o => o.nOrden === ordenId)
    if (idx !== -1) {
      ordenes.value[idx] = { ...ordenes.value[idx], estatus: descripcion }
    }
  } catch (err) {
    console.error('Error al cambiar estatus:', err)
  } finally {
    updatingStatus.value = false
  }
}

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
      <!-- Filtros -->
      <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Filtros</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">

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

          <div class="flex flex-col">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Desde</label>
            <SimpleDatePicker v-model="fechaInicio"/>
          </div>
          <div class="flex flex-col">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hasta</label>
            <SimpleDatePicker v-model="fechaFin"/>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-8 text-gray-600 dark:text-gray-400">
        Cargando ordenes...
      </div>

      <div v-else-if="ordenes.length === 0" class="text-center py-8 text-gray-600 dark:text-gray-400">
        No hay ordenes con los filtros seleccionados.
      </div>

      <template v-else>
        <PanelDual
          :left-panel="{ ...leftPanelConfig, items: ordenes }"
          :right-panel="{ ...rightPanelConfig, items: ordenesFiltradas }"
          :selected-item="selectedOrden"
          @item-selected="handleOrdenSelected"
        />

        <!-- Acciones de estatus (solo SIIF/GRT/SUP) -->
        <div
          v-if="puedeModificarEstatus && selectedOrden"
          class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
            Cambiar Estatus - Orden #{{ selectedOrden.nOrden }}
          </h3>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="opcion in estatusOptions"
              :key="opcion.value"
              @click="cambiarEstatus(selectedOrden!.nOrden, parseInt(opcion.value), opcion.label)"
              :disabled="updatingStatus || selectedOrden?.estatus === opcion.label"
              class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :class="selectedOrden?.estatus === opcion.label
                ? 'bg-blue-600 text-white border-blue-600'
                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600'"
            >
              {{ opcion.label }}
              <span v-if="selectedOrden?.estatus === opcion.label" class="ml-1">(actual)</span>
            </button>
          </div>
        </div>
      </template>
    </div>
  </AppLayout>
</template>
