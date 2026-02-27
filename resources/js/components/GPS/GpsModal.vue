<!-- resources/js/components/GPS/GpsModal.vue -->
<script setup lang="ts">
import BaseModal from '../BaseModal.vue';
import GpsMap from './GpsMap.vue'

// Este componente simplemente "deja pasar" los props hacia el componente del mapa.
const props = defineProps<{
  modelValue: boolean // Para controlar la visibilidad con v-model
  rfv: string | null
  fechaDesde: string | null
  fechaHasta: string | null
}>()

const emit = defineEmits(['update:modelValue'])

</script>

<template>
  <BaseModal
    :model-value="props.modelValue"
    @update:model-value="value => emit('update:modelValue', value)"
    title="Visor de Ruta GPS"
    description="Mapa interactivo que muestra las visitas del representante seleccionado."
    size="xl"
  >
    <template #default>
      <GpsMap
        v-if="props.modelValue"
        :rfv="props.rfv"
        :fecha-desde="props.fechaDesde"
        :fecha-hasta="props.fechaHasta"
      />
    </template>
  </BaseModal>
</template>