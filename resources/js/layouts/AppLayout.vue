<!-- resources/js/layouts/AppLayout.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import GenericGlobalAlert from '@/components/GenericGlobalAlert.vue'
import { defineAsyncComponent } from 'vue';
import { useEasterEggStore } from '@/stores/easterEggStore';

// Carga asíncrona del CubEgg para no afectar el bundle principal
const CubEgg = defineAsyncComponent(() =>
  import('@/components/LayoutComponents/CubEgg.vue')
);

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}
withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// El Konami Code se registra globalmente en plugins/konamiCode.ts
// Aquí solo consumimos el estado del store
const cubeggEnabled = import.meta.env.VITE_CUBEGG_ENABLED === 'true';
const easterEggStore = useEasterEggStore();
</script>

<template>
    <div>
        <AppLayout :breadcrumbs="breadcrumbs">
             <GenericGlobalAlert />
            <slot />
        </AppLayout>

        <CubEgg v-if="cubeggEnabled && easterEggStore.showEasterEgg" @close="easterEggStore.close()" />
    </div>
</template>