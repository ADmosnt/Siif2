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

  if (Date.now() > cached.expires_at) {
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

  localStorage.setItem(OFFLINE_SESSION_KEY, JSON.stringify(session))
  return session
}

export function getOfflineSession(): OfflineSession | null {
  const data = localStorage.getItem(OFFLINE_SESSION_KEY)
  if (!data) return null

  try {
    const session = JSON.parse(data) as OfflineSession
    if (Date.now() > session.expires_at) {
      clearOfflineSession()
      return null
    }
    return session
  } catch {
    return null
  }
}

export function clearOfflineSession(): void {
  localStorage.removeItem(OFFLINE_SESSION_KEY)
}

export function isOfflineAuthenticated(): boolean {
  return getOfflineSession() !== null
}
