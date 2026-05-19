<script setup lang="ts">
import { ref, watch, computed} from 'vue';
import type vueDropzone from 'vue-dropzone';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import 'dropzone/dist/dropzone.css'
import { useDropzone } from '@/composables/useDropzone';
import { useExport } from '@/composables/useExport';
import axios from 'axios';
import { useNotificationHandler } from '@/composables/useNotificationHandler';
import Dropzone from '@/components/Dropzone.vue';
import { type BreadcrumbItem } from '@/types';
import SingleSelectSearch from '@/components/SingleSelectSearch.vue';

const breadcrumbs: BreadcrumbItem[]= [
    { label: 'SIIF', href: '/dashboard' },
    { label: 'Monitor'}
];

interface Usuario {
  idgrupo_persona: string;
  idFabricante: string;
}

// Tipos y Props
export interface Empresa {
    idPersona: string;
    nombre_completo_razon_social: string;
    idFabricante: string;
}

const props = defineProps<{
  empresas: Empresa[];
  usuario: Usuario;
}>();

const selectedEmpresaId = ref<string | null>(null);
const downloadLoading = ref(false);
const uploadLoading = ref(false);

// Si es GRT y solo hay una empresa, la selección es automática
if (props.usuario.idgrupo_persona === 'GRT' && props.empresas.length === 1) {
  selectedEmpresaId.value = props.empresas[0].idPersona;
}

// Estado
const empresa = computed(() => {
  return props.empresas.find(emp => emp.idPersona === selectedEmpresaId.value) || null;
});

const empresaOptions = computed(() => {
  return props.empresas.map(emp => ({
    label: emp.nombre_completo_razon_social,
    value: emp.idPersona
  }));
});

const currentTab = ref('productos');
const {
    showErrorDialog,
    errorDialogMessage,
    errorDialogDetails,
    errorSummary,
    showToast,
    toastMessage,
    toastType,
    toastDetails,
    showNotification,
    clearNotifications
} = useNotificationHandler();

const tabs = [
    { id: 'productos', label: 'Productos' },
    { id: 'personas', label: 'Personas' },
];

const handleDownload = async (endpoint: string, filename: string) => {
    if (!empresa.value) {
        showNotification('Selecciona una empresa primero', 'warning');
        return;
    }

    downloadLoading.value = true;
    try {
        await descargarArchivo(endpoint, filename);
    } catch (error) {
        // El composable useExport ya maneja errores, pero por si acaso:
        showNotification('Error al descargar el archivo', 'error');
    } finally {
        downloadLoading.value = false;
    }
};


const Dproducto = ref<typeof vueDropzone | null>(null);
const Dclimat = ref<typeof vueDropzone | null>(null);
const Dpersona = ref<typeof vueDropzone | null>(null);
const Dclirfv = ref<typeof vueDropzone | null>(null);

// Usar los composables
const { validateFile, disableAllDropzones, enableAllDropzones } = useDropzone(empresa, {
    Dproducto,
    Dclimat,
    Dpersona,
    Dclirfv
});
const { descargarArchivo } = useExport(empresa);

type PersonaEndpointKeys = 'personas' | 'clientesRfv';
type ProductoEndpointKeys = 'productos' | 'materiales';

type UploadEndpoints = {
    personas: Record<PersonaEndpointKeys, string>;
    productos: Record<ProductoEndpointKeys, string>;
};

type TabKey = 'personas' | 'productos';
type EndpointKey = PersonaEndpointKeys | ProductoEndpointKeys;

// Objeto con firma de índice para subida
const uploadEndpoints: UploadEndpoints = {
    personas: {
        personas: '/upload/personas/personas',
        clientesRfv: '/upload/personas/clientesRfv',

    },
    productos: {
        productos: '/upload/productos',
        materiales: '/upload/productos/materiales'
    },
};

// Manejo de carga de archivo
const handleFileUpload = async (file: File, tipoArchivo: EndpointKey, tab: TabKey) => {
    // Validar archivo
    const { isValid, error: clientValidationError } = validateFile(file, tipoArchivo, tab);
    if (!isValid && clientValidationError) {
        showNotification(clientValidationError, 'error');
        return;
    }

    // Iniciar loading
    uploadLoading.value = true;

    try {
        const formData = new FormData();
        formData.append('archivo_excel', file);
        formData.append('idFabricante', empresa.value!.idFabricante);

        // Determinar endpoint
        let endpoint: string;
        switch (tab) {
        case 'personas':
            endpoint = uploadEndpoints.personas[tipoArchivo as PersonaEndpointKeys];
            break;
        case 'productos':
            endpoint = uploadEndpoints.productos[tipoArchivo as ProductoEndpointKeys];
            break;
        default:
            throw new Error('Pestaña no soportada');
        }

        const response = await axios.post(endpoint, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (response.status === 200) {
        showNotification(response.data.message || 'Carga completada correctamente.', 'success', response.data);
        } else if (response.status === 207) {
        showNotification(response.data.message || 'Carga completada con advertencias.', 'warning', response.data);
        }
    } catch (error: any) {
        console.error("Error al subir el archivo:", error);
        let message = 'Error al subir el archivo.';
        if (error.response) {
        message = error.response.data.message || message;
        } else if (error.request) {
        message = 'No se pudo conectar con el servidor.';
        }
        showNotification(message, 'error');
    } finally {
        uploadLoading.value = false; // ← siempre apagar el loading
    }
};
// Watch para habilitar/deshabilitar según empresa
watch(
    () => empresa.value,
    (newEmpresa) => {
        if (newEmpresa) {
            enableAllDropzones();
        } else {
            disableAllDropzones();
            clearNotifications();
        }
    },
    { immediate: true }
)

</script>

<template>
    <Head title="Monitor" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="relative min-h-[100vh] flex-1 rounded-xl">
            <div v-if="downloadLoading" class="flex justify-center items-center p-4 mb-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600">Generando archivo Excel...</span>
            </div>
            <div v-if="uploadLoading" class="flex justify-center items-center p-4 mb-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-green-600"></div>
                <span class="ml-2 text-gray-600">Subiendo archivo...</span>
            </div>
            <div v-if="!(usuario.idgrupo_persona === 'GRT' && empresas.length === 1)" class="mb-6">
                <label class="block text-sm font-medium text-gray-400 mb-1">Empresa</label>
                <SingleSelectSearch
                v-model="selectedEmpresaId"
                :options="empresaOptions"
                placeholder="Selecciona una empresa…"
                maxListHeight="12rem"
                />
            </div>
            <div v-else class="mb-6">
                <label class="block text-sm font-medium text-gray-400 mb-1">Empresa</label>
                <div class="px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-md">
                    {{ empresas[0].nombre_completo_razon_social }}
                </div>
            </div>
            <!-- Diálogo de Errores -->
                <div v-if="showErrorDialog" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-xl font-bold mb-4 text-red-600">Error en la Carga de Archivos</h3>
                    <p class="mb-4 text-gray-700">{{ errorDialogMessage }}</p>

                    <div v-if="errorSummary" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                        <p class="font-semibold text-red-700 mb-2">Resumen de la Importación:</p>
                        <p class="text-sm text-red-600 mb-2">
                            {{ errorSummary.mensaje_general }}
                            <span v-if="errorSummary.total_filas_fallidas > 0"> ({{ errorSummary.total_filas_fallidas }} filas afectadas)</span>
                        </p>
                        <ul v-if="errorSummary.detalles_por_tipo && errorSummary.detalles_por_tipo.length > 0" class="list-disc list-inside text-sm text-red-600">
                            <li v-for="(detail, index) in errorSummary.detalles_por_tipo" :key="index">
                                {{ detail.tipo }}: <span class="font-bold">{{ detail.cantidad }}</span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="errorDialogDetails.length > 0" class="mt-4">
                    <p class="font-semibold text-gray-800">Detalles Adicionales:</p>
                    <ul class="list-disc list-inside text-sm text-gray-600 max-h-40 overflow-y-auto border border-gray-200 p-3 rounded-md mt-2">
                        <li v-for="(detail, index) in errorDialogDetails" :key="index">
                        <template v-if="typeof detail === 'string'">
                            {{ detail }}
                        </template>
                        <template v-else-if="detail.fila">
                            <span class="font-medium">Fila {{ detail.fila }}</span>:
                            <template v-if="detail.columna_excel"> Columna "{{ detail.columna_excel }}" -</template>
                            <template v-if="detail.errores"> {{ detail.errores.join(', ') }}</template>
                            <template v-if="detail.error"> {{ detail.error }}</template>
                        </template>
                        <template v-else-if="detail.mensaje_error">
                            <span class="font-medium">{{ detail.tipo_error ? `[${detail.tipo_error.split('\\').pop()}] ` : '' }}</span>{{ detail.mensaje_error }}
                        </template>
                        <template v-else>
                            {{ JSON.stringify(detail) }}
                        </template>
                        </li>
                    </ul>
                    </div>

                    <div class="mt-6 text-right">
                    <button
                        @click="showErrorDialog = false"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                    >
                        Cerrar
                    </button>
                    </div>
                </div>
                </div>

                <div v-if="showToast" :class="[
                    'fixed bottom-4 right-4 p-4 rounded-md shadow-lg text-white z-50',
                    toastType === 'success' ? 'bg-green-500' : (toastType === 'warning' ? 'bg-orange-500' : 'bg-red-500') 
                ]">
                    <p class="font-bold">{{ toastMessage }}</p>
                    <p v-if="toastDetails" class="text-sm">{{ toastDetails }}</p>
                </div>

            <!-- Pestañas -->
            <div class="mb-6">
                <nav class="flex space-x-4 border-b border-gray-200">
                    <button 
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="currentTab = tab.id"
                        :class="{
                            'border-indigo-500 text-indigo-600': currentTab === tab.id,
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': currentTab !== tab.id
                        }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>
            <div>
                <!-- Pestaña Productos -->
                <div v-if="currentTab === 'productos'">
                <div class="border rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Dropzone
                        label="Archivos de productos"
                        :empresa="empresa"
                        :loading="downloadLoading || downloadLoading"
                        upload-endpoint="/upload/productos"
                        :on-download="() => handleDownload('/export/productos/productoDown', 'Productos')"
                        @file-selected="(file) => handleFileUpload(file, 'productos', 'productos')"
                    />
                <!--    <Dropzone
                        label="Materiales para Clientes"
                        :empresa="empresa"
                        :loading="downloadLoading || downloadLoading"
                        upload-endpoint="/upload/productos/materiales"
                        :on-download="() => handleDownload('/export/productos/materialesDownload', 'Materiales')"
                        @file-selected="(file) => handleFileUpload(file, 'materiales', 'productos')"
                    />
                                     el @file-selected= de materiales para clientes no está sirviendo, porque directamente no tiene controlador, ruta, ni nada,-->
                    </div>
                </div>
                </div>

                <!-- Pestaña Personas -->
                <div v-if="currentTab === 'personas'">
                <div class="border rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Dropzone
                        label="RFV, Mayoristas o Clientes"
                        :empresa="empresa"
                        :loading="downloadLoading || downloadLoading"
                        upload-endpoint="/upload/personas/personas"
                        :on-download="() => handleDownload('/export/personas/personasDown', 'RFV_Cliente_Mayorista')"
                        @file-selected="(file) => handleFileUpload(file, 'personas', 'personas')"
                    />
                    <Dropzone
                        label="Clientes por RFV"
                        :empresa="empresa"
                        :loading="downloadLoading || downloadLoading"
                        upload-endpoint="/upload/personas/clientesRfv"
                        :on-download="() => handleDownload('/export/personas/clientesRfvDown', 'Cliente_RFV')"
                        @file-selected="(file) => handleFileUpload(file, 'clientesRfv', 'personas')"
                    />
                    </div>
                </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>