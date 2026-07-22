// Copia el service worker construido por vite-plugin-pwa (injectManifest,
// que para un source .ts siempre escribe dentro de outDir) al lugar donde
// realmente se sirve: la raiz de public/, con scope "/" (ver
// resources/js/app.ts -> navigator.serviceWorker.register('/sw.js', {scope: '/'})).
import { copyFile } from 'node:fs/promises'
import { existsSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, resolve } from 'node:path'

const rootDir = dirname(dirname(fileURLToPath(import.meta.url)))
const src = resolve(rootDir, 'public/build/sw.js')
const dest = resolve(rootDir, 'public/sw.js')

if (!existsSync(src)) {
  console.error(`[copy-sw] No se encontro ${src}. ¿Fallo el build del service worker?`)
  process.exit(1)
}

await copyFile(src, dest)
console.log(`[copy-sw] ${src} -> ${dest}`)
