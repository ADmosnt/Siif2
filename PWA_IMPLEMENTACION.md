# PWA - SIIF2: Implementación de Progressive Web App

## Resumen

Se implementó soporte PWA (Progressive Web App) en SIIF2 utilizando `vite-plugin-pwa`. Esto permite que la aplicación web sea **instalable** en dispositivos móviles y de escritorio como si fuera una app nativa.

## ¿Qué es una PWA?

Una PWA convierte tu aplicación web en una app instalable. Los usuarios pueden:
- **Instalarla** desde el navegador (Chrome, Edge, Safari) directamente en su dispositivo
- **Abrirla** como una app independiente (sin barra de navegador)
- **Recibir alertas** cuando pierden conexión a internet

## Archivos Modificados

### 1. `vite.config.ts` (NUEVO)
- Configuración de Vite con todos los plugins necesarios: `laravel-vite-plugin`, `@vitejs/plugin-vue`, `@tailwindcss/vite` y `vite-plugin-pwa`
- El manifest PWA define: nombre, colores, íconos y orientación de la app
- Workbox configurado para cachear assets estáticos (JS, CSS, fuentes) pero NO rutas de navegación (para evitar conflictos con Inertia)
- Cache de fuentes de Bunny Fonts con estrategia CacheFirst (1 año)

### 2. `package.json` (MODIFICADO)
- Agregada dependencia `vite-plugin-pwa: ^0.21.1`

### 3. `resources/views/app.blade.php` (MODIFICADO)
- Agregados meta tags PWA:
  - `theme-color`: Color de la barra de estado en móviles
  - `mobile-web-app-capable` y `apple-mobile-web-app-capable`: Permite modo standalone
  - `apple-mobile-web-app-status-bar-style`: Estilo de barra en iOS
  - `apple-mobile-web-app-title`: Nombre en iOS
  - `apple-touch-icon`: Ícono para iOS

### 4. `resources/js/app.ts` (MODIFICADO)
- Importa y renderiza el componente `OfflineBanner` en la raíz de la aplicación Vue
- El banner se muestra encima de toda la app cuando se pierde conexión

### 5. `resources/js/components/OfflineBanner.vue` (NUEVO)
- Componente Vue que detecta conexión/desconexión en tiempo real
- Muestra un banner rojo fijo en la parte superior de la pantalla cuando no hay internet
- Desaparece automáticamente al reconectarse
- Usa transiciones CSS suaves (slide-down)
- z-index alto (9999) para estar siempre visible

### 6. `public/pwa-192x192.png` y `public/pwa-512x512.png` (NUEVOS)
- Íconos placeholder para el manifest PWA (color sólido #4B5563)
- **IMPORTANTE**: Reemplazar con el logo real de SIIF2 antes de producción

## Instalación

Después de hacer pull de estos cambios, ejecutar:

```bash
npm install
```

Esto instalará `vite-plugin-pwa` y sus dependencias (`workbox-*`).

## Cómo funciona

### En desarrollo (`npm run dev`)
- El service worker NO se registra en modo desarrollo
- El componente `OfflineBanner` sí funciona (detecta desconexión)
- Para probar el PWA completo, hacer `npm run build` y servir desde Laravel

### En producción (`npm run build`)
- Vite genera automáticamente:
  - `manifest.webmanifest` en `/build/`
  - Service Worker (`sw.js`) en la raíz pública
  - Assets pre-cacheados
- El service worker se auto-actualiza cuando hay nuevas versiones
- Los assets estáticos se cachean para carga rápida

### Instalar la app
1. Abrir la web en Chrome/Edge desde un móvil o PC
2. Aparecerá un aviso de "Instalar aplicación" o ir a menú > "Instalar app"
3. La app se agrega al escritorio/pantalla de inicio
4. Al abrirla, funciona sin barra de navegador (modo standalone)

## Configuración del Manifest

| Propiedad | Valor |
|-----------|-------|
| Nombre completo | SIIF2 - Sistema Integral de Información |
| Nombre corto | SIIF2 |
| Color tema | #4B5563 |
| Color fondo | #ffffff |
| Modo | standalone |
| Orientación | portrait |
| Inicio | / |

## Estrategia de Cache (Workbox)

- **Assets estáticos** (JS, CSS, íconos, fuentes): Pre-cache en build time
- **Fuentes Bunny**: Cache-First, expira en 1 año, máximo 10 entradas
- **Navegación HTML**: NO cacheada (Inertia maneja sus propias rutas)
- **API calls**: NO cacheadas (siempre requieren datos frescos del servidor)

## Detección Offline

El componente `OfflineBanner` usa los eventos nativos del navegador:
- `window.addEventListener('online', ...)`
- `window.addEventListener('offline', ...)`

Esto muestra/oculta un banner rojo cuando cambia el estado de conexión.

## Próximos pasos sugeridos

1. **Reemplazar íconos placeholder** con el logo real de SIIF2 (192x192 y 512x512 px)
2. **Agregar screenshots** al manifest para mejorar el prompt de instalación
3. **Configurar push notifications** si se necesitan notificaciones nativas
4. **Probar en dispositivos reales** (Android Chrome, iOS Safari, Edge Desktop)
