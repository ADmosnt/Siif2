<!-- resources/js/pages/Dashboard.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { router, Head, usePage } from '@inertiajs/vue3';
import ActionCard from '@/components/ActionCard.vue'
import GenericCombobox from '@/components/GenericCombobox.vue'
import { type BreadcrumbItem } from '@/types'
import Button from '@/components/ui/button/Button.vue'
import ReportIcon from '@/components/icons/ReportIcon.vue'
import GerencialIcon from '@/components/icons/GerencialIcon.vue'
import ConciliarIcon from '@/components/icons/ConciliarIcon.vue'
import UsersIcon from '@/components/icons/UsersIcon.vue'
import CubeIcon from '@/components/icons/CubeIcon.vue'
import MonitorIcon from '@/components/icons/MonitorIcon.vue'
import { ref, computed, onMounted } from 'vue';
import { type SelectOption } from '@/components/GenericCombobox.vue'

interface EmpresaOption {
    idPersona: string;
    nombre_completo_razon_social: string;
    idFabricante: string;
}

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF' },
  { label: 'Home' }
]
//
  
const props = defineProps<{
  empresas: EmpresaOption[];
  usuario: any;
  activeCompanyId: string | null; // <--- Nueva prop
}>();

const empresaSeleccionada = ref<SelectOption | null>(null)

// 1. Opciones del selector con lógica SIIF
const empresaOptions = computed(() => {
  const options = props.empresas.map(emp => ({
    label: emp.nombre_completo_razon_social,
    value: emp.idPersona
  }));

  // Si es SIIF, añadimos la opción de reset al principio
  if (props.usuario.idgrupo_persona === 'SIIF') {
    options.unshift({
      label: '🌐 VER TODAS (MODO GLOBAL)',
      value: '' // El controller recibirá null y limpiará la sesión
    });
  }

  return options;
});

onMounted(() => {
  if (props.activeCompanyId) {
    const encontrada = props.empresas.find(e => e.idPersona === props.activeCompanyId);
    if (encontrada) {
      empresaSeleccionada.value = {
        label: encontrada.nombre_completo_razon_social,
        value: encontrada.idPersona
      };
    }
  } else if (props.usuario.idgrupo_persona === 'SIIF') {
    // Si no hay empresa activa y es SIIF, marcar "Ver Todas" por defecto
    empresaSeleccionada.value = { label: '🌐 VER TODAS (MODO GLOBAL)', value: null };
  }
});


// 2. Función para disparar el cambio en Laravel
function seleccionarEmpresa() {
  // Si el valor es una cadena vacía, enviamos null a Laravel
  const valor = empresaSeleccionada.value?.value;
  const idAEnviar = valor === '' ? null : valor;

  router.post(route('context.switch'), { 
    id: idAEnviar 
  }, {
    preserveScroll: true,
    onSuccess: () => {
       console.log("Contexto actualizado");
    }
  });
}

const accionesRapidas = [
  { 
    icon: ReportIcon, 
    label: 'CONSULTAR REPORTES', 
    to: '/consulta-reporte', 
    bgColor: '#4c7fdd', 
    footerColor: '#2c5cb3',
    labelColor: '#ffffff',
    footerTextColor: '#ffffff'
  },
  { 
    icon: GerencialIcon, 
    label: 'CONSULTA GERENCIAL', 
    to: '/consulta-gerencial', 
    bgColor: '#34c759', 
    footerColor: '#28a745',
    labelColor: '#ffffff',
    footerTextColor: '#ffffff'
  },
  { 
    icon: ConciliarIcon, 
    label: 'CONCILIAR', 
    to: '/consolidar', 
    bgColor: '#ff9500', 
    footerColor: '#e68500',
    labelColor: '#ffffff',
    footerTextColor: '#ffffff'
  },
  { 
    icon: UsersIcon, 
    label: 'PERSONAS', 
    to: '/personas', 
    bgColor: '#ff3b30', 
    footerColor: '#d70015',
    labelColor: '#ffffff',
    footerTextColor: '#ffffff'
  },
  { 
    icon: CubeIcon, 
    label: 'PRODUCTOS', 
    to: '/productos', 
    bgColor: '#af52de', 
    footerColor: '#8e44ad',
    labelColor: '#ffffff',
    footerTextColor: '#ffffff'
  },
  { 
    icon: MonitorIcon, 
    label: 'MONITOR', 
    to: '/monitor', 
    bgColor: '#5856d6', 
    footerColor: '#4a48b5',
    labelColor: '#ffffff',
    footerTextColor: '#ffffff'
  }
]

</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 sm:px-6 py-8 transition-colors duration-200">
      
      <!-- Header Principal -->
      <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 transition-colors duration-200">
          ¡Hola {{ props.usuario.nombre }}!
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-300 transition-colors duration-200">
          Bienvenido al sistema automatizado APP SIIF
        </p>
      </header>

      <!-- Selector de Empresa -->
      <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-8 border border-gray-200 dark:border-gray-700 transition-colors duration-200">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="w-full sm:w-80">
              <GenericCombobox
                v-model="empresaSeleccionada"
                :options="empresaOptions"
                placeholder="Seleccione la empresa"
              />
            </div>

            <Button
              @click="seleccionarEmpresa"
              :disabled="!empresaSeleccionada || empresaSeleccionada.value === props.activeCompanyId"
              class="whitespace-nowrap w-full sm:w-auto"
            >
            {{ empresaSeleccionada?.value === props.activeCompanyId ? 'SELECCIONADA' : 'SELECCIONAR' }}
            </Button>
          </div>
        </div>
      </section>

      <!-- Acciones Rápidas -->
      <section class="mb-6">
        <h2 class="bg-linear-to-r from-blue-600 to-blue-800 text-white px-6 py-4 text-lg font-semibold rounded-t-lg transition-colors duration-200">
          ACCIONES RÁPIDAS
        </h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 p-6 bg-gray-50 dark:bg-gray-800 rounded-b-lg border border-t-0 border-gray-200 dark:border-gray-700 transition-colors duration-200">
          <ActionCard
            v-for="(accion, index) in accionesRapidas"
            :key="index"
            :icon="accion.icon"
            :label="accion.label"
            :to="accion.to"
            :bg-color="accion.bgColor"
            :footer-color="accion.footerColor"
            :label-color="accion.labelColor"
            :footer-text-color="accion.footerTextColor"
            size="md"  
            variant="raised"
          />
        </div>
      </section>
    </div>
  </AppLayout>
</template>