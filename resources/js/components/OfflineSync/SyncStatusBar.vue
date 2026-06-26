<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useOfflineStore } from '@/stores/offlineStore'

const store = useOfflineStore()

onMounted(() => store.init())
onUnmounted(() => store.destroy())
</script>

<template>
  <transition name="slide-down">
    <div
      v-if="!store.isOnline || store.hasPendingItems || store.isSyncing"
      class="sticky top-0 z-[9999] flex w-full items-center justify-center gap-3 px-4 py-2 text-sm font-medium text-white shadow-lg"
      :class="!store.isOnline
        ? 'bg-red-600'
        : store.isSyncing
          ? 'bg-blue-600'
          : 'bg-amber-600'"
    >
      <svg v-if="!store.isOnline" class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M18.364 5.636a9 9 0 010 12.728M5.636 18.364a9 9 0 010-12.728M12 9v4m0 4h.01" />
      </svg>

      <svg v-else-if="store.isSyncing" class="h-5 w-5 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
      </svg>

      <span v-if="!store.isOnline && store.hasPendingItems">
        Sin conexion | {{ store.pendingCount }} operacion(es) pendiente(s)
      </span>
      <span v-else-if="!store.isOnline">
        Sin conexion a internet
      </span>
      <span v-else-if="store.isSyncing">
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
