<script setup lang="ts">
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxList } from '@/components/ui/combobox'
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/components/ui/tags-input'
import { useFilter } from 'reka-ui'
import { computed, ref, type PropType } from 'vue'

// Definimos las props del componente
const props = defineProps({
  // Opciones que se mostrarán en el combobox
  options: {
    type: Array as PropType<Array<{ value: string; label: string }>>,
    required: true,
    default: () => []
  },
  // Valor seleccionado (array de strings)
  modelValue: {
    type: Array as PropType<string[]>,
    required: true,
    default: () => []
  },
  // Placeholder para el input
  placeholder: {
    type: String,
    default: 'Seleccionar...'
  },
  // Si el combobox debe estar abierto por defecto
  defaultOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const open = ref(props.defaultOpen)
const searchTerm = ref('')
const { contains } = useFilter({ sensitivity: 'base' })

// Esta computada ahora también necesita encontrar el LABEL basado en el VALUE guardado
const selectedLabels = computed(() => {
  return props.modelValue
    .map(value => props.options.find(opt => opt.value === value)?.label)
    .filter(Boolean) as string[]
})

const filteredOptions = computed(() => {
  const availableOptions = props.options.filter(i => !props.modelValue.includes(i.value)) // <-- CORRECCIÓN: Compara con i.value
  return searchTerm.value ? 
    availableOptions.filter(option => contains(option.label, searchTerm.value)) : 
    availableOptions
})

// Función para manejar la selección de un item
const handleSelect = (value: string) => { // <-- CORRECCIÓN: Ahora recibe el value (ID)
  searchTerm.value = ''
  const newValue = [...props.modelValue, value]
  emit('update:modelValue', newValue)
  
  // Esto puede cerrarse antes de tiempo, es mejor que el usuario lo cierre
  // if (filteredOptions.value.length === 1) {
  //   open.value = false
  // }
}

const handleRemove = (value: string) => {
  const newValue = props.modelValue.filter(v => v !== value)
  emit('update:modelValue', newValue)
}
</script>

<template>
  <Combobox 
    :model-value="modelValue" 
    @update:model-value="(val) => emit('update:modelValue', val)"
    v-model:open="open" 
    :ignore-filter="true"
  >
    <ComboboxAnchor as-child class="!min-w-0 !w-full" @click="open = true">
      <TagsInput :model-value="modelValue" class="px-2 gap-2 w-full"> <!-- CORRECCIÓN: No necesita @update, ya lo maneja el Combobox -->
        <div class="flex gap-2 flex-wrap items-center"> 
          <TagsInputItem v-for="(item, index) in selectedLabels" :key="index" :value="item"> <!-- CORRECCIÓN: Itera sobre los labels para mostrar -->
            <TagsInputItemText />
            <TagsInputItemDelete @click.prevent="() => handleRemove(modelValue[index])"/>
          </TagsInputItem>

          <ComboboxInput
            v-model="searchTerm" 
            as-child 
            class="flex-1 border-none focus:ring-0 shadow-none h-10 py-2 bg-transparent"
          >
            <TagsInputInput
              :placeholder="modelValue.length > 0 ? '' : placeholder" 
              class="w-full p-0 border-none focus-visible:ring-0 h-auto min-w-[6rem]" 
              @keydown.enter.prevent
            />
          </ComboboxInput>
        </div>
      </TagsInput>

      <ComboboxList>
        <ComboboxEmpty v-if="filteredOptions.length === 0" />
        <ComboboxGroup v-else>
          <ComboboxItem
            v-for="option in filteredOptions" 
            :key="option.value" 
            :value="option.value" 
            @select.prevent="() => handleSelect(option.value)" 
          >
            {{ option.label }}
          </ComboboxItem>
        </ComboboxGroup>
      </ComboboxList>
    </ComboboxAnchor>
  </Combobox>
</template>