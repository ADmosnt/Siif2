// resources/js/types/pagination.ts
// Interfaz para la información de meta de la paginación
export interface PaginationMeta {
  current_page: number;
  from?: number;        
  last_page: number;
  links: PaginationLink[];
  path?: string;        
  per_page: number;
  to?: number;          
  total: number;
}

// Interfaz para los enlaces de la paginación
export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

// Interfaz principal para los datos paginados
export interface PaginatedData<T> {
  data: T[];
  links: PaginationLink[];
  meta: PaginationMeta;
}