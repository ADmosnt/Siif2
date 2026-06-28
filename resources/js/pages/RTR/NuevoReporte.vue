<!-- resources/js/pages/RTR/NuevoReporte.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import { Textarea } from '@/components/ui/textarea';
import Button from "@/components/ui/button/Button.vue";
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import GlobalTable from '@/components/GlobalTable.vue';
import AddMuestra_modal from '@/components/TdpComponents/AddMuestra_modal.vue';
import { type PaginatedData } from '@/types/pagination';
import { type MuestrasItem, Producto, Actividad,Representante, Evento, VisitaTemporal } from '@/types/interfaces';
import GlobalAlert from '@/components/GlobalAlert.vue';
import { useValidationAlert } from '@/composables/useValidationAlert';
import ClienteCombobox from '@/components/ClienteCombobox.vue';
import { useOffline } from '@/composables/useOffline';
import SignaturePad from '@/components/SignaturePad.vue';
import { useGeolocation } from '@/composables/useGeolocation';

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'SIIF', href: '/dashboard' },
    { label: 'Nuevo Reporte'},
]

// === PROPS PASADOS DESDE LARAVEL ===
const props = defineProps<{
    representantes?: PaginatedData<Representante>;
    actividades?: Actividad[];
    eventos?: Evento[];
    productos?: PaginatedData<Producto>;
    visitaTemporal?: VisitaTemporal;
}>();

// === OFFLINE ===
const { isOnline, submitOrQueue } = useOffline()

// === GEOLOCATION ===
const { coords: geoCoords, error: geoError, loading: geoLoading, permissionDenied: geoDenied, requestLocation } = useGeolocation()

    // === USAR COMPOSABLE PARA ALERTAS ===
const {
    success: alertSuccess,
    error: alertError,
    warning: alertWarning,
    message: alertMessage,
    showWarning,
    showError,
    showSuccess,
    clear: clearAlerts
} = useValidationAlert();

// === DATOS REACTIVOS ===


const maxChars = 256;
const descripcion = ref('');
const productosPage = ref(1);
const productosPageSize = ref('15');

const representantes = ref(props.representantes?.data || []);
const actividades = computed(() => props.actividades || []);
const eventos = computed(() => props.eventos || []);


const selectedCliente = ref<{ value: string; label: string } | null>(null);
const selectedRFV = ref<string | null>(null);
const visitaTemporalId = ref<number | null>(null); 
const actividadSeleccionada = ref<string | null>(null);
const eventoSeleccionado = ref<string | null>(null);
const rowsMuestras = ref<MuestrasItem[]>([]);

const loading = ref(false);
const error = ref<string | null>(null);
const loadingProductos = ref(false);
const firmaData = ref<string | null>(null);
const signaturePadRef = ref<InstanceType<typeof SignaturePad> | null>(null);

// === PAGINACIÓN LOCAL PARA LA TABLA DE MUESTRAS (rowsMuestras) ===
const muestrasPagination = ref({
page: 1,
pageSize: '15'
});

// Función para manejar el cambio de selección
const handleClienteUpdate = (value: { value: string; label: string } | null) => {
    selectedCliente.value = value;
};

// Función para manejar el cambio de apertura del dropdown
const handleOpenUpdate = (isOpen: boolean) => {
    console.log('Combobox abierto/cerrado:', isOpen);
};

// === DATOS PAGINADOS PARA LA TABLA DE MUESTRAS ===
const muestrasPaginadas = computed(() => {
    const startIndex = (muestrasPagination.value.page - 1) * parseInt(muestrasPagination.value.pageSize);
    const endIndex = startIndex + parseInt(muestrasPagination.value.pageSize);
return rowsMuestras.value.slice(startIndex, endIndex);
});

const totalMuestrasRecords = computed(() => rowsMuestras.value.length);

// === MANEJADORES DE PAGINACIÓN PARA LA TABLA DE MUESTRAS ===
const handleMuestrasPageChange = (newPage: number) => {
    muestrasPagination.value.page = newPage;
};

const handleMuestrasPageSizeChange = (newSize: string) => {
    muestrasPagination.value.pageSize = newSize;
    muestrasPagination.value.page = 1;
};

const showModal = ref(false);

const page = usePage();

// === FUNCIÓN PARA LIMPIAR EL FORMULARIO ===
const resetForm = () => {
actividadSeleccionada.value = null;
eventoSeleccionado.value = null;
descripcion.value = '';
rowsMuestras.value = [];
muestrasPagination.value.page = 1;
firmaData.value = null;
signaturePadRef.value?.clear();
};

const actividadSeleccionadaDescripcion = computed(() => {
    if (!actividadSeleccionada.value) return '';

    const actividad = actividades.value.find(
        act => act.idtipo_actividad == actividadSeleccionada.value
    );
    return actividad ? actividad.descripcionActividad : '';
});

// === FUNCIÓN PARA PROCESAR EL REPORTE ===
const procesarReporte = async () => { clearAlerts();

    if (!selectedCliente.value || !selectedRFV.value) {
        showWarning('Por favor selecciona un cliente y representante');
        return;
    }

    if (!actividadSeleccionada.value) {
        showWarning('Por favor selecciona una actividad');
        return;
    }

    if (actividadSeleccionadaDescripcion.value === 'ENTREGA MATERIALES' &&
        rowsMuestras.value.length === 0) {
        showWarning('Para la actividad "ENTREGA DE MATERIALES", debes agregar al menos un producto.');
        return;
    }

    if (!eventoSeleccionado.value) {
        showWarning('Por favor selecciona un evento');
        return;
    }

    if (!firmaData.value) {
        showWarning('Por favor solicita la firma del cliente');
        return;
    }

    // Obtener ubicacion GPS
    let location = geoCoords.value
    if (!location) {
        location = await requestLocation()
    }
    if (!location) {
        if (geoDenied.value) {
            showError('Debes habilitar la ubicacion para registrar un reporte. Activa el GPS en la configuracion de tu navegador.')
        } else {
            showError(geoError.value || 'No se pudo obtener la ubicacion. Intentalo de nuevo.')
        }
        return
    }

    const payload = {
        idcliente: selectedCliente.value.value,
        tipo: actividadSeleccionada.value,
        incidentes: eventoSeleccionado.value,
        comentario: descripcion.value,
        firma: firmaData.value,
        lat: location.lat,
        long: location.lon,
        muestras: rowsMuestras.value.map(item => ({
            idproducto: item.codigo,
            cantidad: item.unidades,
            lote: item.lote
        })),
        rfv_id: selectedRFV.value,
        ...(visitaTemporalId.value ? { visita_temporal_id: visitaTemporalId.value } : {})
    };

    if (!isOnline.value) {
        loading.value = true
        try {
            const result = await submitOrQueue('report', payload)
            if (result.queued) {
                showSuccess(`Reporte guardado localmente (${result.localRef}). Se enviara cuando haya conexion.`)
                resetForm()
            }
        } catch (err: any) {
            showError('Error al guardar el reporte localmente.')
        } finally {
            loading.value = false
        }
        return
    }

    router.post(route('reportes.procesar'), payload, {
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; },
        onSuccess: () => {
            showSuccess(visitaTemporalId.value ? 'Visita agendada procesada con éxito.' : 'Actividad reportada con éxito.');

    router.reload({
        only: ['clientes', 'actividades', 'eventos', 'productos'],
        data: { idRfv: selectedRFV.value },
        onFinish: () => {
            resetForm();
        }
    });
        },
        onError: (errors) => {
            console.error('Error procesando reporte:', errors);
            showError('Hubo un error al procesar el reporte.');
        }
    });
};


const cargarProductos = (page: number = 1, size: string = '15') => {
if (!selectedRFV.value) return;

loadingProductos.value = true;

router.reload({
    only: ['productos'],
    data: {
    idRfv: selectedRFV.value,
    page: page,
    size: size
    },
    onFinish: () => {
    loadingProductos.value = false;
    }
});
};


const abrirModal = () => {
showModal.value = true;
};


const handleConfirmFromModal = (items: MuestrasItem[]) => {
items.forEach(item => {
    const idx = rowsMuestras.value.findIndex(r => r.codigo === item.codigo);
    if (idx !== -1) {
    rowsMuestras.value[idx].unidades += item.unidades;
    } else {
    rowsMuestras.value.push({ ...item });
    }
});
};

const deleteProducto = (row: MuestrasItem) => {
const idx = rowsMuestras.value.findIndex(r => r.codigo === row.codigo);
if (idx !== -1) {
    rowsMuestras.value.splice(idx, 1);
}
};

// Definición de columnas de la tabla
const newReporTableheader = [
{ key: 'codigo', label: 'Código', className: 'px-4 py-2 whitespace-nowrap text-left' },
{ key: 'producto', label: 'Producto', className: 'px-4 py-2 text-left max-w-[200px] truncate' },
{ key: 'unidades', label: 'Unidades', className: 'px-4 py-2 whitespace-nowrap text-left' },
{ key: 'lote', label: 'Lote', className: 'px-4 py-2 whitespace-nowrap text-left' },
];


// === WATCHERS ===

watch(selectedRFV, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        productosPage.value = 1;
        cargarProductos(1, productosPageSize.value);
        resetForm();
        router.reload({ only: ['clientes', 'actividades', 'eventos', 'productos'], data: { idRfv: newVal } });
    }
});

watch([productosPage, productosPageSize], ([newPage, newSize]) => {
    cargarProductos(newPage, newSize);
});

// === PROCESAR ===
const onProcess = () => {
procesarReporte();
};

const showMuestras = computed(() => {
if (!actividadSeleccionada.value) return false;

const selectedId = String(actividadSeleccionada.value);

// Encontrar la actividad seleccionada
const actividad = actividades.value.find(
    a => a.idtipo_actividad == selectedId
);

return actividad?.descripcionActividad.toLowerCase().includes('materiales') ?? false;
});

// === CARGA INICIAL ===
onMounted(() => {
    console.log('🔍 Datos recibidos en NuevoReporte:', {
        visitaTemporal: props.visitaTemporal,
        actividadesCount: props.actividades?.length,
        eventosCount: props.eventos?.length,
        productosCount: props.productos?.data?.length,
        representantesCount: props.representantes?.data?.length
    });
    
    // Asegurar que visitaTemporalId se asigne correctamente
    if (props.visitaTemporal) {
        visitaTemporalId.value = props.visitaTemporal.id;
        selectedRFV.value = props.visitaTemporal.idRFV;
        
        // Asegurar que el cliente también se seleccione automáticamente
        selectedCliente.value = {
            value: props.visitaTemporal.idCliente,
            label: props.visitaTemporal.nombre_cliente
        };
        
        console.log('Visita temporal pre-cargada:', {
            id: visitaTemporalId.value,
            rfv: selectedRFV.value,
            cliente: selectedCliente.value
        });
    } else {
        selectedRFV.value = representantes.value[0]?.id;
    }
});

</script>

<template>
    <Head title="Nuevo Reporte" />
    <AppLayout :breadcrumbs="breadcrumbs" class="max-w-full overflow-x-hidden">
        <div class="flex-1 flex flex-col p-4">
        <GlobalAlert
            :type="alertWarning ? 'warning' : alertError ? 'error' : alertSuccess ? 'success' : ''"
            :message="alertMessage"
            @close="clearAlerts"
            auto-hide
            :auto-hide-delay="5000"
        />

        <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ error }}
        </div>

        <div v-if="page.props.errors?.idcliente" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ page.props.errors.idcliente }}
        </div>
        <div v-if="page.props.errors?.tipo" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ page.props.errors.tipo }}
        </div>

        <div v-if="loading" class="flex justify-center items-center p-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2">Cargando...</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
            <div class="md:col-span-4">
                <ClienteCombobox
                    :selected-r-f-v="selectedRFV"
                    :initial-visita-temporal="props.visitaTemporal"
                    :disabled="!selectedRFV || loading"
                    placeholder="Buscar cliente..."
                    @update:model-value="handleClienteUpdate"
                    @update:open="handleOpenUpdate"
                />
                </div>

            <div class="md:col-span-4">
            <Select v-model="selectedRFV" :loading="loading">
                <SelectTrigger>
                <SelectValue placeholder="Representante" />
                </SelectTrigger>
                <SelectContent>
                <SelectGroup>
                    <SelectItem v-for="rep in representantes" :key="rep.id" :value="rep.id">
                    {{ rep.nombre }}
                    </SelectItem>
                </SelectGroup>
                </SelectContent>
            </Select>
            </div>

            <div class="md:col-span-4 md:row-span-2 flex flex-col justify-between">
            <Textarea v-model="descripcion" placeholder="Comentario" :maxlength="maxChars" class="w-full h-[100px]" />
            <p class="text-xs text-right text-gray-500">
                {{ descripcion.length }} / {{ maxChars }} caracteres
            </p>
            </div>

            <div class="md:col-span-4">
            <Select v-model="actividadSeleccionada" :disabled="loading">
                <SelectTrigger>
                <SelectValue placeholder="Seleccione una actividad" />
                </SelectTrigger>
                <SelectContent>
                <SelectGroup>
                    <SelectItem v-for="act in actividades" :key="act.idtipo_actividad" :value="act.idtipo_actividad">
                    {{ act.descripcionActividad }}
                    </SelectItem>
                </SelectGroup>
                </SelectContent>
            </Select>
            </div>

            <div class="md:col-span-4">
            <Select v-model="eventoSeleccionado" :disabled="loading">
                <SelectTrigger>
                <SelectValue placeholder="Seleccione un evento" />
                </SelectTrigger>
                <SelectContent>
                <SelectGroup>
                    <SelectItem v-for="ev in eventos" :key="ev.idtipo_incidentes" :value="ev.idtipo_incidentes">
                    {{ ev.descripcionIncidente }}
                    </SelectItem>
                </SelectGroup>
                </SelectContent>
            </Select>
            </div>
        </div>

        <div v-if="showMuestras" class="border rounded-md overflow-hidden mt-6">
            <GlobalTable
            :columns="newReporTableheader"
            :rows="muestrasPaginadas" 
            :actions="[{ key: 'delete', handler: deleteProducto }]"
            show-add-button
            addButtonLabel="Agregar Muestra"
            addButtonLabelShort="Add.M"
            autoAddActionsColumn
            @add="abrirModal"
            @update:page="handleMuestrasPageChange"
            @update:pageSize="handleMuestrasPageSizeChange" 
            :total-records="totalMuestrasRecords"
            :current-page="muestrasPagination.page"
            :page-size="muestrasPagination.pageSize"
            />
            <AddMuestra_modal v-model="showModal" 
            :productos="props.productos" 
            :loading="loadingProductos"
            @confirm="handleConfirmFromModal" 
            :current-page="productosPage" 
            :page-size="productosPageSize"
            :total-records="props.productos?.meta?.total || 0" 
            :links="props.productos?.links"
            @update:page="productosPage = $event" 
            @update:pageSize="productosPageSize = $event" 
            />
        </div>

        <!-- Firma del cliente -->
        <div class="mt-6 px-4">
            <SignaturePad
                ref="signaturePadRef"
                :disabled="loading"
                @update:signature="firmaData = $event"
            />
        </div>

        <!-- Ubicacion GPS -->
        <div class="mt-4 px-4">
            <div v-if="geoCoords" class="flex items-center gap-2 text-xs text-green-600 dark:text-green-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Ubicacion capturada ({{ geoCoords.lat.toFixed(5) }}, {{ geoCoords.lon.toFixed(5) }})</span>
            </div>
            <div v-else-if="geoLoading" class="flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400">
                <div class="h-3 w-3 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
                <span>Obteniendo ubicacion...</span>
            </div>
            <div v-else-if="geoError" class="flex items-center gap-2 text-xs text-red-600 dark:text-red-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <span>{{ geoError }}</span>
                <button type="button" class="underline font-medium" @click="requestLocation">Reintentar</button>
            </div>
        </div>

        <div class="flex justify-end mt-4 px-4 items-center gap-2">
            <span v-if="!isOnline" class="text-xs text-amber-600 font-medium">Modo offline</span>
            <Button @click="onProcess">
            {{ isOnline ? 'PROCESAR' : 'GUARDAR REPORTE' }}
            </Button>
        </div>
        </div>
    </AppLayout>
</template>