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
import GenericCombobox, { type SelectOption } from '@/components/GenericCombobox.vue'  // ← reemplaza SingleSelectSearch
import { useValidationAlert } from '@/composables/useValidationAlert';
import axios from 'axios'
import type { Empresa, FormaPago, OrdenInfo, TableRow, SummaryItem, Producto } from '@/types/interfacesConciliarFactura';
import type { PaginatedData } from '@/types/pagination';
import { onMounted } from 'vue';

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF', href: '/dashboard' },
  { label: 'Consolidar Factura' },
];

const { success: alertSuccess, error: alertError, warning: alertWarning, message: alertMessage, showWarning, showError, clear } = useValidationAlert();

const props = defineProps<{
  empresas?: Empresa[];
  formasDePago?: FormaPago[];
  ordenInfo?: OrdenInfo;
  productos?: PaginatedData<Producto>;
  selectedFabricante: string | null;
}>()

// Estado reactivo
const FechaSeleccionada   = ref<DateValue>()
const fabricanteSeleccionado = ref<string | null>(props.selectedFabricante || null)
const selectedFormaPago   = ref<string | null>(null)
const nroFactura          = ref<string>('')
const almacenDespacho     = ref<string>('')
const loading             = ref(false)

// ─── Combobox de órdenes con búsqueda dinámica ───────────────────────────────
const ordenOptions        = ref<SelectOption[]>([])
const ordenLoading        = ref(false)
const ordenSeleccionada   = ref<SelectOption | null>(
  props.ordenInfo?.id
    ? { value: String(props.ordenInfo.id), label: `${props.ordenInfo.id} - ${props.ordenInfo.cliente?.nombre ?? ''}` }
    : null
)

async function buscarOrdenes(term: string) {
  ordenLoading.value = true
  try {
    const { data } = await axios.get('/conciliar/buscar-ordenes', {
      params: { term }
    })
    ordenOptions.value = data   // ya viene como [{ value, label }]
  } catch {
    ordenOptions.value = []
  } finally {
    ordenLoading.value = false
  }
}

function onOrdenChange(opcion: SelectOption | null) {
  ordenSeleccionada.value = opcion

  if (!opcion) {
    // Limpiar detalle
    router.get(route('consolidar.index'), {
      fabricante: fabricanteSeleccionado.value,
    }, {
      preserveState: true,
      preserveScroll: true,
      only: ['ordenInfo', 'productos'],
    })
    return
  }

  // Cargar detalle de la orden seleccionada
  router.get(route('consolidar.index'), {
    fabricante: fabricanteSeleccionado.value,
    orden: opcion.value,
    product_page: 1,
    product_size: productPageSize.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['ordenInfo', 'productos'],
  })
}

// Paginación productos
const productPage     = ref(props.productos?.meta?.current_page || 1)
const productPageSize = ref(props.productos?.meta?.per_page?.toString() || '15')

watch(() => props.productos?.meta?.per_page, (v) => {
  if (v && v.toString() !== productPageSize.value) productPageSize.value = v.toString()
}, { immediate: true })

watch(() => props.productos?.meta?.current_page, (v) => {
  if (v && v !== productPage.value) productPage.value = v
}, { immediate: true })

// Filas de la tabla
const rowsData = ref<TableRow[]>([])

watch(() => props.productos, (newProductos) => {
  if (newProductos?.data) {
    rowsData.value = newProductos.data.map(producto => ({
      codigo:      producto.codigo,
      producto:    producto.producto,
      cantidad:    producto.cantidad,
      productoKit: producto.precio,
      costo:       producto.precio_total,
      descuento:   producto.descuento,
      despachadas: producto.cantidad,
      faltantes:   producto.faltante,
      accion:      ''
    }))
  } else {
    rowsData.value = []
  }
}, { immediate: true })

// Summaries
const summaries = computed<SummaryItem[]>(() => {
  if (!ordenSeleccionada.value || !props.ordenInfo) return getDefaultSummaries()

  const subtotal = calcularSubtotal()
  const total    = subtotal * (1 + Number(props.ordenInfo.impuesto) / 100)

  return [
    { label: 'Cliente: ',        value: props.ordenInfo.cliente.nombre },
    { label: 'RFV: ',            value: props.ordenInfo.rfv.nombre },
    { label: 'Mayorista: ',      value: props.ordenInfo.mayorista.nombre },
    { label: 'Registrado por: ', value: props.ordenInfo.registradoPor || 'N/A' },
    { label: 'Impuesto: ',       value: formatCurrency(Number(props.ordenInfo.impuesto)) },
    { label: 'Sub-Total: ',      value: formatCurrency(subtotal) },
    { label: 'Total: ',          value: formatCurrency(total) },
  ]
})

const esValido = computed(() => {
  if (!ordenSeleccionada.value)       return false
  if (!nroFactura.value.trim())        return false
  if (!almacenDespacho.value.trim())   return false
  if (!selectedFormaPago.value)        return false
  if (!FechaSeleccionada.value)        return false
  return rowsData.value.some(p => p.despachadas > 0)
})

const getDefaultSummaries = (): SummaryItem[] => [
  { label: 'Cliente: ',        value: 'N/A' },
  { label: 'RFV: ',            value: 'N/A' },
  { label: 'Mayorista: ',      value: 'N/A' },
  { label: 'Registrado por: ', value: 'N/A' },
  { label: 'Impuesto: ',       value: '$0'  },
  { label: 'Sub-Total: ',      value: '$0'  },
  { label: 'Total: ',          value: '$0'  },
]

const calcularSubtotal = () =>
  rowsData.value.reduce((t, p) => t + p.despachadas * p.productoKit, 0)

const formatCurrency = (value: number) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2 }).format(value)

const handleFabricanteChange = () => {
  ordenSeleccionada.value = null
  ordenOptions.value      = []
  productPage.value       = 1

  router.get(route('consolidar.index'), {
    fabricante: fabricanteSeleccionado.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['selectedFabricante', 'empresas', 'ordenInfo', 'productos'],
  })
}

const handleProductPageChange = (newPage: number) => {
  productPage.value = newPage
  router.get(route('consolidar.index'), {
    fabricante:   fabricanteSeleccionado.value,
    orden:        ordenSeleccionada.value?.value,
    product_page: newPage,
    product_size: productPageSize.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['productos', 'ordenInfo'],
    replace: true,
  })
}

const handleProductPageSizeChange = (newSize: string) => {
  productPageSize.value = newSize
  productPage.value     = 1
  router.get(route('consolidar.index'), {
    fabricante:   fabricanteSeleccionado.value,
    orden:        ordenSeleccionada.value?.value,
    product_page: 1,
    product_size: newSize,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['ordenes', 'ordenInfo', 'productos'],
    replace: true,
  })
}

const resetForm = () => {
  nroFactura.value          = ''
  almacenDespacho.value     = ''
  selectedFormaPago.value   = null
  FechaSeleccionada.value   = undefined
  ordenSeleccionada.value   = null
  ordenOptions.value        = []
  rowsData.value            = []
}

const procesarFactura = () => {
  clear()

  if (!esValido.value) {
    showWarning('Por favor complete todos los campos y despache al menos un producto')
    return
  }

  if (!ordenSeleccionada.value || !props.ordenInfo) {
    showError('Error: Información de orden no disponible')
    return
  }

  const datosPrimarios = {
    norden:        ordenSeleccionada.value.value,
    nfactura:      nroFactura.value,
    almacen:       almacenDespacho.value,
    forma_pago:    selectedFormaPago.value,
    fecha:         FechaSeleccionada.value?.toString() || '',
    impuesto:      props.ordenInfo.impuesto,
    productos:     rowsData.value.map(p => ({
      id:          p.codigo,
      despachadas: p.despachadas,
      precio:      p.productoKit,
    })),
    fabricante:    fabricanteSeleccionado.value,
    product_page:  productPage.value,
    product_size:  productPageSize.value,
  }

  router.post(route('conciliar-factura.store'), datosPrimarios, {
    onStart:   () => (loading.value = true),
    onFinish:  () => (loading.value = false),
    onSuccess: () => {
      // El propio redirect de la petición ya vuelve sin 'orden' seleccionada
      // (ver ConciliarFacturaController::store), no hace falta una segunda
      // navegación para "limpiarla": eso disparaba una recarga redundante
      // casi simultánea a la del envío, que el navegador cancelaba.
      resetForm()
    },
    onError: (errors) => {
      console.error('Error al procesar factura:', errors)
      if (errors.error)         showError(errors.error)
      else if (errors.nfactura) showWarning(errors.nfactura)
      else if (errors.productos) showWarning('Verifique las cantidades despachadas')
      else                      showError('Hubo un error al procesar la factura')
    },
  })
}

const TableColumns = [
  { key: 'codigo',      label: 'Código',         className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'producto',    label: 'Producto',        className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'cantidad',    label: 'Cantidad',        className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'productoKit', label: 'Precio Unitario', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'costo',       label: 'Costo Total',     className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'descuento',   label: 'Descuento',       className: 'px-4 py-2 whitespace-nowrap text-left' },
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
        row.despachadas = value
        row.faltantes   = row.cantidad - value
      },
    }),
  },
  { key: 'faltantes', label: 'Faltantes', className: 'px-4 py-2 whitespace-nowrap text-left' },
]

onMounted(() => {
  // Si ya hay una orden preseleccionada, cargar sus opciones en el combobox
  if (props.ordenInfo?.id) {
    ordenOptions.value = [{
      value: String(props.ordenInfo.id),
      label: `${props.ordenInfo.id} - ${props.ordenInfo.cliente?.nombre ?? ''}`,
    }]
  }
})
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

      <SIIF_Info_cons01 message="Solo se pueden conciliar ordenes que no han sido anuladas o previamente conciliadas, solo se mostrara el primer mayorista solicitado en una orden en caso de existir mas, si la orden se concilia con productos faltantes el sistema generara y le mostrara una nueva orden con el siguiente mayorista, si solo si, existe otro mayorista. ¡Importante! Debe seleccionar una empresa fabricante para filtrar correctamente las ordenes a conciliar" />

      <!-- Selector de fabricante y botón facturar -->
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

      <!-- Formulario principal -->
      <div class="mt-6 border p-4 rounded shadow">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">

          <!-- Combobox de órdenes con búsqueda dinámica -->
          <div class="col-span-1">
            <GenericCombobox
              v-model="ordenSeleccionada"
              :options="ordenOptions"
              :dynamic-search="true"
              :dynamic-loading="ordenLoading"
              :disabled="!fabricanteSeleccionado || loading"
              placeholder="Buscar orden..."
              @update:model-value="onOrdenChange"
              @dynamic-search="buscarOrdenes"
            />
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

        <!-- Tabla de productos -->
        <div v-if="ordenSeleccionada && props.productos" class="mt-6">
          <h3 class="text-lg font-semibold mb-4">Productos de la Orden {{ ordenSeleccionada.value }}</h3>
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
            :subHeaderProps="{ showExport: false, summaries: summaries }"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>