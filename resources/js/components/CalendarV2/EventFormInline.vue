<!-- resources/js/components/CalendarV2/EventFormInline.vue -->
<script setup lang="ts">
import { reactive, onMounted, watch, ref, computed } from 'vue'
import { useVisitasStore, type Visita } from '@/stores/calendarStore'
import { useAlertStore } from '@/stores/alertStore'
import GenericCombobox from '../GenericCombobox.vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'

// =============================================================================
// INTERFACES Y TIPOS
// =============================================================================

interface SelectOption {
  value: string
  label: string
}

type AllowedColor = 'red' | 'green' | 'yellow'
type TipoVisita = 'tmp_temporal' | 'tmp_perdida' | 'procesada'

// =============================================================================
// PROPS Y EMITS
// =============================================================================

const props = defineProps<{
  mode: 'create' | 'edit'
  defaultDate?: Date
  eventId?: string
  embedded?: boolean
}>()

const emit = defineEmits<{
  (e: 'saved', v: Visita): void
  (e: 'deleted', id: string): void
  (e: 'cancel'): void
  (e: 'processed', id: string): void
}>()

// =============================================================================
// STORES
// =============================================================================

const visitasStore = useVisitasStore()
const alertStore = useAlertStore()

// =============================================================================
// ESTADO DEL FORMULARIO
// =============================================================================

const form = reactive({
  startLocal: '',
  endLocal: '',
  tailwindColor: 'yellow' as AllowedColor,
  selectedRfv: null as SelectOption | null,
  selectedCliente: null as SelectOption | null,
  cliente_id: '',
  rfv_id: '',
  tipo: 'tmp_temporal' as TipoVisita
})

// =============================================================================
// ESTADO DE OPCIONES Y CARGA
// =============================================================================

const rfvOptions = ref<SelectOption[]>([])
const clienteOptions = ref<SelectOption[]>([])
const isLoadingRfvs = ref(false)
const isLoadingClientes = ref(false)
const rfvsLoaded = ref(false) // ✅ Nuevo flag para controlar carga
const userRol = ref<string>('')
const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone

// =============================================================================
// COMPUTED PROPERTIES PARA COMBOBOX
// =============================================================================

// ✅ Placeholder dinámico para RFV
const rfvPlaceholder = computed(() => {
  if (isLoadingRfvs.value) return 'Cargando RFVs...'
  if (!puedeEditar.value) return 'No editable'
  if (rfvOptions.value.length === 1) return 'Vendedor asignado'
  return 'Buscar RFV...'
})

// ✅ Deshabilitar combobox de RFV cuando corresponda
const isRfvComboboxDisabled = computed(() => {
  return isLoadingRfvs.value || !puedeEditar.value || rfvOptions.value.length <= 1
})

// ✅ Placeholder dinámico para Cliente
const clientePlaceholder = computed(() => {
  if (!form.rfv_id) return 'Primero seleccione un RFV'
  if (isLoadingClientes.value) return 'Cargando clientes...'
  if (!puedeEditar.value) return 'No editable'
  return 'Buscar cliente...'
})

// ✅ Deshabilitar combobox de Cliente cuando corresponda
const isClienteComboboxDisabled = computed(() => {
  return !form.rfv_id || isLoadingClientes.value || !puedeEditar.value
})

// =============================================================================
// UTILIDADES DE FECHA
// =============================================================================

function toLocal(iso: string): string {
  const d = new Date(iso)
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function toISO(localValue: string): string {
  return new Date(localValue).toISOString()
}

function calculateEndDate(startLocal: string): string {
  if (!startLocal) return ''
  // Sumar 7 días
  const endDate = new Date(new Date(startLocal).getTime() + 7 * 24 * 60 * 60 * 1000)
  return toLocal(endDate.toISOString())
}

function defaultLocalRange(d?: Date) {
  const base = d ? new Date(d) : new Date()
  const start = new Date(base)
  start.setHours(9, 0, 0, 0)
  return {
    start: toLocal(start.toISOString()),
    end: calculateEndDate(toLocal(start.toISOString()))
  }
}

// =============================================================================
// PERMISOS Y ESTADOS
// =============================================================================

const estadoTexto = computed(() => {
  const estados = {
    tmp_temporal: 'Amarillo (Pendiente)',
    tmp_perdida: 'Rojo (Perdida)',
    procesada: 'Verde (Procesada)'
  }
  return estados[form.tipo] || 'Amarillo (Pendiente)'
})

const esRolRestringido = computed(() => ['GRT', 'SUP'].includes(userRol.value))
const puedeEditar = computed(() => form.tipo === 'tmp_temporal')
const puedeEliminar = computed(() => form.tipo === 'tmp_temporal')
const puedeProcesar = computed(() => !esRolRestringido.value && form.tipo === 'tmp_temporal')
const mostrarBotonProcesar = computed(() => props.mode === 'edit' && puedeProcesar.value)
const mostrarBotonEliminar = computed(() => props.mode === 'edit' && puedeEliminar.value)

// =============================================================================
// CARGA DE DATOS
// =============================================================================

async function cargarRfvsDisponibles() {
  isLoadingRfvs.value = true
  rfvsLoaded.value = false
  
  try {
    const response = await axios.get(route('tmp_planificaciones.rfvs_disponibles'))
    rfvOptions.value = response.data
    
    console.log('EventFormInline: RFVs cargados:', rfvOptions.value.length)

    // ✅ AUTO-SELECCIÓN: Si hay un solo RFV, seleccionarlo automáticamente
    if (rfvOptions.value.length === 1 && props.mode === 'create') {
      form.selectedRfv = rfvOptions.value[0]
      form.rfv_id = rfvOptions.value[0].value
      console.log('EventFormInline: Auto-seleccionado RFV único:', form.selectedRfv.label)
      
      // ✅ Cargar clientes para ese RFV automáticamente
      await cargarClientesPorRfv(form.rfv_id)
    }
    
    rfvsLoaded.value = true
  } catch (error) {
    console.error('Error cargando RFVs:', error)
    alertStore.showError('Error al cargar los RFVs disponibles')
    rfvOptions.value = []
    rfvsLoaded.value = true
  } finally {
    isLoadingRfvs.value = false
  }
}

async function cargarClientesPorRfv(rfvId: string, query: string = '') {
  if (!rfvId) {
    clienteOptions.value = []
    return
  }
  
  isLoadingClientes.value = true
  try {
    console.log('EventFormInline: Cargando clientes para RFV:', rfvId, 'Query:', query)
    
    const response = await axios.get(
      route('tmp_planificaciones.clientes_por_rfv', { rfvId }),
      { params: { search: query, size: 25, page: 1 } }
    )
    clienteOptions.value = response.data
    
    console.log('EventFormInline: Clientes cargados:', clienteOptions.value.length)
  } catch (error) {
    console.error('Error cargando clientes:', error)
    alertStore.showError('Error al cargar los clientes')
    clienteOptions.value = []
  } finally {
    isLoadingClientes.value = false
  }
}

async function onClienteDynamicSearch(query: string) {
  if (!form.rfv_id) {
    console.log('No hay RFV seleccionado para búsqueda de clientes')
    return
  }
  await cargarClientesPorRfv(form.rfv_id, query)
}

// =============================================================================
// INICIALIZACIÓN DEL ROL
// =============================================================================

function inicializarRolUsuario() {
  if (visitasStore.userRole) {
    userRol.value = visitasStore.userRole
    return
  }
  
  const pageProps = usePage().props
  const roleFromInertia = (pageProps.auth as any)?.user?.idgrupo_persona
  
  if (roleFromInertia) {
    userRol.value = roleFromInertia
    visitasStore.userRole = roleFromInertia
  }
}

// =============================================================================
// CARGA DE DATOS EN MODO EDICIÓN
// =============================================================================

async function cargarVisitaParaEdicion() {
  const visita = visitasStore.visitas.find(v => v.id === props.eventId)
  if (!visita) return

  console.log('EventFormInline: Cargando visita para edición:', props.eventId)

  // Cargar datos básicos
  form.startLocal = toLocal(visita.start)
  form.endLocal = toLocal(visita.end)
  form.tailwindColor = (visita.tailwindColor as AllowedColor) || 'yellow'
  form.tipo = visita.metadata.tipo
  form.cliente_id = visita.metadata?.cliente_id || ''
  form.rfv_id = visita.metadata?.rfv_id || ''

  // Cargar RFV si existe
  if (form.rfv_id) {
    const rfv = rfvOptions.value.find(r => r.value === form.rfv_id)
    if (rfv) {
      form.selectedRfv = rfv
      await cargarClientesPorRfv(form.rfv_id)
    }
  }

  // Cargar cliente después de un breve delay
  if (form.cliente_id) {
    setTimeout(() => {
      const cliente = clienteOptions.value.find(c => c.value === form.cliente_id)
      if (cliente) {
        form.selectedCliente = cliente
      } else {
        // Cliente no encontrado en la lista, agregarlo manualmente
        const clienteManual = {
          value: form.cliente_id,
          label: visita.metadata.nombre_cliente || 'Cliente no encontrado'
        }
        clienteOptions.value.push(clienteManual)
        form.selectedCliente = clienteManual
      }
    }, 100)
  }
}

// =============================================================================
// WATCHERS
// =============================================================================

watch(() => form.startLocal, (newStart) => {
  if (newStart) form.endLocal = calculateEndDate(newStart)
})

watch(() => form.selectedCliente, (newCliente) => {
  form.cliente_id = newCliente?.value || ''
})

watch(() => form.selectedRfv, async (newRfv, oldRfv) => {
  const currentClienteId = form.cliente_id
  const currentCliente = form.selectedCliente
  
  console.log('EventFormInline: RFV cambió:', newRfv?.label)
  
  if (newRfv) {
    form.rfv_id = newRfv.value
    
    // Limpiar cliente si cambió el RFV
    if (!oldRfv || newRfv.value !== oldRfv.value) {
      form.selectedCliente = null
      form.cliente_id = ''
    }
    
    await cargarClientesPorRfv(newRfv.value)
    
    // Restaurar cliente en modo edición
    if (props.mode === 'edit' && currentClienteId) {
      setTimeout(() => {
        const clienteEnLista = clienteOptions.value.find(c => c.value === currentClienteId)
        if (clienteEnLista) {
          form.selectedCliente = clienteEnLista
          form.cliente_id = currentClienteId
        } else if (currentCliente) {
          clienteOptions.value.push(currentCliente)
          form.selectedCliente = currentCliente
          form.cliente_id = currentClienteId
        }
      }, 150)
    }
  } else {
    form.rfv_id = ''
    form.selectedCliente = null
    form.cliente_id = ''
    clienteOptions.value = []
  }
})

watch(() => form.rfv_id, (newRfvId) => {
  if (newRfvId && !form.selectedRfv) {
    const rfv = rfvOptions.value.find(r => r.value === newRfvId)
    if (rfv) form.selectedRfv = rfv
  }
})


// =============================================================================
// VALIDACIÓN Y CONSTRUCCIÓN DE DATOS
// =============================================================================

function buildEventTitle(): string {
  const cliente = clienteOptions.value.find(c => c.value === form.cliente_id)
  const rfv = rfvOptions.value.find(r => r.value === form.rfv_id)
  
  if (cliente && rfv) return `${rfv.label} - ${cliente.label}`
  if (cliente) return cliente.label
  if (rfv) return rfv.label
  return 'Visita sin título'
}

function validateForm(): boolean {
  if (!form.cliente_id) {
    alertStore.showError('Por favor seleccione un cliente')
    return false
  }
  if (!form.rfv_id) {
    alertStore.showError('Por favor seleccione un RFV')
    return false
  }
  if (!form.startLocal) {
    alertStore.showError('Por favor seleccione una fecha de inicio')
    return false
  }
  return true
}

// =============================================================================
// ACCIONES PRINCIPALES
// =============================================================================

function save() {
  if (!validateForm()) return
  
  const title = buildEventTitle()
  const startDate = new Date(form.startLocal)

  if (props.mode === 'edit' && props.eventId) {
    const current = visitasStore.visitas.find(v => v.id === props.eventId)
    if (!current) return
    
    const updated: Visita = {
      ...current,
      id: props.eventId,
      title,
      start: toISO(form.startLocal),
      end: toISO(form.endLocal),
      tailwindColor: form.tailwindColor,
      metadata: {
        ...current.metadata,
        tipo: form.tipo,
        cliente_id: form.cliente_id,
        rfv_id: form.rfv_id,
        nombre_cliente: form.selectedCliente?.label || current.metadata.nombre_cliente,
        nombre_rfv: form.selectedRfv?.label || current.metadata.nombre_rfv,
        fecha_original: startDate.toISOString().split('T')[0],
        hora_original: startDate.toTimeString().substring(0, 5),
        puedeEditar: puedeEditar.value,
        puedeEliminar: puedeEliminar.value,
        puedeProcesar: puedeProcesar.value
      }
    }
    emit('saved', updated)
    alertStore.showSuccess('Visita actualizada correctamente')
  } else {
    const newVisita: Visita = {
      id: `tmp_${Date.now()}`,
      title,
      start: toISO(form.startLocal),
      end: toISO(form.endLocal),
      tailwindColor: 'yellow',
      metadata: {
        tipo: 'tmp_temporal',
        cliente_id: form.cliente_id,
        rfv_id: form.rfv_id,
        nombre_cliente: form.selectedCliente?.label || '',
        nombre_rfv: form.selectedRfv?.label || '',
        fecha_original: startDate.toISOString().split('T')[0],
        hora_original: startDate.toTimeString().substring(0, 5),
        puedeEditar: true,
        puedeEliminar: true,
        puedeProcesar: true,
        id_original: `tmp_${Date.now()}`
      }
    }
    emit('saved', newVisita)
    alertStore.showSuccess('Visita creada correctamente')
  }
}

function removeEv() {
  if (props.mode === 'edit' && props.eventId && puedeEliminar.value) {
    alertStore.showConfirm(
      '¿Está seguro de que desea eliminar esta visita?',
      {
        onConfirm: () => {
          emit('deleted', props.eventId!)
          alertStore.showSuccess('Visita eliminada correctamente')
        },
        confirmText: 'Eliminar',
        cancelText: 'Cancelar'
      }
    )
  }
}

function procesar() {
  if (props.mode === 'edit' && props.eventId && puedeProcesar.value) {
    alertStore.showConfirm(
      '¿Está seguro de que desea procesar esta visita?',
      {
        onConfirm: () => {
          try {
            const idOriginal = props.eventId!.replace('tmp_', '')
            router.get(route('tmp_planificaciones.procesar', { id: idOriginal }))
            alertStore.showInfo('Procesando visita...')
            emit('processed', props.eventId!)
          } catch (error) {
            console.error('Error al procesar visita:', error)
            alertStore.showError('Error al intentar procesar la visita')
          }
        },
        confirmText: 'Procesar',
        cancelText: 'Cancelar'
      }
    )
  }
}

// =============================================================================
// INICIALIZACIÓN
// =============================================================================

onMounted(async () => {
  console.log('EventFormInline: Componente montado - Modo:', props.mode)
  
  inicializarRolUsuario()
  
  // ✅ Cargar RFVs primero y esperar
  await cargarRfvsDisponibles()
  
  if (props.mode === 'edit' && props.eventId) {
    await cargarVisitaParaEdicion()
  } else {
    const { start, end } = defaultLocalRange(props.defaultDate)
    form.startLocal = start
    form.endLocal = end
    form.tailwindColor = 'yellow'
    form.tipo = 'tmp_temporal'
  }
})
</script>

<template>
  <div v-if="!embedded" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900">
    <h3 class="text-lg font-semibold mb-4">
      {{ props.mode === 'edit' ? 'Editar Visita' : 'Crear Nueva Visita' }}
    </h3>
    
    <form @submit.prevent="save" class="space-y-4">
      
      <!-- RFV -->
      <div>
        <label class="block text-sm font-medium mb-1">RFV *</label>
        <GenericCombobox
          v-model:modelValue="form.selectedRfv"
          :options="rfvOptions"
          :disabled="isRfvComboboxDisabled"
          :placeholder="rfvPlaceholder"
          :dynamic-search="false"
        />
        <div v-if="isLoadingRfvs" class="text-xs text-blue-600 mt-1">
          ⏳ Cargando RFVs disponibles...
        </div>
        <div v-else-if="rfvOptions.length === 1" class="text-xs text-green-600 mt-1">
          ✅ Vendedor asignado automáticamente
        </div>
        <div v-else-if="!puedeEditar" class="text-xs text-amber-600 mt-1">
          ⚠️ No se puede editar RFV en visitas no temporales
        </div>
      </div>

      <!-- Cliente -->
      <div>
        <label class="block text-sm font-medium mb-1">Cliente *</label>
        <GenericCombobox
          v-model:modelValue="form.selectedCliente"
          :options="clienteOptions"
          :disabled="isClienteComboboxDisabled"
          :placeholder="clientePlaceholder"
          :dynamic-search="true"
          :dynamic-loading="isLoadingClientes"
          @dynamic-search="onClienteDynamicSearch"
        />
        <div v-if="!form.rfv_id" class="text-xs text-amber-600 mt-1">
          ⚠️ Debe seleccionar un RFV primero
        </div>
        <div v-else-if="isLoadingClientes" class="text-xs text-blue-600 mt-1">
          ⏳ Cargando clientes...
        </div>
        <div v-else-if="puedeEditar" class="text-xs text-gray-500 mt-1">
          💡 Escriba para buscar clientes
        </div>
        <div v-else class="text-xs text-amber-600 mt-1">
          ⚠️ No se puede editar cliente en visitas no temporales
        </div>
      </div>

      <!-- Fecha Inicio -->
      <div>
        <label class="block text-sm font-medium mb-1">Inicio *</label>
        <input
          v-model="form.startLocal"
          type="datetime-local"
          required
          :disabled="!puedeEditar"
          :class="[
            'w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800',
            puedeEditar 
              ? 'border-gray-300 dark:border-gray-600' 
              : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 cursor-not-allowed'
          ]"
        />
        <p class="text-xs text-gray-500 mt-1">Zona horaria: {{ userTimezone }}</p>
        <div v-if="!puedeEditar" class="text-xs text-amber-600 mt-1">
          ⚠️ No se puede editar fecha en visitas no temporales
        </div>
      </div>

      <!-- Fecha Fin -->
      <div>
        <label class="block text-sm font-medium mb-1">Fin *</label>
        <input
          v-model="form.endLocal"
          type="datetime-local"
          required
          readonly
          class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 cursor-not-allowed"
        />
        <p class="text-xs text-gray-500 mt-1">Calculado automáticamente (7 días después)</p>
      </div>

      <!-- Estado -->
      <div>
        <label class="block text-sm font-medium mb-1">Estado</label>
        <div class="flex items-center gap-2">
          <div 
            class="h-8 w-8 rounded-full border-2 border-white"
            :class="{
              'bg-yellow-500': form.tailwindColor === 'yellow',
              'bg-red-500': form.tailwindColor === 'red', 
              'bg-green-500': form.tailwindColor === 'green'
            }"
          ></div>
          <span class="text-sm text-gray-600 dark:text-gray-300">
            {{ estadoTexto }}
          </span>
        </div>
        <p class="text-xs text-gray-500 mt-1">Asignado automáticamente por el sistema</p>
        <div v-if="form.tipo === 'tmp_perdida'" class="text-xs text-amber-600 mt-1">
          ⚠️ Visita perdida - no se puede procesar ni eliminar
        </div>
        <div v-else-if="form.tipo === 'procesada'" class="text-xs text-green-600 mt-1">
          ✅ Visita procesada
        </div>
        <div v-else-if="esRolRestringido" class="text-xs text-amber-600 mt-1">
          ⚠️ Los usuarios GRT/SUP no pueden procesar visitas
        </div>
      </div>
      
      <!-- Botones -->
      <div class="mt-6 flex justify-end gap-2">
        <button type="button" @click="$emit('cancel')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
          Cancelar
        </button>

        <button
          v-if="mostrarBotonEliminar"
          type="button"
          @click="removeEv"
          class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
        >
          Eliminar
        </button>

        <button
          v-if="mostrarBotonProcesar"
          type="button"
          @click="procesar"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
        >
          Procesar
        </button>

        <button 
          type="submit" 
          :disabled="!puedeEditar"
          :class="[
            'px-4 py-2 text-white rounded-lg',
            puedeEditar
              ? 'bg-blue-600 hover:bg-blue-700'
              : 'bg-blue-400 cursor-not-allowed'
          ]"
        >
          {{ props.mode === 'edit' ? 'Guardar' : 'Crear Visita' }}
        </button>
      </div>
    </form>
  </div>
  
  <!-- Versión Embedded (Modal) -->
  <div v-else class="h-full">
    <h3 class="text-lg font-semibold mb-4">
      {{ props.mode === 'edit' ? 'Editar Visita' : 'Crear Nueva Visita' }}
    </h3>
    
    <form @submit.prevent="save" class="space-y-4 h-full">
      
      <div>
        <label class="block text-sm font-medium mb-1">RFV *</label>
        <GenericCombobox
          v-model:modelValue="form.selectedRfv"
          :options="rfvOptions"
          :disabled="isLoadingRfvs || !puedeEditar"
          :placeholder="isLoadingRfvs ? 'Cargando RFVs...' : 'Buscar RFV...'"
        />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Cliente *</label>
        <GenericCombobox
          v-model:modelValue="form.selectedCliente"
          :options="clienteOptions"
          :disabled="!form.rfv_id || isLoadingClientes || !puedeEditar"
          :placeholder="
            !form.rfv_id ? 'Primero seleccione un RFV' :
            isLoadingClientes ? 'Cargando clientes...' : 'Buscar cliente...'
          "
          :dynamic-search="true"
          :dynamic-loading="isLoadingClientes"
          @dynamic-search="onClienteDynamicSearch"
        />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Inicio *</label>
        <input
          v-model="form.startLocal"
          type="datetime-local"
          required
          :disabled="!puedeEditar"
          :class="[
            'w-full px-3 py-2 rounded-lg border bg-white dark:bg-gray-800',
            puedeEditar 
              ? 'border-gray-300 dark:border-gray-600' 
              : 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 cursor-not-allowed'
          ]"
        />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Fin *</label>
        <input
          v-model="form.endLocal"
          type="datetime-local"
          required
          readonly
          class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 cursor-not-allowed"
        />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Estado</label>
        <div class="flex items-center gap-2">
          <div 
            class="h-8 w-8 rounded-full border-2 border-white"
            :class="{
              'bg-yellow-500': form.tailwindColor === 'yellow',
              'bg-red-500': form.tailwindColor === 'red',
              'bg-green-500': form.tailwindColor === 'green'
            }"
          ></div>
          <span class="text-sm text-gray-600 dark:text-gray-300">
            {{ estadoTexto }}
          </span>
        </div>
      </div>

      <div class="mt-6 flex justify-end gap-2">
        <button type="button" @click="$emit('cancel')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
          Cancelar
        </button>

        <button
          v-if="mostrarBotonEliminar"
          type="button"
          @click="removeEv"
          class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
        >
          Eliminar
        </button>

        <button
          v-if="mostrarBotonProcesar"
          type="button"
          @click="procesar"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
        >
          Procesar
        </button>

        <button 
          type="submit" 
          :disabled="!puedeEditar"
          :class="[
            'px-4 py-2 text-white rounded-lg',
            puedeEditar
              ? 'bg-blue-600 hover:bg-blue-700'
              : 'bg-blue-400 cursor-not-allowed'
          ]"
        >
          {{ props.mode === 'edit' ? 'Guardar' : 'Crear Visita' }}
        </button>
      </div>
    </form>
  </div>
</template>