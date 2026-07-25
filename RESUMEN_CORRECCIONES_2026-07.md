# Resumen de correcciones — Julio 2026

Este documento registra, en un solo lugar, los problemas encontrados y corregidos en dos rondas de trabajo sobre `develop`: (1) modo offline de la PWA, y (2) una batería de 15 bugs reportados tras probar la aplicación. También se puede reconstruir todo esto viendo los commits de `develop` a partir de julio 2026, pero queda aquí como referencia rápida.

## Cómo se probó

- Todo el PHP tocado se validó con `php -l` (sin errores de sintaxis).
- Todo el frontend se validó con `npm run build` (compila) y `eslint` (sin errores nuevos).
- Se instaló `vendor/` (`composer install`) y se armó un entorno mínimo con SQLite para correr `php artisan migrate` y `php artisan route:list`: la aplicación arranca sin errores fatales y las 160 rutas se registran correctamente — esto confirma que las clases nuevas/renombradas (namespaces de eventos de WebPush, controladores, etc.) existen y cargan bien.
- **Limitación importante**: este entorno no tiene un servidor MySQL/MariaDB ni el dump real de la base de datos (`docker-entrypoint-initdb.d/01_dump_patched.sql`, ~24 MB, sintaxis específica de MySQL) cargado, y no hay acceso a Docker con daemon activo para levantar el stack completo. Por eso no fue posible ejecutar los flujos de negocio reales (login, crear pedidos, generar reportes, etc.) ni probar visualmente en un navegador contra datos reales. La verificación de estos cambios de lógica de negocio se hizo por lectura cuidadosa del código contra el esquema real (modelos, migraciones, columnas), no por ejecución. Se recomienda que el usuario repita las pruebas manuales descritas en cada sección.

---

## Parte 1 — Modo offline de la PWA

### Problema original
1. El login no funcionaba sin conexión.
2. IndexedDB exponía toda la información de clientes/productos, incluyendo usuario y contraseña del RFV.

### Decisiones tomadas (con el usuario)
- Reemplazar el guardado de usuario/contraseña por un **token offline firmado** (HMAC) con expiración propia y configurable, en vez de comparar contraseñas en el navegador.
- Minimizar el payload de `/offline/master-data` a lo estrictamente necesario para Toma de Pedidos y Nuevo Reporte.
- El modo offline solo aplica a RFV (los demás roles siempre tienen internet disponible).
- Purga de caché al cerrar sesión / TTLs automáticos: **pospuesto** a una futura iteración (riesgo de borrar la cola de sincronización de pedidos/reportes pendientes).

### Cambios y bugs encontrados en el camino

| Problema | Causa raíz | Archivo(s) |
|---|---|---|
| Token offline siempre "vencido" | `Date.now()` (ms) comparado contra `expires_at` de Laravel (segundos) | `resources/js/offline/authService.ts` |
| Ruta `/offline/cache-auth` 404 | Backend actualizado, frontend desplegado no se había reconstruido (`npm run build` faltante) | — (deploy, no código) |
| Login offline pasaba, pero Toma de Pedidos/Nuevo Reporte no cargaban catálogos | Esas pantallas nunca leían IndexedDB salvo el buscador de cliente; el resto de los datos dependía 100% del servidor | `resources/js/offline/localData.ts`, `localPagination.ts` (nuevos), `TomaDePedidos.vue`, `RTR/NuevoReporte.vue` |
| Navegar a `/tdp`/`/nuevo-reporte` sin red mostraba error nativo del navegador | El Service Worker solo tenía precacheado `/login`, no esas 2 rutas | `resources/js/sw.ts`, `vite.config.ts` |
| El Service Worker nunca lograba instalarse | `globDirectory: 'public/build'` generaba URLs sin el prefijo `/build/` real (`/assets/x.js` en vez de `/build/assets/x.js`); Workbox intenta precachear TODO el manifest al instalar, y un solo 404 hace fallar toda la instalación. Probablemente afectaba a la app desde antes de este trabajo. | `vite.config.ts` (`modifyURLPrefix`) |
| nginx cacheaba `sw.js` por 1 año | Una regla genérica de estáticos (`~* \.(js\|css\|...)$`) también atrapaba al Service Worker — el navegador nunca detectaba versiones nuevas | `.docker/nginx/default.conf` |
| Revisión fija del shell precacheado | Un string fijo (`'offline-login-shell-v1'`) nunca cambia entre builds, así que Workbox no volvía a descargar `/login`/`/tdp`/`/nuevo-reporte` en deploys futuros | `vite.config.ts` (timestamp por build) |

### Pendiente / acción manual del usuario
- Ninguna acción de código pendiente. Para producción: confirmar que el paso de `npm run build` + reinicio de nginx quede en el proceso normal de deploy (ya no es opcional, el Service Worker depende de eso).

---

## Parte 2 — Batería de 15 bugs reportados

Metodología: se investigó cada uno con agentes de exploración en paralelo (solo lectura, sin tocar código) antes de aplicar ningún fix, para confirmar la causa raíz exacta con referencia a archivo y línea.

### Toma de Pedidos (TDP)

| # | Problema | Causa raíz | Fix |
|---|---|---|---|
| 1 | Buscador de productos/mayoristas no filtra | El backend sí filtraba correctamente; nadie escuchaba los eventos `@search` (modal producto) ni `@fetch-mayoristas` (modal mayorista) en el frontend | `TablesTdp.vue`, `TomaDePedidos.vue`: se conectaron los eventos faltantes + nueva función `reloadMayoristas` |
| 2 | Botones +/- del impuesto invisibles | `vue-number-input.vue` dibuja los signos +/- con líneas de 1px casi imperceptibles, sin contraste de fondo | `vue-number-input.vue`: grosor a 2px + fondo con contraste (`bg-gray-100`/`dark:bg-gray-700` con hover) |
| 3 | Coordenadas GPS se capturan pero no se guardan | `ProcesarOrdenController::store` nunca copiaba `lat`/`lon` del payload a `coordenadas_l`/`coordenadas_a` (el controlador de la app móvil sí lo hace) | `ProcesarOrdenController.php`: se agregó la validación y el mapeo faltante |

### Lista de Clientes

| # | Problema | Causa raíz | Fix |
|---|---|---|---|
| 4 | Filtro de cliente no trae resultados | `AgendaService::obtenerDatosAgenda` nunca pasaba `$idCliente` a `obtenerClientesPaginados` cuando había un RFV seleccionado (el caso normal) | `AgendaService.php` |
| 5 | Cambiar de página se ve un instante y vuelve a página 1 | La paginación por número usaba `<Link>` de Inertia (navegación completa, remonta el componente); el `onMounted` de `ListaClientes.vue` fuerza `page:1` para RFV porque el backend nunca manda `idRfv` en `filtros`. Al arreglar la causa de raíz (que no remonte), este síntoma también desaparece. | `TablePagination.vue`: ahora emite `update:page` (igual que ya hacía el cambio de tamaño) en vez de navegar directamente |
| 6 | Con muchas páginas se desborda (1,2,3...40 de corrido) | `AgendaController::formatearLinks` generaba TODAS las páginas sin ventana/ellipsis | `AgendaController.php`: ventana de páginas alrededor de la actual + "...", y de paso se corrigió Previous/Next (apuntaban siempre a página 1/última en vez de la adyacente) |

### Personas / Artículos

| # | Problema | Causa raíz | Fix |
|---|---|---|---|
| 7 | Cambiar tamaño de página no funciona | El backend ya soportaba el parámetro `size`; `seccionGen.vue` no tenía ningún handler `@update:page`/`@update:pageSize` sobre `GlobalTable` | `seccionGen.vue`: se agregaron ambos handlers |
| 8 | RFV veía/editaba clientes de otros vendedores; podía editar/eliminar en módulos donde no debería | `store()`/`update()`/`destroy()` de `PersonaAdminController` y `ProductoAdminController` no tenían **ningún** chequeo de rol (solo `index()` calculaba permisos, pero nunca se aplicaban a las rutas de escritura) | Ver detalle abajo ⬇️ |

**Detalle del punto 8 (permisos por rol):**
- `AccessControlService::canAccessCliente()` (nuevo): verifica la relación `r_cliente_rfv` para confirmar que un cliente pertenece al RFV autenticado.
- `PersonaAdminController`/`ProductoAdminController`: se agregó el guard en `store`/`update`/`destroy`, reutilizando los métodos que ya existían (`checkGlobalPermission`, `checkProductPermission`) pero que nunca se aplicaban fuera de `index()`. Al crear un cliente como RFV, se fuerza `vendedor = uno mismo` sin importar qué mande el formulario.
- `seccionGen.vue`: la columna "Acción" y el botón "Agregar" ahora se ocultan según `rolesQuePuedenEditar` (ya declarado por módulo en `configs/admin/index.ts`, pero nunca conectado al frontend) comparado contra el rol del usuario actual (`page.props.auth.role`). Esto cubre automáticamente representantes/mayoristas/supervisores/gerentes/productos/muestras (ya excluían RFV en su configuración) sin tocar el backend de nuevo.

### Consultas - Reportes

| # | Problema | Causa raíz | Fix |
|---|---|---|---|
| 9 | Dropdown de estatus de órdenes vacío/mal filtrado | `ConsultaOptionsService.php` importaba `App\Models\OperadorFabricante` (no existe) en vez de `App\Models\Scopes\OperadorFabricante`; el `withoutGlobalScope()` nunca removía el scope real | Corregido el `use` |
| 10 | PDF de órdenes no se genera con muchos datos (Excel sí funciona) | dompdf arma todo el HTML en memoria antes de rasterizar (a diferencia de Excel); con muchas filas agota memoria/tiempo y el proceso muere sin dejar rastro | Límite de filas (1000) con mensaje claro + `try/catch` con log real del error |
| 11 | Consulta Gerencial: PDF y Excel sin supervisor/RFV/zona | Ambos leían relaciones Eloquent (`$item->supervisor`, etc.) cuyas FK nunca se seleccionan en el query base — siempre resolvían `null` | `resources/views/pdf/gerencial.blade.php` y `GerencialExport.php`: usan los alias planos que el query ya trae (`supervisor_nombre`, `rfv_nombre`, etc.) |
| 12 | Existía un archivo duplicado `ConsultaGerencialController.php` (no reportado por el usuario, hallazgo del proceso) | Declaraba la misma clase `GerencialController` que el archivo realmente usado (`gerencialController.php`), versión vieja/incompleta, causaba advertencias de colisión en el autoload de Composer | Eliminado (confirmado sin ninguna referencia en rutas ni vistas) |

### Seguimiento de Pedidos / Conciliar Factura

| # | Problema | Causa raíz | Fix |
|---|---|---|---|
| 13 | Todas las visitas aparecen sin estatus | `TEstatusOrdene` (y `TFactura`) tienen su propio Global Scope `OperadorFabricante` que filtra por el usuario autenticado; nunca se quitaba al cargar la relación `estatus`/`factura` de una orden (sí se quitaba para `rfv`) | `PedidoService.php`: se quita el scope también para `estatus`/`factura` en listado, detalle y actualización |
| 14 | Botones +/- de "despachadas" invisibles | Mismo componente compartido que el bug #2 de TDP | Resuelto con el mismo fix de `vue-number-input.vue` |
| 15 | Cálculo de "faltantes" da negativo (ej. 2 conciliadas, -2 faltantes en vez de 1) | `ConciliarFacturaController` accedía a `$pivotData->cantidad_solicitada` (no existe ahí, vive en `->pivot->cantidad_solicitada`), siempre daba 0 | Corregido el acceso al pivot |

### Notificaciones push

| # | Problema | Causa raíz | Fix |
|---|---|---|---|
| 16 | "Enviada 1, push 0", nunca llega, sin error visible | Cadena de fallos: (a) VAPID keys nunca generadas en este entorno → el frontend nunca completa la suscripción push; (b) el paquete de webpush trata un envío fallido como evento de dominio, no como excepción, así que no queda registro en logs; (c) **bug adicional encontrado**: `SiifPushNotification.php` tenía `use Illuminate\Notification\Notification` (singular, namespace que no existe) en vez de `Illuminate\Notifications\Notification` — no se notaba porque nadie tenía suscripción activa, pero en cuanto exista una iba a producir un error fatal de clase no encontrada | (a) **requiere acción manual**: correr `php artisan webpush:vapid`. (b) se agregó un listener global en `AppServiceProvider` que loguea cualquier fallo real de entrega, más una advertencia si las VAPID keys no están configuradas. (c) corregido el typo. También se corrigió el conteo de "push enviados" en `NotificacionPushController` para que refleje entregas reales, no solo "tenía una suscripción" |

### Plantilla Excel de carga masiva de visitas (función nueva, no bug)

Se agregó una segunda hoja "Referencia RFV-Clientes" a `PlantillaVisitasExport.php` (siguiendo el patrón ya usado en `CliRfvExport.php`): lista los RFV disponibles (solo el propio si quien descarga es RFV; todos los de la empresa si es SUP/GRT/SIIF) y los clientes de cada uno, ordenados por RFV, para saber qué `idCliente`/`idRFV` usar al llenar la plantilla.

---

## Pendiente / requiere acción del usuario

1. **Notificaciones push**: correr `php artisan webpush:vapid` para generar las claves VAPID. Sin esto, los push seguirán sin entregarse (ahora sí quedará registrado en `storage/logs`).
2. **CI (`lint.yml`)**: el pipeline de lint en GitHub Actions ya fallaba antes de este trabajo por ~74 errores de ESLint preexistentes en archivos no relacionados con nada de lo hecho aquí. No se tocó (fuera de alcance), pero conviene atenderlo en algún momento si se quiere que el pipeline quede en verde.
3. Repetir las pruebas manuales de cada punto de esta lista en un entorno con la base de datos real, dado que este entorno de trabajo no pudo ejecutar los flujos de negocio de punta a punta (ver sección "Cómo se probó").
