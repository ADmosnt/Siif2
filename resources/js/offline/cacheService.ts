import { db } from './db'
import type {
  CachedCliente,
  CachedProducto,
  CachedPersona,
  CachedTipoActividad,
  CachedTipoIncidente,
  CachedAuth,
  MasterDataResponse,
  OfflineTokenResponse,
} from './types'

const CACHE_MAX_AGE_MS = 24 * 60 * 60 * 1000 // 24 horas

function isCacheStale(cached_at: number, maxAge = CACHE_MAX_AGE_MS): boolean {
  return Date.now() - cached_at > maxAge
}

export async function getLastCacheTimestamp(): Promise<number | null> {
  const first = await db.cached_clientes.orderBy('cached_at').reverse().first()
  return first?.cached_at ?? null
}

export async function isCacheAvailable(): Promise<boolean> {
  const count = await db.cached_clientes.count()
  return count > 0
}

export async function isCacheFresh(): Promise<boolean> {
  const ts = await getLastCacheTimestamp()
  if (!ts) return false
  return !isCacheStale(ts)
}

// --- GUARDAR datos maestros descargados del servidor ---
// Solo se guarda lo estrictamente necesario para Toma de Pedidos y Nuevo
// Reporte (ver ProcesarOrdenController::store y ProcesarReporteController::new
// en el backend): id + nombre para selects, precio/lote/categoria para el
// carrito de productos. Nada de documento, telefono, direccion, email,
// existencia ni supervisores/gerentes: no los usa ninguno de los dos flujos.

export async function storeMasterData(data: MasterDataResponse): Promise<void> {
  const now = Date.now()

  await db.transaction('rw', [
    db.cached_clientes,
    db.cached_productos,
    db.cached_mayoristas,
    db.cached_representantes,
    db.cached_actividades_tipos,
    db.cached_incidentes_tipos,
  ], async () => {
    await db.cached_clientes.clear()
    await db.cached_productos.clear()
    await db.cached_mayoristas.clear()
    await db.cached_representantes.clear()
    await db.cached_actividades_tipos.clear()
    await db.cached_incidentes_tipos.clear()

    if (data.clientes?.length) {
      await db.cached_clientes.bulkPut(
        data.clientes.map((c: any) => ({
          id: c.id ?? c.idPersona,
          nombre: c.nombre ?? c.nombre_completo_razon_social ?? '',
          cached_at: now,
        }))
      )
    }

    const allProducts = [...(data.productos || []), ...(data.muestras || [])]
    if (allProducts.length) {
      await db.cached_productos.bulkPut(
        allProducts.map((p: any) => ({
          id: p.id ?? p.idproducto ?? p.codigo,
          codigo: p.codigo ?? p.idproducto ?? '',
          nombre: p.nombre ?? p.nombre_producto ?? p.producto ?? '',
          precio: Number(p.precio ?? p.Precio_producto ?? 0),
          lote: p.lote ?? '',
          categoria: p.categoria ?? p.idcategorias ?? 'PROD',
          cached_at: now,
        }))
      )
    }

    const personaGroups = [
      { data: data.mayoristas, table: db.cached_mayoristas },
      { data: data.representantes, table: db.cached_representantes },
    ]

    for (const group of personaGroups) {
      if (group.data?.length) {
        await group.table.bulkPut(
          group.data.map((m: any) => ({
            id: m.id ?? m.codigo ?? m.idPersona,
            nombre: m.nombre ?? m.mayorista ?? m.nombre_completo_razon_social ?? '',
            cached_at: now,
          }))
        )
      }
    }

    if (data.actividades?.length) {
      await db.cached_actividades_tipos.bulkPut(
        data.actividades.map((a: any) => ({
          id: a.idtipo_actividad ?? a.idtipo_actividades,
          descripcion: a.descripcionActividad ?? a.descripcion_tipo_actividades ?? '',
          cached_at: now,
        }))
      )
    }

    if (data.incidentes?.length) {
      await db.cached_incidentes_tipos.bulkPut(
        data.incidentes.map((i: any) => ({
          id: Number(i.idtipo_incidentes),
          descripcion: i.descripcionIncidente ?? i.descripcion ?? i.descripcion_tipo_incidentes ?? '',
          cached_at: now,
        }))
      )
    }
  })
}

// --- TOKEN DE LOGIN OFFLINE ---
// Reemplaza el guardado del hash de contraseña: el servidor emite un token
// firmado con expiracion propia (ver OfflineController::issueOfflineToken).
// El navegador nunca guarda la clave ni su hash.

export async function storeOfflineToken(data: OfflineTokenResponse): Promise<void> {
  await db.cached_auth.clear()
  await db.cached_auth.put({
    id: data.idPersona,
    name: data.name,
    nombre_completo: data.nombre_completo,
    idFabricante: data.idFabricante,
    idgrupo_persona: data.idgrupo_persona,
    email: data.email ?? '',
    token: data.token,
    expires_at: data.expires_at,
    cached_at: Date.now(),
  })
}

export async function getCachedAuth(): Promise<CachedAuth | undefined> {
  return db.cached_auth.toCollection().first()
}

export async function clearAuthCache(): Promise<void> {
  await db.cached_auth.clear()
}

// --- LEER datos desde cache local ---

export async function getClientes(): Promise<CachedCliente[]> {
  return db.cached_clientes.orderBy('nombre').toArray()
}

export async function getProductos(): Promise<CachedProducto[]> {
  return db.cached_productos.where('categoria').notEqual('MUES').sortBy('nombre')
}

export async function getMuestras(): Promise<CachedProducto[]> {
  return db.cached_productos.where('categoria').equals('MUES').sortBy('nombre')
}

export async function getAllProductos(): Promise<CachedProducto[]> {
  return db.cached_productos.orderBy('nombre').toArray()
}

export async function getMayoristas(): Promise<CachedPersona[]> {
  return db.cached_mayoristas.orderBy('nombre').toArray()
}

export async function getRepresentantes(): Promise<CachedPersona[]> {
  return db.cached_representantes.orderBy('nombre').toArray()
}

export async function getTiposActividad(): Promise<CachedTipoActividad[]> {
  return db.cached_actividades_tipos.toArray()
}

export async function getTiposIncidente(): Promise<CachedTipoIncidente[]> {
  return db.cached_incidentes_tipos.toArray()
}

export async function searchClientes(term: string): Promise<CachedCliente[]> {
  if (!term || term.length < 2) return []
  const lower = term.toLowerCase()
  return db.cached_clientes
    .filter(c => c.nombre.toLowerCase().includes(lower) || c.id.includes(term))
    .limit(20)
    .toArray()
}

export async function searchProductos(term: string, categoria?: string): Promise<CachedProducto[]> {
  if (!term || term.length < 2) return []
  const lower = term.toLowerCase()
  return db.cached_productos
    .filter(p => {
      const matchesSearch = p.nombre.toLowerCase().includes(lower) || p.codigo.includes(term)
      if (categoria) return matchesSearch && p.categoria === categoria
      return matchesSearch
    })
    .limit(20)
    .toArray()
}
