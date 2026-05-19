<!-- resources/js/components/TablesTdp.vue -->
<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import GlobalTable from '@/components/GlobalTable.vue'
import AddProducto_modal from './AddProducto_modal.vue'
import AddMayorista_modal from './AddMayorista_modal.vue'
import { type Mayoristas, CarritoItem, Producto } from '@/types/interfaces';
import { type PaginatedData } from '@/types/pagination';

// ===== PROPS =====
const props = defineProps<{
  mayoristas?: PaginatedData<Mayoristas> | null;
  productos?: PaginatedData<Producto> | null;
}>()

const carritoPagination = ref({
  page: 1,
  pageSize: '15'
});

// ===== evita errores cuando es null =====
const safeMayoristas = computed(() => props.mayoristas || {
  data: [],
  links: [],
  meta: { total: 0, current_page: 1, per_page: 15 }
});

const safeProductos = computed(() => props.productos || {
  data: [],
  links: [],
  meta: { total: 0, current_page: 1, per_page: 15 }
});

// ===== Datos reactivos =====
const rowsMayoristas = ref<Mayoristas[]>([])
const rowsProductos = ref<CarritoItem[]>([])

// ===== Emisiones =====
const emit = defineEmits<{
  (e: 'update:mayoristas', val: Mayoristas[]): void
  (e: 'update:items', val: CarritoItem[]): void
  (e: 'fetch-mayoristas', payload: { page: number; pageSize: string; search: string }): void
  (e: 'fetch-productos', payload: { page: number; pageSize: string; search: string }): void
}>()

// ===== Columnas =====
const columnsMayoristas = [
  { key: 'codigo', label: 'Código', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'mayorista', label: 'Mayorista', className: 'px-4 py-2 whitespace-nowrap text-left' },
]

const columnsProductos = [
  { key: 'id', label: 'Nº Orden', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'codigo', label: 'Código', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'producto', label: 'Producto', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'unidades', label: 'Unidades', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'precio', label: 'Precio', className: 'px-4 py-2 whitespace-nowrap text-right' },
]

// ===== Paginación local para el carrito =====
const pageA = ref(1)
const pageSizeA = ref('15')
const pageB = ref(1)
const pageSizeB = ref('15')
const searchB = ref('')

// ===== Datos Paginados del Carrito Local =====
const carritoPaginado = computed(() => {
  const start = (carritoPagination.value.page - 1) * parseInt(carritoPagination.value.pageSize);
  return rowsProductos.value.slice(start, start + parseInt(carritoPagination.value.pageSize));
});

const carritoTotalRecords = computed(() => rowsProductos.value.length);

// Manejadores de paginación del carrito local:
const handleCarritoPageChange = (newPage: number) => {
  carritoPagination.value.page = newPage;
};

const handleCarritoPageSizeChange = (newSize: string) => {
  carritoPagination.value.pageSize = newSize;
  carritoPagination.value.page = 1;
};

// ===== Modales =====
const showMayoristaModal = ref(false)
const showProductoModal = ref(false)

function abrirMayoristaModal() {
  showMayoristaModal.value = true
}

function abrirProductoModal() {
  showProductoModal.value = true
}

function handleSelectMayorista(item: Mayoristas) {

  if (!rowsMayoristas.value.some(r => r.codigo === item.codigo)) {
    rowsMayoristas.value.push({ ...item })
  }
  showMayoristaModal.value = false
}

function handleConfirmFromModal(items: CarritoItem[]) {
  items.forEach(it => {
    const idx = rowsProductos.value.findIndex(r => r.codigo === it.codigo)
    if (idx !== -1) {
      rowsProductos.value[idx].unidades += it.unidades
      rowsProductos.value[idx].precio = parseFloat(
        (rowsProductos.value[idx].unitPrice * rowsProductos.value[idx].unidades).toFixed(2)
      )
    } else {
      rowsProductos.value.push({
        ...it,
      })
    }
  })
}

function deleteMayorista(row: Mayoristas) {
  const idx = rowsMayoristas.value.findIndex(r => r.codigo === row.codigo)
  if (idx !== -1) rowsMayoristas.value.splice(idx, 1)
}

function deleteProducto(row: CarritoItem) {
  const idx = rowsProductos.value.findIndex(r => r.id === row.id) // o r.codigo
  if (idx !== -1) rowsProductos.value.splice(idx, 1)
}

// ===== Watchers =====
watch(rowsMayoristas, val => emit('update:mayoristas', val), { deep: true, immediate: true })
watch(rowsProductos, val => emit('update:items', val), { deep: true, immediate: true })

// Funciones para manejar la paginación del servidor (llamadas desde los modales)
const handleFetchMayoristas = (payload: { page: number; pageSize: string; search: string }) => {
  emit('fetch-mayoristas', payload);
  pageA.value = payload.page;
  pageSizeA.value = payload.pageSize;
};

const handleFetchProductos = (payload: { page: number; pageSize: string; search: string }) => {
  pageB.value = payload.page;
  pageSizeB.value = payload.pageSize;
  searchB.value = payload.search;
  emit('fetch-productos', payload);
};



</script>

<template>
  <div class="rounded-b-lg shadow overflow-x-auto">
    <div class="rounded-b-lg shadow overflow-x-auto mb-6">
      <h2 class="text-lg font-semibold px-4 py-2">Mayoristas</h2>
      <GlobalTable
        :columns="columnsMayoristas"
        :rows="rowsMayoristas"
        :total-records="rowsMayoristas.length"
        :current-page="pageA"
        :page-size="pageSizeA"
        :actions="[{ key: 'delete', handler: deleteMayorista }]"
        autoAddActionsColumn
        showAddButton
        addButtonLabel="Agregar Mayorista"
        @add="abrirMayoristaModal"
        @update:page="pageA = $event"
        @update:pageSize="pageSizeA = $event"
      />
      <AddMayorista_modal
        v-model="showMayoristaModal"
        :mayoristas="safeMayoristas"
        @select="handleSelectMayorista"
        @fetch="handleFetchMayoristas"
      />
    </div>

    <div class="rounded-b-lg shadow overflow-x-auto">
      <h2 class="text-lg font-semibold px-4 py-2">Productos</h2>
      <!-- Tabla con paginación LOCAL para rowsProductos -->
      <GlobalTable
        :columns="columnsProductos"
        :rows="carritoPaginado"
        :total-records="carritoTotalRecords"
        :current-page="carritoPagination.page"
        :page-size="carritoPagination.pageSize"
        :actions="[{ key: 'delete', handler: deleteProducto }]"
        autoAddActionsColumn
        showAddButton
        addButtonLabel="Agregar Producto"
        @add="abrirProductoModal"
        @update:page="handleCarritoPageChange" 
        @update:pageSize="handleCarritoPageSizeChange"
      />
        <AddProducto_modal
          v-model="showProductoModal"
          :productos="safeProductos"
          :current-page="pageB"
          :page-size="pageSizeB"
          @confirm="handleConfirmFromModal"
          @update:page="handleFetchProductos({ page: $event, pageSize: pageSizeB, search: searchB })"
          @update:pageSize="handleFetchProductos({ page: 1, pageSize: $event, search: searchB })"
        />
    </div>

  </div>
</template>