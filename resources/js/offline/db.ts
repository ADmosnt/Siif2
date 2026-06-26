import Dexie, { type Table } from 'dexie'
import type {
  CachedCliente,
  CachedProducto,
  CachedPersona,
  CachedTipoActividad,
  CachedTipoIncidente,
  CachedAuth,
  SyncQueueItem,
  SyncLogEntry,
} from './types'

class SiifOfflineDB extends Dexie {
  cached_clientes!: Table<CachedCliente, string>
  cached_productos!: Table<CachedProducto, string>
  cached_mayoristas!: Table<CachedPersona, string>
  cached_representantes!: Table<CachedPersona, string>
  cached_supervisores!: Table<CachedPersona, string>
  cached_gerentes!: Table<CachedPersona, string>
  cached_actividades_tipos!: Table<CachedTipoActividad, string>
  cached_incidentes_tipos!: Table<CachedTipoIncidente, number>
  cached_auth!: Table<CachedAuth, string>
  sync_queue!: Table<SyncQueueItem, number>
  sync_log!: Table<SyncLogEntry, number>

  constructor() {
    super('siif2-offline')

    this.version(1).stores({
      cached_clientes: 'id, nombre, cached_at',
      cached_productos: 'id, codigo, nombre, categoria, cached_at',
      cached_mayoristas: 'id, nombre, cached_at',
      cached_actividades_tipos: 'id, cached_at',
      cached_incidentes_tipos: 'id, cached_at',
      sync_queue: '++id, type, status, created_at',
      sync_log: '++id, type, local_ref, synced_at',
    })

    this.version(2).stores({
      cached_clientes: 'id, nombre, cached_at',
      cached_productos: 'id, codigo, nombre, categoria, cached_at',
      cached_mayoristas: 'id, nombre, cached_at',
      cached_representantes: 'id, nombre, cached_at',
      cached_supervisores: 'id, nombre, cached_at',
      cached_gerentes: 'id, nombre, cached_at',
      cached_actividades_tipos: 'id, cached_at',
      cached_incidentes_tipos: 'id, cached_at',
      cached_auth: 'id, name, cached_at',
      sync_queue: '++id, type, status, created_at',
      sync_log: '++id, type, local_ref, synced_at',
    })
  }

  async clearAllCaches(): Promise<void> {
    await Promise.all([
      this.cached_clientes.clear(),
      this.cached_productos.clear(),
      this.cached_mayoristas.clear(),
      this.cached_representantes.clear(),
      this.cached_supervisores.clear(),
      this.cached_gerentes.clear(),
      this.cached_actividades_tipos.clear(),
      this.cached_incidentes_tipos.clear(),
    ])
  }

  async clearAll(): Promise<void> {
    await Promise.all([
      this.clearAllCaches(),
      this.cached_auth.clear(),
      this.sync_queue.clear(),
      this.sync_log.clear(),
    ])
  }
}

export const db = new SiifOfflineDB()
