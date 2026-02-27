<!-- resources/js/components/AddMuestra_modal.vue -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import Button from '../ui/button/Button.vue'
import BaseModal from '../BaseModal.vue'
import GlobalTable from '../GlobalTable.vue'
import Input from '../ui/input/Input.vue'
import vueNumberInput from '../vue-number-input.vue'
import { type PaginatedData } from '@/types/pagination';
import { type MuestrasItem, Producto } from '@/types/interfaces';

const props = defineProps<{
  modelValue: boolean;
  productos?: PaginatedData<Producto> | null;
  loading?: boolean;
  // Props para paginación
  currentPage?: number;
  pageSize?: string;
  totalRecords?: number;
  links?: Array<{ url: string | null; label: string; active: boolean }>;
}>();

// Asegurarnos de que productos tiene datos
const productosData = computed(() => {
  return props.productos?.data || [];
});

const muestras = ref<MuestrasItem[]>([])

const leftData = computed(() =>
  productosData.value.filter(p =>
    !muestras.value.some(c => c.codigo === p.codigo)
  )
)

const columnsProducto = [
  { key: 'codigo',   label: 'Codigo',   className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'producto', label: 'Producto', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'lote',     label: 'Lote',     className: 'px-4 py-2 whitespace-nowrap text-left' },
] 

const newReporTableheader = [
  { key: 'codigo',   label: 'Codigo',   className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'producto', label: 'Producto', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'unidades', label: 'Unidades', cellComponent: vueNumberInput, cellProps: { size: 'small',min: 1 ,inline: true,center: true,controls: true} },
  { key: 'lote',     label: 'Lote',     className: 'px-4 py-2 whitespace-nowrap text-left' },
]

const search = ref('')

// === PAGINACIÓN DE PRODUCTOS (del servidor) ===
const productosPagination = computed(() => ({
  page: props.currentPage || 1,
  pageSize: props.pageSize || '15',
  totalRecords: props.totalRecords || productosData.value.length,
  links: props.links
}));

// === PAGINACIÓN DEL CARRITO (local) ===
const carritoPagination = ref({
  page: 1,
  pageSize: '15'
});

// === DATOS PAGINADOS DEL CARRITO ===
const carritoPaginado = computed(() => {
  const startIndex = (carritoPagination.value.page - 1) * parseInt(carritoPagination.value.pageSize);
  const endIndex = startIndex + parseInt(carritoPagination.value.pageSize);
  return muestras.value.slice(startIndex, endIndex);
});

const carritoTotalRecords = computed(() => muestras.value.length);

// === MANEJADORES DE PAGINACIÓN ===
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
  carritoPagination.value.page = 1; // Reset a primera página
};

const filteredData = computed(() =>
  leftData.value.filter(row =>
    Object.values(row).some(val =>
      String(val).toLowerCase().includes(search.value.toLowerCase())
    )
  )
)

const internal = ref(props.modelValue)

// Sync: external → internal
watch(() => props.modelValue, (val) => {
  internal.value = val;
});

// Sync: internal → external (v-model)
watch(internal, (val) => {
  emit('update:modelValue', val);
});

// Limpiar carrito al abrir el modal
watch(internal, (val) => {
  if (val && !props.loading) {
    muestras.value = [];
  }
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm', items: MuestrasItem[]): void
  // Eventos de paginación
  (e: 'update:page', value: number): void;
  (e: 'update:pageSize', value: string): void;
}>()

function agregarAlCarrito(prod: Producto) {
  // Asegurarnos de que los tipos sean consistentes
  const codigo = String(prod.codigo);
  const idx = muestras.value.findIndex(p => p.codigo === codigo)
  
  if (idx === -1) {
    muestras.value.push({
      codigo:   codigo,
      producto: String(prod.producto),
      lote:     String(prod.lote),
      unidades: 1
    })
  } else {
    muestras.value[idx].unidades += 1
  }
}

function quitarDelCarrito(item: MuestrasItem) {
  muestras.value = muestras.value.filter(c => c.codigo !== item.codigo)
}

</script>

<template>
  <BaseModal
    v-model="internal"
    title="Buscar muestras"
    description=" "
    size="xl"
  >
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

      <!-- Tabla de productos disponibles -->
      <div class="flex-1">
        <h3 class="text-sm font-medium mb-2">Productos</h3>
          <div class="max-h-[400px] overflow-y-auto">
            <GlobalTable
              :columns="columnsProducto"
              :rows="filteredData"
              :actions="[{ key: 'add', handler: agregarAlCarrito }]"
              autoAddActionsColumn
              :loading="loading"
              @update:page="handleProductosPageChange"
              @update:pageSize="handleProductosPageSizeChange"
              :total-records="productosPagination.totalRecords"
              :current-page="productosPagination.page"
              :page-size="productosPagination.pageSize"
              :links="productosPagination.links"
            />
          </div>
      </div>

      <!-- Línea divisoria -->
      <div class="w-px bg-gray-300 mx-2" />

      <!-- Carrito de productos seleccionados -->
      <div class="flex-1">
        <h3 class="text-sm font-medium mb-2">Carrito ({{ muestras.length }} items)</h3>
          <div class="max-h-[400px] overflow-y-auto">
            <GlobalTable
              :columns="newReporTableheader"
              :rows="carritoPaginado"
              :actions="[{ key: 'delete', handler: quitarDelCarrito }]"
              autoAddActionsColumn
              HiddenTableFoot
              @update:page="handleCarritoPageChange"
              @update:pageSize="handleCarritoPageSizeChange"
              :total-records="carritoTotalRecords"
              :current-page="carritoPagination.page"
              :page-size="carritoPagination.pageSize"
            />
          </div>
      </div>

    </div>

    </template>
    <template #footer>
      <Button variant="default" @click="internal = false; muestras = []">Cancelar</Button>
      <Button variant="default" @click="internal = false; emit('confirm', muestras); muestras = []">Agregar</Button>
    </template>
  </BaseModal>
</template>