import '../css/app.css'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import { createApp, h } from 'vue'
import { createPinia } from 'pinia'
import { ZiggyVue } from 'ziggy-js'
import { initializeTheme } from './composables/useAppearance'
import { setupAxiosInterceptors } from './plugins/axiosInterceptors'
import { setupInertiaAlerts } from './plugins/inertiaAlerts'
import SyncStatusBar from './components/OfflineSync/SyncStatusBar.vue'
import PushNotificationPrompt from './components/PushNotificationPrompt.vue'
import axios from 'axios'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./pages/**/*.vue')
    ),
  setup({ el, App, props, plugin }) {
    const app = createApp({
      render: () => [h(SyncStatusBar), h(PushNotificationPrompt), h(App, props)],
    })
    const pinia = createPinia()

    app.use(plugin)      // Inertia primero
    app.use(pinia)       // Luego Pinia
    app.use(ZiggyVue)
    // Configurar interceptores de axios
    setupAxiosInterceptors()

    // Configurar alertas de Inertia
    setupInertiaAlerts()
    // (Opcional) antes o después del mount, como prefieras:
    axios.defaults.withCredentials = true
    axios.defaults.withXSRFToken = true

    app.mount(el)
  },
  progress: { color: '#4B5563' },
})

initializeTheme()

// Registrar Service Workers
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    // SW de push notifications (scope /)
    navigator.serviceWorker.register('/sw.js').catch((error) => {
      console.warn('Push SW registration failed:', error)
    })
    // SW de Workbox con scope raíz para cache de páginas offline
    navigator.serviceWorker.register('/offline-sw.js', { scope: '/' }).catch((error) => {
      console.warn('Workbox SW registration failed:', error)
    })
  })
}
