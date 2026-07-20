<script setup lang="ts">
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxList } from '@/components/ui/combobox'
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/components/ui/tags-input'
import { useFilter } from 'reka-ui'
import { computed, ref, watch, type PropType } from 'vue'
import { debounce } from 'lodash'

const props = defineProps({
  // Opciones que se mostrarán en el combobox
  options: {
    type: Array as PropType<Array<{ value: string; label: string }>>,
    required: true,
    default: () => [],
  },
  // Valor seleccionado (array de strings/IDs)
  modelValue: {
    type: Array as PropType<string[]>,
    required: true,
    default: () => [],
  },
  // Placeholder para el input
  placeholder: {
    type: String,
    default: 'Seleccionar...',
  },
  // Si el combobox debe estar abierto por defecto
  defaultOpen: {
    type: Boolean,
    default: false,
  },
  // ── Nuevas props para búsqueda dinámica ──────────────────────────
  // Activa el modo de búsqueda dinámica (emite 'dynamic-search' en lugar de filtrar localmente)
  dynamicSearch: {
    type: Boolean,
    default: false,
  },
  // Muestra el spinner/mensaje de "Buscando..." mientras llega la respuesta
  dynamicLoading: {
    type: Boolean,
    default: false,
  },
  // Si el backend tiene más páginas disponibles (paginación)
  hasMore: {
    type: Boolean,
    default: false,
  },
  // Si está deshabilitado (útil para cascada: deshabilitar Ruta hasta tener Zona)
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
  /** Se emite cuando cambia el término de búsqueda (solo en modo dynamicSearch) */
  'dynamic-search': [term: string]
}>()

const open = ref(props.defaultOpen)
const searchTerm = ref('')
const { contains } = useFilter({ sensitivity: 'base' })

// ── Labels de los items seleccionados ───────────────────────────────
const selectedLabels = computed(() =>
  props.modelValue
    .map(value => props.options.find(opt => opt.value === value)?.label)
    .filter(Boolean) as string[]
)

// ── Opciones filtradas ───────────────────────────────────────────────
// En modo dinámico el backend ya filtra; solo excluimos los ya seleccionados.
// En modo estático filtramos localmente como antes.
const filteredOptions = computed(() => {
  const available = props.options.filter(i => !props.modelValue.includes(i.value))

  if (props.dynamicSearch) {
    return available
  }

  return searchTerm.value
    ? available.filter(option => contains(option.label, searchTerm.value))
    : available
})

// ── Debounce para no saturar al backend ─────────────────────────────
const debouncedEmit = debounce((term: string) => {
  emit('dynamic-search', term)
}, 400)

// Emitir búsqueda dinámica cuando cambia el término
watch(searchTerm, newTerm => {
  if (!props.dynamicSearch) return
  debouncedEmit(newTerm)
})

// Al abrir en modo dinámico, cargar las opciones iniciales si están vacías
watch(open, isOpen => {
  if (isOpen && props.dynamicSearch && props.options.length === 0) {
    emit('dynamic-search', '')
  }
})

// ── Handlers ────────────────────────────────────────────────────────
const handleSelect = (value: string) => {
  searchTerm.value = ''
  emit('update:modelValue', [...props.modelValue, value])
}

const handleRemove = (value: string) => {
  emit('update:modelValue', props.modelValue.filter(v => v !== value))
}

const handleLoadMore = () => {
  // El padre decide cómo cargar más; re-emitimos la búsqueda actual
  emit('dynamic-search', searchTerm.value)
}
</script>

<template>
  <Combobox
    :model-value="modelValue"
    @update:model-value="(val) => emit('update:modelValue', val as string[])"
    v-model:open="open"
    :ignore-filter="true"
    :disabled="disabled"
  >
    <ComboboxAnchor as-child class="!min-w-0 !w-full" @click="!disabled && (open = true)">
      <TagsInput :model-value="modelValue" class="px-2 gap-2 w-full">
        <div class="flex gap-2 flex-wrap items-center">
          <TagsInputItem
            v-for="(item, index) in selectedLabels"
            :key="index"
            :value="item"
          >
            <TagsInputItemText />
            <TagsInputItemDelete @click.prevent="() => handleRemove(modelValue[index])" />
          </TagsInputItem>

          <ComboboxInput
            v-model="searchTerm"
            as-child
            class="flex-1 border-none focus:ring-0 shadow-none h-10 py-2 bg-transparent"
          >
            <TagsInputInput
              :placeholder="modelValue.length > 0 ? '' : placeholder"
              class="w-full p-0 border-none focus-visible:ring-0 h-auto min-w-[6rem]"
              :disabled="disabled"
              @keydown.enter.prevent
            />
          </ComboboxInput>
        </div>
      </TagsInput>

      <ComboboxList>
        <!-- Estado de carga dinámica -->
        <div
          v-if="dynamicLoading"
          class="py-3 text-sm text-center text-gray-500 dark:text-gray-400"
        >
          Buscando...
        </div>

        <!-- Sin resultados -->
        <ComboboxEmpty
          v-else-if="filteredOptions.length === 0"
          class="py-3 text-sm text-center text-gray-500 dark:text-gray-400"
        >
          <span v-if="searchTerm && dynamicSearch">
            No se encontraron resultados para "{{ searchTerm }}"
          </span>
          <span v-else>No hay opciones disponibles.</span>
        </ComboboxEmpty>

        <!-- Lista de opciones -->
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

        <!-- Cargar más (cuando el backend tiene más páginas) -->
        <div
          v-if="hasMore && !dynamicLoading"
          class="px-3 py-2 text-xs text-center text-blue-500 dark:text-blue-400 cursor-pointer hover:underline"
          @click.prevent="handleLoadMore"
        >
          Cargar más resultados...
        </div>
      </ComboboxList>
    </ComboboxAnchor>
  </Combobox>
</template>
