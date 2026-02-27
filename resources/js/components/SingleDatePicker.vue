<script setup lang="ts">
import { type DateValue, getLocalTimeZone, today } from '@internationalized/date'
import { type DateRange } from '@react-aria/datepicker' // Importación corregida
import { type Ref, ref } from 'vue'
import { RangeCalendar } from '@/components/ui/range-calendar'

const value = ref({
  start: today(getLocalTimeZone()),
  end: today(getLocalTimeZone()),
}) as Ref<DateRange>
</script>

<template>
  <RangeCalendar v-model="value" weekday-format="short" class="rounded-md border" />
</template>