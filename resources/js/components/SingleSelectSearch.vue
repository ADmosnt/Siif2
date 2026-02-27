<!-- resources/js/components/SingleSelectSearch.vue -->
<script setup lang="ts">
import { ref, computed, watch, defineProps, defineEmits } from 'vue'
import { useFilter } from 'reka-ui'
import {
  Combobox,
  ComboboxAnchor,
  ComboboxInput,
  ComboboxList,
  ComboboxEmpty,
  ComboboxGroup,
  ComboboxItem,
} from '@/components/ui/combobox'
import { Input } from '@/components/ui/input'

// 1) Props que recibimos
const props = defineProps<{
  options: { value: string; label: string }[]
  modelValue: string | null
  placeholder?: string
  maxListHeight?: string
}>()

// 2) Emit para v-model
const emit = defineEmits<{
  (e: 'update:modelValue', v: string | null): void
}>()

// 3) Estado interno
const open = ref(false)
const searchTerm = ref('')



// 5) Filtrado de opciones
const { contains } = useFilter({ sensitivity: 'base' })
const filtered = computed(() =>
  searchTerm.value
    ? props.options.filter(o => contains(o.label, searchTerm.value))
    : props.options
)

// 6) Manejador de selección
function onSelect(opt: { value: string; label: string }) {
  emit('update:modelValue', opt.value);
  searchTerm.value = opt.label; // Asignación directa del label
  open.value = false;
}

watch(
  () => props.options,
  (newOptions) => {
    if (props.modelValue) {
      const found = newOptions.find(o => o.value === props.modelValue);
      if (found) searchTerm.value = found.label;
    }
  },
  { deep: true }
);
</script>


<template>
  <Combobox
    v-model="props.modelValue"
    v-model:open="open"
    :ignore-filter="true">
    
    <div class="flex flex-wrap gap-2 items-center rounded-md border border-input bg-background text-sm">
        <ComboboxAnchor
            as-child
            @pointerdown="open = true"
        >
            <ComboboxInput
            v-model="searchTerm"
            @pointerdown="open = true"            
            as-child
            class="w-full bg-transparent border-none outline-none placeholder:text-muted-foreground"
            >
            <Input
                :model-value="searchTerm"
                :placeholder="placeholder"
                class="w-full bg-transparent border-none outline-none placeholder:text-muted-foreground"
                @keydown.enter.prevent
            />
            </ComboboxInput>
        </ComboboxAnchor>
    </div>

    <!-- Lista desplegable -->
    <ComboboxList
      class="mt-1 border rounded-md shadow overflow-y-auto"
      @escape-key-down="open = false"
    >
    <div :style="{ maxHeight: props.maxListHeight || '12rem' }" class="overflow-y-auto">
      <ComboboxEmpty v-if="filtered.length === 0">
        No hay resultados
      </ComboboxEmpty>
      <ComboboxGroup v-else>
        <ComboboxItem
          v-for="opt in filtered"
          :key="opt.value"
          :value="opt.value"
          @select.prevent="() => onSelect(opt)"
          class="cursor-pointer px-3 py-2 hover:bg-gray-100"
        >
          {{ opt.label }}
        </ComboboxItem>
      </ComboboxGroup>
    </div>
    </ComboboxList>
  </Combobox>
</template>
