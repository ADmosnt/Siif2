<script setup lang="ts">
import { computed } from 'vue';
import { Empresa } from '@/pages/Monitor.vue';

interface Props {
  label: string;
  empresa: Empresa | null;
  loading?: boolean;
  onDownload?: () => void; // Función opcional para manejar la descarga desde el padre
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: 'file-selected', file: File): void;
}>();

const isDisabled = computed(() => !props.empresa || props.loading);

const handleUploadClick = () => {
  if (isDisabled.value) return;

  const input = document.createElement('input');
  input.type = 'file';
  input.accept = '.xlsx,.xls,.csv';
  input.onchange = (event) => {
    const file = (event.target as HTMLInputElement)?.files?.[0];
    if (file) {
      emit('file-selected', file);
    }
    input.remove();
  };
  document.body.appendChild(input);
  input.click();
};

const handleDownload = () => {
  props.onDownload?.();
};
</script>

<template>
  <div class="border p-3 rounded">
    <div class="flex justify-between items-center mb-2">
      <p class="font-bold">{{ label }}</p>
      <button
        v-if="onDownload"
        @click="handleDownload"
        :disabled="isDisabled"
        class="flex items-center px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:bg-gray-400 text-sm"
      >
        <span v-if="loading">Generando...</span>
        <span v-else>Descargar</span>
        <svg v-if="!loading" class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
      </button>
    </div>

    <div
      class="drop-zone border border-dashed border-gray-400 p-4 text-center cursor-pointer transition-opacity"
      :class="{ 'opacity-50 cursor-not-allowed': isDisabled }"
      @click="handleUploadClick"
    >
      Arrastra o haz clic para seleccionar
    </div>
  </div>
</template>