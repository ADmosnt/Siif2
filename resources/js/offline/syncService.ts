import axios, { AxiosError } from 'axios'
import { db } from './db'
import { storeMasterData, storeOfflineToken } from './cacheService'
import type { SyncQueueItem, SyncOperationType, MasterDataResponse, OfflineTokenResponse } from './types'

function generateLocalRef(type: SyncOperationType): string {
  const prefix = type === 'order' ? 'PED' : 'RPT'
  const date = new Date()
  const dateStr = date.toISOString().slice(0, 10).replace(/-/g, '')
  const rand = Math.random().toString(36).substring(2, 6).toUpperCase()
  return `${prefix}-${dateStr}-${rand}`
}

function isNetworkError(error: unknown): boolean {
  if (error instanceof AxiosError) {
    return !error.response || error.code === 'ERR_NETWORK' || error.code === 'ECONNABORTED'
  }
  return false
}

function buildSummary(type: SyncOperationType, payload: Record<string, any>): string {
  if (type === 'order') {
    const prodCount = payload.productos?.length ?? 0
    return `Pedido con ${prodCount} producto(s)`
  }
  return 'Reporte de actividad'
}

const ENDPOINT_MAP: Record<SyncOperationType, string> = {
  order: '/toma-de-pedidos',
  report: '/reportes/nuevo',
}

// --- DESCARGAR DATOS MAESTROS ---

export async function downloadMasterData(): Promise<{ success: boolean; error?: string }> {
  try {
    const response = await axios.get<MasterDataResponse>('/offline/master-data')
    await storeMasterData(response.data)
    return { success: true }
  } catch (error: any) {
    const message = error.response?.data?.message || error.message || 'Error descargando datos'
    return { success: false, error: message }
  }
}

// --- DESCARGAR Y CACHEAR TOKEN DE LOGIN OFFLINE ---
// Reemplaza el antiguo cacheo de usuario/contraseña. Se llama al login y,
// ademas, cada vez que el dispositivo recupera conexion (ver offlineStore.ts)
// para que un fallo puntual no deje el token vencido o ausente por dias.

export async function downloadOfflineToken(): Promise<{ success: boolean; error?: string }> {
  try {
    const response = await axios.get<OfflineTokenResponse>('/offline/token', {
      _suppressAlert: true,
    } as any)
    await storeOfflineToken(response.data)
    return { success: true }
  } catch (error: any) {
    const message = error.response?.data?.message || error.message || 'Error renovando el token offline'
    return { success: false, error: message }
  }
}

// --- COLA DE SINCRONIZACION ---

export async function addToQueue(
  type: SyncOperationType,
  payload: Record<string, any>,
): Promise<{ queued: true; localRef: string }> {
  const localRef = generateLocalRef(type)

  await db.sync_queue.add({
    type,
    payload: { ...payload, _local_ref: localRef },
    status: 'pending',
    created_at: Date.now(),
    attempts: 0,
    last_attempt_at: null,
    error_message: null,
    local_ref: localRef,
  })

  return { queued: true, localRef }
}

export async function getQueueCount(): Promise<number> {
  return db.sync_queue.where('status').anyOf('pending', 'failed').count()
}

export async function getPendingItems(): Promise<SyncQueueItem[]> {
  return db.sync_queue.orderBy('created_at').toArray()
}

export async function removeFromQueue(id: number): Promise<void> {
  await db.sync_queue.delete(id)
}

export async function retryItem(id: number): Promise<void> {
  await db.sync_queue.update(id, { status: 'pending', error_message: null })
}

// --- ENVIAR O ENCOLAR ---

export async function submitOrQueue(
  type: SyncOperationType,
  payload: Record<string, any>,
): Promise<{ queued: boolean; localRef?: string; serverId?: any }> {
  if (navigator.onLine) {
    try {
      const response = await axios.post(ENDPOINT_MAP[type], payload, {
        _suppressAlert: true,
      } as any)
      return { queued: false, serverId: response.data?.id }
    } catch (error) {
      if (isNetworkError(error)) {
        return await addToQueue(type, payload)
      }
      throw error
    }
  }

  return await addToQueue(type, payload)
}

// --- PROCESAR COLA ---

export interface SyncResult {
  processed: number
  succeeded: number
  failed: number
  errors: Array<{ localRef: string; message: string }>
}

export async function processQueue(): Promise<SyncResult> {
  const result: SyncResult = { processed: 0, succeeded: 0, failed: 0, errors: [] }

  const items = await db.sync_queue
    .where('status')
    .anyOf('pending', 'failed')
    .sortBy('created_at')

  for (const item of items) {
    if (!navigator.onLine) break

    result.processed++

    try {
      await db.sync_queue.update(item.id!, { status: 'sending' })

      const response = await axios.post(ENDPOINT_MAP[item.type], item.payload, {
        _suppressAlert: true,
      } as any)

      await db.sync_log.add({
        type: item.type,
        local_ref: item.local_ref,
        server_id: response.data?.id ?? null,
        synced_at: Date.now(),
        payload_summary: buildSummary(item.type, item.payload),
      })

      await db.sync_queue.delete(item.id!)
      result.succeeded++
    } catch (error: any) {
      if (isNetworkError(error)) {
        await db.sync_queue.update(item.id!, { status: 'pending' })
        break
      }

      const message = error.response?.data?.message
        || (typeof error.response?.data?.errors === 'object'
          ? Object.values(error.response.data.errors).flat().join(', ')
          : null)
        || error.message
        || 'Error desconocido'

      await db.sync_queue.update(item.id!, {
        status: 'failed',
        attempts: (item.attempts || 0) + 1,
        last_attempt_at: Date.now(),
        error_message: message,
      })

      result.failed++
      result.errors.push({ localRef: item.local_ref, message })
    }
  }

  return result
}

// --- HISTORIAL ---

export async function getSyncLog() {
  return db.sync_log.orderBy('synced_at').reverse().limit(50).toArray()
}

export async function clearSyncLog() {
  const thirtyDaysAgo = Date.now() - 30 * 24 * 60 * 60 * 1000
  await db.sync_log.where('synced_at').below(thirtyDaysAgo).delete()
}
