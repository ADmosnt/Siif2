/*
  Nombre: CosultaReporte
  Proceso: Vista central para consultar reportes de visitas, órdenes y facturas.
               Permite seleccionar un rango de fechas y un RFV para filtrar los datos.
               Incluye botones para mostrar/ocultar cada sección (visitas, órdenes, facturas).  
  Fecha creado: 25 de junio del 2025
  Quien lo hizo: Bimodal - A.Lozada
  Ultima Modificacion:
  Ultima Modificacion por: 
*/

<script setup lang="ts">
  import { type BreadcrumbItem } from "@/types";
  import AppLayout from '@/layouts/AppLayout.vue';
  import { Head } from '@inertiajs/vue3';
  import DatePicker from "@/components/DatePicker.vue";
  import { ref, watch, computed } from 'vue'
  import Visitas from "@/components/Visitas.vue";
  import Ordenes from "@/components/Ordenes.vue";
  import Facturas from "@/components/Facturas.vue";
  import SIIF_Info_cons01 from "@/components/SIIF_Info_cons01.vue";
  import GenericSelect from "@/components/GenericSelect.vue";


  const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF', href: '/dashboard' },
  { label: 'Consulta - Reportes', href: '/consulta-reporte' },
]

  // Define cuál de las secciones está visible (visitas, órdenes o facturas)
  const seccionActiva = ref<'visitas' | 'ordenes' | 'facturas' | null>(null)

  // Cambia la sección activa, o la oculta si ya estaba seleccionada
  function toggleSeccion(nombre: 'visitas' | 'ordenes' | 'facturas') {
    seccionActiva.value = seccionActiva.value === nombre ? null : nombre
  }

const datos = [
  { id: 1, nombre: 'ACME S.A.' },
  { id: 2, nombre: 'Globex Corp.' },
  // …
]
const datoSeleccionado = ref(null)

// Rango de fechas seleccionado desde el calendario
const selectedRange = ref<{ start: Date; end: Date } | null>(null)

// Método que actualiza el rango de fechas
function onRangeSelected(range: { start: Date; end: Date }) {
  selectedRange.value = range
}
</script>

<template>
  <!-- Título de la pestaña -->
  <Head title="ConsG" />

  <!-- Layout general con breadcrumbs -->  
  <AppLayout :breadcrumbs="breadcrumbs" class="max-w-full overflow-x-hidden">

    <div class="flex-1 flex flex-col p-4">
      <div class="grid grid-cols-1 gap-y-6">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

          <!-- Calendario + RFV (juntos) -->
          <div class="flex flex-col gap-4">
            <DatePicker @update:date-range="onRangeSelected" />

            <!-- selector de RFV debajo del calendario -->
           <!-- <div class="mt-5"> -->
              <GenericSelect
                v-model="datoSeleccionado"
                :items="datos"
                placeholder="-- Elija una empresa --"
                label="Empresa"
                :triggerClass="'w-full '"      
                :contentClass="'max-w-md'"            
                :triggerProps="{ disabled: false }"   
                :contentProps="{ position: 'popper' }"
              />

              <div class="flex flex-col sm:flex-row items-stretch gap-4">
              <button
                class="flex-1 sm:flex-grow-0 sm:self-center bg-[#63c00d] hover:bg-[#00aa39] text-white font-semibold py-2 px-4 rounded-md shadow transition-colors"
              >
                CONSULTAR
              </button> 
            </div>
          </div>        

          <!-- 2: InfoCard (Tarjeta de información general) -->
          <div class="min-w-0 sm:col-span-2 md:col-span-2">
            <SIIF_Info_cons01
              message="Las consultas que pueden ser realizadas corresponden a reportes de visitas realizadas, órdenes y sus facturas. Se deben consultar estas por separado.
                       Si lo desea puede seleccionar un rango de fecha para las cosultas asi como los distintos filtros segun el tipo de consulta.
                       
              ¡Importante! De no seleccionar un filtro la consulta sera generica para dicho filtro"
            />
          </div>

        </div>

        <!-- Botones debajo del calendario -->
        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
          <!-- Botón Visitas -->
          <button @click="toggleSeccion('visitas')"
            :class="[
              
            'w-full text-white font-semibold rounded-full px-6 py-2 shadow flex items-center justify-center gap-2 transition',
            seccionActiva === 'visitas' ? 'bg-[#014a99]' : 'bg-[#015ab6] hover:bg-[#014a99]'
            ]">
            VISITAS
            <i :class="seccionActiva === 'visitas' ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
          </button>

          <!-- Botón Órdenes --> 
          <button @click="toggleSeccion('ordenes')"
            :class="[
            'w-full text-white font-semibold rounded-full px-6 py-2 shadow flex items-center justify-center gap-2 transition',
            seccionActiva === 'ordenes' ? 'bg-[#014a99]' : 'bg-[#015ab6] hover:bg-[#014a99]'
            ]">
            ORDENES
            <i :class="seccionActiva === 'ordenes' ? 'fas fa-list-alt' : 'fas fa-list'"></i>
          </button>

          <!-- Botón Facturas -->
          <button @click="toggleSeccion('facturas')"
            :class="[
            'w-full text-white font-semibold rounded-full px-6 py-2 shadow flex items-center justify-center gap-2 transition',
            seccionActiva === 'facturas' ? 'bg-[#014a99]' : 'bg-[#015ab6] hover:bg-[#014a99]'
            ]">
            FACTURAS
            <i :class="seccionActiva === 'facturas' ? 'fas fa-calendar-check' : 'fas fa-calendar'"></i>
          </button>
        </div>

        <!-- Contenido dinámico -->

        <!-- Sección de visitas -->
        <Visitas v-if="seccionActiva === 'visitas'" />
        <!-- Sección de órdenes -->
        <Ordenes v-if="seccionActiva === 'ordenes'" />
        <!-- Sección de facturas -->
        <Facturas v-if="seccionActiva === 'facturas'" />
          
      </div>
    </div> 
  </AppLayout>
</template>

