// resources/js/types/agenda.ts
import type { PaginatedData, PaginationLink } from './pagination'
import type { PageProps } from '@inertiajs/core'

export interface DiaVisita {
  id: number
  descripcion: string
}

export interface Horario {
  id: number
  descripcion: string
}

export interface ClienteAgenda {
  id: string
  idRFV: string
  nombre: string
  ranking: string
  actividad: string
  frecuencia: string
  visitado: number
  dias_visita: DiaVisita[]
  horarios: Horario[]
}

export interface EstadisticasAgenda {
  ciclo: number
  dhabiles: number | null
  visitados: number
  cobertura: number
  visitasDiarias: number | null
}

// ✅ Interfaz para SelectOption compatible con GenericCombobox
export interface SelectOption {
  value: string
  label: string
}

// ✅ Representante adaptado para SelectOption
export interface RepresentanteSelect extends SelectOption {
  idFabricante?: string
  empresa?: string
}

// ✅ Interfaz principal con índice de firma para cumplir con PageProps
export interface AgendaPageProps extends PageProps {
  representantes: Array<{
    id: string
    nombre: string
    idFabricante?: string
    empresa?: string
  }>
  clientes: PaginatedData<ClienteAgenda>
  estadisticas: EstadisticasAgenda
  filtros: {
    search?: string
    size?: string
    page?: number
    idRfv?: string
    idCliente?: string
  }
  auth: {
    user: {
      idPersona: string
      idgrupo_persona: string // 'RFV', 'GRT', 'SUP', 'SIIF'
      idFabricante?: string
      nombre_completo_razon_social: string
    }
  }
  // ✅ Firma de índice para cumplir con PageProps de Inertia
  [key: string]: any
}