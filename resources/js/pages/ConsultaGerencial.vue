<!-- resources/js/pages/ConsultaGerencial.vue -->
<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { getLocalTimeZone, type DateValue } from '@internationalized/date'
import { format } from 'date-fns'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import Button from "@/components/ui/button/Button.vue"
import ComboSelect from "@/components/ComboSelect.vue"
import GlobalTable from '@/components/GlobalTable.vue'
import SimpleDatePicker from '@/components/simpleDatePicker.vue'
import GpsModal from '@/components/GPS/GpsModal.vue'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'

// =================================================================
// 1. DEFINICIÓN DE INTERFACES PARA LAS PROPS (CON CORRECCIÓN)
// =================================================================

// --- Tipos para las listas de los filtros ---
interface Zona { idzona: number; descripcion_zona: string; }
interface Brick { idbrick: number; Descripcion: string; }
interface Supervisor { idPersona: number; nombre_completo_razon_social: string; }
interface Representante { idPersona: string; nombre_completo_razon_social: string; }

// --- Tipo para los objetos de relación anidados ---
// ESTA SECCIÓN ES LA CORRECCIÓN. AHORA DEFINIMOS ESTOS TIPOS.
interface SupervisorRelation { nombre_completo_razon_social: string; }
interface RepresentanteRelation { nombre_completo_razon_social: string; }
interface ZonaRelation { descripcion_zona: string; }
interface RutaRelation { Descripcion: string; }


// --- Tipo para cada fila de la tabla de estadísticas ---
// Esta interfaz ahora refleja la estructura ANIDADA que devuelve el controlador con las relaciones.
interface EstadisticaItem {
  MesRegistro: string;
  porce_cobertura: number;
  productoEsperado: number;
  monto_esperado: number;
  productoFacturado: number;
  monto_facturado: number;
  // Propiedades de relación opcionales (pueden ser null si no se encuentra la relación)
  supervisor?: SupervisorRelation | null;
  representante?: RepresentanteRelation | null;
  zona?: ZonaRelation | null;
  ruta?: RutaRelation | null;
}

// --- Tipo genérico para el paginador de Laravel ---
interface Paginator<T> {
  data: T[];
  links: any[];
  total: number;
  current_page: number;
  per_page: number;
}

// --- Tipo para los filtros que ya vienen aplicados desde el backend ---
interface FiltrosAplicados {
  supervisores?: string[];
  rfv?: string[];
  zonas?: string[];
  rutas?: string[];
}

// --- Interfaz principal que agrupa todas las props ---
interface Props {
  estadisticas: Paginator<EstadisticaItem>;
  listasParaFiltros: {
    zonas: Zona[];
    bricks: Brick[];
    supervisores: Supervisor[];
    representantes: Representante[];
  };
  filtrosAplicados: FiltrosAplicados;
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF', href: '/dashboard' },
  { label: 'Consulta Gerencial'},
]

// =================================================================
// 2. ESTADO DEL FORMULARIO Y MODAL
// =================================================================
const form = reactive({
  supervisores: props.filtrosAplicados.supervisores || [],
  rfv: props.filtrosAplicados.rfv || [],
  zonas: props.filtrosAplicados.zonas || [],
  rutas: props.filtrosAplicados.rutas || [],
})

const fechaInicio = ref<DateValue>();
const fechaFin = ref<DateValue>();

const isGpsModalOpen = ref(false)

// =================================================================
// 3. MÉTODOS DE CONSULTA
// =================================================================
function consultar() {
  const payload: any = { ...form };

  if (fechaInicio.value) {
    payload.fechaIni = format(fechaInicio.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd');
  }
  if (fechaFin.value) {
    payload.fechaFin = format(fechaFin.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd');
  }

  // console.log('Enviando filtros para la TABLA:', payload);

  router.get(route('gerencial.index'), payload, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function handlePageSizeChange(newSize: string) {
  const payload: any = { ...form, size: newSize };

  if (fechaInicio.value) {
    payload.fechaIni = format(fechaInicio.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd');
  }
  if (fechaFin.value) {
    payload.fechaFin = format(fechaFin.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd');
  }

  router.get(route('gerencial.index'), payload, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

// =================================================================
// 4. ADAPTAR LAS OPCIONES DE LOS SELECTORES A LA PROP 'listasParaFiltros'
//    (CORREGIDO: Se añaden tipos explícitos para los parámetros del map)
// =================================================================
const ZonaOptions = computed(() => 
  props.listasParaFiltros.zonas.map(z => ({ value: String(z.idzona), label: z.descripcion_zona }))
)
const BrickRutaOptions = computed(() =>
  props.listasParaFiltros.bricks.map(b => ({ value: String(b.idbrick), label: b.Descripcion }))
)
const SupervisorOptions = computed(() =>
  props.listasParaFiltros.supervisores.map(s => ({ value: String(s.idPersona), label: s.nombre_completo_razon_social }))
)
const RFVOptions = computed(() =>
  props.listasParaFiltros.representantes.map(r => ({ value: String(r.idPersona), label: r.nombre_completo_razon_social }))
)

// =================================================================
// 5. CONFIG DE LA TABLA
// =================================================================
const rowsData = computed(() => {
  if (!props.estadisticas.data) {
    return [];
  }
  return props.estadisticas.data.map(row => ({
    ...row,
    supervisor_nombre: row.supervisor?.nombre_completo_razon_social || 'N/A',
    rfv_nombre: row.representante?.nombre_completo_razon_social || 'N/A',
    zona_nombre: row.zona?.descripcion_zona || 'N/A',
    brick_descripcion: row.ruta?.Descripcion || 'N/A',
  }));
});

const TableColumns = [
    // Ahora las 'key' apuntan a las propiedades planas que acabamos de crear
    { key: 'supervisor_nombre',   label: 'Supervisor',        className: 'px-4 py-2 whitespace-nowrap text-left' },
    { key: 'rfv_nombre',          label: 'RFV',               className: 'px-4 py-2 whitespace-nowrap text-left' },
    { key: 'zona_nombre',         label: 'Zona',              className: 'px-4 py-2 whitespace-nowrap text-left' },
    { key: 'brick_descripcion',   label: 'Brick',             className: 'px-4 py-2 whitespace-nowrap text-left' },
    { key: 'MesRegistro',         label: 'Mes/Año',           className: 'px-4 py-2 whitespace-nowrap text-left' },
    { key: 'porce_cobertura',     label: '% Cobertura',       className: 'px-4 py-2 whitespace-nowrap text-right' },
    { key: 'productoEsperado',    label: 'Cant. Esperada',    className: 'px-4 py-2 whitespace-nowrap text-right' },
    { key: 'monto_esperado',      label: 'Monto Esperado',    className: 'px-4 py-2 whitespace-nowrap text-right' },
    { key: 'productoFacturado',   label: 'Cant. Facturada',   className: 'px-4 py-2 whitespace-nowrap text-right' },
    { key: 'monto_facturado',     label: 'Monto Facturado',   className: 'px-4 py-2 whitespace-nowrap text-right' },
];

// Lógica para resúmenes y descargas
const algoref = ref(0)

const summaries = ref([
  { label: 'Órdenes Esperadas',   value:  algoref },
  { label: 'Total Esperado',      value:  0 },
  { label: 'Órdenes Facturadas',  value:  0 },
  { label: 'Total Facturado',     value:  0 },
])

// =================================================================
// 5.1 LÓGICA DE EXPORTACIÓN (PDF Y EXCEL)
// =================================================================

const downloadReport = (type: 'pdf' | 'excel') => {
    const routeName = type === 'pdf' ? 'gerencial.export.pdf' : 'gerencial.export.excel';
    const params = new URLSearchParams();

    // Filtros de los combos (Arrays)
    Object.entries(form).forEach(([key, value]) => {
        if (Array.isArray(value) && value.length > 0) {
            value.forEach(item => params.append(`${key}[]`, item));
        }
    });

    // Fechas
    if (fechaInicio.value) {
        params.append('fechaIni', format(fechaInicio.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd'));
    }
    if (fechaFin.value) {
        params.append('fechaFin', format(fechaFin.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd'));
    }

    // Ejecutar la descarga abriendo la URL en la misma ventana
    window.location.href = `${route(routeName)}?${params.toString()}`;
};

function downloadPdf() { 
    downloadReport('pdf'); 
}

function downloadExcel() { 
    downloadReport('excel'); 
}

// =================================================================
// 6. LOGICA PARA EL MODAL DEL GPS 
// =================================================================

// Formatea las fechas para pasarlas como props al modal.
const formattedFechaInicio = computed(() => {
  return fechaInicio.value ? format(fechaInicio.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd') : null;
});
const formattedFechaFin = computed(() => {
  return fechaFin.value ? format(fechaFin.value.toDate(getLocalTimeZone()), 'yyyy-MM-dd') : null;
});

// Extrae de forma segura el RFV seleccionado. El modal solo acepta uno.
const selectedRfvForGps = computed(() => {
  // AHORA ES MÁS SIMPLE Y DIRECTO
  // Si hay exactamente 1 item en el array, ese es nuestro ID.
  return form.rfv && form.rfv.length === 1 ? form.rfv[0] : null
})
// Controla si el botón del GPS debe estar habilitado o no.
const isGpsButtonDisabled = computed(() => {
  // El botón se deshabilita si no hay exactamente 1 RFV y un rango de fechas.
  return !selectedRfvForGps.value || !formattedFechaInicio.value || !formattedFechaFin.value;
});
</script>

<template>
  <Head title="Gerencial" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 min-w-0">
        <!-- RANGO DE FECHAS -->
        <div class="flex flex-col md:flex-row gap-4 md:col-span-1">
            <div class="flex-1 min-w-0">
                <label class="text-sm font-medium text-gray-700 mb-1 block">Desde</label>
                <SimpleDatePicker v-model="fechaInicio"/>
            </div>
            <div class="flex-1 min-w-0">
                <label class="text-sm font-medium text-gray-700 mb-1 block">Hasta</label>
                <SimpleDatePicker v-model="fechaFin"/>
            </div>
        </div>
        
        <!-- ZONA -->
        <div class="min-w-0">
          <label class="text-sm font-medium text-gray-700 mb-1 block">Zona</label>
          <ComboSelect 
            v-model="form.zonas"
            :options="ZonaOptions"
            placeholder="Seleccionar Zona"
          />
        </div>
        
        <!-- BRICK-RUTA -->
        <div class="min-w-0">
          <label class="text-sm font-medium text-gray-700 mb-1 block">Brick-Ruta</label>
          <ComboSelect 
            v-model="form.rutas"
            :options="BrickRutaOptions"
            placeholder="Seleccionar Brick-Ruta"
          />
        </div>

        <!-- SUPERVISOR -->
        <div class="min-w-0">
          <label class="text-sm font-medium text-gray-700 mb-1 block">Supervisor</label>
          <ComboSelect 
            v-model="form.supervisores"
            :options="SupervisorOptions"
            placeholder="Seleccionar Supervisor"
          />
        </div>
        
        <!-- RFV -->
        <div class="min-w-0">
          <label class="text-sm font-medium text-gray-700 mb-1 block">RFV</label>
          <ComboSelect 
            v-model="form.rfv"
            :options="RFVOptions"
            placeholder="Seleccionar RFV"
          />
        </div>
        
        <!-- BOTONES -->
        <div class="grid grid-cols-2 items-end min-w-0 space-x-2">
          <Button @click="consultar">Consultar</Button>

          <Tooltip :delay-duration="100">
            <TooltipTrigger as-child>
              <!-- El <span> exterior es el que recibe el hover cuando el botón está deshabilitado -->
              <span class="inline-block w-full" :class="{ 'cursor-not-allowed': isGpsButtonDisabled }">
                <Button
                  class="w-full"
                  @click="isGpsModalOpen = true"
                  :disabled="isGpsButtonDisabled"
                >
                  GPS
                </Button>
              </span>
            </TooltipTrigger>
            <TooltipContent v-if="isGpsButtonDisabled">
              <p>Para activar, seleccione un (1) único RFV y un rango de fechas.</p>
            </TooltipContent>
          </Tooltip>

        </div>
      </div>

      <!-- TABLA DE RESULTADOS -->
      <div class="rounded-b-lg shadow overflow-x-auto mt-4">
          <GlobalTable
              :columns="TableColumns"
              :rows="rowsData"
              :links="estadisticas.links"
              :total-records="estadisticas.total"  
              :current-page="estadisticas.current_page"
              @update:pageSize="handlePageSizeChange"
              :page-size="String(estadisticas.per_page)"
              showSubHeader
              :subHeaderProps="{
                exportActions: [
                  { key: 'pdf', onClick: downloadPdf },
                  { key: 'excel', onClick: downloadExcel }
                ],
                summaries: summaries
              }"
          />
      </div>
    </div>
    <GpsModal
      v-model="isGpsModalOpen"
      :rfv="selectedRfvForGps"
      :fecha-desde="formattedFechaInicio"
      :fecha-hasta="formattedFechaFin"
    />
  </AppLayout>
</template>
