import UsersIcon from '@/components/icons/UsersIcon.vue'
import CubeIcon from '@/components/icons/CubeIcon.vue'

// 1. Definimos la interfaz correcta para los breadcrumbs
interface BreadcrumbItem {
  label: string;
  href?: string; // Href es opcional (el último item no suele llevar link)
}

export interface MenuAction {
  label: string;
  to: string;
  icon: any;
  bgColor?: string;
  footerColor?: string;
}

export interface MenuConfig {
  title: string;
  breadcrumbs: BreadcrumbItem[]; // <--- Usamos la interfaz
  actions: MenuAction[];
}

export const menuMap: Record<string, MenuConfig> = {
  
  // --- PERSONAS ---
  personas: {
    title: 'Administrar Personas',
    // AQUÍ DEFINIMOS LA RUTA EXACTA QUE QUIERES
    breadcrumbs: [
      { label: 'SIIF', href: '/dashboard' }, // Link al home
      { label: 'Personas' } // Página actual (sin link)
    ],
    actions: [
      { label: 'Cliente', to: '/clientes', icon: UsersIcon, bgColor: '#4c7fdd', footerColor: '#2c5cb3' },
      { label: 'Gerente', to: '/gerentes', icon: UsersIcon, bgColor: '#4c7fdd', footerColor: '#2c5cb3' },
      { label: 'Mayorista', to: '/mayoristas', icon: UsersIcon, bgColor: '#4c7fdd', footerColor: '#2c5cb3' },
      { label: 'Representante Fuerza Venta', to: '/representantes', icon: UsersIcon, bgColor: '#4c7fdd', footerColor: '#2c5cb3' },
      { label: 'Supervisor', to: '/supervisores', icon: UsersIcon, bgColor: '#4c7fdd', footerColor: '#2c5cb3' },
    ]
  },

  // --- PRODUCTOS ---
  productos: {
    title: 'Administrar Productos',
    breadcrumbs: [
      { label: 'SIIF', href: '/dashboard' },
      { label: 'Productos' }
    ],
    actions: [
      { label: 'Muestras', to: '/muestras', icon: CubeIcon, bgColor: '#34c759', footerColor: '#28a745' },
      { label: 'Productos', to: '/productos-lista', icon: CubeIcon, bgColor: '#34c759', footerColor: '#28a745' },
      { label: 'Linea-Producto', to: '/lineas', icon: CubeIcon, bgColor: '#34c759', footerColor: '#28a745' },
      { label: 'Tipo-Producto', to: '/tipos', icon: CubeIcon, bgColor: '#34c759', footerColor: '#28a745' },
    ]
  }
}