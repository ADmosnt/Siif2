<script setup lang="ts">
import { onMounted } from 'vue'
import { useOfflineStore } from '@/stores/offlineStore'

const store = useOfflineStore()

onMounted(() => {
  store.refreshCounts()
})
</script>

<template>
  <span
    v-if="store.pendingCount > 0"
    class="inline-flex items-center justify-center rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white"
  >
    {{ store.pendingCount }}
  </span>
</template>
