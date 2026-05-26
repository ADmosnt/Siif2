import { computed, onMounted } from 'vue'
import { useOfflineStore } from '@/stores/offlineStore'
import { submitOrQueue as _submitOrQueue } from '@/offline/syncService'
import * as cacheService from '@/offline/cacheService'
import type { SyncOperationType } from '@/offline/types'

export function useOffline() {
  const store = useOfflineStore()

  const isOnline = computed(() => store.isOnline)
  const pendingCount = computed(() => store.pendingCount)
  const isSyncing = computed(() => store.isSyncing)
  const hasPendingItems = computed(() => store.hasPendingItems)
  const hasCache = computed(() => store.hasCache)

  async function submitOrQueue(type: SyncOperationType, payload: Record<string, any>) {
    const result = await _submitOrQueue(type, payload)
    await store.refreshCounts()
    return result
  }

  async function syncNow() {
    return store.syncNow()
  }

  async function downloadData() {
    return store.downloadData()
  }

  return {
    isOnline,
    pendingCount,
    isSyncing,
    hasPendingItems,
    hasCache,
    submitOrQueue,
    syncNow,
    downloadData,
    cache: cacheService,
  }
}
