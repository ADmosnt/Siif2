<!-- resources/js/layouts/AppLayout.vue -->
<script setup lang="ts">
// --- Tus imports originales ---
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

import GenericGlobalAlert from '@/components/GenericGlobalAlert.vue'


// // --- Imports para el Easter Egg ---
// import { ref, onMounted, onUnmounted } from 'vue';
// // ¡Importante! Importamos el 'router' de Inertia, NO de vue-router
// import { router } from '@inertiajs/vue3'; 
// // import CubEgg from '@/components/CubEgg.vue';

// --- Tus props originales ---
interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}
withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// ========================================================================
// --- LÓGICA DEL EASTER EGG (VERSIÓN INERTIA.JS) ---
// ========================================================================

// const showEasterEgg = ref(false);
// const routeHistory = ref<string[]>([]);
// const secretRouteSequence = ['/tdp', '/consulta-reporte', '/consulta-gerencial']; 

// // Variable para guardar la función que elimina el listener y evitar fugas de memoria
// let removeInertiaListener: () => void;

// onMounted(() => {
//   // En lugar de 'watch', nos suscribimos al evento 'success' de Inertia.
//   // Este evento se dispara cada vez que una visita a una página se completa con éxito.
//   removeInertiaListener = router.on('success', (event) => {
//     const newPath = event.detail.page.url; // Obtenemos la nueva URL del evento
//     console.log(`Inertia navigation success: ${newPath}`);

//     routeHistory.value.push(newPath);
//     const lastVisited = routeHistory.value.slice(-secretRouteSequence.length);

//     if (JSON.stringify(lastVisited) === JSON.stringify(secretRouteSequence)) {
//         showEasterEgg.value = !showEasterEgg.value;
//         console.log(`SECUENCIA SECRETA DETECTADA. Easter Egg: ${showEasterEgg.value ? 'ON' : 'OFF'}`);
//         routeHistory.value = [];
//     }
//   });
// });

// onUnmounted(() => {
//   // Cuando el componente se destruye, es CRUCIAL eliminar el listener
//   // para evitar que se siga ejecutando y cause problemas de memoria.
//   if (removeInertiaListener) {
//     removeInertiaListener();
//   }
// });

</script>

<template>
    <div> 
        <AppLayout :breadcrumbs="breadcrumbs">
             <GenericGlobalAlert />
            <slot />
        </AppLayout>

        <!-- <CubEgg v-if="showEasterEgg" /> -->
    </div>
</template>