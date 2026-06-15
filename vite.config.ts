import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
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
            registerType: 'autoUpdate',
            manifest: false,
            includeAssets: ['favicon.ico', 'pwa-192x192.png', 'pwa-512x512.png'],
            workbox: {
                globPatterns: ['**/*.{js,css,ico,png,svg,woff,woff2}'],
                navigateFallback: null,
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.bunny\.net\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'bunny-fonts-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: ({ request }) => request.mode === 'navigate',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pages-cache',
                            expiration: {
                                maxEntries: 30,
                                maxAgeSeconds: 60 * 60 * 24 * 7,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                            networkTimeoutSeconds: 5,
                        },
                    },
                    {
                        urlPattern: /\/offline\/master-data/,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'master-data-cache',
                            expiration: {
                                maxEntries: 1,
                                maxAgeSeconds: 60 * 60 * 24,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                ],
            },
        }),
    ],
})
