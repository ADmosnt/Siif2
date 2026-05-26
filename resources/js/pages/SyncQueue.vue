<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Button from '@/components/ui/button/Button.vue'
import { useOfflineStore } from '@/stores/offlineStore'
import { useAlertStore } from '@/stores/alertStore'
import type { SyncQueueItem } from '@/offline/types'

const store = useOfflineStore()
const alertStore = useAlertStore()

const breadcrumbs = [
  { label: 'SIIF', href: '/dashboard' },
  { label: 'Cola de Sincronizacion' },
]

const pendingItems = computed(() => store.queueItems.filter(i => i.status !== 'failed'))
const failedItems = computed(() => store.queueItems.filter(i => i.status === 'failed'))

function formatDate(ts: number): string {
  return new Date(ts).toLocaleString('es-VE', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function typeLabel(type: string): string {
  return type === 'order' ? 'Pedido' : 'Reporte'
}

function statusLabel(status: string): string {
  const map: Record<string, string> = {
    pending: 'Pendiente',
    sending: 'Enviando...',
    failed: 'Error',
  }
  return map[status] ?? status
}

function statusClass(status: string): string {
  const map: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    sending: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
  }
  return map[status] ?? ''
}

async function handleSync() {
  const result = await store.syncNow()
  if (result.succeeded > 0) {
    alertStore.showSuccess(`${result.succeeded} operacion(es) sincronizada(s) correctamente`)
  }
  if (result.failed > 0) {
    alertStore.showError(`${result.failed} operacion(es) fallaron. Revise los detalles.`)
  }
}

async function handleRemove(item: SyncQueueItem) {
  if (confirm(`¿Eliminar ${typeLabel(item.type)} ${item.local_ref}? Esta accion no se puede deshacer.`)) {
    await store.removeItem(item.id!)
  }
}

async function handleRetry(item: SyncQueueItem) {
  await store.retryFailedItem(item.id!)
  await handleSync()
}

async function handleDownload() {
  const result = await store.downloadData()
  if (result.success) {
    alertStore.showSuccess('Datos actualizados correctamente')
  } else {
    alertStore.showError(result.error || 'Error actualizando datos')
  }
}

onMounted(async () => {
  store.init()
  await store.refreshQueue()
  await store.refreshLog()
})
</script>

<template>
  <Head title="Cola de Sincronizacion" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">

      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Cola de Sincronizacion
        </h1>
        <div class="flex gap-2">
          <Button
            variant="outline"
            :disabled="store.isDownloading || !store.isOnline"
            @click="handleDownload"
          >
            {{ store.isDownloading ? 'Descargando...' : 'Actualizar Datos' }}
          </Button>
          <Button
            :disabled="!store.hasPendingItems || store.isSyncing || !store.isOnline"
            @click="handleSync"
          >
            {{ store.isSyncing ? 'Sincronizando...' : 'Sincronizar Todo' }}
          </Button>
        </div>
      </div>

      <!-- Estado del cache -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border p-4">
        <h2 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Estado de datos locales</h2>
        <div class="flex items-center gap-4 text-sm">
          <span class="flex items-center gap-1">
            <span
              class="h-2.5 w-2.5 rounded-full"
              :class="store.isOnline ? 'bg-green-500' : 'bg-red-500'"
            />
            {{ store.isOnline ? 'Conectado' : 'Sin conexion' }}
          </span>
          <span v-if="store.hasCache" class="text-gray-600 dark:text-gray-400">
            Datos descargados: {{ store.lastCacheDate?.toLocaleString('es-VE') ?? 'nunca' }}
          </span>
          <span v-else class="text-gray-500">
            Sin datos locales. Presione "Actualizar Datos" para descargar.
          </span>
          <span
            v-if="store.hasCache && !store.cacheIsFresh"
            class="text-amber-600 dark:text-amber-400 font-medium"
          >
            (datos de hace mas de 24h)
          </span>
        </div>
      </div>

      <!-- Operaciones pendientes -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border">
        <div class="border-b p-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Pendientes ({{ pendingItems.length }})
          </h2>
        </div>

        <div v-if="pendingItems.length === 0" class="p-8 text-center text-gray-500">
          No hay operaciones pendientes
        </div>

        <div v-else class="divide-y">
          <div
            v-for="item in pendingItems"
            :key="item.id"
            class="flex items-center justify-between p-4"
          >
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <span class="font-mono text-sm text-gray-500">{{ item.local_ref }}</span>
                <span
                  class="rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="statusClass(item.status)"
                >
                  {{ statusLabel(item.status) }}
                </span>
              </div>
              <p class="text-sm text-gray-900 dark:text-white mt-1">
                {{ typeLabel(item.type) }}
              </p>
              <p class="text-xs text-gray-500 mt-0.5">
                Creado: {{ formatDate(item.created_at) }}
              </p>
            </div>
            <button
              @click="handleRemove(item)"
              class="ml-4 text-red-500 hover:text-red-700 text-sm"
              title="Eliminar"
            >
              Eliminar
            </button>
          </div>
        </div>
      </div>

      <!-- Operaciones con error -->
      <div v-if="failedItems.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border border-red-200">
        <div class="border-b border-red-200 p-4">
          <h2 class="text-lg font-semibold text-red-700 dark:text-red-400">
            Con errores ({{ failedItems.length }})
          </h2>
        </div>

        <div class="divide-y divide-red-100">
          <div
            v-for="item in failedItems"
            :key="item.id"
            class="p-4"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <span class="font-mono text-sm text-gray-500">{{ item.local_ref }}</span>
                  <span class="rounded-full bg-red-100 text-red-800 px-2 py-0.5 text-xs font-semibold">
                    Error ({{ item.attempts }} intento{{ item.attempts !== 1 ? 's' : '' }})
                  </span>
                </div>
                <p class="text-sm text-gray-900 dark:text-white mt-1">
                  {{ typeLabel(item.type) }}
                </p>
                <p v-if="item.error_message" class="text-xs text-red-600 mt-1">
                  {{ item.error_message }}
                </p>
              </div>
              <div class="flex gap-2 ml-4">
                <button
                  @click="handleRetry(item)"
                  :disabled="!store.isOnline"
                  class="text-blue-600 hover:text-blue-800 text-sm disabled:opacity-50"
                >
                  Reintentar
                </button>
                <button
                  @click="handleRemove(item)"
                  class="text-red-500 hover:text-red-700 text-sm"
                >
                  Eliminar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Historial de sincronizaciones -->
      <div v-if="store.logEntries.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border">
        <div class="border-b p-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Historial reciente
          </h2>
        </div>

        <div class="divide-y">
          <div
            v-for="entry in store.logEntries"
            :key="entry.id"
            class="flex items-center justify-between p-4"
          >
            <div>
              <div class="flex items-center gap-2">
                <span class="font-mono text-sm text-gray-500">{{ entry.local_ref }}</span>
                <span class="rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-semibold">
                  Sincronizado
                </span>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                {{ entry.payload_summary }}
              </p>
            </div>
            <span class="text-xs text-gray-500">
              {{ formatDate(entry.synced_at) }}
            </span>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
