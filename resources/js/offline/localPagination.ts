import type { PaginatedData, PaginationMeta } from '@/types/pagination'

/**
 * Pagina en el cliente un arreglo completo leido de IndexedDB, imitando la
 * forma PaginatedData<T> que normalmente entrega Laravel/Inertia, para que
 * los componentes (TablesTdp, AddProducto_modal, etc.) no necesiten saber
 * si los datos vienen del servidor o del cache offline.
 */
export function paginateLocal<T>(items: T[], page: number, pageSize: number): PaginatedData<T> {
  const total = items.length
  const lastPage = Math.max(1, Math.ceil(total / pageSize))
  const currentPage = Math.min(Math.max(1, page), lastPage)
  const start = (currentPage - 1) * pageSize
  const data = items.slice(start, start + pageSize)

  const meta: PaginationMeta = {
    current_page: currentPage,
    last_page: lastPage,
    per_page: pageSize,
    total,
    links: [],
  }

  return { data, links: [], meta }
}

export function matchesSearch(term: string, ...fields: (string | number)[]): boolean {
  if (!term) return true
  const lower = term.toLowerCase()
  return fields.some(f => String(f).toLowerCase().includes(lower))
}
