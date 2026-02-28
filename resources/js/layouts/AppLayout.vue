<!-- resources/js/layouts/AppLayout.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import GenericGlobalAlert from '@/components/GenericGlobalAlert.vue'

// --- Imports para el Easter Egg (controlado por VITE_CUBEGG_ENABLED) ---
import { ref, onMounted, onUnmounted, defineAsyncComponent } from 'vue';
import { router } from '@inertiajs/vue3';

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

// ========================================================================
// --- LÓGICA DEL EASTER EGG (VERSIÓN INERTIA.JS) ---
// Se activa SOLO si VITE_CUBEGG_ENABLED=true en el .env
// ========================================================================
const cubeggEnabled = import.meta.env.VITE_CUBEGG_ENABLED === 'true';
const showEasterEgg = ref(false);
const routeHistory = ref<string[]>([]);
const secretRouteSequence = ['/consulta-reporte', '/preguntas', '/consulta-gerencial'];

let removeInertiaListener: (() => void) | null = null;

onMounted(() => {
  if (!cubeggEnabled) {
    console.log('[CubEgg] ❌ Desactivado (VITE_CUBEGG_ENABLED no es "true")');
    return;
  }

  console.log('[CubEgg] ✅ Activado. Secuencia secreta:', secretRouteSequence);

  removeInertiaListener = router.on('success', (event) => {
    const newPath = new URL(event.detail.page.url, window.location.origin).pathname;

    routeHistory.value.push(newPath);

    // Solo mantener las últimas N rutas necesarias
    if (routeHistory.value.length > secretRouteSequence.length) {
      routeHistory.value = routeHistory.value.slice(-secretRouteSequence.length);
    }

    const lastVisited = routeHistory.value.slice(-secretRouteSequence.length);

    console.log('[CubEgg] Navegación:', newPath, '| Historial:', lastVisited, '| Esperado:', secretRouteSequence);

    if (lastVisited.length === secretRouteSequence.length &&
        JSON.stringify(lastVisited) === JSON.stringify(secretRouteSequence)) {
      console.log('[CubEgg] 🎉 ¡Secuencia completada! Activando Easter Egg');
      showEasterEgg.value = !showEasterEgg.value;
      routeHistory.value = [];
    }
  });
});

onUnmounted(() => {
  if (removeInertiaListener) {
    removeInertiaListener();
  }
});
</script>

<template>
    <div>
        <AppLayout :breadcrumbs="breadcrumbs">
             <GenericGlobalAlert />
            <slot />
        </AppLayout>

        <CubEgg v-if="cubeggEnabled && showEasterEgg" @close="showEasterEgg = false" />
    </div>
</template>