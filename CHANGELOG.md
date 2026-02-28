# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

---

## [Unreleased] - 2026-02-28

### Agregado
- **README.md**: Documentación completa del proyecto incluyendo stack tecnológico, estructura, instalación, roles de usuario y análisis de código
- **CHANGELOG.md**: Este archivo para seguimiento de cambios
- **Easter Egg (CubEgg) funcional**: El componente CubEgg ahora funciona correctamente
  - Carga asíncrona del componente para no afectar el bundle principal
  - Fallback de colores sólidos cuando las texturas no están disponibles
  - Manejo graceful cuando el audio no existe (no rompe el componente)
  - Botón de cerrar (X) y soporte para tecla ESC
  - Transición de fade in/out
  - Overlay informativo con instrucciones de controles
  - Limpieza completa de recursos Three.js (geometrías, materiales, texturas) en onUnmounted
- **Variable de entorno `VITE_CUBEGG_ENABLED`**: Controla si el Easter Egg está activo
  - `false` por defecto (seguro para producción)
  - Agregado en `default_env.txt` y `.env.example_with_docker_mariadb`
- **Texturas placeholder** en `public/textures/cara{1-6}.png`: Imágenes de colores para las 6 caras del cubo
- **Audio placeholder** en `public/sounds/audio.wav`: Archivo WAV silencioso de 1 segundo

### Modificado
- **`resources/js/layouts/AppLayout.vue`**: Descomentado y mejorado la lógica del Easter Egg
  - Imports activos para el sistema de detección de secuencia de rutas
  - Integración con Inertia.js `router.on('success')` para tracking de navegación
  - Carga condicional basada en `VITE_CUBEGG_ENABLED`
  - Parsing correcto de URLs usando `new URL()` para extraer solo el pathname
  - Limpieza del array de historial para no acumular rutas innecesarias
  - Evento `@close` para cerrar el Easter Egg
- **`resources/js/components/LayoutComponents/CubEgg.vue`**: Reescrito con mejoras
  - Emit `close` para comunicación con el padre
  - Error handling en carga de texturas (fallback a colores sólidos)
  - Error handling en carga de audio (componente funciona sin audio)
  - Limpieza completa de recursos Three.js (dispose de geometrías, materiales y texturas)
  - Variable `resizeHandler` tracked para limpieza correcta del event listener
  - Guard clauses en `animate()` y `snapToFace()` para evitar null references
  - Eliminados console.log innecesarios
  - Comentario de path corregido (LayoutComponents/ en vez de raíz)

### Análisis de Código Realizado
Se documentaron **32 hallazgos** en el README.md, clasificados por severidad:
- **4 Críticos**: Ruta GPS sin auth, bug en OptionsController, XSS en TablePagination, memory leak en calendar interactions
- **12 Altos**: N+1 queries, mass assignment, IDOR, falta de auth en CRUD, rutas sin middleware, etc.
- **10 Medios**: Import no usado, controllers duplicados, naming inconsistente, strings mágicos, etc.
- **6 Bajos**: Console.logs, type hints faltantes, typos, etc.
