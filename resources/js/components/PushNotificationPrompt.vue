<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

const permission = ref(Notification.permission)
const showPrompt = ref(false)
const subscribing = ref(false)

onMounted(() => {
    // Solo mostrar el prompt si el navegador soporta notificaciones
    // y el usuario aún no ha decidido
    if ('Notification' in window && 'serviceWorker' in navigator) {
        if (Notification.permission === 'default') {
            // Esperar un poco antes de mostrar el prompt
            setTimeout(() => {
                showPrompt.value = true
            }, 3000)
        } else if (Notification.permission === 'granted') {
            // Ya tiene permiso, registrar suscripción silenciosamente
            registerSubscription()
        }
    }
})

async function requestPermission() {
    subscribing.value = true
    try {
        const result = await Notification.requestPermission()
        permission.value = result

        if (result === 'granted') {
            await registerSubscription()
        }
    } catch (error) {
        console.error('Error solicitando permiso de notificación:', error)
    } finally {
        subscribing.value = false
        showPrompt.value = false
    }
}

async function registerSubscription() {
    try {
        const registration = await navigator.serviceWorker.ready

        // Obtener la clave pública VAPID del servidor
        const vapidKey = document.querySelector<HTMLMetaElement>('meta[name="vapid-public-key"]')?.content
        if (!vapidKey) {
            console.warn('VAPID public key no encontrada en meta tag')
            return
        }

        const applicationServerKey = urlBase64ToUint8Array(vapidKey)

        // Si el navegador ya tiene una suscripcion con una clave VAPID distinta
        // a la actual (p.ej. tras rotar las claves en el servidor), subscribe()
        // falla con "A subscription with a different application server key
        // already exists". Hay que dar de baja esa suscripcion vieja primero.
        const existing = await registration.pushManager.getSubscription()
        if (existing) {
            const existingKey = new Uint8Array(existing.options.applicationServerKey as ArrayBuffer)
            const sameKey = existingKey.length === applicationServerKey.length
                && existingKey.every((byte, i) => byte === applicationServerKey[i])

            if (!sameKey) {
                await existing.unsubscribe()
            }
        }

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey,
        })

        const subJson = subscription.toJSON()

        // Enviar suscripción al servidor
        await axios.post('/push/subscribe', {
            endpoint: subJson.endpoint,
            keys: {
                p256dh: subJson.keys?.p256dh,
                auth: subJson.keys?.auth,
            },
            content_encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
        })
    } catch (error) {
        console.error('Error registrando suscripción push:', error)
    }
}

function dismissPrompt() {
    showPrompt.value = false
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - base64String.length % 4) % 4)
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
    const rawData = window.atob(base64)
    const outputArray = new Uint8Array(rawData.length)
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i)
    }
    return outputArray
}
</script>

<template>
    <transition name="slide-up">
        <div
            v-if="showPrompt"
            class="fixed bottom-4 left-4 right-4 z-[9998] mx-auto max-w-md rounded-lg border border-gray-200 bg-white p-4 shadow-xl dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        Activar notificaciones
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Recibe alertas de promociones, avisos y novedades importantes.
                    </p>
                    <div class="mt-3 flex gap-2">
                        <button
                            :disabled="subscribing"
                            class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                            @click="requestPermission"
                        >
                            {{ subscribing ? 'Activando...' : 'Activar' }}
                        </button>
                        <button
                            class="rounded-md px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            @click="dismissPrompt"
                        >
                            Ahora no
                        </button>
                    </div>
                </div>
                <button
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    @click="dismissPrompt"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}
</style>
