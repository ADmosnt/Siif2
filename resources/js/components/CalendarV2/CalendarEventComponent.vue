<!-- resources/js/components/CalendarV2/CalendarEventComponent.vue -->
<template>
  <!--
    COMPONENTE: Evento de Calendario Individual
    PROPÓSITO: Representar visualmente una visita en la vista mensual del calendario
    CARACTERÍSTICAS:
    - Visita arrastrable (drag & drop)
    - Muestra nombre del cliente en lugar del título
    - Soporta temas claro/oscuro
    - Colores de estado (rojo/verde/amarillo)
    - Slot personalizable para contenido
  -->
  <div
    :key="visita.id"
    :data-visita-id="visita.id"
    role="button"
    aria-label="Visita de calendario"
    :aria-describedby="`visita-${visita.id}-description`"
    :class="[
      'text-sm p-2 rounded cursor-move absolute truncate group event-transition focus:outline-none focus:ring-2 focus:ring-primary-500',
      isDark ? 'hover:bg-white/15' : 'hover:shadow-sm',
      'mt-2 z-10',
      getVisitaBgClass(visita.id),
      `border-l-4 ${getVisitaBorderClass(visita.id)}`,
      (isDark ? 'text-gray-100' : 'text-gray-800'),
      customClasses?.eventContainer,
      $attrs.class
    ]"

    draggable="true"
    @click="handleClick($event)"
    @dragstart="onDragStart"
    @dragend="onDragEnd"
  >
    <!-- Slot personalizable - Contenido por defecto si no se provee -->
    <slot :visita="visita">
      <!-- Contenido por defecto de la visita -->
      <div :class="['font-medium', isDark ? 'text-gray-100' : 'text-gray-900', customClasses?.eventTitle]">
        {{ getClientName() }}
      </div>
      <div :class="['text-xs', isDark ? 'text-gray-300' : 'text-gray-600', customClasses?.eventTime]">
        {{ formatVisitaTime() }}
      </div>
      <!-- Información de RFV - Solo se muestra si está disponible -->
      <div v-if="getRfvName()" :class="['text-xs mt-1', isDark ? 'text-gray-300' : 'text-gray-500']">
        {{ getRfvName() }}
      </div>
    </slot>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { type Visita } from "@/stores/calendarStore";
import { useTimezone } from "@/composables/useTimezone";
import { debug } from "@/utils/debug";

// =============================================================================
// INTERFACES Y TIPOS
// =============================================================================

interface SelectOption {
  value: string;
  label: string;
}

/**
 * Props del componente CalendarEventComponent
 * 
 * ✅ CORREGIDO: Usa Visita en lugar de CalendarEvent
 */
interface EventComponentProps {
  /**
   * Datos de la visita a mostrar
   * @required
   */
  visita: Visita;

  /**
   * Clases CSS personalizadas para diferentes partes del componente
   */
  customClasses?: {
    eventContainer?: string;
    eventTitle?: string;
    eventTime?: string;
  };

  /**
   * Estilos inline personalizados
   */
  customStyles?: {
    eventContainer?: Record<string, string>;
  };

  /**
   * Formato de hora (12h o 24h)
   * @default '24h'
   */
  timeFormat?: "12h" | "24h";

  /**
   * Opciones de clientes para mostrar nombres en lugar de IDs
   */
  clienteOptions?: SelectOption[];

  /**
   * Opciones de RFV para mostrar nombres en lugar de IDs
   */
  rfvOptions?: SelectOption[];
}

/**
 * Eventos emitidos por el componente
 */
interface EventComponentEmits {
  /**
   * Se emite cuando se hace click en la visita
   * @param visita - La visita clickeada
   */
  (e: "click", visita: Visita): void;
}

// =============================================================================
// CONSTANTES Y CONFIGURACIÓN
// =============================================================================

/**
 * Mapa de colores para bordes en modo claro y oscuro
 */
const BORDER_COLOR_MAP: Record<string, { light: string; dark: string }> = {
  red: { light: "border-l-red-500", dark: "border-l-red-400" },
  green: { light: "border-l-green-500", dark: "border-l-green-400" },
  yellow: { light: "border-l-yellow-500", dark: "border-l-yellow-400" },
};

/**
 * Colores de fondo para modo claro
 */
const BG_LIGHT: Record<string, string> = {
  red: "bg-red-100",
  green: "bg-green-100",
  yellow: "bg-yellow-100",
};

/**
 * Colores de fondo para modo oscuro
 */
const BG_DARK: Record<string, string> = {
  red: "bg-red-800",
  green: "bg-green-800",
  yellow: "bg-yellow-800",
};

// =============================================================================
// PROPS, EMITS Y COMPUTED
// =============================================================================

const props = withDefaults(defineProps<EventComponentProps>(), {
  timeFormat: "24h",
  clienteOptions: () => [],
  rfvOptions: () => []
});

const emit = defineEmits<EventComponentEmits>();

/**
 * Determina si el tema actual es oscuro
 */
const isDark = computed(() =>
  document.documentElement.classList.contains("dark")
);

const { formatTime, formatTime12 } = useTimezone();

// =============================================================================
// FUNCIONES DE AYUDA - APARIENCIA
// =============================================================================

/**
 * ✅ CORREGIDO: Renombrado a getVisitaBgClass y usa visita en lugar de event
 */
function getVisitaBgClass(id: string) {
  // Prioridad 1: Usar color definido en la visita
  if (props.visita.tailwindColor) {
    const color = props.visita.tailwindColor as 'red' | 'green' | 'yellow';
    return isDark.value ? (BG_DARK[color] ?? "bg-slate-800")
                        : (BG_LIGHT[color] ?? "bg-slate-100");
  }

  // Prioridad 2: Generar color consistente basado en hash del ID
  const availableColors = Object.values(BG_LIGHT);
  let hash = 0;
  for (let i = 0; i < id.length; i++) hash = ((hash << 5) - hash + id.charCodeAt(i)) & 0xffffffff;
  const idx = Math.abs(hash) % availableColors.length;

  const colorKeys = Object.keys(BG_LIGHT) as ('red' | 'green' | 'yellow')[];
  const randomColor = colorKeys[idx];

  return isDark.value ? BG_DARK[randomColor] : BG_LIGHT[randomColor];
}

/**
 * ✅ CORREGIDO: Renombrado a getVisitaBorderClass y usa visita en lugar de event
 */
function getVisitaBorderClass(id: string) {
  // Prioridad 1: Usar color definido en la visita
  if (props.visita.tailwindColor) {
    const color = props.visita.tailwindColor as 'red' | 'green' | 'yellow';
    const colorMap = BORDER_COLOR_MAP[color];
    return colorMap ? (isDark.value ? colorMap.dark : colorMap.light) : "border-l-gray-500";
  }

  // Prioridad 2: Generar color consistente basado en hash del ID
  const availableBorders = Object.values(BORDER_COLOR_MAP);
  let hash = 0;
  for (let i = 0; i < id.length; i++) hash = ((hash << 5) - hash + id.charCodeAt(i)) & 0xffffffff;
  const idx = Math.abs(hash) % availableBorders.length;
  const colorMap = availableBorders[idx];

  return isDark.value ? colorMap.dark : colorMap.light;
}

// =============================================================================
// FUNCIONES DE AYUDA - DATOS
// =============================================================================

/**
 * ✅ CORREGIDO: Usa cliente_id en lugar de clienteId
 */
const getClientName = () => {
  const cliente_id = props.visita.metadata?.cliente_id;
  if (!cliente_id) return props.visita.title || 'Sin cliente';
  
  const cliente = props.clienteOptions.find(c => c.value === cliente_id);
  return cliente ? cliente.label : props.visita.title || 'Cliente no encontrado';
};

/**
 * ✅ CORREGIDO: Usa rfv_id en lugar de rfvId
 */
const getRfvName = () => {
  const rfv_id = props.visita.metadata?.rfv_id;
  if (!rfv_id) return '';
  
  const rfv = props.rfvOptions.find(r => r.value === rfv_id);
  return rfv ? rfv.label : '';
};

/**
 * ✅ CORREGIDO: Renombrado a formatVisitaTime y usa visita en lugar de event
 */
const formatVisitaTime = () => {
  try {
    const startTime =
      props.timeFormat === "12h"
        ? formatTime12(props.visita.start)
        : formatTime(props.visita.start);

    const endTime =
      props.timeFormat === "12h"
        ? formatTime12(props.visita.end)
        : formatTime(props.visita.end);

    return `${startTime} - ${endTime}`;
  } catch (error) {
    debug.error("Time formatting failed:", error);
    return "--:-- - --:--";
  }
};

// =============================================================================
// MANEJADORES DE EVENTOS
// =============================================================================

/**
 * ✅ CORREGIDO: Emite visita en lugar de event
 */
const handleClick = (event?: Event) => {
  if (event) {
    event.stopPropagation();
  }

  debug.log('Visita: Clicked in month view', {
    visitaId: props.visita.id,
    cliente_id: props.visita.metadata?.cliente_id,
    rfv_id: props.visita.metadata?.rfv_id
  });
  emit('click', props.visita);
};

/**
 * ✅ CORREGIDO: Usa visita en lugar de event
 */
function onDragStart(e: DragEvent) {
  debug.log('DRAG START', {
    visitaId: props.visita.id,
    visita: props.visita,
    e
  });
  e.dataTransfer?.setData('text/plain', props.visita.id);
  e.dataTransfer?.setData('application/visita-id', props.visita.id);
  e.dataTransfer!.effectAllowed = 'move';
}

/**
 * ✅ CORREGIDO: Usa visita en lugar de event
 */
function onDragEnd(e: DragEvent) {
  debug.log('DRAG END', {
    visitaId: props.visita.id,
    visita: props.visita,
    e
  });
}
</script>