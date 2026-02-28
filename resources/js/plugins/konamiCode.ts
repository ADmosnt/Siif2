// resources/js/plugins/konamiCode.ts
// Konami Code: ↑ ↑ ↓ ↓ ← → ← → B A
import { useEasterEggStore } from '@/stores/easterEggStore'

const KONAMI_SEQUENCE = [
  'ArrowUp', 'ArrowUp',
  'ArrowDown', 'ArrowDown',
  'ArrowLeft', 'ArrowRight',
  'ArrowLeft', 'ArrowRight',
  'b', 'a',
]

const TIMEOUT_MS = 3000 // Reiniciar si pasan 3s sin tecla

export function setupKonamiCode() {
  const enabled = import.meta.env.VITE_CUBEGG_ENABLED === 'true'

  if (!enabled) {
    console.log('[CubEgg] ❌ Desactivado (VITE_CUBEGG_ENABLED no es "true")')
    return
  }

  console.log('[CubEgg] ✅ Konami Code activado. Secuencia: ↑↑↓↓←→←→BA')

  let position = 0
  let lastKeyTime = 0

  document.addEventListener('keydown', (event: KeyboardEvent) => {
    // Ignorar si el usuario está escribiendo en un input/textarea
    const tag = (event.target as HTMLElement)?.tagName
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return

    const now = Date.now()

    // Reiniciar si pasó mucho tiempo desde la última tecla
    if (now - lastKeyTime > TIMEOUT_MS) {
      position = 0
    }

    lastKeyTime = now

    const expected = KONAMI_SEQUENCE[position]
    const pressed = event.key.length === 1 ? event.key.toLowerCase() : event.key

    if (pressed === expected) {
      position++

      if (position === KONAMI_SEQUENCE.length) {
        console.log('[CubEgg] 🎉 ¡Konami Code completado!')
        const store = useEasterEggStore()
        store.toggle()
        position = 0
      }
    } else {
      // Si falló, verificar si la tecla es el inicio de la secuencia
      position = pressed === KONAMI_SEQUENCE[0] ? 1 : 0
    }
  })
}
