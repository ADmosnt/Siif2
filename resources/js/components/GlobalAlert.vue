<!-- src/components/GlobalAlert.vue -->
<script setup lang="ts">
import { computed, watch, onUnmounted } from 'vue';

const props = defineProps<{
  type: 'success' | 'error' | 'warning' | '' | null;
  message: string;
  autoHide?: boolean;
  autoHideDelay?: number;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
}>();

// Determina si la alerta debe mostrarse
const isVisible = computed(() => Boolean(props.message && props.type));

// Clases de estilo basadas en el tipo
const bgColorClass = computed(() => {
  switch (props.type) {
    case 'success': return 'bg-green-100 border-green-400 text-green-700';
    case 'error': return 'bg-red-100 border-red-400 text-red-700';
    case 'warning': return 'bg-yellow-100 border-yellow-400 text-yellow-700';
    default: return '';
  }
});

// Estado interno para el temporizador
let hideTimer: number | null = null;

// Función para iniciar el auto-ocultado
const startAutoHide = () => {
  if (props.autoHide && props.autoHideDelay !== undefined) {
    hideTimer = window.setTimeout(() => {
      emit('close');
    }, props.autoHideDelay);
  }
};

// Función para detener el auto-ocultado
const stopAutoHide = () => {
  if (hideTimer !== null) {
    clearTimeout(hideTimer);
    hideTimer = null;
  }
};

// Observar cambios en el mensaje para reiniciar el auto-ocultado
watch(() => props.message, (newMessage) => {
  stopAutoHide();
  if (newMessage) {
    startAutoHide();
  }
});


onUnmounted(() => {
  stopAutoHide();
});
</script>

<template>
  <transition name="fade"> <!-- Opcional: Agregar animación -->
    <div v-if="isVisible" :class="['border px-4 py-3 rounded mb-4 relative', bgColorClass]">
      <p class="text-center pr-6">{{ message }}</p>
      <!-- Botón de cierre -->
      <button
        @click="$emit('close')"
        class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 focus:outline-none"
        aria-label="Cerrar alerta"
      >
        <!-- Icono simple de 'X' o usa un componente de icono -->
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>
  </transition>
</template>

<style scoped>
/* Opcional: Estilos para la transición */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>