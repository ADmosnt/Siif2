# PWA - SIIF2: Implementación de Progressive Web App

## Resumen

Se implementó soporte PWA (Progressive Web App) en SIIF2 utilizando `vite-plugin-pwa` y notificaciones push con `laravel-notification-channels/webpush`. Esto permite que la aplicación web sea **instalable** en dispositivos móviles, muestre alertas de desconexión, y envíe **notificaciones push reales** al teléfono de los vendedores.

## ¿Qué es una PWA?

Una PWA convierte tu aplicación web en una app instalable. Los usuarios pueden:
- **Instalarla** desde el navegador (Chrome, Edge, Safari) directamente en su dispositivo
- **Abrirla** como una app independiente (sin barra de navegador) - requiere HTTPS
- **Recibir notificaciones push** en su teléfono aunque la app esté cerrada
- **Ver alertas** cuando pierden conexión a internet

**IMPORTANTE**: El modo standalone (sin barra de navegador) requiere **HTTPS**. En desarrollo local con HTTP, la app funcionará pero mostrará la barra del navegador.

---

## Archivos del Sistema

### PWA Base

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `vite.config.ts` | Nuevo | Configuración Vite con plugins (laravel, vue, tailwind, pwa) |
| `public/manifest.json` | Nuevo | Manifest PWA (nombre, íconos, colores, modo standalone) |
| `public/sw.js` | Nuevo | Service Worker para push notifications |
| `public/pwa-192x192.png` | Nuevo | Ícono PWA 192x192 (placeholder, reemplazar con logo real) |
| `public/pwa-512x512.png` | Nuevo | Ícono PWA 512x512 (placeholder, reemplazar con logo real) |
| `package.json` | Modificado | Agregada dependencia `vite-plugin-pwa` |
| `.gitignore` | Modificado | Removido `/vite.config.ts` del ignore |

### Push Notifications (Backend)

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `composer.json` | Modificado | Agregado `laravel-notification-channels/webpush` |
| `config/webpush.php` | Nuevo | Configuración VAPID (claves de push) |
| `database/migrations/2025_07_01_000001_create_push_subscriptions_table.php` | Nuevo | Tabla para suscripciones push |
| `app/Models/TPersona.php` | Modificado | Agregado trait `HasPushSubscriptions` |
| `app/Notifications/SiifPushNotification.php` | Nuevo | Clase de notificación push |
| `app/Http/Controllers/Api/PushSubscriptionController.php` | Nuevo | Suscribir/desuscribir push (API y web) |
| `app/Http/Controllers/NotificacionPushController.php` | Nuevo | Envío de notificaciones con push (web, para SIIF/GRT/SUP) |
| `app/Http/Controllers/Api/NotificacionController.php` | Modificado | Store ahora dispara push a vendedores |

### Push Notifications (Frontend)

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `resources/views/app.blade.php` | Modificado | Meta tags PWA + manifest link + VAPID key |
| `resources/js/app.ts` | Modificado | Registra SW, renderiza OfflineBanner y PushNotificationPrompt |
| `resources/js/components/OfflineBanner.vue` | Nuevo | Banner rojo de desconexión |
| `resources/js/components/PushNotificationPrompt.vue` | Nuevo | Prompt para activar notificaciones |

### Rutas Agregadas

| Ruta | Método | Descripción | Auth |
|------|--------|-------------|------|
| `/api/push/subscribe` | POST | Registrar suscripción push | Sanctum |
| `/api/push/unsubscribe` | POST | Eliminar suscripción push | Sanctum |
| `/push/subscribe` | POST | Registrar suscripción push (web) | Session |
| `/push/unsubscribe` | POST | Eliminar suscripción push (web) | Session |
| `/notificacion/enviar` | POST | Enviar notificación + push | Session |

---

## Instalación

### 1. Dependencias

```bash
composer install
npm install
```

### 2. Generar claves VAPID

```bash
php artisan webpush:vapid
```

Esto agrega automáticamente a tu `.env`:
```
VAPID_PUBLIC_KEY=BXXXXXXXXXXXXXXXXXX...
VAPID_PRIVATE_KEY=XXXXXXXXXXXXXXXX...
```

### 3. Migrar la tabla de suscripciones push

```bash
php artisan migrate
```

Esto crea la tabla `push_subscriptions`.

### 4. Compilar

```bash
npm run build
```

---

## Cómo funciona el flujo de Push Notifications

### Suscripción (automática)

```
Usuario abre la app → 3 segundos después aparece prompt "Activar notificaciones"
  → Si acepta → El navegador genera un subscription token
    → Se envía a /push/subscribe → Se guarda en push_subscriptions
  → Si rechaza → No se vuelve a pedir (el navegador lo recuerda)
```

### Envío de notificación

```
SIIF/GRT/SUP escribe mensaje en /notificacion → Click "Enviar"
  → POST /notificacion/enviar
    → Se guarda en t_notificaciones
    → Se buscan todos los RFV del fabricante con push_subscriptions
    → Se envía push a cada uno via Web Push API
      → El teléfono muestra la notificación (incluso con app cerrada)
      → Al tocar la notificación → Abre /notificacion en la app
```

### Desde API (app móvil)

```
SIIF/GRT/SUP envía POST /api/notificacion/crear (con token Sanctum)
  → Se guarda en t_notificaciones
  → Se dispara push a los vendedores suscritos del mismo fabricante
```

### Permisos de envío

Solo pueden enviar notificaciones los usuarios con `idgrupo_persona`:
- `SIIF` (Administrador)
- `GRT` (Gerente)
- `SUP` (Supervisor)

---

## Service Worker (`public/sw.js`)

Maneja:
- **push**: Recibe la notificación del servidor y la muestra
- **notificationclick**: Al tocar la notificación, abre o enfoca la app en la URL de la notificación
- Vibración: patrón `[200ms, 100ms, 200ms]`
- `requireInteraction: true`: La notificación permanece hasta que el usuario la toque o cierre

---

## Detección Offline (`OfflineBanner.vue`)

- Banner rojo fijo en la parte superior de la pantalla
- Aparece automáticamente al perder conexión
- Desaparece al reconectarse
- Usa eventos nativos: `window.addEventListener('online/offline', ...)`

---

## Requisitos para producción

### HTTPS (obligatorio)
Las Push Notifications y el modo standalone **requieren HTTPS**. Opciones:
- Certificado SSL en el servidor (Let's Encrypt, Cloudflare, etc.)
- En desarrollo: `chrome://flags` > "Insecure origins treated as secure" > agregar tu IP

### Claves VAPID
Las claves VAPID deben estar configuradas en `.env`. Son únicas por instalación.
**No compartir la clave privada.**

---

## Próximos pasos sugeridos

1. **Reemplazar íconos placeholder** con el logo real de SIIF2 (192x192 y 512x512 px)
2. **Configurar HTTPS** en el servidor de producción
3. **Probar en dispositivos reales** (Android Chrome, iOS Safari 16.4+)
4. **Agregar screenshots** al manifest.json para mejorar el prompt de instalación
