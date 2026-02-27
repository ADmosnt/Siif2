export interface Empresa {
  id: string | number;
  nombre: string;
}

export interface FormaPago {
  id: number | string;
  nombre: string;
}

export interface Producto {
  id: string;
  codigo: string;
  producto: string;
  cantidad: number;
  precio: number;
  precio_total: number;
  descuento: number;
  conciliada: number;
  faltante: number;
}

// Orden básica para el select (sin productos)
export interface OrdenSelect {
  id: string | number;
  nombre: string;
  cliente: string;
  fecha: string;
  total: number;
}

// Información completa de una orden (para cuando se selecciona)
export interface OrdenInfo {
  id: string | number;
  cliente: { id: string; nombre: string };
  rfv: { id: string; nombre: string };
  mayorista: { id: string; nombre: string };
  total: number;
  fecha: string;
  registradoPor?: string;
  impuesto: number;
  costoTotal: number;
}

export interface TableRow {
  codigo: string;
  producto: string;
  cantidad: number;
  productoKit: number;
  costo: number;
  descuento: number;
  despachadas: number;
  faltantes: number;
  accion: string;
}

export interface SummaryItem {
  label: string;
  value: string | number;
}

