<!-- resources/js/components/ui/sidebarShadcn/Sidebar.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { Frame } from 'lucide-vue-next'
import {FileLinesIcon, ClockIcon, TransferArrowsIcon, SiifIcon, FaqIcon, ContactIcon } from '@/components/icons'
import NavMain from '@/components/ui/sidebarShadcn/NavMain.vue'
import NavProjects from '@/components/ui/sidebarShadcn/NavProjects.vue'
import NavSecondary from '@/components/ui/sidebarShadcn/NavSecondary.vue'
import NavUser from '@/components/ui/sidebarShadcn/NavUser.vue'
import {  Sidebar,  SidebarContent,  SidebarFooter,  SidebarHeader,  SidebarMenu,  SidebarMenuButton,  SidebarMenuItem,  type SidebarProps,} from '@/components/ui/sidebar'
import { useOfflineStore } from '@/stores/offlineStore'

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

  ],
  projects: [
    {
      name: 'Conciliar Facturas',
      url: 'consolidar',
      icon: Frame,
    },
  ],
  navSecondary: [
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
      <NavMain :items="data.navMain" :is-online="isOnline" :is-url-offline-enabled="isUrlOfflineEnabled" />
      <NavProjects :projects="data.projects" :is-online="isOnline" :is-url-offline-enabled="isUrlOfflineEnabled" />
      <NavSecondary :items="data.navSecondary" :is-online="isOnline" :is-url-offline-enabled="isUrlOfflineEnabled" />
    </SidebarContent>
    <SidebarFooter>
      <NavUser :is-online="isOnline" />
    </SidebarFooter>
  </Sidebar>
</template>
