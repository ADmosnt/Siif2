<script setup lang="ts">
import { ref, nextTick, watch } from 'vue'

interface Props {
  index?: number
  question: string
  answer: string
  open?: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  toggle: [id: number]
}>()

const panelRef = ref<HTMLDivElement | null>(null)
const maxH = ref<number>(0)

// 1. Función Toggle Simplificada:
// Solo avisa al padre que se hizo clic. No calcula alturas aquí.
function toggle() {
  if (props.index) {
    emit('toggle', props.index)
  }
}

// 2. Watcher (El arreglo mágico):
// Vigila la propiedad 'open'. Si cambia a true (se abre) o false (se cierra porque abriste otro),
// recalcula la altura automáticamente.
watch(
  () => props.open, 
  async (isOpen) => {
    await nextTick() // Espera a que el DOM esté listo
    if (isOpen) {
      maxH.value = panelRef.value?.scrollHeight ?? 0
    } else {
      maxH.value = 0
    }
  }, 
  { immediate: true } // Para que funcione si alguno viene abierto por defecto
)
</script>

<template>
  <div class="border border-gray-200 dark:border-gray-700 rounded-lg 
              bg-white dark:bg-gray-800 
              shadow-sm hover:shadow-md transition-shadow duration-200">
    <button
      :id="`faq-button-${index}`"
      type="button"
      class="w-full flex items-center justify-between gap-4 px-6 py-4 text-left 
             hover:bg-gray-50 dark:hover:bg-gray-700/50 
             transition-colors duration-200 rounded-lg"
      :aria-expanded="open"
      :aria-controls="`faq-panel-${index}`"
      @click="toggle"
    >
      <span class="text-blue-600 dark:text-blue-400 font-semibold text-base leading-relaxed">
        <span v-if="index" class="text-blue-500 dark:text-blue-300">{{ index }}.</span>
        {{ question }}
      </span>

      <svg
        class="h-5 w-5 flex-none transition-transform duration-300 text-gray-500 dark:text-gray-400"
        :class="open ? 'rotate-180' : 'rotate-0'"
        viewBox="0 0 20 20"
        fill="currentColor"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z"
          clip-rule="evenodd"
        />
      </svg>
    </button>

    <div
      :id="`faq-panel-${index}`"
      ref="panelRef"
      class="overflow-hidden transition-[max-height] duration-500 ease-in-out"
      :style="{ maxHeight: `${maxH}px` }"
      role="region"
      :aria-labelledby="`faq-button-${index}`"
    >
      <div class="px-6 pb-5 text-gray-700 dark:text-gray-300 leading-relaxed 
                  whitespace-pre-line border-t border-gray-100 dark:border-gray-700 
                  pt-4 mt-1">
        {{ answer }}
      </div>
    </div>
  </div>
</template>