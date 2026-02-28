import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig(({ mode }) => {
  // Cargar variables de entorno (VITE_HMR_HOST viene de docker-compose -> .env -> dev.sh)
  const env = loadEnv(mode, process.cwd(), '');
  const hostIP = env.VITE_HMR_HOST || 'localhost';
  console.log(`\n🎯 HMR Configurado forzosamente a: ${hostIP}\n`);

  return {
    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,
      cors: true,
      hmr: {
        host: hostIP,
        port: 5173,
        clientPort: 5173,
      },
    },
    plugins: [
      laravel({
        input: ['resources/js/app.ts'],
        refresh: true,
        detectTls: false,
      }),
      vue(),
      tailwindcss(),
    ],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'resources/js'),
        '@utils': path.resolve(__dirname, 'resources/js/utils'),
        'vue-dropzone': path.resolve(__dirname, 'resources/js/shims/vue-dropzone.ts'),
      },
    },
    build: {
      outDir: 'public/build',
      emptyOutDir: true,
      manifest: true,
      rollupOptions: {
        output: {
          manualChunks: {
            vendor: ['vue'],
          },
        },
      },
    },
    css: {
      devSourcemap: true
    },
    optimizeDeps: {
      exclude: ['reka-ui']
    },
    define: {
      __VUE_OPTIONS_API__: true,
      __VUE_PROD_DEVTOOLS__: false,
    }
  }
})
