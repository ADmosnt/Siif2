// resources/js/stores/easterEggStore.ts
import { ref } from 'vue'
import { defineStore } from 'pinia'

export const useEasterEggStore = defineStore('easterEgg', () => {
  const showEasterEgg = ref(false)

  function toggle() {
    showEasterEgg.value = !showEasterEgg.value
  }

  function close() {
    showEasterEgg.value = false
  }

  return {
    showEasterEgg,
    toggle,
    close,
  }
})
