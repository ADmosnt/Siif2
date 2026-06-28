<script setup lang="ts">
import { type Ref, ref, watch, computed } from 'vue'
import GlobalTable from '@/components/GlobalTable.vue'
import AddMayorista_modal from './TdpComponents/AddMayorista_modal.vue'
import AddProducto_modal from './TdpComponents/AddProducto_modal.vue'
import vueNumberInput from './vue-number-input.vue'

// === 1) Tus datos “completos” (allData) ===
interface RowData {}

const algoref = ref(0)

const summaries = ref([
  { label: 'Órdenes Esperadas',   value:  algoref },
  { label: 'Total Esperado',      value:  0 },
  { label: 'Órdenes Facturadas',  value:  0 },
  { label: 'Total Facturado',     value:  0 },
])


function downloadPdf() { /* tu lógica */ }
function downloadExcel() { /* tu lógica */ }

const rowsMayoristas = ref<RowData[]>([])

const rowsProductos = ref<RowData[]>([])

// === 2) Definir columnas para la “Tabla A” (Supervisor / RFV / Zona / Brick) ===
const columnsMayoristas = [
  { key: 'id', label: 'Codigo', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'mayorista',        label: 'Mayorista',        className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'descuento',       label: 'Descuento',       className: 'px-4 py-2 whitespace-nowrap text-left' },
]

// === 3) Definir columnas para la “Tabla B” (Nº Orden / Producto / Unidades / Descuento / Precio / Acción) ===
// Nota: Como ejemplo, reutilizo algunas propiedades de RowData.
//       Pero tú puedes crear un array distinto (o un objeto distinto) con claves como 'mesAno' o 'cantidadEsperada' según necesites.
const columnsProductos = [
  { key: 'id',                  label: 'Nº Orden',        className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'codigo',                 label: 'Código',          className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'producto',          label: 'Producto',        className: 'px-4 py-2 text-left max-w-[200px] truncate' },
  { key: 'unidades',    label: 'Unidades',        className: 'px-4 py-2 whitespace-nowrap text-right'},
  { key: 'precio',       label: 'Precio',          className: 'px-4 py-2 whitespace-nowrap text-right' },
]

// === 4) Estados de paginación (para capturar página/size si hace falta) ===
const pageA = ref(1)
const pageSizeA = ref('15')
const pageB = ref(1)
const pageSizeB = ref('15')

/** === 6) Funciones para “Eliminar fila” en cada tabla === */
function deleteMayorista(row: RowData) {
  // Busca índice por id y lo quita del arreglo allData
  const idx = rowsMayoristas.value.findIndex(r => r.id === row.id)
  if (idx !== -1) {
    rowsMayoristas.value.splice(idx, 1)
  }
}

function deleteProducto(row: RowData) {
  // Busca índice por id y lo quita del arreglo allData
  const idx = rowsProductos.value.findIndex(r => r.id === row.id)
  if (idx !== -1) {
    rowsProductos.value.splice(idx, 1)
  }
}

const showMayoristaModal = ref(false)
function abrirMayoristaModal() { showMayoristaModal.value = true }

const showProductoModal = ref(false)
function abrirProductoModal() { showProductoModal.value = true }


</script>

<template>

  <div class="rounded-b-lg shadow overflow-x-auto ">
    
      <div class="rounded-b-lg shadow overflow-x-auto">
        <h2 class="text-lg font-semibold px-4 py-2">Mayoristas</h2>

        <!-- Usamos GlobalTable: -->
        <GlobalTable
          :columns="columnsMayoristas"
          :rows="rowsMayoristas"
          :actions="[{ key: 'delete', handler: deleteMayorista }]"
          autoAddActionsColumn
          showAddButton
          addButtonLabel="Agregar Mayorista"
          addButtonLabelShort="Add.M"
          @add="abrirMayoristaModal"
          @update:page="pageA = $event"
          @update:pageSize="pageSizeA = $event"          
          showSubHeader
          :subHeaderProps="{
            exportActions: [
              { key: 'pdf',   onClick: downloadPdf },
              { key: 'excel', onClick: downloadExcel }
            ],
            summaries:summaries
          }"
        />
        <AddMayorista_modal v-model="showMayoristaModal"  />
    </div>

        <!-- === TABLA B === -->
    <div class="rounded-b-lg shadow overflow-x-auto">
      <h2 class="text-lg font-semibold px-4 py-2">Productos</h2>

      <GlobalTable
        :columns="columnsProductos"
        :rows="rowsProductos"
        :actions="[{ key: 'delete', handler: deleteMayorista }]"
        autoAddActionsColumn
        showAddButton
        addButtonLabel="Agregar Producto"
        addButtonLabelShort="Add.P"   
        @add="abrirProductoModal"     
        @update:page="pageB = $event"
        @update:pageSize="pageSizeB = $event"
      />      
      <AddProducto_modal 
        v-model="showProductoModal"  
        @confirm="rowsProductos = [...rowsProductos, ...$event]"
      />
 
    </div>
      
  </div>

</template>

