<!-- resources/js/components/AddProducto_modal.vue -->
<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Button from '../ui/button/Button.vue'
import BaseModal from '../BaseModal.vue'
import GlobalTable from '../GlobalTable.vue'
import Input from '../ui/input/Input.vue'
import vueNumberInput from '../vue-number-input.vue'
import { type PaginatedData } from '@/types/pagination';
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

const leftData = computed(() =>
  productosData.value.filter(p =>
    !carrito.value.some(c => c.codigo === p.codigo)
  )
)

const search = ref('')

// Columnas para la tabla de productos disponibles
const columnsProducto = [
  { key: 'codigo',          label: 'Codigo',      className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'producto',        label: 'Producto',    className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'precio',          label: 'Precio',      className: 'px-4 py-2 whitespace-nowrap text-left' },
]

// Columnas para la tabla del carrito
const columnsCarrito = [
  { key: 'codigo',          label: 'Codigo',      className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'producto',        label: 'Producto',    className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'unidades',        label: 'Unidades',    cellComponent: vueNumberInput, cellProps: { size: 'small', min: 1, inline: true, center: true, controls: true } },
  { key: 'precio',          label: 'Precio',      className: 'px-4 py-2 whitespace-nowrap text-left' },
]

// --- Paginación del Servidor (Productos) ---
const productosPagination = computed(() => ({
  
  page: props.currentPage || 1,
  pageSize: props.pageSize || '15',
  totalRecords: props.totalRecords || props.productos?.meta?.total || 0,
}));

// --- Paginación del Carrito (Local) ---
const carritoPagination = ref({
  page: 1,
  pageSize: '15'
});

const carritoPaginado = computed(() => {
  const startIndex = (carritoPagination.value.page - 1) * parseInt(carritoPagination.value.pageSize, 10);
  const endIndex = startIndex + parseInt(carritoPagination.value.pageSize, 10);
  return carrito.value.slice(startIndex, endIndex);
});

const carritoTotalRecords = computed(() => carrito.value.length);

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

const filteredData = computed(() =>
  leftData.value.filter(row =>
    Object.values(row).some(val =>
      String(val).toLowerCase().includes(search.value.toLowerCase())
    )
  )
)

// Sincroniza el v-model del modal
const internal = ref(props.modelValue)

watch(() => props.modelValue, (val, oldVal) => {
    if (val === oldVal) return
    internal.value = val
    if (val) { carrito.value = [] }
})
watch(internal, (val, oldVal) => {
    if (val === oldVal) return
    emit('update:modelValue', val)
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm', items: CarritoItem[]): void
  (e: 'update:page', value: number): void;
  (e: 'update:pageSize', value: string): void;
}>()

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
  <BaseModal v-model="internal" 
    title="Buscar Productos" 
    description=" "
    size="xl">
    <template #default>

    <div class="mb-4 flex items-center justify-between gap-2">
        <Input
          v-model="search"
          type="text"
          placeholder="Buscar..."
          class="border rounded px-3 py-1 w-full"
        />
      </div> 
    <div class="flex flex-col md:flex-row gap-6">
        <div class="flex-1">
          <h3 class="text-sm font-medium mb-2">Todos los productos</h3>
            <div class="max-h-[400px] overflow-y-auto">
              <GlobalTable
                :columns="columnsProducto"
                :rows="filteredData"
                :total-records="productosPagination.totalRecords"
                :current-page="productosPagination.page"
                :page-size="productosPagination.pageSize"
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
      <Button variant="default" @click="internal = false; carrito = []">Cancelar</Button>
      <Button variant="default" @click="internal = false; emit('confirm', carrito); carrito = []">Agregar</Button>
    </template>
  </BaseModal>
</template>