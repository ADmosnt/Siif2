// Service Worker de SIIF2.
//
// Se escribe a mano (en vez de generateSW) para poder usar setCatchHandler:
// es la unica forma de que la navegacion offline caiga a un "shell" de
// respaldo SOLO cuando la red realmente falla, sin tocar el comportamiento
// normal (online) de ninguna otra pagina. navigateFallback (modo generateSW)
// no sirve para esto: siempre sirve el precache para las URLs que abarca,
// tambien estando online.
import { cleanupOutdatedCaches, precache, precacheAndRoute, matchPrecache } from 'workbox-precaching'
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

// registerType: 'autoUpdate' (vite.config.ts) asume que un service worker
// nuevo toma control apenas se instala. Pero eso solo pasa si el SW lo pide
// explicitamente: nada en la app llamaba a skipWaiting() (el mensaje de
// arriba nunca lo enviaba nadie) ni a clients.claim(), asi que un SW nuevo
// se quedaba en estado "waiting" indefinidamente y el viejo seguia
// controlando todas las pestañas abiertas. Con cada build posterior el
// desfasaje entre el SW activo y los assets/precache reales crecia (HTML
// desactualizado con token CSRF viejo -> 419 en el primer intento, PNGs u
// otros assets nuevos que el precache viejo no conocia -> "unexpected
// error" del ServiceWorker), y solo se corregia a mano cerrando pestañas o
// limpiando el Service Worker. Con skipWaiting()+clients.claim(), el SW
// nuevo reemplaza al viejo apenas termina de instalarse.
self.skipWaiting()

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim())
})

// precacheAndRoute() no solo precachea: tambien registra una ruta que
// sirve esas URLs directo desde cache (cache-first) para cualquier
// request que las pida, SIN pasar por el NetworkFirst de mas abajo. Eso
// pisaba /login, /tdp y /nuevo-reporte: quedaban serviditas siempre
// desde el precache (incluso online, ya logueado), y si ese precache se
// habia poblado antes del login (ej. la primera visita, sin sesion,
// donde /tdp y /nuevo-reporte redirigen a /login) el usuario terminaba
// viendo el login en vez del modulo real. Estas 3 URLs se precachean
// (quedan disponibles via matchPrecache en el catch handler) pero SIN
// auto-ruta, para que la navegacion normal siempre intente la red primero.
const SHELL_URLS = ['/login', '/tdp', '/nuevo-reporte']
const manifestEntries = self.__WB_MANIFEST
const shellEntries = manifestEntries.filter((entry) =>
  SHELL_URLS.includes(typeof entry === 'string' ? entry : entry.url),
)
const assetEntries = manifestEntries.filter((entry) =>
  !SHELL_URLS.includes(typeof entry === 'string' ? entry : entry.url),
)

precacheAndRoute(assetEntries)
precache(shellEntries)
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
    // Sin networkTimeoutSeconds: con un timeout, Workbox trata una
    // respuesta simplemente LENTA (ej. /nuevo-reporte y /tdp, que en la
    // misma carga resuelven varias consultas: representantes, productos,
    // mayoristas/actividades/eventos) igual que una red caida, y cae al
    // shell offline (login) aunque el servidor si iba a responder. Sin
    // limite, solo se usa el fallback cuando el fetch realmente falla.
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
