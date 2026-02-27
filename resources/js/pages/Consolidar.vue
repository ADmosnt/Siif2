<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import type { DateValue } from '@internationalized/date';
import { route } from 'ziggy-js';
import SIIF_Info_cons01 from '@/components/SIIF_Info_cons01.vue';
import Button from '@/components/ui/button/Button.vue';
import GlobalSelect from '@/components/GlobalSelect.vue';
import Input from '@/components/ui/input/Input.vue';
import GlobalTable from '@/components/GlobalTable.vue';
import simpleDatePicker from '@/components/simpleDatePicker.vue';
import vueNumberInput from '@/components/vue-number-input.vue';
import { useValidationAlert } from '@/composables/useValidationAlert';
import type { Empresa, FormaPago, OrdenSelect, OrdenInfo, TableRow, SummaryItem, Producto } from '@/types/interfacesConciliarFactura';
import type { PaginatedData } from '@/types/pagination';
import SingleSelectSearch from '@/components/SingleSelectSearch.vue';
import { onMounted } from 'vue';

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF', href: '/dashboard' },
  { label: 'Consolidar Factura' },
];

const { success: alertSuccess, error: alertError, warning: alertWarning, message: alertMessage, showWarning, showError, clear } = useValidationAlert();

// Props
const props = defineProps<{
  empresas?: Empresa[];
  formasDePago?: FormaPago[];
  ordenes?: PaginatedData<OrdenSelect>;
  ordenInfo?: OrdenInfo;
  productos?: PaginatedData<Producto>; 
  selectedFabricante: string | null;
}>();

// Estado reactivo
const FechaSeleccionada = ref<DateValue>();
const fabricanteSeleccionado = ref<string | null>(props.selectedFabricante || null);
const selectedFormaPago = ref<string | null>(null);
const nroFactura = ref<string>('');
const almacenDespacho = ref<string>('');
const loading = ref(false);

// Paginación
const ordenPage = ref(props.ordenes?.meta?.current_page || 1);
const ordenPageSize = ref(props.ordenes?.meta?.per_page?.toString() || '25');
const productPage = ref(props.productos?.meta?.current_page || 1);
const productPageSize = ref(props.productos?.meta?.per_page?.toString() || '15');

const buildQueryParams = (overrides: Record<string, any> = {}) => {
  const params: Record<string, string | number> = {};

  if (fabricanteSeleccionado.value) params.fabricante = fabricanteSeleccionado.value;
  if (selectedOrdenId.value) params.orden = selectedOrdenId.value;
  
  params.page = ordenPage.value;
  params.size = ordenPageSize.value;
  params.product_page = overrides.product_page ?? productPage.value;
  params.product_size = overrides.product_size ?? productPageSize.value;

  // Puedes agregar otros si los necesitas
  return params;
};
// ID de orden seleccionada
const selectedOrdenId = ref<string | null>(
  props.ordenInfo?.id ? String(props.ordenInfo.id) : null
);

// Datos de productos
const rowsData = ref<TableRow[]>([]);

// Summaries computados
const summaries = computed<SummaryItem[]>(() => {
  if (!selectedOrdenId.value || !props.ordenInfo) {
    return getDefaultSummaries();
  }

  const subtotal = calcularSubtotal();
  const total = subtotal * (1 + Number(props.ordenInfo.impuesto) / 100);

  return [
    { label: 'Cliente: ', value: props.ordenInfo.cliente.nombre },
    { label: 'RFV: ', value: props.ordenInfo.rfv.nombre },
    { label: 'Mayorista: ', value: props.ordenInfo.mayorista.nombre },
    { label: 'Registrado por: ', value: props.ordenInfo.registradoPor || 'N/A' },
    { label: 'Impuesto: ', value: formatCurrency(Number(props.ordenInfo.impuesto)) },
    { label: 'Sub-Total: ', value: formatCurrency(subtotal) },
    { label: 'Total: ', value: formatCurrency(total) }
  ];
});

// Computed para validación y estado
const esValido = computed(() => {
  if (!selectedOrdenId.value) return false;
  if (!nroFactura.value.trim()) return false;
  if (!almacenDespacho.value.trim()) return false;
  if (!selectedFormaPago.value) return false;
  if (!FechaSeleccionada.value) return false;
  
  return rowsData.value.some(producto => producto.despachadas > 0);
});

const noHayOrdenes = computed(() => {
  return props.ordenes && (!props.ordenes.data || props.ordenes.data.length === 0);
});

const mensajeNoOrdenes = computed(() => {
  if (!fabricanteSeleccionado.value) {
    return "Por favor, seleccione un fabricante para ver las órdenes pendientes";
  }
  
  if (noHayOrdenes.value) {
    return "No hay órdenes pendientes de conciliación para este fabricante";
  }
  
  return "";
});

const ordenOptions = computed(() => {
  return props.ordenes?.data?.map(orden => ({
    value: String(orden.id), 
    label: `${orden.id} - ${orden.cliente} (${orden.fecha})` 
  })) || [];
});

// Funciones de utilidad
const getDefaultSummaries = (): SummaryItem[] => [
  { label: 'Cliente: ', value: 'N/A' },
  { label: 'RFV: ', value: 'N/A' },
  { label: 'Mayorista: ', value: 'N/A' },
  { label: 'Registrado por: ', value: 'N/A' },
  { label: 'Impuesto: ', value: '$0' },
  { label: 'Sub-Total: ', value: '$0' },
  { label: 'Total: ', value: '$0' }
];

const calcularSubtotal = (): number => {
  return rowsData.value.reduce((total, producto) => {
    return total + (producto.despachadas * producto.productoKit);
  }, 0);
};

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 2
  }).format(value);
};

// watchers

watch(() => props.productos?.meta?.per_page, (newPerPage) => {
  if (newPerPage && newPerPage.toString() !== productPageSize.value) {
    productPageSize.value = newPerPage.toString();
  }
}, { immediate: true });

watch(() => props.productos?.meta?.current_page, (newPage) => {
  if (newPage && newPage !== productPage.value) {
    productPage.value = newPage;
  }
}, { immediate: true });

watch(() => props.productos, (newProductos) => {
  if (newProductos?.data) {
    rowsData.value = newProductos.data.map(producto => ({
      codigo: producto.codigo,
      producto: producto.producto,
      cantidad: producto.cantidad,
      productoKit: producto.precio,
      costo: producto.precio_total,
      descuento: producto.descuento,
      despachadas: producto.cantidad,
      faltantes: producto.faltante,
      accion: ''
    }));
  } else {
    rowsData.value = [];
  }
}, { immediate: true });


const limpiarSeleccionOrden = () => {
  selectedOrdenId.value = null;
  rowsData.value = [];
  productPage.value = 1;
  
  router.get(route('consolidar.index'), {
    fabricante: fabricanteSeleccionado.value,
    page: ordenPage.value,
    size: ordenPageSize.value
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['ordenes', 'ordenInfo', 'productos']
  });
};

// Handlers
const handleFabricanteChange = () => {
  selectedOrdenId.value = null;
  productPage.value = 1;
  
  const params = buildQueryParams({ 
    fabricante: fabricanteSeleccionado.value,
    orden: null, // explícitamente lo quitamos
    product_page: 1 
  });
  // Eliminar `orden` si es null
  if (params.orden === null) delete params.orden;
  
  router.get(route('consolidar.index'), params, {
    preserveState: true,
    preserveScroll: true,
    only: ['ordenes', 'selectedFabricante', 'empresas', 'ordenInfo', 'productos']
  });
};

const handleOrdenChange = (ordenId: string | null) => { 
  if (ordenId === null) {
    limpiarSeleccionOrden();
    return;
  }
  
  selectedOrdenId.value = ordenId;
  productPage.value = 1;
  
  router.get(route('consolidar.index'), buildQueryParams({ 
    orden: ordenId, 
    product_page: 1 
  }), {
    preserveState: true,
    preserveScroll: true,
    only: ['ordenes', 'ordenInfo', 'productos']
  });
};

const handleProductPageChange = (newPage: number) => {
  productPage.value = newPage;
  router.get(route('consolidar.index'), buildQueryParams({ product_page: newPage }), {
    preserveState: true,
    preserveScroll: true,
    only: ['productos', 'ordenInfo'],
    replace: true
  });
};

const handleProductPageSizeChange = (newSize: string) => {
  productPageSize.value = newSize;
  productPage.value = 1;
  router.get(route('consolidar.index'), buildQueryParams({ product_page: 1, product_size: newSize }), {
    preserveState: true,
    preserveScroll: true,
    only: ['ordenes', 'ordenInfo', 'productos'],
    replace: true
  });
};

const resetForm = () => {
  nroFactura.value = '';
  almacenDespacho.value = '';
  selectedFormaPago.value = null;
  FechaSeleccionada.value = undefined;
  limpiarSeleccionOrden(); // Reutilizar la función de limpieza
};

const procesarFactura = () => {
  clear();
  
  if (!esValido.value) {
    showWarning('Por favor complete todos los campos y despache al menos un producto');
    return;
  }

  if (!selectedOrdenId.value || !props.ordenInfo) {
    showError('Error: Información de orden no disponible');
    return;
  }

  const datosPrimarios = {
    norden: selectedOrdenId.value,
    nfactura: nroFactura.value,
    almacen: almacenDespacho.value,
    forma_pago: selectedFormaPago.value,
    fecha: FechaSeleccionada.value?.toString() || '',
    impuesto: props.ordenInfo.impuesto,
    productos: rowsData.value.map(producto => ({
      id: producto.codigo,
      despachadas: producto.despachadas,
      precio: producto.productoKit
    })),
    fabricante: fabricanteSeleccionado.value,
    page: ordenPage.value,
    size: ordenPageSize.value,
    product_page: productPage.value,
    product_size: productPageSize.value
  };

  router.post(route('conciliar-factura.store'),
    datosPrimarios,
    {
      onStart: () => (loading.value = true),
      onFinish: () => (loading.value = false),
      onSuccess: () => {
        resetForm();
        handleFabricanteChange();
      },
      onError: (errors) => {
        console.error('Error al procesar factura:', errors);
        
        if (errors.error) {
          showError(errors.error);
        } else if (errors.nfactura) {
          showWarning(errors.nfactura);
        } else if (errors.productos) {
          showWarning('Verifique las cantidades despachadas');
        } else {
          showError('Hubo un error al procesar la factura');
        }
      }
    }
  );
};

// Columnas de la tabla
const TableColumns = [
  { key: 'codigo', label: 'Código', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'producto', label: 'Producto', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'cantidad', label: 'Cantidad', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'productoKit', label: 'Precio Unitario', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'costo', label: 'Costo Total', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'descuento', label: 'Descuento', className: 'px-4 py-2 whitespace-nowrap text-left' },
  {
    key: 'despachadas',
    label: 'Despachadas',
    className: 'px-4 py-2 whitespace-nowrap text-left',
    cellComponent: vueNumberInput,
    cellProps: (row: TableRow) => ({
      modelValue: row.despachadas,
      min: 0,
      max: row.cantidad,
      inline: true,
      controls: true,
      size: 'small',
      'onUpdate:modelValue': (value: number) => {
        row.despachadas = value;
        row.faltantes = row.cantidad - value;
      }
    })
  },
  { key: 'faltantes', label: 'Faltantes', className: 'px-4 py-2 whitespace-nowrap text-left' },
];

onMounted(() => {
  console.log('Flash messages:', page.props.flash);
});
</script>

<template>
  <Head title="Consolidar Factura" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      
      <!-- Alertas -->
      <div v-if="alertSuccess" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <p class="text-center">{{ alertMessage }}</p>
      </div>
      <div v-if="alertError" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <p class="text-center">{{ alertMessage }}</p>
      </div>
      <div v-if="alertWarning" class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
        <p class="text-center">{{ alertMessage }}</p>
      </div>
      
      <div v-if="loading" class="flex justify-center items-center p-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-2">Procesando factura...</span>
      </div>
      
      <SIIF_Info_cons01
        message="
          Solo se pueden conciliar ordenes que no han sido anuladas o previamente conciliadas, solo se mostrara el primer mayorista solicitado en una orden en caso de existir mas, si la orden se concilia con productos faltantes el sistema generara y le mostrara una nueva orden con el siguiente mayorista, si solo si, existe otro mayorista.
              
        ¡Importante! Debe seleccionar una empresa fabricante para filtrar correctamente las ordenes a conciliar
        "
      />
      
      <!-- Selector de fabricante y botón de facturar -->
      <div class="border rounded-lg p-4 mb-6">
        <div class="flex flex-col md:flex-row items-center gap-4">
          <div class="w-full md:w-1/3">
            <GlobalSelect
              v-model="fabricanteSeleccionado"
              :options="props.empresas || []"
              placeholder="Fabricante"
              @update:modelValue="handleFabricanteChange"
            />
          </div>

          <div class="mt-4 md:mt-0">
            <Button 
              @click="procesarFactura" 
              :disabled="!esValido || loading"
              class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            >
              FACTURAR
            </Button>
          </div>
        </div>
      </div>

      <!-- Contenedor principal del formulario -->
      <div class="mt-6 border p-4 rounded shadow">
        <!-- Filtros / Campos del formulario -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
          <div class="col-span-1">
            <SingleSelectSearch 
              v-model="selectedOrdenId"
              :options="ordenOptions"
              placeholder="Buscar Nro Orden..."
              maxListHeight="12rem"
              @update:modelValue="handleOrdenChange"
              :disabled="loading" 
              :clearable="true" />
          </div>
          <div class="flex items-center">
            <Button 
              @click="limpiarSeleccionOrden"
              :disabled="!selectedOrdenId"
              class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded ml-2"
            >
              Limpiar Selección
            </Button>
          </div>

          <div>
            <Input v-model="nroFactura" type="text" placeholder="N° de Factura" />
          </div>

          <div>
            <Input v-model="almacenDespacho" type="text" placeholder="Almacen de Despacho" />
          </div>

          <div>
            <GlobalSelect
              v-model="selectedFormaPago"
              :options="props.formasDePago || []"
              placeholder="Forma de pago"
            />
          </div>
          
          <div>
            <simpleDatePicker v-model="FechaSeleccionada" placeholder="Fecha de factura" />
          </div>
        </div>
        
        <!-- Mensaje cuando no hay órdenes -->
        <div v-if="noHayOrdenes" class="p-4 text-center text-gray-500 bg-gray-50 rounded">
          {{ mensajeNoOrdenes }}
        </div>
        
        <!-- Tabla de productos de la orden seleccionada -->
        <div v-if="selectedOrdenId && props.productos" class="mt-6">
          <h3 class="text-lg font-semibold mb-4">Productos de la Orden {{ selectedOrdenId }}</h3>
          
          <GlobalTable
            :columns="TableColumns"
            :rows="rowsData" 
            :links="props.productos.links" 
            :total-records="props.productos?.meta?.total || 0"
            :current-page="productPage"
            :page-size="productPageSize"
            @update:page="handleProductPageChange"
            @update:pageSize="handleProductPageSizeChange"
            showSubHeader
            :subHeaderProps="{
              showExport: false,
              summaries: summaries
            }"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>