/*
  Nombre: Visitas
  Proceso: Componente que activa el boton de visita en el modulo de consulta - reporte,
            ademas de que tiene dos tablas de datos, una parta reportes de visitas y otra para muestas entregadas
            aparte de que cada una tiene sus respectivos datos de prueba
  Fecha creado: 25 de junio del 2025
  Quien lo hizo: Bimodal - A.Lozada
  Ultima Modificacion:
  Ultima Modificacion por: 
*/

<script setup lang="ts">
import { ref } from 'vue'
import GlobalTable from '@/components/GlobalTable.vue'
import FiltroVisitas from '@/components/FiltroVisitas.vue'

// Datos completos (sin filtrar)
const allVisitas = ref([
  {
    id: 1,
    rfv: "RFV-001",
    cliente: "Clínica La Esperanza",
    zona: "Oeste",
    ruta: "Ruta 4",
    ciudad: "Maracaibo",
    estado: "Zulia",
    fecha: "2025-07-06",
    actividad: "Revisión médica",
    especialidad: "Pediatría",
    ranking: "A",
    comentarios: "Paciente estable",
    coordenadas: "10.654, -71.636"
  },
  {
    id: 2,
    rfv: "RFV-002",
    cliente: "Hospital Central",
    zona: "Sur",
    ruta: "Ruta 1",
    ciudad: "Caracas",
    estado: "Distrito Capital",
    fecha: "2025-07-07",
    actividad: "Entrega de informe",
    especialidad: "Cardiología",
    ranking: "B",
    comentarios: "Requiere seguimiento",
    coordenadas: "10.500, -66.917"
  }
])

// Datos que se mostrarán en la tabla de visitas
const rowsVisitas = ref([...allVisitas.value])

// Datos de prueba para la tabla de muestras
const allMuestras = ref([
  {
    id: 1,
    materiales: "Amoxicilina 500mg",
    cliente: "Clínica La Esperanza",
    ciudad: "Maracaibo",
    estado: "Zulia",
    zona: "Oeste",
    ruta: "Ruta 4",
    cantidad: 20,
    coordenadas: "10.654, -71.636"
  },
  {
    id: 2,
    materiales: "Ibuprofeno 400mg",
    cliente: "Hospital Central",
    ciudad: "Caracas",
    estado: "Distrito Capital",
    zona: "Sur",
    ruta: "Ruta 1",
    cantidad: 15,
    coordenadas: "10.500, -66.917"
  }
])

const rowsMuestras = ref([...allMuestras.value])

// Función que recibe los filtros y actualiza la tabla
function filtrarVisitas(filtros: {
  ranking: string[],
  especialidad: string[],
  actividad: string[],
  evento: string[],
  zona: string[],
  ruta: string[]
}) {
  rowsVisitas.value = allVisitas.value.filter(row => {
    const matchRanking = filtros.ranking.length === 0 || filtros.ranking.includes(row.ranking)
    const matchEspecialidad = filtros.especialidad.length === 0 || filtros.especialidad.includes(row.especialidad)
    const matchActividad = filtros.actividad.length === 0 || filtros.actividad.includes(row.actividad)
    const matchEvento = true // aún no implementado
    const matchZona = filtros.zona.length === 0 || filtros.zona.includes(row.zona)
    const matchRuta = filtros.ruta.length === 0 || filtros.ruta.includes(row.ruta)

    return matchRanking && matchEspecialidad && matchActividad && matchEvento && matchZona && matchRuta
  })

  // --- Filtrar muestras entregadas ---
  rowsMuestras.value = allMuestras.value.filter(row => {
    const matchZona = filtros.zona.length === 0 || filtros.zona.includes(row.zona)
    const matchRuta = filtros.ruta.length === 0 || filtros.ruta.includes(row.ruta)

    // Solo filtramos zona y ruta por ahora
    return matchZona && matchRuta
  })

}

// Columnas para la tabla de visitas
const columnsVisitas = [
  { key: 'id', label: 'Nº Orden', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'rfv', label: 'RFV', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'cliente', label: 'Cliente', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'zona', label: 'Zona', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'ruta', label: 'Ruta', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'ciudad', label: 'Ciudad', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'estado', label: 'Estado', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'fecha', label: 'Fecha', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'actividad', label: 'Actividad', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'especialidad', label: 'Especialidad', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'ranking', label: 'Ranking', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'comentarios', label: 'Comentarios', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'coordenadas', label: 'Coordenadas', className: 'px-4 py-2 whitespace-nowrap text-right' },
]

// Columnas para la tabla de muestras
const columnsMuestras = [
  { key: 'id', label: 'Reportes', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'materiales', label: 'Materiales', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'cliente', label: 'Cliente', className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'ciudad', label: 'Ciudad', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'estado', label: 'Estado', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'zona', label: 'Zona', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'ruta', label: 'Ruta', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'cantidad', label: 'Cantidad', className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'coordenadas', label: 'Coordenadas', className: 'px-4 py-2 whitespace-nowrap text-right' },
]

// Filtros predefinidos (opciones)
const rankingOptions = ref([
  { value: 'A', label: 'A' },
  { value: 'B', label: 'B' },
  { value: 'C', label: 'C' },
])
const especialidadOptions = ref([
  { value: 'Cardiología', label: 'Cardiología' },
  { value: 'Pediatría', label: 'Pediatría' },
  { value: 'Dermatología', label: 'Dermatología' },
])
const actividadOptions = ref([
  { value: 'VISITA_MEDICA', label: 'Visita Médica' },
  { value: 'ENTREGA_MATERIAL', label: 'Entrega de Material' },
])
const eventoOptions = ref([
  { value: 'SATISFECHO', label: 'Satisfecho' },
  { value: 'NO_ATENDIDO', label: 'No Atendido' },
])
const zonaOptions = ref([
  { value: 'Norte', label: 'Norte' },
  { value: 'Sur', label: 'Sur' },
  { value: 'Este', label: 'Este' },
  { value: 'Oeste', label: 'Oeste' },
])
const rutaOptions = ref([
  { value: 'Ruta 1', label: 'Ruta 1' },
  { value: 'Ruta 2', label: 'Ruta 2' },
  { value: 'Ruta 3', label: 'Ruta 3' },
])

const pageA = ref(1)
const pageSizeA = ref('15')
const pageB = ref(1)
const pageSizeB = ref('15')

function downloadPdf() { /* tu lógica */ }
function downloadExcel() { /* tu lógica */ }

</script>

<template>

  <div class="px-4 py-2">

    <FiltroVisitas
      :ranking-options="rankingOptions"
      :especialidad-options="especialidadOptions"
      :actividad-options="actividadOptions"
      :evento-options="eventoOptions"
      :zona-options="zonaOptions"
      :ruta-options="rutaOptions"
      @consultar="filtrarVisitas"
    />
  </div>

  <!-- Tabla de Visitas -->
  <div class="mt-6">
    <h2 class="text-lg font-semibold mb-4">Reportes de visitas.</h2>
  <GlobalTable
    :columns="columnsVisitas"
    :rows="rowsVisitas"
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

  <!-- Tabla de Muestras -->
  <div class="mt-6">
    <h2 class="text-lg font-semibold px-4 py-2">Muestras Entregadas</h2>
    <GlobalTable
      :columns="columnsMuestras"
      :rows="rowsMuestras"
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
