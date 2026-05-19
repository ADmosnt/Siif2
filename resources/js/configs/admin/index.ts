// resources/js/configs/admin/index.ts

// ==========================================
// INTERFACES Y TIPOS
// ==========================================

export interface FieldConfig {
  name: string;      
  label: string;     
  type: 'text' | 'email' | 'number' | 'combobox' | 'radio' | 'date' | 'password';
  class?: string;    // Clases de grid (ej: md:col-span-1)
  options?: { label: string; value: any }[]; 
}

interface BreadcrumbItem {
  label: string;
  href?: string;
}

export interface CrudConfig {
  title: string;
  routePrefix: string;
  breadcrumbs: BreadcrumbItem[];
  columns: { key: string; label: string; className?: string }[];
  fields: FieldConfig[];
  rolesQuePuedenEditar?: string[];
}

// ==========================================
// CONSTANTES REUTILIZABLES (PERSONAS)
// ==========================================

// Columnas de Tabla (Igual para todas las personas y productos)
const COLS_PERSONA = [
  { key: 'nombre_completo', label: 'Nombre' },
  { key: 'documento', label: 'Documento de identidad' },
  { key: 'email', label: 'Email' },
  { key: 'telefono', label: 'Teléfono' },
  { key: 'direccion', label: 'Dirección' },
];

const COLS_PRODUCTO = [
    { key: 'id', label: 'Código' },
    { key: 'producto', label: 'Producto' },
    { key: 'existencia', label: 'Cantidad' },
    { key: 'linea', label: 'Línea' },
    { key: 'fecha_registro', label: 'F. Expedición' },
    { key: 'fecha_vencimiento', label: 'F.Vencimiento' },
    { key: 'precio', label: 'Precio' },
    { key: 'descuento', label: 'Descuento' },
];

// Campos: Datos Personales (Parte Superior del Modal)
const FIELDS_PERSONAL_INFO: FieldConfig[] = [
  { name: 'nombre', label: 'Nombre', type: 'text', class: 'md:col-span-1' },
  { name: 'apellido', label: 'Apellido', type: 'text', class: 'md:col-span-1' },
  { name: 'tipo_doc', label: 'Tipo', type: 'combobox', class: 'md:col-span-1', },
  { name: 'documento', label: 'Documento', type: 'text', class: 'md:col-span-1' },
  { name: 'email', label: 'Email', type: 'email', class: 'md:col-span-2' },
  
  { name: 'telefono', label: 'Teléfono', type: 'text', class: 'md:col-span-1' },
  { name: 'direccion', label: 'Dirección', type: 'text', class: 'md:col-span-2' },
];

// Campos: Ubicación (Parte Inferior del Modal)
const FIELDS_UBICACION: FieldConfig[] = [
  { name: 'pais', label: 'País', type: 'combobox', class: 'md:col-span-1'},
  { name: 'estado', label: 'Estado', type: 'combobox', class: 'md:col-span-1', },
  { name: 'ciudad', label: 'Ciudad', type: 'combobox', class: 'md:col-span-1', },
];

// ==========================================
// MAPA DE CONFIGURACIÓN PRINCIPAL
// ==========================================

export const configMap: Record<string, CrudConfig> = {
  
  // ------------------------------------------------
  // CLIENTES 
  // ------------------------------------------------
  clientes: {
    title: 'Agregar - Cliente',
    rolesQuePuedenEditar: ['SIIF', 'GRT', 'SUP', 'RFV'],
    routePrefix: 'clientes',
    breadcrumbs: [
        { label: 'SIIF', href: '/dashboard' },
        { label: 'Personas', href: '/personas' },
        { label: 'Clientes' }
    ],
    columns: COLS_PERSONA,
    fields: [
      ...FIELDS_PERSONAL_INFO,
      // Datos por Grupo: Especialidad, Clase, Ranking, Frecuencia (4 selects)
      { name: 'especialidad', label: 'Especialidad', type: 'combobox', class: 'md:col-span-1', options: [] },
      { name: 'clase', label: 'Clase', type: 'combobox', class: 'md:col-span-1', options: [] },
      { name: 'ranking', label: 'Ranking', type: 'combobox', class: 'md:col-span-1', options: [] },
      { name: 'frecuencia', label: 'Frecuencia', type: 'combobox', class: 'md:col-span-1', options: [] },
      { name: 'vendedor', label: 'Vendedor Asignado (RFV)', type: 'combobox', class: 'md:col-span-2', options: [] },
      ...FIELDS_UBICACION
    ]
  },

  // ------------------------------------------------
  // GERENTES 
  // ------------------------------------------------
  gerentes: {
    title: 'Agregar - Gerente',
    routePrefix: 'gerentes',
    breadcrumbs: [
        { label: 'SIIF', href: '/dashboard' },
        { label: 'Personas', href: '/personas' },
        { label: 'Gerentes' }
    ],
    columns: COLS_PERSONA,
    fields: [
      ...FIELDS_PERSONAL_INFO,
      // Datos por Grupo: Username y Password
      { name: 'username', label: 'Username', type: 'text', class: 'md:col-span-1' },
      { name: 'password', label: 'Password', type: 'password', class: 'md:col-span-2' },
      { name: 'supervisor', label: 'Supervisor', type: 'combobox', class: 'md:col-span-3', options: [] },
      ...FIELDS_UBICACION
    ]
  },

  // ------------------------------------------------
  // MAYORISTAS
  // ------------------------------------------------
  mayoristas: {
    title: 'Agregar - Mayorista',
    rolesQuePuedenEditar: ['SIIF', 'GRT', 'SUP'],
    routePrefix: 'mayoristas',
    breadcrumbs: [
        { label: 'SIIF', href: '/dashboard' },
        { label: 'Personas', href: '/personas' },
        { label: 'Mayoristas' }
    ],
    columns: COLS_PERSONA,
        fields: [
      ...FIELDS_PERSONAL_INFO,
      // Datos por Grupo: Descuento
      { name: 'descuento', label: 'Descuento', type: 'text', class: 'md:col-span-1' }, 
      ...FIELDS_UBICACION
    ]

  },

  // ------------------------------------------------
  // REPRESENTANTE FUERZA VENTA 
  // ------------------------------------------------
  representantes: {
    title: 'Agregar - Representante Fuerza Venta',
    routePrefix: 'representantes',
    breadcrumbs: [
        { label: 'SIIF', href: '/dashboard' },
        { label: 'Personas', href: '/personas' },
        { label: 'Representantes' }
    ],
    columns: COLS_PERSONA,
    fields: [
      ...FIELDS_PERSONAL_INFO,
      // Datos por Grupo: Username, Password, Ciclo, Supervisor
      { name: 'username', label: 'Username', type: 'text', class: 'md:col-span-1' },
      { name: 'password', label: 'Password', type: 'password', class: 'md:col-span-2' },
      { name: 'ciclo', label: 'Ciclo', type: 'combobox', class: 'md:col-span-1', options: [] },
      { name: 'supervisor', label: 'Supervisor', type: 'combobox', class: 'md:col-span-2', options: [] },
      ...FIELDS_UBICACION
    ]
  },

  // ------------------------------------------------
  // SUPERVISOR 
  // ------------------------------------------------
  supervisores: {
    title: 'Agregar - Supervisor',
    routePrefix: 'supervisores',
    breadcrumbs: [
        { label: 'SIIF', href: '/dashboard' },
        { label: 'Personas', href: '/personas' },
        { label: 'Supervisores' }
    ],
    columns: COLS_PERSONA,
    fields: [
      ...FIELDS_PERSONAL_INFO,
      // Datos por Grupo: Idéntico a RFV (según imagen)
      { name: 'username', label: 'Username', type: 'text', class: 'md:col-span-1' },
      { name: 'password', label: 'Password', type: 'password', class: 'md:col-span-2' },
      ...FIELDS_UBICACION
    ]
  },

  // ------------------------------------------------
  // EMPRESAS
  // ------------------------------------------------

  empresas: {
    title: 'Administrar Empresas (Fabricantes)',
    rolesQuePuedenEditar: ['SIIF'], // Solo el Super Administrador
    routePrefix: 'empresas',
    breadcrumbs: [
        { label: 'SIIF', href: '/dashboard' },
        { label: 'Configuración' },
        { label: 'Empresas' }
    ],
    columns: COLS_PERSONA,
      fields: [
        { name: 'nombre', label: 'Razón Social / Nombre', type: 'text', class: 'md:col-span-2' },
        // Reemplazamos 'apellido' por 'idOperador' solo para este módulo
        { name: 'idOperador', label: 'Código de Operador', type: 'text', class: 'md:col-span-1' },
        { name: 'idFabricante', label: 'Código de Fabricante', type: 'text', class: 'md:col-span-2' },
        { name: 'documento', label: 'RIF / Documento', type: 'text', class: 'md:col-span-1' },
        { name: 'email', label: 'Correo Electrónico', type: 'email', class: 'md:col-span-2' },
        { name: 'telefono', label: 'Teléfono', type: 'text', class: 'md:col-span-1' },
        { name: 'direccion', label: 'Dirección Fiscal', type: 'text', class: 'md:col-span-3' },
        ...FIELDS_UBICACION
      ]
  },
  // ==========================
  // GRUPO: PRODUCTOS
  // ==========================

  // ------------------------------------------------
  // MUESTRAS
  // ------------------------------------------------
  muestras: {
    title: 'Agregar Muestra',
    rolesQuePuedenEditar: ['SIIF', 'GRT', 'SUP'],
    routePrefix: 'muestras',
    breadcrumbs: [
        { label: 'SIIF', href: '/dashboard' },
        { label: 'Productos', href: '/productos' },
        { label: 'Muestras' }
    ],
    columns: COLS_PRODUCTO,
    fields: [
      // Fila 1
      { name: 'id', label: 'Código', type: 'text', class: 'md:col-span-1' },
      { name: 'producto', label: 'Producto', type: 'text', class: 'md:col-span-1' },
      { name: 'existencia', label: 'Cantidad', type: 'number', class: 'md:col-span-1' },
      { name: 'linea', label: 'Linea', type: 'combobox', class: 'md:col-span-1', options: [] },
      
      // Fila 2
      { name: 'tipo_producto', label: 'Tipo de producto', type: 'combobox', class: 'md:col-span-1', options: [] },
      { name: 'mayorista', label: 'Mayorista', type: 'combobox', class: 'md:col-span-1', options: [] },
      { name: 'fecha_registro', label: 'Fecha Expedicion', type: 'date', class: 'md:col-span-1' }, // Si usas un datepicker, type='date'
      { name: 'fecha_vencimiento', label: 'Fecha Vencimiento', type: 'date', class: 'md:col-span-1' },

      // Fila 3
      { name: 'presentacion', label: 'Presententación', type: 'text', class: 'md:col-span-1' },
      { name: 'lote', label: 'Lote', type: 'text', class: 'md:col-span-1' },
    ]
  },

  // ------------------------------------------------
  // PRODUCTOS 
  // ------------------------------------------------
  productos: {
    title: 'Agregar Producto',
    rolesQuePuedenEditar: ['SIIF', 'GRT', 'SUP'],
    routePrefix: 'productos-lista',
    breadcrumbs: [
        { label: 'SIIF', href: '/dashboard' },
        { label: 'Productos', href: '/productos' },
        { label: 'Lista de Productos' }
    ],
    columns: COLS_PRODUCTO,
    fields: [
      // Fila 1
      { name: 'id', label: 'Código', type: 'text', class: 'md:col-span-1' },
      { name: 'producto', label: 'Producto', type: 'text', class: 'md:col-span-1' },
      { name: 'existencia', label: 'Cantidad', type: 'number', class: 'md:col-span-1' },
      { name: 'linea', label: 'Linea', type: 'combobox', class: 'md:col-span-1', options: [] },
      
      // Fila 2
      { name: 'tipo_producto', label: 'Tipo de producto', type: 'combobox', class: 'md:col-span-1', options: [] },
      { name: 'mayorista', label: 'Mayorista', type: 'combobox', class: 'md:col-span-1', options: [] },
      { name: 'fecha_registro', label: 'Fecha Expedicion', type: 'date', class: 'md:col-span-1' },
      { name: 'fecha_vencimiento', label: 'Fecha Vencimiento', type: 'date', class: 'md:col-span-1' },

      // Fila 3
      { name: 'precio', label: 'Precio', type: 'text', class: 'md:col-span-1' },
      { name: 'presentacion', label: 'Presententación', type: 'text', class: 'md:col-span-1' },
      { name: 'descuento', label: 'Descuento', type: 'text', class: 'md:col-span-1' },
      { name: 'lote', label: 'Lote', type: 'text', class: 'md:col-span-1' },
    ]
  },

  // ------------------------------------------------
  // LINEA-PRODUCTOS 
  // ------------------------------------------------

  lineas_productos: {
    title: 'Líneas de Productos',
    rolesQuePuedenEditar: ['SIIF', 'GRT', 'SUP'],
    routePrefix: 'lineas',
    breadcrumbs: [
      { label: 'SIIF', href: '/dashboard' },
      { label: 'Productos', href: '/productos' },
      { label: 'Líneas de Productos' }
    ],
    columns: [
      { key: 'id', label: 'ID' },
      { key: 'descripcion_linea_producto', label: 'Descripción de la Línea' },
    ],
    fields: [
      { name: 'id', label: 'ID', type: 'text', class: 'md:col-span-1' },
      { name: 'descripcion_linea_producto', label: 'Descripción de la Línea', type: 'text', class: 'md:col-span-2' },
    ]
  },

  // ------------------------------------------------
  // TIPO-PRODUCTOS 
  // ------------------------------------------------

  tipos_productos: {
    title: 'Tipos de Productos',
    rolesQuePuedenEditar: ['SIIF', 'GRT', 'SUP'],
    routePrefix: 'tipos',
    breadcrumbs: [
      { label: 'SIIF', href: '/dashboard' },
      { label: 'Productos', href: '/productos' },
      { label: 'Tipos de Productos' }
    ],
    columns: [
      { key: 'idtipo_producto', label: 'Código ID' },
      { key: 'descripcion_tipo_producto', label: 'Descripción' },
    ],
    fields: [
      { name: 'descripcion_tipo_producto', label: 'Descripción del Tipo', type: 'text', class: 'md:col-span-3' },
    ]
  }
}