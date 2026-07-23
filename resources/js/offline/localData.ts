// Adapta lo guardado en IndexedDB (cacheService.ts) a la forma exacta que
// esperan los componentes de Toma de Pedidos / Nuevo Reporte cuando no hay
// internet, para que esas pantallas funcionen igual que online.
import * as cacheService from './cacheService'
import { paginateLocal, matchesSearch } from './localPagination'
import type { PaginatedData } from '@/types/pagination'
import type { Producto, Mayoristas, Representante, Actividad, Evento } from '@/types/interfaces'
import type { CachedProducto } from './types'

function toProducto(p: CachedProducto): Producto {
  return {
    id: Number(p.id) || 0,
    codigo: p.codigo,
    producto: p.nombre,
    precio: p.precio,
    lote: p.lote,
    unidades: 0,
  }
}

export async function getOfflineProductosPaginated(page: number, pageSize: number, search = ''): Promise<PaginatedData<Producto>> {
  const cached = await cacheService.getProductos()
  const filtered = cached.filter(p => matchesSearch(search, p.nombre, p.codigo))
  return paginateLocal(filtered.map(toProducto), page, pageSize)
}

export async function getOfflineMuestrasPaginated(page: number, pageSize: number, search = ''): Promise<PaginatedData<Producto>> {
  const cached = await cacheService.getMuestras()
  const filtered = cached.filter(p => matchesSearch(search, p.nombre, p.codigo))
  return paginateLocal(filtered.map(toProducto), page, pageSize)
}

export async function getOfflineMayoristasPaginated(page: number, pageSize: number, search = ''): Promise<PaginatedData<Mayoristas>> {
  const cached = await cacheService.getMayoristas()
  const filtered = cached.filter(m => matchesSearch(search, m.nombre, m.id))
  const mapped: Mayoristas[] = filtered.map(m => ({ codigo: m.id, mayorista: m.nombre }))
  return paginateLocal(mapped, page, pageSize)
}

export async function getOfflineRepresentantes(): Promise<Representante[]> {
  const cached = await cacheService.getRepresentantes()
  return cached.map(r => ({ id: r.id, nombre: r.nombre }))
}

export async function getOfflineActividades(): Promise<Actividad[]> {
  const cached = await cacheService.getTiposActividad()
  return cached.map(a => ({ idtipo_actividad: a.id, descripcionActividad: a.descripcion }))
}

export async function getOfflineIncidentes(): Promise<Evento[]> {
  const cached = await cacheService.getTiposIncidente()
  return cached.map(i => ({ idtipo_incidentes: i.id, descripcionIncidente: i.descripcion }))
}
