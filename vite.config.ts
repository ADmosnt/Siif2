import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
        server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
            port: 5173,
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
        VitePWA({
            buildBase: '/',
            registerType: 'autoUpdate',
            injectRegister: false,
            manifest: false,
            includeAssets: ['favicon.ico', 'pwa-192x192.png', 'pwa-512x512.png'],
            // injectManifest (SW escrito a mano en resources/js/sw.ts) en vez de
            // generateSW: necesitamos setCatchHandler para servir el login
            // precacheado SOLO cuando la navegacion offline realmente falla,
            // sin afectar la navegacion normal online. navigateFallback (modo
            // generateSW) no permite esa condicion: sirve el precache siempre.
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.ts',
            // No se fija swDest aqui: para un source .ts, vite-plugin-pwa
            // SIEMPRE lo resuelve dentro de outDir (public/build/sw.js) y usa
            // ese mismo archivo como swSrc para inyectar el manifest. Forzar
            // otra ruta aqui hace que injectManifest lea un swSrc que no
            // existe (o un sw.js viejo de una build anterior) y falle con
            // "unable to find a place to inject the manifest". El script
            // "build" copia public/build/sw.js a public/sw.js despues
            // (ver package.json + scripts/copy-sw.mjs).
            injectManifest: {
                globDirectory: 'public/build',
                globPatterns: ['**/*.{js,css,ico,png,svg,woff,woff2}'],
                // Garantiza que el login quede precacheado desde la primera
                // instalacion del SW, sin depender de que el navegador haya
                // navegado ahi antes (ver setCatchHandler en sw.ts).
                additionalManifestEntries: [
                    { url: '/login', revision: 'offline-login-shell-v1' },
                    { url: '/tdp', revision: 'offline-tdp-shell-v1' },
                    { url: '/nuevo-reporte', revision: 'offline-nuevo-reporte-shell-v1' },
                ],
            },
        }),
    ],
})
