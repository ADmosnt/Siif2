<template>
  <div class="p-4">
    <h3 class="text-center text-blue-700 dark:text-blue-300 font-semibold text-lg mb-2">
      {{ title }}
    </h3>
    <div class="h-1 bg-blue-600 dark:bg-blue-400 rounded-full mb-4"></div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string
}

defineProps<Props>()
</script>