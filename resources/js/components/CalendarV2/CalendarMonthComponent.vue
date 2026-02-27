<!-- resources/js/components/CalendarV2/CalendarMonthComponent.vue -->
<script setup lang="ts">
import { computed } from "vue";
import { useVisitasStore, type Visita } from "@/stores/calendarStore";
import CalendarEventComponent from "./CalendarEventComponent.vue";
import { debug } from "@/utils/debug";

// =============================================================================
// INTERFACES Y TIPOS
// =============================================================================

interface SelectOption {
  value: string;
  label: string;
}

interface MonthComponentProps {
  currentDate: Date;
  locale?: string;
  firstDayOfWeek?: number;
  eventFilter?: (e: Visita) => boolean;
  clienteOptions?: SelectOption[];
  rfvOptions?: SelectOption[];
  isLoading?: boolean;
}

const props = withDefaults(defineProps<MonthComponentProps>(), {
  firstDayOfWeek: 0,
  locale: 'es',
  clienteOptions: () => [],
  rfvOptions: () => [],
  isLoading: false
});

interface MonthComponentEmits {
  (e: "open-day-list", date: Date): void;
  (e: "date-clicked", date: Date): void;
  (e: "eventClick", event: Visita): void;
}

const emit = defineEmits<MonthComponentEmits>();

// =============================================================================
// STORE Y DATOS
// =============================================================================

const visitasStore = useVisitasStore();
const allEvents = computed(() => visitasStore.visitasDelMes);

// =============================================================================
// FUNCIONES DE AYUDA
// =============================================================================

function sameDay(a: Date, b: Date) {
  return a.getFullYear() === b.getFullYear() &&
         a.getMonth()    === b.getMonth() &&
         a.getDate()     === b.getDate();
}

const getEventsForDate = (date: Date) => {
  const base = allEvents.value.filter(e => sameDay(new Date(e.start), date));
  const filtered = props.eventFilter ? base.filter(props.eventFilter) : base;
  
  filtered.forEach(visita => console.log(' - Visita:', visita.id, visita.title));
  
  return filtered;
};

const getStackedEvents = (date: Date) => {
  const events = getEventsForDate(date);
  return events
    .map(e => ({ ...e, width: 100, left: 0, marginLeft: 0 }))
    .sort((a, b) => new Date(a.start).getTime() - new Date(b.start).getTime());
};

const getCellSummary = (date: Date) => {
  const events = getStackedEvents(date);
  return {
    first: events[0] ?? null,
    extra: Math.max(0, events.length - 1)
  };
};

// =============================================================================
// COMPUTED - CONFIGURACIÓN VISUAL
// =============================================================================

const days = computed(() => {
  const base = Array.from({ length: 7 }).map((_, i) => {
    const d = new Date(2023, 0, 1 + i);
    return new Intl.DateTimeFormat(props.locale, { weekday: 'short' }).format(d);
  });
  return [...base.slice(props.firstDayOfWeek), ...base.slice(0, props.firstDayOfWeek)];
});

const visibleDates = computed<Date[]>(() => {
  try {
    const start = new Date(
      props.currentDate.getFullYear(),
      props.currentDate.getMonth(),
      1
    );
    
    start.setDate(
      start.getDate() - ((start.getDay() - props.firstDayOfWeek + 7) % 7)
    );

    const end = new Date(start);
    end.setDate(end.getDate() + 41);

    const dates: Date[] = [];
    const date = new Date(start);
    while (date <= end) {
      const newDate = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0, 0);
      dates.push(newDate);
      date.setDate(date.getDate() + 1);
    }
    return dates;
  } catch (error) {
    console.error("Error generating visible dates:", error);
    return [];
  }
});

// =============================================================================
// MANEJADORES DE EVENTOS - CORREGIDOS
// =============================================================================

function openDayList(date: Date) {
  emit('open-day-list', date);
}

const isToday = (date: Date) => {
  const today = new Date();
  return date.toDateString() === today.toDateString();
};

/**
 * ✅ CORREGIDO: Usa actualizarVisita (método REAL del store)
 */
const handleDrop = async (date: Date, e: DragEvent) => {
  const visitaId = e.dataTransfer?.getData("text/plain");
  if (visitaId) {
    debug.log('Month: Visita dropped', {
      visitaId,
      targetDate: date.toISOString()
    });

    try {
      // ✅ CORREGIDO: Usa actualizarVisita que SÍ existe
      await visitasStore.actualizarVisita(visitaId, {
        Fecha: date.toISOString().split('T')[0],
        Hora: date.toTimeString().substring(0, 5)
      });
    } catch (error) {
      console.error('Error moving visita:', error);
      debug.warn('Month: Error moving visita', {
        visitaId,
        targetDate: date.toISOString(),
        error
      });
    }
  } else {
    debug.warn('Month: Drop event missing visita ID', {
      targetDate: date.toISOString()
    });
  }
};

const handleDateClick = (date: Date, e: Event) => {
  debug.log('Month: Date clicked', {
    date: date.toISOString(),
    isCurrentMonth: isCurrentMonth(date),
    isToday: isToday(date)
  });
  emit("date-clicked", date);
};

const isCurrentMonth = (date: Date) => {
  const currentMonth = new Date(props.currentDate.getFullYear(), props.currentDate.getMonth()).getMonth();
  const targetMonth = new Date(date.getFullYear(), date.getMonth()).getMonth();
  return currentMonth === targetMonth;
};
</script>

<template>
  <div class="grow flex flex-col w-full">

    <!-- ENCABEZADOS DE DÍAS -->
    <div class='grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700 border-t dark:border-gray-600 sticky top-0 z-10'>
      <div
        v-for="day in days"
        :key="day"
        class=" p-2 font-semibold text-center"
      >
        {{ day }}
      </div>
    </div>

    <!-- CELDAS DE FECHAS -->
    <div class="grow grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700 border-t dark:border-gray-600">
      <div
        v-for="date in visibleDates"
        :key="date.toISOString()"
        :class="[
          'min-h-32 p-2 relative gap-y-2 flex flex-col overflow-hidden',
          isCurrentMonth(date) ? 'bg-white dark:bg-gray-900' : 'bg-gray-100 dark:bg-gray-800',
          props.isLoading ? 'opacity-50 cursor-wait' : ''
        ]"
        @dragover.prevent 
        @drop="handleDrop(date, $event)" 
        @click="handleDateClick(date, $event)" 
        role="gridcell" 
      >

        <!-- NÚMERO DEL DÍA -->
        <div :class="[
          'text-right text-sm shrink-0',
          isCurrentMonth(date) ? 'text-gray-600 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500'
        ]">
          {{ date.getDate() }}
          <span v-if="isToday(date)" class="text-blue-500 font-extrabold">•</span>
        </div>

        <!-- CONTENIDO DEL DÍA -->
        <div class="flex-1 flex flex-col overflow-hidden">
          <!-- PRIMERA VISITA -->
          <template v-if="getCellSummary(date).first">
            <CalendarEventComponent
              :visita="getCellSummary(date).first"
              :cliente-options="clienteOptions"
              :rfv-options="rfvOptions"
              class="relative"
              @click="emit('eventClick', $event)"
            />
          </template>

          <!-- CONTADOR "+N MÁS" -->
          <button
            v-if="getCellSummary(date).extra > 0"
            class="mt-1 text-[11px] self-start px-1.5 py-0.5 rounded
                  text-blue-600 dark:text-blue-300 hover:underline
                  bg-transparent"
            :aria-label="`Ver ${getCellSummary(date).extra} visitas más del ${date.toLocaleDateString('es-ES')}`"
            @click.stop="openDayList(date)" 
          >
            +{{ getCellSummary(date).extra }} más
          </button>

          <!-- INDICADOR DE CARGA -->
          <div v-if="props.isLoading" class="mt-1 text-xs text-gray-500 text-center">
            Cargando...
          </div>
        </div>
      </div>
    </div>
  </div>
</template>