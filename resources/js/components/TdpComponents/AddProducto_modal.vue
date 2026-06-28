<!-- resources/js/components/AddProducto_modal.vue -->
<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Button from '../ui/button/Button.vue'
import BaseModal from '../BaseModal.vue'
import GlobalTable from '../GlobalTable.vue'
import Input from '../ui/input/Input.vue'
import vueNumberInput from '../vue-number-input.vue'
import { type PaginatedData } from '@/types/pagination';
import { debounce } from 'lodash'
import { type CarritoItem, Producto} from '@/types/interfaces';

const props = defineProps<{
  modelValue: boolean;
  productos?: PaginatedData<Producto> | null;
  loading?: boolean;
  currentPage?: number;
  pageSize?: string;
  totalRecords?: number;
  links?: Array<{ url: string | null; label: string; active: boolean }>;
}>();

const productosData = computed(() => {
  return props.productos?.data || [];
});

const carrito = ref<CarritoItem[]>([])

const search = ref('')

// Columnas para la tabla de productos disponibles
const columnsProducto = [
  { key: 'codigo',          label: 'Codigo',      className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'producto',        label: 'Producto',    className: 'px-4 py-2 text-left max-w-[200px] truncate' },
  { key: 'precio',          label: 'Precio',      className: 'px-4 py-2 whitespace-nowrap text-left' },
]

// Columnas para la tabla del carrito
const columnsCarrito = [
  { key: 'codigo',          label: 'Codigo',      className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'producto',        label: 'Producto',    className: 'px-4 py-2 text-left max-w-[200px] truncate' },
  { key: 'unidades',        label: 'Unidades',    cellComponent: vueNumberInput, cellProps: { size: 'small', min: 1, inline: true, center: true, controls: true } },
  { key: 'precio',          label: 'Precio',      className: 'px-4 py-2 whitespace-nowrap text-left' },
]

// --- Paginación del Servidor (Productos) ---
const productosPagination = computed(() => ({
  page: props.currentPage || 1,
  pageSize: props.pageSize || '15',
  totalRecords: props.totalRecords || props.productos?.meta?.total || 0,
  links: props.links || props.productos?.links || []
}));

// --- Paginación del Carrito (Local) ---
const carritoPagination = ref({
  page: 1,
  pageSize: '15' // Usamos string, lo convertimos con parseInt
});

const carritoPaginado = computed(() => {
  const startIndex = (carritoPagination.value.page - 1) * parseInt(carritoPagination.value.pageSize, 10);
  const endIndex = startIndex + parseInt(carritoPagination.value.pageSize, 10);
  return carrito.value.slice(startIndex, endIndex);
});

const carritoTotalRecords = computed(() => carrito.value.length);

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm', items: CarritoItem[]): void
  (e: 'update:page', value: number): void;
  (e: 'update:pageSize', value: string): void;
  (e: 'search', value: string): void;
}>()

// Sincroniza el v-model del modal
const internal = ref(props.modelValue)
watch(() => props.modelValue, val => {
    internal.value = val
    if (val) {
        // Opcional: Limpiar carrito al abrir
        carrito.value = [];
    }
})
watch(internal, val => emit('update:modelValue', val))

const emitSearch = debounce(() => {
    emit('search', search.value);
}, 300);

watch(search, emitSearch)

// --- Manejadores de Paginación ---
const handleProductosPageChange = (newPage: number) => {
  emit('update:page', newPage);
};

const handleProductosPageSizeChange = (newSize: string) => {
  emit('update:pageSize', newSize);
};

const handleCarritoPageChange = (newPage: number) => {
  carritoPagination.value.page = newPage;
};

const handleCarritoPageSizeChange = (newSize: string) => {
  carritoPagination.value.pageSize = newSize;
  carritoPagination.value.page = 1;
};

function agregarAlCarrito(prod: Producto) {
  const idx = carrito.value.findIndex(p => p.codigo === prod.codigo)
  if (idx === -1) {
    carrito.value.push({
      id:         Math.floor(Math.random() * 1e9),
      codigo:     prod.codigo,
      producto:   prod.producto,
      unitPrice:  prod.precio ?? 0,
      unidades:   1,
      precio:     prod.precio ?? 0,
    })
  } else {
    carrito.value[idx].unidades += 1
    carrito.value[idx].precio = carrito.value[idx].unitPrice * carrito.value[idx].unidades;
  }
}

function quitarDelCarrito(item: CarritoItem) {
  carrito.value = carrito.value.filter(c => c.codigo !== item.codigo)
}

watch(carrito, (newCarrito) => {
  newCarrito.forEach(item => {
    item.precio = parseFloat((item.unitPrice * item.unidades).toFixed(2));
  });
}, { deep: true })

</script>

<template>
  <BaseModal v-model="internal" title="Buscar Productos" size="xl">
    <template #default>
      <div class="mb-4">
        <Input v-model="search" type="text" placeholder="Buscar..." />
      </div>
      <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
          <h3 class="text-sm font-medium mb-2">Todos los productos</h3>
            <div class="max-h-[400px] overflow-y-auto">
              <GlobalTable
                :columns="columnsProducto"
                :rows="productosData"
                :total-records="productosPagination.totalRecords"
                :current-page="productosPagination.page"
                :page-size="productosPagination.pageSize"
                :links="productosPagination.links"
                :actions="[{ key: 'add', handler: agregarAlCarrito }]"
                autoAddActionsColumn
                :loading="loading"
                @update:page="handleProductosPageChange"
                @update:pageSize="handleProductosPageSizeChange"
              />
            </div>
        </div>
        <div class="w-px bg-gray-300 mx-2" />
        <div class="flex-1">
          <h3 class="text-sm font-medium mb-2">Carrito ({{ carritoTotalRecords }} items)</h3>
            <div class="max-h-[400px] overflow-y-auto">
              <GlobalTable
                :columns="columnsCarrito"
                :rows="carritoPaginado" 
                :total-records="carritoTotalRecords"
                :current-page="carritoPagination.page"
                :page-size="carritoPagination.pageSize"
                :actions="[{ key: 'delete', handler: quitarDelCarrito }]"
                autoAddActionsColumn
                @update:page="handleCarritoPageChange" 
                @update:pageSize="handleCarritoPageSizeChange"
                :show-pagination="true"
              />
            </div>
        </div>
      </div>
    </template>
    <template #footer>
      <Button @click="internal = false; carrito = []">Cancelar</Button>
      <Button @click="internal = false; emit('confirm', carrito); carrito = []">Agregar</Button>
    </template>
  </BaseModal>
</template>