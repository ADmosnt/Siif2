export interface CachedCliente {
  id: string
  nombre: string
  cached_at: number
}

export interface CachedProducto {
  id: string
  codigo: string
  nombre: string
  precio: number
  lote: string
  categoria: string // 'PROD' | 'MUES'
  cached_at: number
}

export interface CachedPersona {
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

export interface CachedAuth {
  id: string
  name: string
  nombre_completo: string
  idFabricante: string
  idgrupo_persona: string
  email: string
  token: string
  expires_at: number
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
  representantes: any[]
  actividades: any[]
  incidentes: any[]
  muestras: any[]
  timestamp: number
}

export interface OfflineTokenResponse {
  token: string
  expires_at: number
  idPersona: string
  name: string
  nombre_completo: string
  idFabricante: string
  idgrupo_persona: string
  email: string
}
