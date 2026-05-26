export interface CachedCliente {
  id: string
  nombre: string
  documento: string
  telefono: string
  direccion: string
  email: string
  ranking: string
  frecuencia: string
  cached_at: number
}

export interface CachedProducto {
  id: string
  codigo: string
  nombre: string
  precio: number
  existencia: number
  linea: string
  lote: string
  categoria: string // 'PROD' | 'MUES'
  cached_at: number
}

export interface CachedMayorista {
  id: string
  nombre: string
  cached_at: number
}

export interface CachedTipoActividad {
  id: string
  descripcion: string
  cached_at: number
}

export interface CachedTipoIncidente {
  id: number
  descripcion: string
  cached_at: number
}

export type SyncOperationType = 'order' | 'report'
export type SyncItemStatus = 'pending' | 'sending' | 'failed'

export interface SyncQueueItem {
  id?: number
  type: SyncOperationType
  payload: Record<string, any>
  status: SyncItemStatus
  created_at: number
  attempts: number
  last_attempt_at: number | null
  error_message: string | null
  local_ref: string
}

export interface SyncLogEntry {
  id?: number
  type: SyncOperationType
  local_ref: string
  server_id: string | number | null
  synced_at: number
  payload_summary: string
}

export interface MasterDataResponse {
  clientes: any[]
  productos: any[]
  mayoristas: any[]
  actividades: any[]
  incidentes: any[]
  muestras: any[]
  timestamp: number
}
