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

// Registrar Service Worker para PWA y Push Notifications
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch((error) => {
      console.warn('Service Worker registration failed:', error)
    })
  })
}
