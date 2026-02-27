<script setup lang="ts">
import { defineProps, ref, computed } from 'vue'

const props = defineProps({
  title: { type: String, default: 'Información' },
  message: { type: String, required: true },
  isDismissible: { type: Boolean, default: false },
})

const isVisible = ref(true)
function dismiss() {
  isVisible.value = false
}

const formattedParagraphs = computed(() => {
  return (props.message || '')
    .trim()
    .split(/\n\s*\n/)
    .map(paragraph => {
      let htmlParagraph = paragraph
        .replace(/\n/g, '<br/>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')

      // RESPONSIVE: Hacemos que "¡Importante!" sea un bloque para que el texto siguiente
      // fluya a una nueva línea, evitando saltos de línea extraños.
      htmlParagraph = htmlParagraph.replace(
        /¡Importante!/gi,
        '<span class="block font-extrabold text-red-700 dark:text-red-300 text-base mb-1">¡Importante!</span>'
      );

      return htmlParagraph;
    });
});
</script>

<template>
  <div
    v-if="isVisible"
    class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700
           text-blue-800 dark:text-blue-100 rounded-lg shadow-sm
           px-4 py-4 sm:px-6 sm:py-5"  
           
    role="alert"
  >
    <div 
      class="flex items-start gap-3 sm:gap-4" 
    >
      <svg class="w-6 h-6 text-blue-500 dark:text-blue-400 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>

      <div class="flex-grow">
        <h3 class="font-bold text-lg text-blue-900 dark:text-blue-100 mb-1">
          {{ title }}
        </h3>

        
        <div 
          class="space-y-3 text-sm leading-relaxed" 
        >
          <p v-for="(p, i) in formattedParagraphs" :key="i" v-html="p"></p>
        </div>
      </div>

      <button
        v-if="isDismissible"
        type="button"
        @click="dismiss"
        class="ml-auto p-1.5 rounded-md text-blue-500 dark:text-blue-200
               hover:bg-blue-200/50 dark:hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
        aria-label="Cerrar"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>
  </div>
</template>