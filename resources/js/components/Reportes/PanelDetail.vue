<template>
  <div class="flex-1 overflow-y-auto px-4 py-2">
    <!-- Template para múltiples actividades de un cliente -->
    <div v-if="detailTemplate === 'actividades-cliente'">
      <div v-if="items.length" class="space-y-6">
        <div
          v-for="actividad in items"
          :key="actividad.id"
          class="border-b border-gray-200 dark:border-gray-600 pb-6 last:border-b-0"
        >
          <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
            Actividad: {{ actividad.idreporte }}
          </h4>
          
          <div class="grid grid-cols-1 gap-3">
            <p class="text-gray-700 dark:text-gray-300">
              <strong class="text-gray-900 dark:text-white">Cliente:</strong> 
              {{ actividad.idCliente }}
            </p>
            <p class="text-gray-700 dark:text-gray-300">
              <strong class="text-gray-900 dark:text-white">Fecha:</strong> 
              {{ formatDateTime(actividad.fecha_actividad) }}
            </p>
            <p class="text-gray-700 dark:text-gray-300">
              <strong class="text-gray-900 dark:text-white">Observaciones:</strong> 
              {{ actividad.observaciones_cliente }}
            </p>
            <p v-if="actividad.rfv" class="text-gray-700 dark:text-gray-300">
              <strong class="text-gray-900 dark:text-white">RFV:</strong> 
              {{ actividad.rfv }}
            </p>
          </div>
          
          <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <p class="text-gray-700 dark:text-gray-300 mb-3">
              <strong class="text-gray-900 dark:text-white">Firma del cliente:</strong>
            </p>
            <div v-if="actividad.Firma_cliente" class="firma-container">
              <img
                :src="actividad.Firma_cliente"
                alt="Firma del cliente"
                class="firma-image"
              />
            </div>
            <p v-else class="text-gray-500 dark:text-gray-400 text-sm">
              Sin firma.
            </p>
          </div>
        </div>
      </div>
      <div v-else class="text-center mt-10 text-gray-400 dark:text-gray-500">
        No hay actividades para mostrar.
      </div>
    </div>

    <!-- Template para detalle de orden -->
    <div v-else-if="detailTemplate === 'orden-detalle' && ordenItem">
      <div class="space-y-4">
        <!-- Información de la empresa, mayorista y descuento -->
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
          <h5 class="font-bold text-gray-900 dark:text-white mb-2 text-center">
            EMPRESA - MAYORISTA - DESCUENTO
          </h5>
          <table class="w-full">
            <thead>
              <tr class="bg-gray-100 dark:bg-gray-700">
                <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">EMPRESA</th>
                <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">MAYORISTA</th>
                <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">DESCUENTO</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="py-2 px-3 border-t border-gray-200 dark:border-gray-600 align-top">
                  {{ ordenItem.cliente }}
                </td>
                <td class="py-2 px-3 border-t border-gray-200 dark:border-gray-600 align-top">
                  <div v-if="ordenItem.mayoristas && ordenItem.mayoristas.length > 0">
                    <div v-for="(mayorista, index) in ordenItem.mayoristas" :key="mayorista.id" 
                         :class="{'mt-1': index > 0}">
                      {{ mayorista.nombre }}
                    </div>
                  </div>
                  <div v-else class="text-gray-500 dark:text-gray-400">
                    Sin mayorista
                  </div>
                </td>
                <td class="py-2 px-3 border-t border-gray-200 dark:border-gray-600 align-top text-right">
                  <div v-if="ordenItem.mayoristas && ordenItem.mayoristas.length > 0">
                    <div v-for="(mayorista, index) in ordenItem.mayoristas" :key="mayorista.id"
                         :class="{'mt-1': index > 0}">
                      {{ formatNumber(mayorista.descuento) }}
                    </div>
                  </div>
                  <div v-else class="text-gray-500 dark:text-gray-400">
                    -
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Datos de conciliación - SOLO si está facturada -->
        <div v-if="ordenItem.estaFacturada" class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
          <h5 class="font-bold text-gray-900 dark:text-white mb-2 text-center">
            Datos de conciliación
          </h5>
          <table class="w-full">
            <thead>
              <tr class="bg-gray-100 dark:bg-gray-700">
                <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">NÚMERO DE FACTURA</th>
                <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">FECHA DE FACTURACIÓN</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="py-2 px-3 border-t border-gray-200 dark:border-gray-600">
                  {{ ordenItem.factura?.idfactura || 'No facturado' }}
                </td>
                <td class="py-2 px-3 border-t border-gray-200 dark:border-gray-600">
                  {{ ordenItem.factura?.fechaFactura || 'N/A' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Información de la orden -->
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-100 dark:bg-gray-700">
                <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">NUMERO DE ORDEN</th>
                <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">FECHA</th>
                <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">ESTATUS</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="py-2 px-3 border-t border-gray-200 dark:border-gray-600 font-bold">
                  {{ ordenItem.nOrden }}
                </td>
                <td class="py-2 px-3 border-t border-gray-200 dark:border-gray-600">
                  {{ ordenItem.fecha }}
                </td>
                <td class="py-2 px-3 border-t border-gray-200 dark:border-gray-600">
                  <span :class="getStatusClass(ordenItem.estatus)" 
                        class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ ordenItem.estatus }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Detalle de productos -->
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
          <h5 class="font-bold text-gray-900 dark:text-white mb-2 text-center">
            Productos
          </h5>
          <div class="overflow-x-auto">
            <table class="w-full min-w-full">
              <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                  <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">CODIGO</th>
                  <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">DESCRIPCION</th>
                  <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">DESC.</th>
                  <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">CANT.</th>
                  <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">PRECIO</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="producto in ordenItem.productos" :key="producto.id" 
                    class="border-t border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                  <td class="py-2 px-3 align-top">
                    {{ producto.id }}
                  </td>
                  <td class="py-2 px-3 align-top">
                    {{ producto.nombre }}
                  </td>
                  <td class="py-2 px-3 align-top text-right">
                    {{ formatNumber(producto.descuento) }}
                  </td>
                  <td class="py-2 px-3 align-top text-right">
                    {{ formatNumber(producto.cantidad_mostrar) }}
                  </td>
                  <td class="py-2 px-3 align-top text-right">
                    {{ formatCurrency(Number(producto.precio)) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Resumen y Total -->
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-100 dark:bg-gray-700">
                <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">COMENTARIO</th>
                <th class="py-2 px-3 text-left text-sm font-semibold text-gray-900 dark:text-white">RESUMEN</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="py-2 px-3 border-t border-gray-200 dark:border-gray-600 align-top w-1/2">
                  <div class="max-h-32 overflow-y-auto">
                    {{ ordenItem.comentario }}
                  </div>
                </td>
                <td class="py-2 px-3 border-t border-gray-200 dark:border-gray-600 align-top w-1/2">
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-gray-700 dark:text-gray-300">Impuesto:</span>
                      <span class="font-medium">{{ formatCurrency(Number(ordenItem.impuesto)) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-300 dark:border-gray-600 pt-2 mt-2">
                      <span class="text-lg font-bold text-gray-900 dark:text-white">Total:</span>
                      <span class="text-lg font-bold text-green-600 dark:text-green-400">
                        {{ formatCurrency(Number(ordenItem.total)) }}
                      </span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {{ formatNumber(ordenItem.cantidad_unidades) }} unidades
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Información adicional - Solo ubicación del cliente con coordenadas -->
        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
          <h5 class="font-bold text-gray-900 dark:text-white mb-2 text-center">
            Información Adicional
          </h5>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">RFV/Representante:</p>
              <p class="font-medium">{{ ordenItem.persona }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">Ubicación del Cliente:</p>
              <p class="font-medium">{{ ordenItem.ubicacion_cliente?.estado }}, {{ ordenItem.ubicacion_cliente?.ciudad }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Coordenadas: {{ ordenItem.coordenadas?.latitud }}, {{ ordenItem.coordenadas?.longitud }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Template por defecto para item individual -->
    <div v-else-if="item" class="space-y-4">
      <div class="border-b border-gray-200 dark:border-gray-600 pb-4">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
          {{ item.nombre || 'Detalles' }}
        </h4>
        
        <div class="grid grid-cols-1 gap-2">
          <p v-if="item.fecha" class="text-gray-700 dark:text-gray-300">
            <strong class="text-gray-900 dark:text-white">Fecha:</strong> {{ item.fecha }}
          </p>
          <p v-if="item.rfv" class="text-gray-700 dark:text-gray-300">
            <strong class="text-gray-900 dark:text-white">RFV:</strong> {{ item.rfv }}
          </p>
          <p v-if="item.hora" class="text-gray-700 dark:text-gray-300">
            <strong class="text-gray-900 dark:text-white">Hora:</strong> {{ item.hora }}
          </p>
        </div>
        
        <div class="mt-4 text-right">
          <button
            class="text-xs font-bold text-green-600 border border-green-500 px-3 py-1 rounded hover:bg-green-100 dark:hover:bg-gray-700 transition-colors"
          >
            PROCESAR
          </button>
        </div>
      </div>
    </div>
    
    <!-- Estado vacío -->
    <div v-else class="text-center mt-10 text-gray-400 dark:text-gray-500">
      {{ emptyMessage }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { PanelItem } from './PanelDual.vue'
import type { Orden } from '@/services/pedidoService'

// ÚNICA declaración de Props
interface Props {
  item?: PanelItem | null
  items?: PanelItem[]
  detailTemplate?: string
  emptyMessage?: string
}

const props = withDefaults(defineProps<Props>(), {
  item: null,
  items: () => [],
  emptyMessage: 'Seleccione un elemento para ver detalles.',
  detailTemplate: 'default'
})

// Computed para asegurar el tipo correcto
const ordenItem = computed(() => props.item as Orden | null)

// Función para formatear moneda
const formatCurrency = (value: number): string => {
  if (isNaN(value) || !isFinite(value)) {
    return '$0.00'
  }
  return new Intl.NumberFormat('es-VE', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(value)
}

// Función para formatear números
const formatNumber = (value: string | number): string => {
  const num = Number(value)
  if (isNaN(num) || !isFinite(num)) {
    return '0'
  }
  return num.toLocaleString('es-VE')
}

// Función para obtener clase CSS según estatus
const getStatusClass = (estatus: string | number): string => {
  const statusStr = String(estatus).toLowerCase()
  if (statusStr.includes('complet') || statusStr.includes('finaliz')) {
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
  }
  if (statusStr.includes('pendiente')) {
    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
  }
  if (statusStr.includes('proceso') || statusStr.includes('enviado')) {
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
  }
  if (statusStr.includes('cancel') || statusStr.includes('anulad')) {
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }
  return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
}

// Función para formatear fecha
const formatDateTime = (dateString: string) => {
  if (!dateString) return 'N/A'
  try {
    const date = new Date(dateString)
    return date.toLocaleString('es-ES', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return 'Fecha inválida'
  }
}
</script>

<style scoped>
/* Estilos específicos para el detalle de órdenes */
.table-responsive {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

/* Asegurar que las tablas sean responsivas */
@media (max-width: 768px) {
  .table-responsive table {
    min-width: 600px;
  }
}

/* Estilos para firma */
.firma-container {
  max-width: 300px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px;
  background: white;
}

.firma-image {
  width: 100%;
  height: auto;
  display: block;
}

.dark .firma-container {
  border-color: #374151;
  background: white;
}
</style>