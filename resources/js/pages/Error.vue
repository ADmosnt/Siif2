<!-- resources/js/pages/Error.vue -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

withDefaults(defineProps<{
  message?: string;
}>(), {
  message: 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo más tarde.',
});
</script>

<template>
  <Head title="Error" />

  <div class="flex min-h-svh flex-col items-center justify-center gap-4 p-6 text-center">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
      Algo salió mal
    </h1>
    <p class="max-w-md text-muted-foreground">
      {{ message }}
    </p>
    <Link href="/dashboard">
      <Button>Volver al inicio</Button>
    </Link>
  </div>
</template>
