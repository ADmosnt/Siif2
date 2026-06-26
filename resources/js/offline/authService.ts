import bcrypt from 'bcryptjs'
import { getCachedAuth, clearAuthCache } from './cacheService'
import type { CachedAuth } from './types'

export interface OfflineSession {
  idPersona: string
  name: string
  nombre_completo: string
  idFabricante: string
  idgrupo_persona: string
  email: string
  authenticated_at: number
}

const OFFLINE_SESSION_KEY = 'siif2_offline_session'

export async function verifyOfflineCredentials(
  name: string,
  password: string,
): Promise<{ success: boolean; user?: CachedAuth; error?: string }> {
  const cached = await getCachedAuth()

  if (!cached) {
    return { success: false, error: 'No hay datos de sesion guardados. Necesita iniciar sesion con internet al menos una vez.' }
  }

  if (cached.name !== name) {
    return { success: false, error: 'Usuario no encontrado en cache local.' }
  }

  // PHP bcrypt uses $2y$ prefix, bcryptjs supports it
  const passwordMatch = await bcrypt.compare(password, cached.password_hash)

  if (!passwordMatch) {
    return { success: false, error: 'Contraseña incorrecta.' }
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
  }

  localStorage.setItem(OFFLINE_SESSION_KEY, JSON.stringify(session))
  return session
}

export function getOfflineSession(): OfflineSession | null {
  const data = localStorage.getItem(OFFLINE_SESSION_KEY)
  if (!data) return null

  try {
    return JSON.parse(data) as OfflineSession
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
