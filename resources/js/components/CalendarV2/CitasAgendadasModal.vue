<!-- resources/js/components/CalendarV2/CitasAgendadasModal.vue -->
<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useVisitasStore, type Visita } from '@/stores/calendarStore'
import { useAlertStore } from '@/stores/alertStore'
import EventFormInline from './EventFormInline.vue'

// =============================================================================
// INTERFACES Y TIPOS
// =============================================================================

interface SelectOption {
  value: string;
  label: string;
}

const props = defineProps<{
  mode?: 'day' | 'global'
  date?: Date | null
  focusEventId?: string | null
  eventFilter?: (e: Visita) => boolean
  clienteOptions?: SelectOption[]
  rfvOptions?: SelectOption[]
}>()

const emit = defineEmits<{ 
  (e:'close'): void; 
  (e:'addNew', d?: Date): void 
}>()

// =============================================================================
// STORE Y ESTADO DEL MODAL
// =============================================================================

const visitasStore = useVisitasStore()
const alertStore = useAlertStore()

const isOpen = ref(false)
const localMode = ref<'day'|'global'>(props.mode ?? 'day')
const selectedDate = ref<Date | null>(props.date ?? null)
const focusedId = ref<string | null>(props.focusEventId ?? null)
const editingId = ref<string | null>(null)
const creating = ref(false)
const selectedClienteId = ref<string | null>(null)

// =============================================================================
// DATOS LOCALES Y PROPS REACTIVOS
// =============================================================================

const localClienteOptions = ref<SelectOption[]>(props.clienteOptions || [])
const localRfvOptions = ref<SelectOption[]>(props.rfvOptions || [])

// =============================================================================
// FUNCIONES DE AYUDA - DATOS Y NOMBRES
// =============================================================================

function getClienteName(clienteId?: string) {
  if (!clienteId) return 'Sin cliente'
  const cliente = localClienteOptions.value.find(c => c.value === clienteId)
  return cliente ? cliente.label : 'Cliente no encontrado'
}

function getRfvName(rfvId?: string) {
  if (!rfvId) return ''
  const rfv = localRfvOptions.value.find(r => r.value === rfvId)
  return rfv ? rfv.label : ''
}

// =============================================================================
// COMPUTED - DATOS Y FILTRADO
// =============================================================================

const allEvents = computed(() => visitasStore.visitas)

const filteredEvents = computed(() => {
  const base = allEvents.value
  
  if (localMode.value === 'day' && selectedDate.value) {
    const d = selectedDate.value
    const sameDay = (dateString: string) => {
      const date = new Date(dateString)
      return date.getFullYear() === d.getFullYear() && 
             date.getMonth() === d.getMonth() && 
             date.getDate() === d.getDate()
    }
    return base.filter(e => sameDay(e.start))
  }
  
  return base
})

const clientesConEventos = computed(() => {
  const eventos = filteredEvents.value
  
  const eventosPorCliente = eventos.reduce((acc, evento) => {
    const clienteId = evento.metadata?.cliente_id
    if (clienteId) {
      if (!acc[clienteId]) {
        acc[clienteId] = []
      }
      acc[clienteId].push(evento)
    }
    return acc
  }, {} as Record<string, Visita[]>)
  
  return Object.entries(eventosPorCliente).map(([clienteId, eventos]) => {
    const cliente = localClienteOptions.value.find(c => c.value === clienteId)
    return {
      id: clienteId,
      nombre: cliente ? cliente.label : 'Cliente no encontrado',
      cantidadEventos: eventos.length,
      eventos: eventos
    }
  }).sort((a, b) => b.cantidadEventos - a.cantidadEventos)
})

const eventosFiltrados = computed(() => {
  if (!selectedClienteId.value) {
    return filteredEvents.value
  }
  
  return filteredEvents.value.filter(evento => 
    evento.metadata?.cliente_id === selectedClienteId.value
  )
})

const sortedEvents = computed(() =>
  eventosFiltrados.value.slice().sort((a, b) => 
    new Date(a.start).getTime() - new Date(b.start).getTime()
  )
)

const currentEvent = computed(() => 
  (editingId.value || focusedId.value) ? 
  (visitasStore.visitas.find(v => v.id === (editingId.value || focusedId.value!)) ?? null) : 
  null
)

// =============================================================================
// WATCHERS - SINCRONIZACIÓN DE ESTADO
// =============================================================================

watch(clientesConEventos, (nuevosClientes) => {
  if (nuevosClientes.length === 1) {
    selectedClienteId.value = nuevosClientes[0].id
  } else {
    selectedClienteId.value = null
  }
}, { immediate: true })

watch(() => props.focusEventId, (v) => { focusedId.value = v ?? null })

watch(() => props.clienteOptions, (newOptions) => {
  console.log('🔍 CitasAgendadasModal: clienteOptions prop changed', newOptions)
  localClienteOptions.value = newOptions || []
}, { immediate: true })

watch(() => props.rfvOptions, (newOptions) => {
  console.log('🔍 CitasAgendadasModal: rfvOptions prop changed', newOptions)
  localRfvOptions.value = newOptions || []
}, { immediate: true })

// =============================================================================
// MÉTODOS PRINCIPALES - GESTIÓN DEL MODAL
// =============================================================================

function open(payload?: { 
  mode?: 'day'|'global', 
  date?: Date, 
  focusEventId?: string | null, 
  create?: boolean,
  clienteOptions?: SelectOption[],
  rfvOptions?: SelectOption[]
}) {
  console.log('🔍 CitasAgendadasModal.open called with:', {
    payload,
    hasClienteOptions: payload?.clienteOptions?.length,
    hasRfvOptions: payload?.rfvOptions?.length
  })
  
  if (payload?.mode) localMode.value = payload.mode
  if (payload?.date !== undefined) selectedDate.value = payload.date
  focusedId.value = payload?.focusEventId ?? null
  editingId.value = null
  creating.value = !!payload?.create
  selectedClienteId.value = null
  
  if (payload?.clienteOptions) {
    localClienteOptions.value = payload.clienteOptions
    console.log('✅ Updated localClienteOptions:', localClienteOptions.value.length)
  }
  if (payload?.rfvOptions) {
    localRfvOptions.value = payload.rfvOptions
    console.log('✅ Updated localRfvOptions:', localRfvOptions.value.length)
  }
  
  isOpen.value = true
}

function close() { 
  isOpen.value = false; 
  emit('close') 
}

function selectCliente(clienteId: string) {
  selectedClienteId.value = clienteId
}

function clearClienteSelection() {
  selectedClienteId.value = null
}

// =============================================================================
// MÉTODOS DE NAVEGACIÓN - FLUJO DE PANTALLAS
// =============================================================================

function startEdit(id: string) { 
  creating.value = false; 
  editingId.value = id 
}

function backToList() { 
  editingId.value = null; 
  creating.value = false 
}

function startCreate() { 
  editingId.value = null; 
  creating.value = true;
  emit('addNew', selectedDate.value ?? undefined)
}

// =============================================================================
// FUNCIONES DE FORMATEO
// =============================================================================

function formatRange(e: Visita) {
  const s = new Date(e.start), en = new Date(e.end)
  const f = (d: Date) => `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`
  return `${f(s)} – ${f(en)}`
}

// =============================================================================
// EXPOSICIÓN DE MÉTODOS PARA USO EXTERNO
// =============================================================================

defineExpose({ 
  open,         // Abrir modal
  startCreate,  // Iniciar creación
  startEdit,    // Iniciar edición
  backToList    // Volver a lista
})
</script>

<template>
  <transition name="fade">
    <div v-if="isOpen" class="fixed inset-0 z-100 flex items-center justify-center">
        <!-- OVERLAY DE FONDO -->
        <div class="absolute inset-0 bg-black/40" @click.self="close"></div>

        <!-- CONTENEDOR PRINCIPAL DEL MODAL -->
        <div class="modal-shell bg-white dark:bg-gray-800 shadow-xl rounded-2xl flex flex-col" @click.stop style="z-index:101;">
          
          <!-- CABECERA DEL MODAL -->
          <div class="flex items-center justify-between p-6 pb-4 shrink-0">
            <div class="flex items-center gap-3">
              <!-- BOTÓN DE RETROCESO -->
              <button v-if="editingId || creating"
                      class="px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="backToList">←</button>
              
              <!-- TÍTULO DINÁMICO -->
              <h3 class="text-xl font-semibold">
                <template v-if="editingId && currentEvent">
                  {{ getClienteName(currentEvent.metadata?.cliente_id) || 'Editar visita' }}
                </template>
                
                <template v-else-if="creating">
                  Crear nueva visita
                </template>
                
                <template v-else>
                  <span v-if="localMode==='day' && selectedDate">
                    {{ selectedDate.toLocaleDateString('es-ES', { weekday:'long', day:'numeric', month:'long' }) }}
                  </span>
                  <span v-else>Todas las agendas</span>
                </template>
              </h3>
            </div>
            
            <!-- BOTÓN CERRAR -->
            <button class="text-gray-500 hover:text-gray-900 dark:hover:text-white text-lg" @click="close">✕</button>
          </div>

          <!-- CONTENIDO PRINCIPAL - GRID DE 2 COLUMNAS -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 flex-1 min-h-0 px-6 pb-6">
            
            <!-- COLUMNA IZQUIERDA - LISTA DE CLIENTES -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 h-full flex flex-col min-h-0">
              <h4 class="text-center text-blue-700 dark:text-blue-300 font-semibold mb-3 shrink-0">Clientes</h4>
              <div class="h-1 bg-blue-600 dark:bg-blue-400 rounded-full mb-4 shrink-0"></div>

              <!-- CONTENIDO SCROLLABLE -->
              <div class="scroll-area">
                <div class="text-blue-600 dark:text-blue-400">
                  <div class="font-medium mb-1">
                    {{ localMode === 'day' ? 'Clientes del día' : 'Clientes con actividades' }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                    {{ clientesConEventos.length }} 
                    {{ clientesConEventos.length === 1 ? 'cliente con actividades' : 'clientes con actividades' }}
                  </div>
                  
                  <!-- LISTA DE CLIENTES -->
                  <div class="space-y-2">
                    <!-- ITEM DE CLIENTE -->
                    <div 
                      v-for="cliente in clientesConEventos" 
                      :key="cliente.id"
                      :class="[
                        'text-sm p-3 rounded transition-colors cursor-pointer',
                        selectedClienteId === cliente.id 
                          ? 'bg-blue-100 dark:bg-blue-900 border border-blue-300 dark:border-blue-700' 
                          : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                      ]"
                      @click="selectCliente(cliente.id)"
                    >
                      <div class="font-medium flex justify-between items-center">
                        <span>{{ cliente.nombre }}</span>
                        <!-- BADGE CON CONTADOR DE EVENTOS -->
                        <span class="text-xs bg-blue-500 text-white px-2 py-1 rounded-full">
                          {{ cliente.cantidadEventos }} actividad{{ cliente.cantidadEventos !== 1 ? 'es' : '' }}
                        </span>
                      </div>
                    </div>
                    
                    <!-- BOTÓN "VER TODOS LOS EVENTOS" -->
                    <div 
                      v-if="selectedClienteId && clientesConEventos.length > 1"
                      :class="[
                        'text-sm p-3 rounded transition-colors cursor-pointer text-center',
                        'text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 border border-dashed border-blue-300 dark:border-blue-700'
                      ]"
                      @click="clearClienteSelection"
                    >
                      Ver todos los eventos
                    </div>
                    
                    <!-- ESTADO VACÍO -->
                    <div v-if="clientesConEventos.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
                      No hay clientes con actividades {{ localMode === 'day' ? 'en esta fecha' : 'pendientes' }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- COLUMNA DERECHA - EVENTOS/FORMULARIOS -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 h-full flex flex-col min-h-0">
              
              <!-- VISTA DE LISTA DE EVENTOS -->
              <section v-if="!editingId && !creating" class="flex-1 min-h-0 flex flex-col">
                <!-- TÍTULO DINÁMICO DE SECCIÓN -->
                <h4 class="text-center text-blue-700 dark:text-blue-300 font-semibold mb-3 shrink-0">
                  <template v-if="selectedClienteId">
                    Visitas - {{ getClienteName(selectedClienteId) }}
                  </template>
                  <template v-else>
                    {{ localMode==='day' && selectedDate ? selectedDate.toLocaleDateString('es-ES', { weekday:'long', day:'numeric', month:'long' }) : 'Todas las visitas' }}
                  </template>
                </h4>
                <div class="h-1 bg-blue-600 dark:bg-blue-400 rounded-full mb-4 shrink-0"></div>

                <!-- ESTADO VACÍO -->
                <div v-if="sortedEvents.length === 0" class="text-gray-500 dark:text-gray-300 text-center py-8">
                  <template v-if="selectedClienteId">
                    No hay visitas para este cliente {{ localMode === 'day' ? 'en esta fecha' : '' }}
                  </template>
                  <template v-else>
                    No hay visitas programadas {{ localMode === 'day' ? 'para esta fecha' : '' }}
                  </template>
                </div>

                <!-- LISTA DE EVENTOS -->
                <div class="scroll-area divide-y divide-gray-200 dark:divide-gray-700">
                  <!-- ITEM DE VISITA -->
                  <div v-for="e in sortedEvents" :key="e.id" class="flex items-start justify-between py-4 px-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded transition-colors">
                    <div class="flex-1">
                      <!-- NOMBRE DEL CLIENTE (solo si no hay filtro aplicado) -->
                      <div v-if="!selectedClienteId" class="font-medium text-gray-900 dark:text-gray-100 text-lg">
                        {{ getClienteName(e.metadata?.cliente_id) }}
                      </div>
                      
                      <!-- RFV (si está disponible) -->
                      <div v-if="getRfvName(e.metadata?.rfv_id)" class="text-sm text-blue-600 dark:text-blue-400 mt-2">
                        {{ getRfvName(e.metadata?.rfv_id) }}
                      </div>
                      
                      <!-- RANGO DE TIEMPO -->
                      <div class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                        {{ formatRange(e) }}
                      </div>
                      
                      <!-- INDICADOR DE ESTADO POR COLOR -->
                      <div class="flex items-center mt-2">
                        <div :class="[
                          'w-3 h-3 rounded-full mr-2',
                          e.tailwindColor === 'red' ? 'bg-red-500' :
                          e.tailwindColor === 'green' ? 'bg-green-500' :
                          'bg-yellow-500'
                        ]"></div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                          {{ 
                            e.tailwindColor === 'red' ? 'Perdida' :
                            e.tailwindColor === 'green' ? 'Completada' :
                            'Pendiente'
                          }}
                        </span>
                      </div>
                    </div>
                    <!-- BOTÓN PARA EDITAR VISITA -->
                    <button class="text-xs px-3 py-2 border border-emerald-500 text-emerald-600 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900 transition-colors"
                            @click="startEdit(e.id)">Ver</button>
                  </div>
                </div>

                <!-- BOTÓN CREAR NUEVA VISITA -->
                <div class="text-center mt-4 shrink-0 pt-4 border-t border-gray-200 dark:border-gray-700">
                  <button class="text-blue-600 dark:text-blue-300 hover:underline font-medium" @click="startCreate">
                    + Visita nueva
                  </button>
                </div>
              </section>

              <!-- VISTA DE EDICIÓN -->
              <section v-else-if="editingId">
                <EventFormInline
                  embedded
                  mode="edit"
                  :event-id="editingId"
                  @cancel="backToList"
                  @saved="(ev) => {
                    visitasStore.actualizarVisita(ev.id, {
                      idCliente: ev.metadata.cliente_id,
                      Fecha: ev.metadata.fecha_original,
                      Hora: ev.metadata.hora_original,
                      idRFV: ev.metadata.rfv_id
                    }).then(() => {
                      alertStore.showSuccess('Visita actualizada correctamente')
                      return visitasStore.cargarVisitasDelMes(visitasStore.mesActual)
                    }).catch((err: any) => {
                      alertStore.showError(err.response?.data?.message || 'Error al actualizar la visita')
                    })
                  }"
                  @deleted="(id) => { 
                    visitasStore.eliminarVisita(id).then(() => backToList())
                  }"
                  @processed="(id) => { 
                    console.log('Visita procesada:', id)
                    backToList() 
                  }"
                />
              </section>

              <!-- VISTA DE CREACIÓN -->
              <section v-else-if="creating">
                <EventFormInline
                  embedded
                  mode="create"
                  :default-date="selectedDate ?? undefined"
                  @cancel="creating=false"
                  @saved="(ev: Visita) => {
                    visitasStore.crearVisita({
                      idCliente: ev.metadata.cliente_id,
                      Fecha: ev.metadata.fecha_original,
                      Hora: ev.metadata.hora_original,
                      idRFV: ev.metadata.rfv_id
                    }).then(() => {
                      alertStore.showSuccess('Visita creada correctamente')
                      creating = false
                    }).catch((err: any) => {
                      alertStore.showError(err.response?.data?.message || 'Error al crear la visita')
                    })
                  }"
                />
              </section>
            </div>
          </div>
        </div>
      </div>
  </transition>
</template>

<style scoped>
.modal-shell{
  width: min(1000px, 92vw);
  height: min(800px, 90vh);
  max-height: 90vh;
}

.scroll-area{
  height: 400px;
  min-height: 0;
  overflow-y: auto;
  scrollbar-gutter: stable both-edges;
}

.nice-scroll {
  height: 500px;
  min-height: 0;
  overflow-y: auto;
  scrollbar-gutter: stable both-edges;
}

.scroll-area, .nice-scroll{
  scrollbar-width: thin;
  scrollbar-color: #475569 #1f2937;
}

.scroll-area::-webkit-scrollbar,
.nice-scroll::-webkit-scrollbar{
  width: 8px;
}

.scroll-area::-webkit-scrollbar-track,
.nice-scroll::-webkit-scrollbar-track{
  background: #1f2937;
  border-radius: 8px;
}

.scroll-area::-webkit-scrollbar-thumb,
.nice-scroll::-webkit-scrollbar-thumb{
  background: #475569;
  border-radius: 8px;
}

.scroll-area::-webkit-scrollbar-thumb:hover,
.nice-scroll::-webkit-scrollbar-thumb:hover{
  background: #64748b;
}
</style>

<style>
.fade-enter-from, .fade-leave-to { opacity: 0 }
.fade-enter-active, .fade-leave-active { transition: opacity .15s }
</style>