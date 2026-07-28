<script setup lang="ts">
import { onMounted } from 'vue'
import { useOfflineStore } from '@/stores/offlineStore'

const store = useOfflineStore()

// Este componente se monta una sola vez para toda la sesion (ver app.ts,
// se renderiza junto al root de Inertia, no dentro de una pagina), asi que
// nunca deberia desmontarse en uso normal. No se llama a store.destroy()
// en onUnmounted: si este componente llegara a desmontarse igual (HMR,
// etc.) eso mataria el tracking de online/offline para el resto de la
// sesion, dejando el sidebar y el boton de logout sin actualizarse.
onMounted(() => store.init())
</script>

<template>
  <transition name="slide-down">
    <div
      v-if="store.hasPendingItems || store.isSyncing"
      class="sticky top-0 z-[9999] flex w-full items-center justify-center gap-3 px-4 py-2 text-sm font-medium text-white shadow-lg"
      :class="store.isSyncing ? 'bg-blue-600' : 'bg-amber-600'"
    >
      <svg v-if="store.isSyncing" class="h-5 w-5 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
      </svg>

      <span v-if="store.isSyncing">
        Sincronizando...
      </span>
      <span v-else-if="store.hasPendingItems">
        {{ store.pendingCount }} operacion(es) pendiente(s)
      </span>

      <button
        v-if="store.isOnline && store.hasPendingItems && !store.isSyncing"
        @click="store.syncNow()"
        class="ml-2 rounded bg-white/20 px-3 py-1 text-xs font-semibold hover:bg-white/30 transition-colors"
      >
        Sincronizar ahora
      </button>
    </div>
  </transition>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}
</style>
