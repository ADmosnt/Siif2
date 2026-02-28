# SIIF2 - Sales Intelligence & Information Framework

Sistema integral de gestión de ventas, inventario y fuerza de campo, construido con **Laravel 12** + **Vue 3** + **Inertia.js**.

---

## Stack Tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3.5, TypeScript 5.2, Vite 6 |
| Bridge SSR | Inertia.js 2.0 |
| UI | Tailwind CSS 4, Reka UI, Shadcn-vue |
| Estado | Pinia 3 |
| Base de Datos | MySQL / MariaDB |
| Auth Web | Session (Laravel) |
| Auth API | Sanctum (Token) |
| Mapas | Leaflet + MarkerCluster |
| Calendario | FullCalendar Vue3 |
| 3D/Animaciones | Three.js, GSAP |
| Exportación | Laravel Excel, DomPDF |

---

## Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/          # 46 controladores organizados por módulo
│   │   ├── Admin/            # CRUD personas y productos
│   │   ├── Agenda/           # Planificador temporal
│   │   ├── Api/              # API móvil (13 controladores)
│   │   ├── Auth/             # Autenticación web y API
│   │   ├── ConciliarFactura/ # Conciliación de facturas
│   │   ├── Gerencial/        # Consulta gerencial y GPS
│   │   ├── ListaCliente/     # Agenda de clientes
│   │   ├── ListaReportes/    # Lista de reportes
│   │   ├── MonitorDown/      # Exportación de datos
│   │   ├── MonitorUp/        # Importación de datos
│   │   ├── Ordenes/          # Procesamiento de pedidos
│   │   ├── Reportes/         # Reportes de actividad
│   │   ├── SeguimientoPedido/# Seguimiento de pedidos
│   │   └── Settings/         # Configuración de perfil
│   ├── Middleware/
│   └── Requests/             # 6 Form Requests
├── Models/                   # 80+ modelos Eloquent
└── Services/                 # 12 servicios de lógica de negocio

resources/js/
├── pages/                    # 26 páginas Vue
├── layouts/                  # 8 layouts
├── components/               # 100+ componentes
│   ├── CalendarV2/           # Sistema de calendario
│   ├── GPS/                  # Componentes de mapa
│   ├── LayoutComponents/     # Componentes de layout (incluye CubEgg)
│   ├── ui/                   # Primitivas UI (Shadcn)
│   └── icons/                # Iconos SVG
├── stores/                   # Pinia stores (alerts, calendar)
├── composables/              # 9 composables reutilizables
└── types/                    # 12 archivos de tipos TypeScript
```

---

## Instalación

```bash
# Clonar el repositorio
git clone <url-del-repo>
cd Siif2

# Instalar dependencias PHP
composer install

# Instalar dependencias JS
npm install

# Configurar entorno
cp default_env.txt .env
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Desarrollo
composer dev   # o: npm run dev + php artisan serve
```

### Con Docker

```bash
cp .env.example_with_docker_mariadb .env
docker-compose up -d
```

---

## Variables de Entorno Relevantes

| Variable | Descripción | Valores |
|----------|------------|---------|
| `VITE_CUBEGG_ENABLED` | Activa el Easter Egg (CubEgg) | `true` / `false` |
| `DB_CONNECTION` | Conexión de BD | `mysql` |
| `SESSION_DRIVER` | Driver de sesiones | `database` |
| `MAIL_MAILER` | Driver de correo | `log` / `smtp` |

---

## Roles de Usuario

| Código | Rol | Acceso |
|--------|-----|--------|
| `SIIF` | Administrador | Acceso total, modo global |
| `GRT` | Gerente | Su fabricante, gestión completa |
| `SUP` | Supervisor | Su fabricante, supervisión |
| `RFV` | Representante de Ventas | Sus clientes, reportes, pedidos |
| `MAY` | Mayorista | Datos de mayorista |
| `CLI` | Cliente | Datos de cliente |

---

## Easter Egg: CubEgg

Un cubo 3D animado con Three.js que se activa al seguir una secuencia secreta de navegación. Es un guiño a un compañero que trabajó en el proyecto.

### Activación
1. Establecer `VITE_CUBEGG_ENABLED=true` en `.env`
2. Navegar en secuencia: `/tdp` → `/consulta-reporte` → `/consulta-gerencial`
3. El cubo aparece como overlay

### Controles
- **Flechas**: Rotar entre caras
- **Espacio**: Activar auto-rotación
- **M**: Toggle sonido
- **+/-**: Velocidad del audio
- **ESC**: Cerrar

### Personalización
- Reemplazar texturas en `public/textures/cara{1-6}.png`
- Reemplazar audio en `public/sounds/audio.wav`

---

## Análisis de Código - Hallazgos

### Resumen Ejecutivo

Se realizó un análisis exhaustivo de todo el código del repositorio (46 controladores PHP, 12 servicios, 180+ componentes Vue, 9 composables, 2 stores Pinia). A continuación se documentan los hallazgos organizados por severidad.

---

### Problemas Críticos

#### 1. Ruta GPS pública sin autenticación
**Archivo**: `routes/api.php`
**Severidad**: CRITICA
La ruta `/api/gerencial/gps/ruta` está fuera del middleware `auth:sanctum`, permitiendo acceso no autenticado a datos de geolocalización.

#### 2. Bug en OptionsController - Variable no definida
**Archivo**: `app/Http/Controllers/OptionsController.php` (líneas 99-114)
**Severidad**: CRITICA
En `searchCiudades()`, la variable `$estadoId` se usa fuera del bloque `if` donde se define, causando un error de variable indefinida. Además hay un `where` duplicado.

#### 3. XSS vía v-html en TablePagination.vue
**Archivo**: `resources/js/components/TablePagination.vue` (líneas 101, 110)
**Severidad**: CRITICA
Se usa `v-html` para renderizar labels de paginación sin sanitización.

#### 4. Memory leak en useCalendarEventInteractions.ts
**Archivo**: `resources/js/composables/useCalendarEventInteractions.ts`
**Severidad**: CRITICA
Event listeners de `mousemove`/`mouseup` añadidos al document sin limpieza garantizada si el componente se desmonta durante un drag/resize.

---

### Problemas de Severidad Alta

#### 5. Código inalcanzable en gpsController
**Archivo**: `app/Http/Controllers/Gerencial/gps/gpsController.php` (líneas 57-61)
Código de logging después de un `return`, nunca se ejecuta.

#### 6. N+1 Queries en AgendaController API
**Archivo**: `app/Http/Controllers/Api/AgendaController.php` (líneas 183-195)
En `clientesDisponibles()`, se ejecuta una query por cada cliente en un `foreach`.

#### 7. Mass Assignment sin filtrar en update()
**Archivos**: `PersonaAdminController.php` (línea 194), `ProductoAdminController.php` (línea 116)
Los métodos `update()` pasan `$request->all()` sin validar a los servicios.

#### 8. Falta de autorización en update/destroy
**Archivo**: `PersonaAdminController.php`
Los métodos `update()` y `destroy()` no verifican roles, mientras que `index()` sí lo hace.

#### 9. IDOR en ConciliarFacturaController
**Archivo**: `app/Http/Controllers/ConciliarFactura/ConciliarFacturaController.php`
Se obtienen órdenes por ID sin verificar que el usuario tenga acceso a ellas.

#### 10. Log incorrecto de módulo
**Archivos**: `PersonaAdminController.php`, `ProductoAdminController.php`, `TmpPlanificadorController.php`
Todos logean "Conciliación de Facturas" como módulo de acceso no autorizado, independientemente del módulo real.

#### 11. N+1 en procesamiento de productos
**Archivos**: `ConciliarFacturaController.php`, `ProcesarOrdenController.php`
Llamadas a `TProducto::find()` dentro de loops `foreach`.

#### 12. Ruta duplicada /consulta-reporte
**Archivo**: `routes/web.php` (líneas 95 y 143)
La misma ruta está definida dos veces.

#### 13. Código muerto duplicado (AgendaController)
**Archivo**: `app/Http/Controllers/ListaCliente/AgendaController.php` (línea 84)
Return duplicado que nunca se alcanza en `exportarExcel()`.

#### 14. Rutas de Upload/Export sin middleware auth
**Archivo**: `routes/web.php` (líneas 160-167)
Las rutas de upload y export están fuera del grupo `auth`.

#### 15. Mutación directa de page props
**Archivo**: `resources/js/composables/useValidationAlert.ts` (línea 86)
`page.props.flash = {}` muta las props de Inertia directamente.

#### 16. setTimeout sin limpieza
**Archivo**: `resources/js/components/GenericCombobox.vue` (líneas 133-135)
setTimeout no se limpia en `onUnmounted`.

---

### Problemas de Severidad Media

#### 17. Import no utilizado
**Archivo**: `app/Http/Controllers/Reportes/ReporteController.php` (línea 14)
`PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels` no se usa.

#### 18. Controlador duplicado/incompleto
**Archivo**: `app/Http/Controllers/Gerencial/ConsultaGerencialController.php`
Parece ser una versión incompleta de `GerencialController`.

#### 19. Naming conventions inconsistentes
- `gerencialController` (minúscula) vs `GerencialController` (mayúscula)
- `gpsController` debería ser `GpsController` (PSR-12)

#### 20. Strings mágicos para roles
Múltiples archivos usan strings como `'SIIF'`, `'GRT'`, `'RFV'`, `'SUP'` directamente. Deberían ser constantes.

#### 21. Valores de estatus hardcodeados
`'idestatus' => '1'`, `'Enviada'`, `'Facturada'` deberían ser constantes del modelo.

#### 22. default_env.txt con entradas duplicadas
**Archivo**: `default_env.txt`
Casi todas las variables están duplicadas en el archivo.

#### 23. Debounce sin cancel en onUnmounted
**Archivo**: `resources/js/components/GenericCombobox.vue`
La función debounced no llama `.cancel()` al desmontar.

#### 24. Watchers en cascada
**Archivo**: `resources/js/pages/TomaDePedidos.vue` (líneas 196-221)
Múltiples watchers que se disparan en cascada pueden causar llamadas API innecesarias.

#### 25. Error handling comentado
**Archivo**: `resources/js/composables/useClienteCombobox.ts`
Los errores se capturan pero la notificación al usuario está comentada.

#### 26. Contaminación del namespace global
**Archivo**: `resources/js/composables/useCalendarEventInteractions.ts`
Usa `window.__calendarEventModified` para estado global.

---

### Problemas de Severidad Baja

#### 27. 93 console.log en producción
Múltiples archivos contienen `console.log`, `console.error`, `console.warn` que deberían eliminarse en producción.

#### 28. Falta de type hints de retorno en PHP
Múltiples controladores carecen de tipos de retorno en sus métodos.

#### 29. Respuesta inconsistente en APIs
Algunos endpoints devuelven 404 para listas vacías en vez de 200 con array vacío.

#### 30. MayoristaController devuelve 404 para lista vacía
**Archivo**: `app/Http/Controllers/Api/MayoristaController.php`
Debería devolver 200 con array vacío.

#### 31. Typo en nombre de columna
**Archivo**: `app/Http/Controllers/Api/NotificacionController.php` (línea 55)
`'descripcion_notoficacion'` - posible typo (debería ser `notificacion`).

#### 32. LocalStorage sin validación de tipo
**Archivo**: `resources/js/composables/useAppearance.ts`
El valor de localStorage se castea sin validar.

---

### Recomendaciones Prioritarias

1. **Inmediatas (Seguridad)**:
   - Mover ruta GPS dentro del middleware `auth:sanctum`
   - Mover rutas de upload/export dentro del middleware `auth`
   - Eliminar `v-html` sin sanitizar en TablePagination.vue
   - Corregir el bug de `$estadoId` en OptionsController
   - Añadir autorización en `update()`/`destroy()` de Admin controllers

2. **Corto Plazo (Calidad)**:
   - Extraer roles a constantes (`UserRoles::SIIF`, etc.)
   - Implementar Form Requests para validaciones complejas
   - Resolver N+1 queries con eager loading
   - Limpiar código muerto y controladores duplicados
   - Corregir logs con nombre de módulo incorrecto

3. **Largo Plazo (Arquitectura)**:
   - Implementar Laravel Policies para autorización
   - Crear composable reutilizable para fetch con error handling
   - Añadir tests unitarios para servicios y composables
   - Implementar ESLint rules para prevenir anti-patterns
   - Eliminar console.log de producción

---

## Licencia

Proyecto privado - Bimodal Technology &copy; 2025
