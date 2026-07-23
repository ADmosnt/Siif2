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

// Rutas que de verdad funcionan sin conexion (cargan sus catalogos desde
// IndexedDB, ver resources/js/offline/localData.ts). Si la navegacion falla
// hacia una de estas, se sirve su propio shell precacheado; para cualquier
// otra ruta no soportada offline, se cae al login.
const OFFLINE_CAPABLE_ROUTES = ['/tdp', '/nuevo-reporte']
const LOGIN_FALLBACK_URL = '/login'

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
// pedida tampoco estaba en el cache de paginas. Si la ruta es una de las
// offline-capable, se sirve su propio shell (ya precacheado) para que la
// pagina cargue y tome sus catalogos de IndexedDB. Para cualquier otra ruta,
// se cae al login para que el RFV pueda entrar con el token offline.
setCatchHandler(async ({ event }) => {
  if (event.request.mode === 'navigate') {
    const url = new URL(event.request.url)
    const matchedRoute = OFFLINE_CAPABLE_ROUTES.find((route) => url.pathname === route)
    const fallback = await matchPrecache(matchedRoute ?? LOGIN_FALLBACK_URL)
    if (fallback) return fallback
  }
  return Response.error()
})
