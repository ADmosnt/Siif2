<script setup lang="ts">
import { useClienteCombobox } from '@/composables/useClienteCombobox';
import { ComboboxRoot, ComboboxAnchor, ComboboxInput, ComboboxCancel, ComboboxPortal, ComboboxContent, ComboboxViewport, ComboboxItem, ComboboxEmpty } from 'reka-ui'; // Ajusta la ruta si es necesario
import type { VisitaTemporal } from '@/types/interfaces';
import { computed, watch } from 'vue';

// Define las props que el componente padre debe pasar
interface Props {
    selectedRFV: string | null;
    disabled?: boolean;
    initialVisitaTemporal?: VisitaTemporal | null;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    initialVisitaTemporal: undefined,
    placeholder: 'Buscar cliente...'
});


interface Emits {
    // Se emite cuando se selecciona/deselecciona un cliente
    (e: 'update:modelValue', value: { value: string; label: string } | null): void;
    // Opcional: Se emite cuando se abre/cierra el dropdown
    (e: 'update:open', value: boolean): void;
}

const emit = defineEmits<Emits>();

// Llama al composable con las props necesarias
const {
    selectedCliente,
    clientesFiltrados,
    searchTerm,
    loadingClientes,
    isOpen,
    } = useClienteCombobox({
    selectedRFV: computed(() => props.selectedRFV),
    initialVisitaTemporal: props.initialVisitaTemporal
});

// Watchers internos para emitir eventos al padre
watch(selectedCliente, (newValue) => {
    emit('update:modelValue', newValue);
});

watch(isOpen, (newValue) => {
    emit('update:open', newValue);
});

// Función para limpiar la selección
const clearSelection = () => {
    selectedCliente.value = null;
    searchTerm.value = '';
};

</script>

<template>
    <!-- El template usa los valores del composable -->
    <ComboboxRoot
        v-model="selectedCliente"
        :disabled="disabled"
        :ignore-filter="true"
        :openOnFocus="true"
        :open="isOpen"
        @update:open="(val) => { isOpen = val; $emit('update:open', val); }" 
        @update:model-value="(val) => { selectedCliente = val; $emit('update:modelValue', val); }"
    >
    <ComboboxAnchor>
        <ComboboxInput
            v-model="searchTerm"
            :display-value="(item) => item?.label || ''"
            :disabled="disabled"
            :placeholder="placeholder"
            class="w-full h-10 px-3 py-2 text-sm border border-input rounded-md focus:outline-none focus:ring-0 focus:ring-offset-0"
        />
        <!-- Botón para limpiar selección (opcional) -->
        <ComboboxCancel v-if="selectedCliente && !disabled" @click="clearSelection" class="ml-1">
            <span>X</span>
        </ComboboxCancel>
    </ComboboxAnchor>

    <ComboboxPortal>
        <ComboboxContent position="popper" class="z-50 max-h-60 w-full overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md">
            <ComboboxViewport class="p-1">
            <ComboboxItem
                v-for="cli in clientesFiltrados"
                :key="cli.value"
                :value="cli"
                class="flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-2 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
            >
                {{ cli.label }}
            </ComboboxItem>
            <ComboboxEmpty v-if="clientesFiltrados.length === 0 && !loadingClientes" class="py-2 text-sm text-center text-gray-500">
                No se encontraron clientes.
            </ComboboxEmpty>
            </ComboboxViewport>
        </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>