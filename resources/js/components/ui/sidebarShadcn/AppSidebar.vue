<!-- resources/js/components/ui/sidebarShadcn/Sidebar.vue -->
<script setup lang="ts">
import { Frame } from 'lucide-vue-next'
import {FileLinesIcon, ClockIcon, TransferArrowsIcon, SiifIcon, FaqIcon, ContactIcon } from '@/components/icons'
import NavMain from '@/components/ui/sidebarShadcn/NavMain.vue'
import NavSecondary from '@/components/ui/sidebarShadcn/NavSecondary.vue'
import NavUser from '@/components/ui/sidebarShadcn/NavUser.vue'
import {  Sidebar,  SidebarContent,  SidebarFooter,  SidebarHeader,  SidebarMenu,  SidebarMenuButton,  SidebarMenuItem,  type SidebarProps,} from '@/components/ui/sidebar'
import { usePage } from '@inertiajs/vue3'
import {computed} from 'vue'
import { useOfflineStore } from '@/stores/offlineStore'

const page = usePage()
const userRole = computed(() => page.props.auth.role)
const offlineStore = useOfflineStore()

const props = withDefaults(defineProps<SidebarProps>(), {
  variant: 'inset',
})

const OFFLINE_URLS = new Set(['/nuevo-reporte', 'tdp', '/sync-queue'])

const data = {

  navMain: [
    {
      title: 'Consultas - Reportes',
      url: '/consulta-reporte',
      icon: FileLinesIcon,
    },
    {
      title: 'Consulta Gerencial',
      url: '/consulta-gerencial',
      role: ['SIIF', 'GRT', 'SUP'],
      icon: FileLinesIcon,
    },
    {
      title: 'RTR',
      url: '#',
      icon: ClockIcon,
      isActive: true,
      items: [
        {
          title: 'Toma de Reporte',
          url: '/nuevo-reporte',
        },
        {
          title: 'Lista de Clientes',
          url: '/agenda',
        },
        {
          title: 'Planificador de Visitas',
          url: '/tmp-planificaciones',
        },
        {
          title: 'Lista de Reportes',
          url: '/reporte-agenda',
        },
      ],
    },
    {
      title: 'CT',
      url: '#',
      icon: TransferArrowsIcon,
      isActive: true,
      items: [
        {
          title: 'Toma de Pedidos',
          url: 'tdp',
        },
        {
          title: 'Seguimiento de Pedidos',
          url: 'seguimiento',
        },
        {
          title: 'Cola de Sincronizacion',
          url: '/sync-queue',
        },
      ],
    },
    {
      title: 'Administrar',
      url: '#',
      role: ['SIIF', 'GRT', 'SUP'],
      icon: Frame,
      isActive: true,
      items: [
        {
          title: 'Conciliar Facturas',
          url: 'consolidar',
        },
        {
          title: 'Monitor de Archivos',
          url: '/monitor',
        },
      ],
    },

    {
      title: 'Personas',
      url: '#',
      icon: Frame,
      items: [
        {
          title: 'Clientes',
          url: '/clientes',
        },
        {
          title: 'Representantes',
          url: '/representantes',
        },
        {
          title: 'Mayoristas',
          url: '/mayoristas',
        },
        {
          title: 'Supervisores',
          url: '/supervisores',
        },
        {
          title: 'Gerentes',
          url: '/gerentes',
        },
        {
          title: 'Empresas',
          url: '/empresas',
          role: ['SIIF'],
        },
      ],
    },

    {
      title: 'Artículos',
      url: '#',
      icon: Frame,
      items: [
        {
          title: 'Productos',
          url: '/productos-lista',
        },
        {
          title: 'Muestras',
          url: '/muestras',
        },
      ],
    }
  ],

  navSecondary: [
    {
      title: 'Notificaciones',
      url: '/notificacion',
      icon: ContactIcon,
    },
    {
      title: 'Preguntas Frecuentes',
      url: '/preguntas',
      icon: FaqIcon,
    },
    {
      title: 'Contacto',
      url: '/Contacto',
      icon: ContactIcon,
    },
  ],
}

//filtrar recursivamente
const filterByRole = (items: any[]) =>{
  return items
  .filter(item => {
    if (!item.role) return true
    return item.role.includes(userRole.value)
  })
  .map(item=>{
    if (item.items) {
      return {
        ...item,
        items: filterByRole(item.items)
      }
    }
    return item
  })
}

//datos filtrados por role
const filteredNavMain = computed(() => filterByRole(data.navMain))
const filteredNavSecondary = computed(() => filterByRole(data.navSecondary))

const isOnline = computed(() => offlineStore.isOnline)

function isUrlOfflineEnabled(url: string): boolean {
  return OFFLINE_URLS.has(url)
}
</script>

<template>
  <Sidebar v-bind="props">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <a href="/dashboard">

              <img :src="SiifIcon" alt="App SIIF" class="size-12"/>
              <div class="grid flex-1 text-left text-sm leading-tight">
                <span class=" truncate font-semibold"> App Gestoria SIIF</span>
                <span class=" truncate text-xs">by Bimodal Technology</span>
              </div>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>
    <SidebarContent>
      <NavMain :items="filteredNavMain" :is-online="isOnline" :is-url-offline-enabled="isUrlOfflineEnabled" />

      <NavSecondary :items="filteredNavSecondary" :is-online="isOnline" :is-url-offline-enabled="isUrlOfflineEnabled" class="mt-auto" />
    </SidebarContent>
    <SidebarFooter>
      <NavUser :is-online="isOnline" />
    </SidebarFooter>
  </Sidebar>
</template>
