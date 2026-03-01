<!-- resources/js/components/GenericCombobox.vue -->
<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { ComboboxRoot, ComboboxAnchor, ComboboxInput, ComboboxCancel, ComboboxPortal, ComboboxContent, ComboboxViewport, ComboboxItem, ComboboxEmpty } from 'reka-ui';
import { debounce } from 'lodash';

// Definir tipo para las opciones
export interface SelectOption {
  value: string | null;
  label: string;
}

// Props genéricas
interface Props {
  placeholder?: string;
  disabled?: boolean;
  options?: SelectOption[];
  modelValue?: SelectOption | null | string;
  loadOnOpen?: boolean;
  searchable?: boolean;
  dynamicSearch?: boolean;
  dynamicLoading?: boolean;
  resetOnOpen?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Buscar...',
  disabled: false,
  options: () => [],
  modelValue: null,
  loadOnOpen: false,
  searchable: true,
  dynamicSearch: false,
  dynamicLoading: false,
  resetOnOpen: false,
  
});

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: SelectOption | null];
  'open': [];
  'search': [term: string];
  'dynamic-search': [term: string];
  'blur': [];
}>();

// Estado local
const selectedValue = ref<SelectOption | null>(props.modelValue);
const searchTerm = ref('');
const isOpen = ref(false);
const loading = ref(false);
const isInitialSearch = ref(true);
const inputRef = ref<InstanceType<typeof ComboboxInput> | null>(null);
const isTyping = ref(false);
const lastSearchTerm = ref('');

// Opciones filtradas
const filteredOptions = computed(() => {
  if (!props.searchable || !searchTerm.value.trim()) {
    return props.options;
  }
  
  // Si tenemos búsqueda dinámica, mostramos todas las opciones que lleguen
  // El filtrado lo hace el backend
  if (props.dynamicSearch) {
    return props.options;
  }
  
  // Si no es búsqueda dinámica, filtramos localmente
  const term = searchTerm.value.toLowerCase().trim();
  return props.options.filter(option => {
    const label = (option.label ?? '').toString().toLowerCase();
    const value = (option.value ?? '').toString().toLowerCase();
    return label.includes(term) || value.includes(term);
  });
});

//Función debounced para búsqueda dinámica
const debouncedDynamicSearch = debounce((term: string) => {
  if (props.dynamicSearch && term.length >= 2) {
    emit('dynamic-search', term);
  }
}, 500);

let typingTimer: number | undefined;

onUnmounted(() => {
  debouncedDynamicSearch.cancel();
  if (typingTimer !== undefined) clearTimeout(typingTimer);
});

//Función para enfocar el input
const focusInput = () => {
  // Intentamos enfocar el $el (elemento raíz del componente)
  const el = inputRef.value?.$el || inputRef.value;
  if (el && typeof el.focus === 'function') {
    el.focus();
  }
};

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (newValue === '' || newValue === undefined) {
    selectedValue.value = null
    searchTerm.value = ''
  } else if (newValue && typeof newValue === 'object') {
    selectedValue.value = newValue
    searchTerm.value = newValue.label
  } else {
    selectedValue.value = null
    searchTerm.value = ''
  }
}, { immediate: true });

watch(selectedValue, (newValue) => {
  emit('update:modelValue', newValue);
});

// Watch para el término de búsqueda - maneja búsqueda dinámica
watch(searchTerm, (newTerm, oldTerm) => {
  isTyping.value = true;

  if (props.dynamicSearch) {
    if (newTerm.length >= 2) {
      // Solo buscar si el término cambió significativamente
      if (newTerm !== oldTerm) {
        debouncedDynamicSearch(newTerm);
      }
    } else if (newTerm.length === 0 && !isInitialSearch.value) {
      // Búsqueda vacía después de haber buscado - recargar opciones iniciales
      lastSearchTerm.value = '';
      emit('dynamic-search', '');
    }else if (newTerm.length === 1) {
      // Si solo hay un carácter, limpiar la última búsqueda
      lastSearchTerm.value = '';
    }
  }
  // Restablecer isTyping después de un tiempo
  typingTimer = window.setTimeout(() => {
    isTyping.value = false;
  }, 100);
});

// Control de apertura/cierre del combobox
watch(isOpen, (newVal) => {
  if (newVal) {
    emit('open');
    
    //Resetear búsqueda si está configurado
    if (props.resetOnOpen) {
      searchTerm.value = '';
      lastSearchTerm.value = '';
    }
    //Para búsqueda dinámica, cargar opciones iniciales al abrir
    if (props.dynamicSearch && (props.options.length === 0 || props.resetOnOpen)) {
      emit('dynamic-search', '');
      isInitialSearch.value = false;
    }
    
    //Enfocar el input después de que se abra


    if (props.options.length > 0) {
      loading.value = false;
    } else if (props.loadOnOpen) {
      loading.value = true;
    }
  } else {
    isInitialSearch.value = true;
    if (isTyping.value && !selectedValue.value && searchTerm.value) {
    }
  }
});

//Función para manejar el clic en el combobox
const handleComboboxClick = () => {
  if (!isOpen.value && !props.disabled) {
    isOpen.value = true;
  } else {
    focusInput();
  }
};
// Función para limpiar selección
const clearSelection = (event?: Event) => {
if (event) event.stopPropagation();
  
  selectedValue.value = null;
  searchTerm.value = '';
  lastSearchTerm.value = '';
  isOpen.value = false;
  
  //Emitir el cambio al padre
  emit('update:modelValue', null);
  
  //Si es búsqueda dinámica, cargar opciones iniciales
  if (props.dynamicSearch) {
    emit('dynamic-search', '');
  }
  
  //Enfocar el input después de limpiar
  setTimeout(() => {
    focusInput();
  }, 50);
};

//Función para manejar el evento blur
const handleBlur = () => {
  // Si no hay valor seleccionado y el usuario no estaba escribiendo, limpiar el término de búsqueda
  if (!selectedValue.value && !isTyping.value && searchTerm.value) {
    // Si el término de búsqueda no coincide con ninguna opción, limpiarlo
    const exactMatch = props.options.some(option => 
      option.label.toLowerCase() === searchTerm.value.toLowerCase()
    );
    
    if (!exactMatch && !props.dynamicSearch) {
      searchTerm.value = '';
    }
  }
  
  emit('blur');
};

//Función para manejar el cambio en las opciones
const handleOptionsChange = () => {
  // Si el usuario estaba escribiendo y tenemos opciones, no forzar la selección
  if (isTyping.value && searchTerm.value && !selectedValue.value) {
    // No hacer nada - dejar que el usuario continúe escribiendo
    return;
  }
  
  // Si tenemos un término de búsqueda y no hay selección, buscar coincidencia exacta
  if (searchTerm.value && !selectedValue.value && !props.dynamicSearch) {
    const exactMatch = props.options.find(option => 
      option.label.toLowerCase() === searchTerm.value.toLowerCase()
    );
    
    if (exactMatch) {
      selectedValue.value = exactMatch;
      searchTerm.value = exactMatch.label;
    }
  }
};

// Watch para opciones que llegan después de abrir
watch(() => props.options, (newOptions) => {
  if (isOpen.value && newOptions.length > 0) {
    loading.value = false;
    handleOptionsChange();
  }
}, { deep: true });

//Computed para mostrar estado de carga dinámica
const showLoading = computed(() => {
  return loading.value || props.dynamicLoading;
});

// Computed para determinar el placeholder
const effectivePlaceholder = computed(() => {
  if (props.disabled) return 'Deshabilitado...';
  return props.placeholder;
});

//Función para manejar la selección de un item
const handleItemSelect = (option: SelectOption) => {
  selectedValue.value = option;
  searchTerm.value = option.label;
  isOpen.value = false;
  isTyping.value = false;
};

// Función para manejar el keydown
const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    isOpen.value = false;
    if (!selectedValue.value) {
      searchTerm.value = '';
    }
  }
  
  // Si presiona Enter y hay un término de búsqueda pero no hay selección
  // NO forzar la selección automática
  if (event.key === 'Enter' && searchTerm.value && !selectedValue.value) {
    event.preventDefault();
  }
};

</script>

<template>
  <ComboboxRoot
    v-model="selectedValue"
    :disabled="disabled"
    v-model:open="isOpen"
    :openOnFocus="false" 
  >
    <ComboboxAnchor class="relative">
      <ComboboxInput
        ref="inputRef"
        v-model="searchTerm"
        :display-value="(item: SelectOption) => item?.label || ''"
        :disabled="disabled"
        :placeholder="effectivePlaceholder"
        @focus="isOpen = true"
        @blur="handleBlur"
        @keydown="handleKeyDown"
        @click="handleComboboxClick"
        class="w-full h-10 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-text"
      />
      
      <ComboboxCancel 
        v-if="selectedValue && !disabled" 
        @click="clearSelection" 
        @mousedown.prevent
        class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
      >
        <span class="text-lg">×</span>
      </ComboboxCancel>

      <!--Indicador de que se puede hacer clic -->
      <div 
        v-if="!selectedValue && !disabled" 
        @click="handleComboboxClick"
        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
    </ComboboxAnchor>

    <ComboboxPortal>
      <ComboboxContent 
        position="popper"
        :side-offset="4"
        class="z-100 max-h-60 w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg"
      >
        <ComboboxViewport class="p-1">
          <!-- Estado de carga -->
          <div v-if="showLoading" class="py-2 text-sm text-center text-gray-500">
            <span v-if="dynamicLoading">Buscando...</span>
            <span v-else>Cargando opciones...</span>
          </div>
          
          <!-- Sin opciones -->
          <ComboboxEmpty 
            v-else-if="filteredOptions.length === 0" 
            class="py-3 text-sm text-center text-gray-500 dark:text-gray-400"
          >
            <span v-if="searchTerm && dynamicSearch">
              No se encontraron opciones para "{{ searchTerm }}"
            </span>
            <span v-else-if="searchTerm">
              No se encontraron opciones.
            </span>
            <span v-else>
              No hay opciones disponibles.
            </span>
          </ComboboxEmpty>
          
          <!-- Lista de opciones -->
          <ComboboxItem
            v-for="option in filteredOptions"
            :key="option.value"
            :value="option"
            class="flex w-full cursor-default select-none items-center rounded-md py-2.5 px-3 text-sm outline-none data-highlighted:bg-gray-100 data-highlighted:text-gray-900 dark:data-highlighted:bg-gray-700 dark:data-highlighted:text-gray-100"
          >
            {{ option.label }}
          </ComboboxItem>
        </ComboboxViewport>
      </ComboboxContent>
    </ComboboxPortal>
  </ComboboxRoot>
</template>