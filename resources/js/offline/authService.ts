import { getCachedAuth } from './cacheService'
import type { CachedAuth } from './types'

export interface OfflineSession {
  idPersona: string
  name: string
  nombre_completo: string
  idFabricante: string
  idgrupo_persona: string
  email: string
  authenticated_at: number
  expires_at: number
}

const OFFLINE_SESSION_KEY = 'siif2_offline_session'

// expires_at viaja en SEGUNDOS (timestamp de Carbon/Laravel). Date.now() es
// en MILISEGUNDOS: hay que convertir antes de comparar, si no el token
// siempre parece vencido.
function isExpired(expiresAtSeconds: number): boolean {
  return Date.now() > expiresAtSeconds * 1000
}

/**
 * Valida el login offline contra el token firmado emitido por el servidor
 * (ver OfflineController::issueOfflineToken), no contra una contraseña.
 * El navegador nunca guarda la clave del usuario ni su hash.
 */
export async function verifyOfflineToken(
  name: string,
): Promise<{ success: boolean; user?: CachedAuth; error?: string }> {
  const cached = await getCachedAuth()

  if (!cached) {
    return { success: false, error: 'No hay una sesion offline guardada en este dispositivo. Debes iniciar sesion con internet al menos una vez.' }
  }

  if (cached.name !== name) {
    return { success: false, error: 'Ese usuario no tiene una sesion offline guardada en este dispositivo.' }
  }

  if (isExpired(cached.expires_at)) {
    return { success: false, error: 'Tu acceso offline vencio. Conectate a internet para renovarlo.' }
  }

  return { success: true, user: cached }
}

export function createOfflineSession(user: CachedAuth): OfflineSession {
  const session: OfflineSession = {
    idPersona: user.id,
    name: user.name,
    nombre_completo: user.nombre_completo,
    idFabricante: user.idFabricante,
    idgrupo_persona: user.idgrupo_persona,
    email: user.email,
    authenticated_at: Date.now(),
    expires_at: user.expires_at,
  }

  // localStorage puede lanzar SecurityError en Safari/iOS cuando el
  // usuario tiene bloqueadas las cookies/datos de sitio a nivel de
  // sistema: se degrada a "sin sesion offline guardada" en vez de
  // propagar el error (esto se lee de forma sincrona durante el setup()
  // de offlineStore, que corre para TODA pagina — un throw aca tumba el
  // montaje completo de la app, ver PushNotificationPrompt.vue).
  try {
    localStorage.setItem(OFFLINE_SESSION_KEY, JSON.stringify(session))
  } catch (error) {
    console.warn('No se pudo guardar la sesion offline (localStorage no disponible):', error)
  }
  return session
}

export function getOfflineSession(): OfflineSession | null {
  let data: string | null
  try {
    data = localStorage.getItem(OFFLINE_SESSION_KEY)
  } catch {
    return null
  }
  if (!data) return null

  try {
    const session = JSON.parse(data) as OfflineSession
    if (isExpired(session.expires_at)) {
      clearOfflineSession()
      return null
    }
    return session
  } catch {
    return null
  }
}

export function clearOfflineSession(): void {
  try {
    localStorage.removeItem(OFFLINE_SESSION_KEY)
  } catch {
    // Nada que limpiar si localStorage no esta disponible
  }
}

export function isOfflineAuthenticated(): boolean {
  return getOfflineSession() !== null
}
