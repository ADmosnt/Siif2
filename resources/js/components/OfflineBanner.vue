<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const isOffline = ref(!navigator.onLine)

const handleOnline = () => {
    isOffline.value = false
}

const handleOffline = () => {
    isOffline.value = true
}

onMounted(() => {
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
})

onUnmounted(() => {
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)
})
</script>

<template>
    <transition name="slide-down">
        <div
            v-if="isOffline"
            class="fixed top-0 left-0 z-[9999] flex w-full items-center justify-center gap-2 bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-lg"
        >
            <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M18.364 5.636a9 9 0 010 12.728M5.636 18.364a9 9 0 010-12.728M12 9v4m0 4h.01"
                />
            </svg>
            <span>Sin conexión a internet. Algunas funciones pueden no estar disponibles.</span>
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
