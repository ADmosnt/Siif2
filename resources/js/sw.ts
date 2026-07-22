// Service Worker de SIIF2.
//
// Se escribe a mano (en vez de generateSW) para poder usar setCatchHandler:
// es la unica forma de que la navegacion offline caiga a un "shell" de
// respaldo SOLO cuando la red realmente falla, sin tocar el comportamiento
// normal (online) de ninguna otra pagina. navigateFallback (modo generateSW)
// no sirve para esto: siempre sirve el precache para las URLs que abarca,
// tambien estando online.
import { cleanupOutdatedCaches, precacheAndRoute, matchPrecache } from 'workbox-precaching'
import { registerRoute, setCatchHandler } from 'workbox-routing'
import { NetworkFirst, CacheFirst } from 'workbox-strategies'
import { ExpirationPlugin } from 'workbox-expiration'
import { CacheableResponsePlugin } from 'workbox-cacheable-response'

declare let self: ServiceWorkerGlobalScope

// La URL que se sirve cuando una navegacion falla por completo (sin red y
// sin cache propia para esa ruta). Es la pantalla de login real, precacheada
// mas abajo, para que el RFV siempre pueda intentar el login offline.
const OFFLINE_FALLBACK_URL = '/login'

importScripts('/push-handlers.js')

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting()
  }
})

precacheAndRoute(self.__WB_MANIFEST)
cleanupOutdatedCaches()

registerRoute(
  /^https:\/\/fonts\.bunny\.net\/.*/i,
  new CacheFirst({
    cacheName: 'bunny-fonts-cache',
    plugins: [
      new ExpirationPlugin({ maxEntries: 10, maxAgeSeconds: 60 * 60 * 24 * 365 }),
      new CacheableResponsePlugin({ statuses: [0, 200] }),
    ],
  }),
  'GET',
)

registerRoute(
  ({ request }) => request.mode === 'navigate',
  new NetworkFirst({
    cacheName: 'pages-cache',
    networkTimeoutSeconds: 5,
    plugins: [
      new ExpirationPlugin({ maxEntries: 30, maxAgeSeconds: 60 * 60 * 24 * 7 }),
      new CacheableResponsePlugin({ statuses: [0, 200] }),
    ],
  }),
  'GET',
)

registerRoute(
  /\/offline\/master-data/,
  new NetworkFirst({
    cacheName: 'master-data-cache',
    plugins: [
      new ExpirationPlugin({ maxEntries: 1, maxAgeSeconds: 60 * 60 * 24 }),
      new CacheableResponsePlugin({ statuses: [0, 200] }),
    ],
  }),
  'GET',
)

// Solo se activa cuando NetworkFirst de arriba ya fallo (sin red) y la URL
// pedida tampoco estaba en el cache de paginas. En ese caso, en vez de un
// error de navegador, se muestra el login real (ya precacheado) para que
// el RFV pueda entrar con el token offline guardado en IndexedDB.
setCatchHandler(async ({ event }) => {
  if (event.request.mode === 'navigate') {
    const fallback = await matchPrecache(OFFLINE_FALLBACK_URL)
    if (fallback) return fallback
  }
  return Response.error()
})
