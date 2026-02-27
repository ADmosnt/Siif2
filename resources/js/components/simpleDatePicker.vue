// resources/js/components/ui/DatePicker.vue

<script setup lang="ts">
import { computed } from 'vue'
import { type DateValue, getLocalTimeZone} from '@internationalized/date'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils' // Asegúrate que esta ruta sea correcta
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import monthYearCalendar from './MonthYearCalendar.vue'

// Usamos defineModel para crear un v-model de forma más sencilla
// Este será el valor (la fecha) que el componente expone hacia afuera.
const modelValue = defineModel<DateValue>()

// Un formateador para mostrar la fecha de forma legible
const formattedDate = computed(() => {
  if (modelValue.value) {
    // Formato: 28 de agosto de 2025
    return new Intl.DateTimeFormat('es-VE', { dateStyle: 'long' }).format(modelValue.value.toDate(getLocalTimeZone()))
  }
  return 'Selecciona una fecha'
})
</script>

<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !modelValue && 'text-muted-foreground',
        )"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        <span>{{ formattedDate }}</span>
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-auto p-0">
        <monthYearCalendar  v-model="modelValue" />
    </PopoverContent>
  </Popover>
</template>