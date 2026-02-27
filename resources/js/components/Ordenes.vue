/*
  Nombre: Ordenes
  Proceso: Componente que activa el boton de ordenes en el modulo de consulta - reporte,
            ademas de que tiene dos tablas de datos, una para reportes de ordenes y otra para estadisticas de productos
  Fecha creado: 25 de junio del 2025
  Quien lo hizo: Bimodal - A.Lozada
  Ultima Modificacion:
  Ultima Modificacion por: 
*/

<script setup lang="ts">
import { ref } from 'vue'
import GlobalTable from '@/components/GlobalTable.vue'
import FiltroOrdenes from './FiltroOrdenes.vue'

// Interfaz de datos para representar filas de reportes
interface Orden {
  id: number
  numeroFactura: string
  rfv: string
  cliente: string
  fecha: string
  ciudad: string
  estado: string
  mayorista: string
  unidades: number
  valor: number
  estatus: string
  comentarios: string
  coordenadas: string
}
interface RowProducto {
  id: number
  rfv: string
  cliente: string
  producto: string
  mayorista: string
  solicitado: number
  montoSolicitado: number
  conciliadas: number
  montoConciliado: number
  fallas: number
  montoFalla: number
}

const rowsOrdenes = ref<Orden[]>([])
const rowsProductos = ref<RowProducto[]>([])

// === Reportes de Órdenes ===
const columnsOrdenes = [
  // esenciales en móvil
  { key: 'numeroFactura', label: 'Nº Fact', className: 'px-3 py-2 text-left w-24' },
  { key: 'cliente',       label: 'Cliente', className: 'px-3 py-2 text-left max-w-[180px] truncate' },
  { key: 'fecha',         label: 'Fecha',   className: 'px-3 py-2 text-right w-28' },
  { key: 'valor',         label: 'Valor',   className: 'px-3 py-2 text-right w-24' },

  // se muestran desde ciertos breakpoints
  { key: 'rfv',           label: 'RFV',        className: 'px-3 py-2 text-left hidden sm:table-cell' },
  { key: 'ciudad',        label: 'Ciudad',     className: 'px-3 py-2 text-right hidden md:table-cell' },
  { key: 'estado',        label: 'Estado',     className: 'px-3 py-2 text-right hidden lg:table-cell' },
  { key: 'mayorista',     label: 'Mayorista',  className: 'px-3 py-2 text-right hidden xl:table-cell' },
  { key: 'unidades',      label: 'Unidades',   className: 'px-3 py-2 text-right hidden sm:table-cell' },
  { key: 'estatus',       label: 'Estatus',    className: 'px-3 py-2 text-right hidden md:table-cell' },
  { key: 'comentarios',   label: 'Comentarios',className: 'px-3 py-2 text-right hidden xl:table-cell' },
  { key: 'coordenadas',   label: 'Coordenadas',className: 'px-3 py-2 text-right hidden 2xl:table-cell' },
]

// === Estadísticas de Productos ===
const columnsProductos = [
  // esenciales en móvil
  { key: 'producto',        label: 'Producto',         className: 'px-3 py-2 text-left max-w-[200px] truncate' },
  { key: 'solicitado',      label: 'Solicitado',       className: 'px-3 py-2 text-right w-24' },
  { key: 'montoSolicitado', label: 'Monto solicitado', className: 'px-3 py-2 text-right w-28' },

  // se muestran desde ciertos breakpoints
  { key: 'id',              label: 'Nº',               className: 'px-3 py-2 text-left hidden sm:table-cell w-14' },
  { key: 'cliente',         label: 'Cliente',          className: 'px-3 py-2 text-left hidden sm:table-cell' },
  { key: 'rfv',             label: 'RFV',              className: 'px-3 py-2 text-left hidden md:table-cell' },
  { key: 'mayorista',       label: 'Mayorista',        className: 'px-3 py-2 text-left hidden lg:table-cell' },
  { key: 'conciliadas',     label: 'Conciliadas',      className: 'px-3 py-2 text-right hidden sm:table-cell' },
  { key: 'montoConciliado', label: 'Monto conciliado', className: 'px-3 py-2 text-right hidden md:table-cell' },
  { key: 'fallas',          label: 'Fallas',           className: 'px-3 py-2 text-right hidden lg:table-cell' },
  { key: 'montoFalla',      label: 'Monto Falla',      className: 'px-3 py-2 text-right hidden xl:table-cell' },
]

// Paginación para ambas tablas
const pageA = ref(1)
const pageSizeA = ref('15')
const pageB = ref(1)
const pageSizeB = ref('15')

function downloadPdf() { /* tu lógica */ }
function downloadExcel() { /* tu lógica */ }



</script>

<template>
  <!-- Componente de filtros (estatus + mayorista) -->
  <FiltroOrdenes  />

  <!-- Tabla A: Reportes de Órdenes -->
    <div class="rounded-b-lg shadow overflow-x-auto mb-6">
    <h2 class="text-lg font-semibold px-4 py-2">Reportes de órdenes</h2>
    <GlobalTable
      :columns="columnsOrdenes"
      :rows="rowsOrdenes"
      :total-records="rowsOrdenes.length"
      :current-page="pageA"
      :page-size="pageSizeA"
      showSubHeader
      :subHeaderProps="{
            exportActions: [
              { key: 'pdf',   onClick: downloadPdf },
              { key: 'excel', onClick: downloadExcel }
            ],
          }"
      @update:page="pageA = $event"
      @update:pageSize="pageSizeA = $event"      
    />
  </div>

  <!-- Tabla B: Estadísticas de Productos -->
  <div class="rounded-b-lg shadow overflow-x-auto">
    <h2 class="text-lg font-semibold px-4 py-2">Estadísticas de Productos</h2>
    <GlobalTable
      :columns="columnsProductos"
      :rows="rowsProductos"
      :total-records="rowsProductos.length" 
      :current-page="pageB"
      :page-size="pageSizeB"
      showSubHeader
      :subHeaderProps="{
            exportActions: [
              { key: 'pdf',   onClick: downloadPdf },
              { key: 'excel', onClick: downloadExcel }
            ],
          }"
      @update:page="pageB = $event"
      @update:pageSize="pageSizeB = $event"
    />  
 </div>

</template>
