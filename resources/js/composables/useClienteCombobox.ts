// resources/js/composables/useClienteCombobox.ts

import { ref, watch, type Ref } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';
import type { VisitaTemporal } from '@/types/ interfaces';
import { getClientes, searchClientes as searchClientesOffline } from '@/offline/cacheService';

interface UseClienteComboboxParams {
    selectedRFV: Ref<string | null>;
    initialVisitaTemporal?: VisitaTemporal | null;
}

interface UseClienteComboboxReturn {
    selectedCliente: Ref<{ value: string; label: string } | null>;
    clientesFiltrados: Ref<{ value: string; label: string }[]>;
    searchTerm: Ref<string>;
    loadingClientes: Ref<boolean>;
    isOpen: Ref<boolean>;
    visitaTemporalId: Ref<number | null>;
    loadInitialClientes: () => Promise<void>;
    fetchClientes: (search: string) => Promise<void>;
}

export function useClienteCombobox({ selectedRFV, initialVisitaTemporal = null }: UseClienteComboboxParams): UseClienteComboboxReturn {
  // === DATOS REACTIVOS ===
    const visitaTemporalId = ref<number | null>(initialVisitaTemporal?.id || null);
    const isOpen = ref(false);
    const selectedCliente = ref<{ value: string; label: string } | null>(null);
    const clientesFiltrados = ref<{ value: string; label: string }[]>([]);
    const searchTerm = ref('');
    const loadingClientes = ref(false);

  // Bandera para manejar prellenado inicial
const isInitialLoad = ref(!!initialVisitaTemporal);

    // === FUNCIÓN PARA CARGAR CLIENTES AL ABRIR EL COMBOBOX ===
const loadInitialClientes = async () => {
        if (!selectedRFV.value) {
        clientesFiltrados.value = [];
        return;
    }

    loadingClientes.value = true;
    try {
        if (!navigator.onLine) {
            const cached = await getClientes();
            clientesFiltrados.value = cached.map(c => ({ value: c.id, label: c.nombre }));
            return;
        }
        const response = await axios.get(route('clientes.search'), {
        params: {
            search: '',
            idRfv: selectedRFV.value,
            },
        });
        clientesFiltrados.value = response.data;
    } catch (error) {
        const cached = await getClientes();
        if (cached.length > 0) {
            clientesFiltrados.value = cached.map(c => ({ value: c.id, label: c.nombre }));
        } else {
            console.error('Error fetching initial clientes:', error);
            clientesFiltrados.value = [];
        }
    } finally {
        loadingClientes.value = false;
    }
};

    // === FUNCIÓN PARA BUSCAR CLIENTES ===
    const fetchClientes = async (search: string) => {
        if (!selectedRFV.value) {
            clientesFiltrados.value = [];
            return;
        }

        loadingClientes.value = true;
        try {
        if (!navigator.onLine) {
            const cached = search
                ? await searchClientesOffline(search)
                : await getClientes();
            clientesFiltrados.value = cached.map(c => ({ value: c.id, label: c.nombre }));
            return;
        }
        const response = await axios.get(route('clientes.search'), {
            params: {
            search: search,
            idRfv: selectedRFV.value,
            },
        });
        clientesFiltrados.value = response.data;
        } catch (error) {
        const cached = search
            ? await searchClientesOffline(search)
            : await getClientes();
        if (cached.length > 0) {
            clientesFiltrados.value = cached.map(c => ({ value: c.id, label: c.nombre }));
        } else {
            console.error('Error fetching clientes:', error);
            clientesFiltrados.value = [];
        }
        } finally {
        loadingClientes.value = false;
        }
    };

    // === FUNCIÓN DE DEBOUNCE PARA BUSQUEDA ===
    const debouncedFetchClientes = debounce((term: string) => {
        fetchClientes(term);
    }, 300);

    // === WATCHERS ===
    // --- Watch para el término de búsqueda del combobox ---
    watch(searchTerm, (newTerm) => {
        if (isInitialLoad.value || !selectedRFV.value) {
        return;
        }
        debouncedFetchClientes(newTerm);
    });

  // --- Watch para selectedRFV (limpiar selección si cambia RFV) ---
    watch(selectedRFV, (newVal, oldVal) => {
        if (newVal !== oldVal) {
        if (!isInitialLoad.value) {
            selectedCliente.value = null;
            searchTerm.value = '';
            clientesFiltrados.value = [];
        } else {
            isInitialLoad.value = false; 
        }
        }
    });

  // --- Watch para isOpen (cargar clientes iniciales al abrir) ---
    watch(isOpen, (newVal) => {
        if (newVal && !isInitialLoad.value) {
        loadInitialClientes();
        }
    });

  // --- Prellenado inicial desde visitaTemporal ---
    if (initialVisitaTemporal) {
        selectedRFV.value = initialVisitaTemporal.idRFV;
        visitaTemporalId.value = initialVisitaTemporal.id;

        // Cargar cliente específico o lista inicial
        // fetchClientes(initialVisitaTemporal.idCliente); // Opcional: buscar solo ese cliente
        loadInitialClientes(); // Cargar lista inicial para que el cliente preseleccionado esté en la lista

        // Preenllenar Cliente directamente con el objeto
        selectedCliente.value = {
            value: initialVisitaTemporal.idCliente,
            label: initialVisitaTemporal.nombre_cliente
            };
        searchTerm.value = initialVisitaTemporal.nombre_cliente;
    }

    return {
        selectedCliente,
        clientesFiltrados,
        searchTerm,
        loadingClientes,
        isOpen,
        visitaTemporalId,
        loadInitialClientes,
        fetchClientes,
    };
}