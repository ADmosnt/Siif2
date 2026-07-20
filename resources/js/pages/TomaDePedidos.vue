<!-- resources/js/pages/TomaDePedidos.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import Button from "@/components/ui/button/Button.vue";
import vueNumberInput from '@/components/vue-number-input.vue';
import { type PaginatedData } from '@/types/pagination';
import { type Cliente, Representante, Mayoristas, Producto, CarritoItem } from '@/types/interfaces';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue, } from '@/components/ui/select';
import TablesTdp from '@/components/TdpComponents/TablesTdp.vue';
import GlobalAlert from '@/components/GlobalAlert.vue';
import { useValidationAlert } from '@/composables/useValidationAlert';
import ClienteCombobox from '@/components/ClienteCombobox.vue';
import { useOffline } from '@/composables/useOffline';
import { useGeolocation } from '@/composables/useGeolocation';

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'Centro de Transferencias (CT)' },
  { label: 'Toma de Pedidos' }
];

const productosPage = ref(1);
const productosPageSize = ref('15');
const productosSearch = ref('');

// Props para coincidir con el backend
const props = defineProps<{
  clientes?: PaginatedData<Cliente>;
  representantes?: PaginatedData<Representante>;
  mayoristas?: PaginatedData<Mayoristas>;
  productos?: PaginatedData<Producto>;
}>();

// === OFFLINE ===
const { isOnline, submitOrQueue } = useOffline()

// === GEOLOCATION ===
const { coords: geoCoords, error: geoError, loading: geoLoading, permissionDenied: geoDenied, requestLocation } = useGeolocation()

// === USAR COMPOSABLE PARA ALERTAS ===
const {
  success: alertSuccess,
  error: alertError,
  warning: alertWarning,
  message: alertMessage,
  showWarning,
  showError,
  showSuccess,
  clear: clearAlerts
} = useValidationAlert();

// === DATOS REACTIVOS ===
const descripcion = ref('');
const keyTables = ref(0);
const maxChars = 256;
const tax = ref(0);

// datos paginados
const representantes = computed(() => {
  return (props.representantes?.data || []).filter(rep =>
    rep.id !== null &&
    rep.id !== undefined &&
    rep.id.toString().trim() !== ""
  );
});
const items = ref<CarritoItem[]>([]);

const selectedCliente = ref<{ value: string; label: string } | null>(null);
const selectedRFV = ref<string | null>(null);
const rowsMayoristas = ref<Mayoristas[]>([]);

// Estados de carga y alertas
const loading = ref(false);

const totalItems = computed(() =>
  items.value.reduce((sum, i) => sum + i.unidades, 0)
);

const subTotal = computed(() =>
  parseFloat(
    items.value.reduce((sum, i) => sum + i.precio, 0).toFixed(2)
  )
);

const totalAmount = computed(() =>
  parseFloat(
    (subTotal.value * (1 + Number(tax.value) / 100)).toFixed(2)
  )
);

// Función para manejar el cambio de selección de cliente
const handleClienteUpdate = (value: { value: string; label: string } | null) => {
  selectedCliente.value = value;
};

// Función para manejar el cambio de apertura del dropdown (opcional)
const handleClienteOpenUpdate = (isOpen: boolean) => {
  console.log('Cliente Combobox abierto/cerrado:', isOpen);
};

// Función para resetear el formulario
const resetForm = () => {
  selectedCliente.value = null;
  descripcion.value = '';
  rowsMayoristas.value = [];
  items.value = [];
};

const procesarPedido = async (): Promise<void> => {
  clearAlerts();

  // === VALIDACIONES ===
  if (!selectedCliente.value) {
    showWarning('Por favor selecciona un cliente.');
    return;
  }
  if (!selectedRFV.value) {
    showWarning('Por favor selecciona un representante.');
    return;
  }
  if (rowsMayoristas.value.length === 0) {
    showWarning('Por favor agrega al menos un mayorista.');
    return;
  }
  if (items.value.length === 0) {
    showWarning('Por favor agrega al menos un producto.');
    return;
  }

    const clienteId = selectedCliente.value?.value || null;

    if (!clienteId) {
        showWarning('El cliente seleccionado no es válido.');
        return;
    }

  // Obtener ubicacion GPS
  let location = geoCoords.value
  if (!location) {
    location = await requestLocation()
  }
  if (!location) {
    if (geoDenied.value) {
      showError('Debes habilitar la ubicacion para tomar un pedido. Activa el GPS en la configuracion de tu navegador.')
    } else {
      showError(geoError.value || 'No se pudo obtener la ubicacion. Intentalo de nuevo.')
    }
    return
  }

  const datosPrimarios = {
    cliente: clienteId,
    representante: selectedRFV.value,
    mayoristas: rowsMayoristas.value.map(m => ({
      id: m.codigo,
      nombre: m.mayorista
    })),
    descripcion: descripcion.value,
    impuesto: tax.value,
    lat: location.lat,
    lon: location.lon,
    productos: items.value.map(item => ({
      id: item.codigo,
      unidades: item.unidades,
      precio: item.unitPrice
    }))
  };

  if (!isOnline.value) {
    loading.value = true
    try {
      const result = await submitOrQueue('order', datosPrimarios)
      if (result.queued) {
        showSuccess(`Pedido guardado localmente (${result.localRef}). Se enviara cuando haya conexion.`)
        resetForm()
        keyTables.value++
      }
    } catch (err: any) {
      showError('Error al guardar el pedido localmente.')
    } finally {
      loading.value = false
    }
    return
  }

  // Online: enviar normalmente via Inertia
  router.post(
    route('toma-de-pedidos.procesar'),
    datosPrimarios,
    {
      preserveState: false,
      replace: true,
      onStart: () => (loading.value = true),
      onFinish: () => (loading.value = false),
      onSuccess: () => {
        showSuccess('Pedido procesado exitosamente.');
        resetForm();
        keyTables.value++;
      },
      onError: (errors) => {
        console.error('Error procesando pedido:', errors);

        let errorMessage = 'Hubo un error al procesar el pedido.';
        if (errors.cliente) {
          errorMessage = errors.cliente;
        } else if (errors.representante) {
          errorMessage = errors.representante;
        } else if (errors.productos) {
          errorMessage = 'Verifique los productos seleccionados';
        }

        showError(errorMessage);
      }
    }
  );
};
const reloadProductos = (page: number, pageSize: string, search: string) => {
  productosPage.value = page;
  productosPageSize.value = pageSize;
  productosSearch.value = search;

  router.reload({
    only: ['productos'],
    data: {
      page: page,
      size: parseInt(pageSize),
      search: search,
      idRfv: selectedRFV.value,
    },
  });
};

// Watcher para cambios de paginación
watch([productosPage, productosPageSize], ([newPage, newSize]) => {
  reloadProductos(newPage, newSize, productosSearch.value);
});

// Watchers
watch(selectedRFV, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    resetForm();
    
    // Reiniciar paginación
    productosPage.value = 1;
    productosPageSize.value = '15';
    productosSearch.value = '';

    router.reload({
      only: ['mayoristas', 'productos'],
      data: { 
        idRfv: newVal,
        page: 1,
        size: 15,
        search: ''
      }
    });
  }
});

watch(representantes, (newList) => {
  // Si solo hay un representante (ej: un GRT), lo seleccionamos automáticamente
  if (newList.length === 1 && !selectedRFV.value) {
    selectedRFV.value = newList[0].id?.toString();
  }
}, { immediate: true });

</script>

<template>
  <Head title="Toma de Pedidos" />

  <AppLayout :breadcrumbs="breadcrumbs" class="max-w-full overflow-x-hidden">
    <div class="flex-1 flex flex-col p-4">

      <!-- Componente de Alerta Global -->
      <GlobalAlert
        :type="alertWarning ? 'warning' : alertError ? 'error' : alertSuccess ? 'success' : ''"
        :message="alertMessage"
        @close="clearAlerts"
        auto-hide
        :auto-hide-delay="5000"
      />

      <div v-if="loading" class="flex justify-center items-center p-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-2">Cargando...</span>
      </div>

      <div class="grid grid-cols-1 gap-y-6 overflow-hidden rounded-l">

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 min-w-0">

          <div>
            <Input 
              type="text" 
              placeholder="Codigo" 
              :model-value="selectedCliente?.value || ''" 
              readonly
            />
          </div>
          
          <div class="grid w-full gap-2 order-last md:order-none md:row-span-2 md:col-start-3">
            <Textarea
              v-model="descripcion"
              placeholder="Comentario"
              :maxlength="maxChars"
            />
            <p class="text-sm text-gray-500">
              {{ descripcion.length }} / {{ maxChars }} caracteres
            </p>
          </div>

          <div class="md:order-none md:row-start-2 md:col-start-1">
            <ClienteCombobox
              :selected-r-f-v="selectedRFV"
              :disabled="!selectedRFV || loading"
              placeholder="Buscar cliente…"
              @update:model-value="handleClienteUpdate"
              @update:open="handleClienteOpenUpdate"
            />
          </div>

          <Select v-model="selectedRFV" class="md:order-none md:row-start-1 md:col-start-2"
            :disabled="loading">
            <SelectTrigger>
              <SelectValue placeholder="Representante" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectItem v-for="rep in representantes" :key="rep.id" :value="rep.id.toString()">
                  {{ rep.nombre }}
                </SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>

        </div>

        <TablesTdp
          :key="keyTables"
          @update:items="items = $event"
          @update:mayoristas="rowsMayoristas = $event"
          :mayoristas="props.mayoristas"
          :productos="props.productos"
          @fetch-productos="reloadProductos($event.page, $event.pageSize, $event.search)"
        />

        <!-- Ubicacion GPS -->
        <div class="px-4">
            <div v-if="geoCoords" class="flex items-center gap-2 text-xs text-green-600 dark:text-green-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Ubicacion capturada ({{ geoCoords.lat.toFixed(5) }}, {{ geoCoords.lon.toFixed(5) }})</span>
            </div>
            <div v-else-if="geoLoading" class="flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400">
                <div class="h-3 w-3 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
                <span>Obteniendo ubicacion...</span>
            </div>
            <div v-else-if="geoError" class="flex items-center gap-2 text-xs text-red-600 dark:text-red-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <span>{{ geoError }}</span>
                <button type="button" class="underline font-medium" @click="requestLocation">Reintentar</button>
            </div>
        </div>

        <!-- Barra de acción abajo -->
        <div class="flex flex-wrap items-center gap-4 p-4 rounded shadow mt-4">
          <div class="flex items-baseline space-x-6">
            <span class="font-medium">Total items: <strong>{{ totalItems }}</strong></span>
            <span class="font-medium">Monto total: <strong>${{ totalAmount }}</strong></span>
          </div>

          <div class="flex items-center space-x-2">
            <label class="font-medium whitespace-nowrap">Impuesto %</label>
            <vueNumberInput
              v-model="tax"
              size="small"
              :min="0"
              :max="16"
              inline
              center
              controls
            />
          </div>

          <div class="ml-auto flex items-center gap-2">
            <span v-if="!isOnline" class="text-xs text-amber-600 font-medium">Modo offline</span>
            <Button @click="procesarPedido">
              {{ isOnline ? 'PROCESAR' : 'GUARDAR PEDIDO' }}
            </Button>
          </div>
        </div>

        <p class="mt-2 text-sm text-gray-600 text-center">
          NOTA: El impuesto es calculado al momento de la conciliación de esta orden, los precios como descuentos
          pueden cambiar al momento de la conciliación.
        </p>

      </div>
    </div>
  </AppLayout>
</template>