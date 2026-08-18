// Service Worker de SIIF2.
//
// Se escribe a mano (en vez de generateSW) para poder usar setCatchHandler:
// es la unica forma de que la navegacion offline caiga a un "shell" de
// respaldo SOLO cuando la red realmente falla, sin tocar el comportamiento
// normal (online) de ninguna otra pagina. navigateFallback (modo generateSW)
// no sirve para esto: siempre sirve el precache para las URLs que abarca,
// tambien estando online.
import { cleanupOutdatedCaches, precacheAndRoute } from 'workbox-precaching'
import { registerRoute, setCatchHandler } from 'workbox-routing'
import { NetworkFirst, CacheFirst } from 'workbox-strategies'
import { ExpirationPlugin } from 'workbox-expiration'
import { CacheableResponsePlugin } from 'workbox-cacheable-response'

declare let self: ServiceWorkerGlobalScope

// Rutas que de verdad funcionan sin conexion (cargan sus catalogos desde
// IndexedDB, ver resources/js/offline/localData.ts). Si la navegacion falla
// hacia una de estas, se sirve su propio shell cacheado; para cualquier
// otra ruta no soportada offline, se cae al login.
// /sync-queue entra aqui porque su contenido sale enteramente de IndexedDB
// (ver pages/SyncQueue.vue) y el sidebar la ofrece habilitada offline
// (OFFLINE_URLS en AppSidebar.vue); antes no estaba y navegar ahi sin
// conexion terminaba en la pagina de error del navegador.
const OFFLINE_CAPABLE_ROUTES = ['/tdp', '/nuevo-reporte', '/sync-queue']
const LOGIN_FALLBACK_URL = '/login'
const SHELL_URLS = [LOGIN_FALLBACK_URL, ...OFFLINE_CAPABLE_ROUTES]

// Los shells se guardan en su PROPIO cache, fuera del precache de Workbox, a
// proposito. El precache es todo-o-nada: si una sola de sus ~75 entradas falla
// (un asset con hash viejo que ya no existe, o un shell que responde 302
// porque todavia no hay sesion), Workbox rechaza el evento install y el
// service worker no se activa nunca, con lo cual se pierde TODO el modo
// offline. Cachearlos a mano permite tolerar fallos individuales.
const SHELL_CACHE = 'offline-shells'

importScripts('/push-handlers.js')

// Guarda los shells de navegacion offline. Tolerante a fallos: si alguno no se
// puede traer, se siguen guardando los demas.
async function cacheOfflineShells(): Promise<void> {
  const cache = await caches.open(SHELL_CACHE)

  await Promise.allSettled(
    SHELL_URLS.map(async (url) => {
      try {
        // cache: 'reload' evita que el navegador devuelva su propia copia
        // vieja de HTTP; credentials para que viaje la cookie de sesion y el
        // servidor conteste el modulo real y no un redirect al login.
        const response = await fetch(url, { credentials: 'same-origin', cache: 'reload' })

        // Solo se guarda una respuesta REAL. Si el servidor redirigio (caso
        // tipico: el SW se instala en la pantalla de login, sin sesion, y
        // /tdp responde 302 hacia /login), guardar eso bajo la clave /tdp
        // haria que offline se viera el login en vez del modulo. Se deja sin
        // cachear y se reintenta despues del login (mensaje de abajo).
        if (response.ok && !response.redirected) {
          await cache.put(url, response)
        }
      } catch {
        // Sin conexion al instalar: no es fatal, se reintenta en el proximo
        // arranque o cuando la app avise que ya hay sesion.
      }
    }),
  )
}

self.addEventListener('install', (event) => {
  event.waitUntil(cacheOfflineShells())
})

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting()
  }

  // La app avisa cuando acaba de iniciar sesion (ver pages/auth/Login.vue).
  // Recien en ese momento existe la cookie de sesion, asi que es el momento
  // en que /tdp, /nuevo-reporte y /sync-queue devuelven su HTML real en vez
  // de redirigir al login.
  if (event.data && event.data.type === 'CACHE_OFFLINE_SHELLS') {
    event.waitUntil(cacheOfflineShells())
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

// Solo assets con hash en el nombre (JS/CSS/imagenes de public/build). Los
// shells de navegacion NO van aca: se manejan arriba, en SHELL_CACHE, porque
// el precache es todo-o-nada y una URL que redirige al login tumbaba la
// instalacion completa del service worker.
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
// offline-capable, se sirve su propio shell (de SHELL_CACHE) para que la
// pagina cargue y tome sus catalogos de IndexedDB. Para cualquier otra ruta,
// se cae al login para que el RFV pueda entrar con el token offline.
setCatchHandler(async ({ event }) => {
  if (event.request.mode === 'navigate') {
    const url = new URL(event.request.url)
    const matchedRoute = OFFLINE_CAPABLE_ROUTES.find((route) => url.pathname === route)
    const cache = await caches.open(SHELL_CACHE)

    // Si la ruta es offline-capable se sirve su propio shell; si ese no esta
    // (por ejemplo nunca se pudo cachear con sesion), se cae al login, que
    // siempre se puede cachear porque no requiere autenticacion.
    const fallback =
      (matchedRoute ? await cache.match(matchedRoute) : undefined) ??
      (await cache.match(LOGIN_FALLBACK_URL))

    if (fallback) return fallback
  }
  return Response.error()
})
