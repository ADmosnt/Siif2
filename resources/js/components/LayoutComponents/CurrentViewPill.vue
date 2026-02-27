<!-- resources/js/components/ui/CurrentViewPill.vue -->
<script setup lang="ts">
defineProps<{
  currentView?: string
}>()
</script>

<template>
  <div
    v-if="currentView"
    class="current-view-pill"
  >
    <span class="current-view-text">
      {{ currentView }}
    </span>
  </div>
</template>

<!-- ELIMINA el style scoped y usaremos solo el CSS global -->