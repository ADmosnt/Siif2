import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  downloadMasterData,
  downloadOfflineToken,
  processQueue,
  getQueueCount,
  getPendingItems,
  getSyncLog,
  clearSyncLog,
  removeFromQueue,
  retryItem,
  type SyncResult,
} from '@/offline/syncService'
import { isCacheAvailable, isCacheFresh, getLastCacheTimestamp } from '@/offline/cacheService'
import { getOfflineSession, clearOfflineSession, type OfflineSession } from '@/offline/authService'
import { db } from '@/offline/db'
import type { SyncQueueItem, SyncLogEntry } from '@/offline/types'

export const useOfflineStore = defineStore('offline', () => {
  const isOnline = ref(navigator.onLine)
  const pendingCount = ref(0)
  const isSyncing = ref(false)
  const isDownloading = ref(false)
  const lastSyncResult = ref<SyncResult | null>(null)
  const hasCache = ref(false)
  const cacheIsFresh = ref(false)
  const lastCacheTime = ref<number | null>(null)
  const queueItems = ref<SyncQueueItem[]>([])
  const logEntries = ref<SyncLogEntry[]>([])
  const offlineSession = ref<OfflineSession | null>(getOfflineSession())
  let initialized = false

  const hasPendingItems = computed(() => pendingCount.value > 0)
  const isOfflineAuth = computed(() => offlineSession.value !== null && !isOnline.value)

  const lastCacheDate = computed(() => {
    if (!lastCacheTime.value) return null
    return new Date(lastCacheTime.value)
  })

  async function refreshCounts() {
    pendingCount.value = await getQueueCount()
    hasCache.value = await isCacheAvailable()
    cacheIsFresh.value = await isCacheFresh()
    lastCacheTime.value = await getLastCacheTimestamp()
  }

  async function refreshQueue() {
    queueItems.value = await getPendingItems()
    await refreshCounts()
  }

  async function refreshLog() {
    logEntries.value = await getSyncLog()
  }

  async function downloadData(): Promise<{ success: boolean; error?: string }> {
    isDownloading.value = true
    try {
      const result = await downloadMasterData()
      await refreshCounts()
      return result
    } finally {
      isDownloading.value = false
    }
  }

  async function cacheAllOnLogin(): Promise<void> {
    if (!navigator.onLine) return
    await Promise.all([
      downloadMasterData(),
      downloadOfflineToken(),
    ])
    await refreshCounts()
  }

  // Renueva el token offline y los datos maestros cada vez que vuelve la
  // conexion (no solo al momento del login), para que un fallo puntual no
  // deje el dispositivo sin poder loguearse offline por dias.
  function refreshOfflineCache() {
    Promise.all([downloadMasterData(), downloadOfflineToken()])
      .then(() => refreshCounts())
      .catch(() => {})
  }

  async function syncNow(): Promise<SyncResult> {
    if (isSyncing.value || !navigator.onLine) {
      return { processed: 0, succeeded: 0, failed: 0, errors: [] }
    }

    isSyncing.value = true
    try {
      const result = await processQueue()
      lastSyncResult.value = result
      await refreshCounts()
      await refreshQueue()
      return result
    } finally {
      isSyncing.value = false
    }
  }

  async function removeItem(id: number) {
    await removeFromQueue(id)
    await refreshQueue()
  }

  async function retryFailedItem(id: number) {
    await retryItem(id)
    await refreshQueue()
  }

  async function cleanOldLogs() {
    await clearSyncLog()
    await refreshLog()
  }

  async function clearEverything() {
    await db.clearAll()
    clearOfflineSession()
    offlineSession.value = null
    await refreshCounts()
    queueItems.value = []
    logEntries.value = []
  }

  function setOfflineSession(session: OfflineSession) {
    offlineSession.value = session
  }

  function endOfflineSession() {
    clearOfflineSession()
    offlineSession.value = null
  }

  function handleOnline() {
    isOnline.value = true
    if (offlineSession.value) {
      endOfflineSession()
    }
    refreshOfflineCache()
    if (pendingCount.value > 0) {
      syncNow().catch(() => {})
    }
  }

  function handleOffline() {
    isOnline.value = false
  }

  function init() {
    if (initialized) return
    initialized = true
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
    isOnline.value = navigator.onLine
    refreshCounts()
  }

  function destroy() {
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)
    // Sin esto, si destroy() llega a ejecutarse una vez (SyncStatusBar
    // deberia permanecer montado toda la sesion, pero por las dudas), init()
    // nunca vuelve a registrar los listeners (el guard "if (initialized)
    // return" lo bloquearia para siempre) y el sidebar/logout quedan
    // congelados con el ultimo estado online/offline conocido.
    initialized = false
  }

  return {
    isOnline,
    pendingCount,
    isSyncing,
    isDownloading,
    lastSyncResult,
    hasCache,
    cacheIsFresh,
    lastCacheTime,
    lastCacheDate,
    hasPendingItems,
    queueItems,
    logEntries,
    offlineSession,
    isOfflineAuth,
    refreshCounts,
    refreshQueue,
    refreshLog,
    downloadData,
    cacheAllOnLogin,
    syncNow,
    removeItem,
    retryFailedItem,
    cleanOldLogs,
    clearEverything,
    setOfflineSession,
    endOfflineSession,
    init,
    destroy,
  }
})
