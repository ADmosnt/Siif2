<script setup lang="ts">
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import ActionCard from '@/components/ActionCard.vue'
import { menuMap } from '@/configs/admin/menus' // Importamos la config nueva

const props = defineProps<{
  tipo: string;
}>()

// Buscamos la configuración o devolvemos un fallback vacio para evitar errores
const config = computed(() => menuMap[props.tipo] || { 
  title: 'Menú Desconocido', 
  breadcrumbs: [], 
  actions: [] 
})

</script>

<template>
  <Head :title="config.title" />

  <AppLayout :breadcrumbs="config.breadcrumbs">
    <div class="px-4 sm:px-6 py-12">
      
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-8">
        {{ config.title }}
      </h1>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
        
        <ActionCard
          v-for="(accion, index) in config.actions"
          :key="index"
          :icon="accion.icon"
          :label="accion.label"
          :to="accion.to"
          size="lg"          
          :bg-color="accion.bgColor"
          :footer-color="accion.footerColor"
          label-color="#ffffff"
          footer-text-color="#ffffff"
          variant="raised"
        />

      </div>
    </div>
  </AppLayout>
</template>