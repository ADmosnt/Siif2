import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  downloadMasterData,
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

  const hasPendingItems = computed(() => pendingCount.value > 0)

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
    await refreshCounts()
    queueItems.value = []
    logEntries.value = []
  }

  function handleOnline() {
    isOnline.value = true
    if (pendingCount.value > 0) {
      syncNow()
    }
  }

  function handleOffline() {
    isOnline.value = false
  }

  function init() {
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
    isOnline.value = navigator.onLine
    refreshCounts()
  }

  function destroy() {
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)
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
    refreshCounts,
    refreshQueue,
    refreshLog,
    downloadData,
    syncNow,
    removeItem,
    retryFailedItem,
    cleanOldLogs,
    clearEverything,
    init,
    destroy,
  }
})
