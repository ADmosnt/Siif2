<!-- resources/js/componentes/GlobalSelect.vue -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

import { type Empresa } from '@/types/interfacesConciliarFactura';

const props = defineProps<{
  modelValue?: string | number | bigint | null
  options: (Option | string)[]
  placeholder?: string
}>()

const emit = defineEmits(['update:modelValue'])

const internalValue = ref(props.modelValue ?? null)

watch(() => props.modelValue, val => {
  if (val !== undefined) internalValue.value = val
})

const updateValue = (value: string | number | bigint | null) => {
  internalValue.value = value
  emit('update:modelValue', value)
}

const normalizedOptions = computed(() => {

  return (props.options || []).map(option => {
    if (typeof option === 'string') {
      return { id: option, nombre: option }
    }
    return option
  })
})
</script>

<template>
  <Select :model-value="internalValue" @update:model-value="updateValue">
    <SelectTrigger>
      <SelectValue :placeholder="placeholder ?? 'Seleccionar'" />
    </SelectTrigger>
    <SelectContent>
      <SelectGroup>
        <SelectItem
          v-for="option in normalizedOptions"
          :key="option.id"
          :value="option.id"
        >
          {{ option.nombre }}
        </SelectItem>
      </SelectGroup>
    </SelectContent>
  </Select>
</template>


<!--  Esta es la forma de usar el componente en otras vistas
<script setup lang="ts">

import { ref } from 'vue'
import GlobalSelect from '@/components/GlobalSelect.vue'; Se importa el componente GlobalTable para poder usarlo

const representantes = ref([{ id: 1, nombre: 'Empresa A' }, { id: 2, nombre: 'Empresa B' }])
            Se define un array con los valores que va a llevar los datos
const representantes = ref(['V', 'E'] o asi con arrays simples solo con strings
)

const representanteSeleccionado = ref()     Se define una constante para guardar el valor que se seleccione en el select


</script>

<template>

    <GlobalSelect                               Y asi se usa el componente
        v-model="representanteSeleccionado"     Se le pasa como v-model la constante donde se va a almacenar el dato
        :options="representantes"               en options se pone el array de los datos
        placeholder="Representante"             y el placeholder es el nombre q va a tener el select
    />


</template> 

    <GlobalSelect                               
        v-model="representanteSeleccionado"     
        :options="representantes"               
        placeholder="Representante"            
    />

-->