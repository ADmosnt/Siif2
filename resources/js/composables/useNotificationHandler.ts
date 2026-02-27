import { ref, watch } from 'vue';

interface ErrorSummaryDetail {
    tipo: string;
    cantidad: number;
}

interface ErrorSummary {
    total_filas_fallidas: number;
    detalles_por_tipo?: ErrorSummaryDetail[];
    mensaje_general: string;
    estado_general: 'SUCCESS' | 'PARTIAL_SUCCESS' | 'CRITICAL_ERROR';
}

interface BackendErrorPayload {
    message?: string;
    summary?: ErrorSummary;
    errors?: Record<string, string[]>; // Para errores de validación de Laravel
    validation_errors?: any[]; // Maatwebsite\Excel\Validators\ValidationException
    validation_failures?: any[]; // Maatwebsite\Excel\Validators\Failure (SkipsOnFailure)
    processing_errors?: any[]; // SkipsOnError
    details?: string | any[] | object;
}

export function useNotificationHandler() {
    const showErrorDialog = ref(false);
    const errorDialogMessage = ref('');
    const errorDialogDetails = ref<any[]>([]);
    const errorSummary = ref<ErrorSummary | null>(null);

    const showToast = ref(false);
    const toastMessage = ref('');
    const toastType = ref<'success' | 'warning' | 'error'>('success');
    const toastDetails = ref('');
    let toastTimeout: ReturnType<typeof setTimeout> | null = null;

    const clearNotifications = () => {
        if (toastTimeout) {
            clearTimeout(toastTimeout);
            toastTimeout = null;
        }
        showErrorDialog.value = false;
        errorSummary.value = null;
        errorDialogDetails.value = [];
        showToast.value = false;
        toastMessage.value = '';
        toastDetails.value = '';
    };

    /**
     * Muestra una notificación al usuario, ya sea en un modal de error o un toast.
     * @param mainMessage Mensaje principal a mostrar.
     * @param type Tipo de notificación ('success', 'error', 'warning').
     * @param dataPayload Datos adicionales del backend, incluyendo summary y detalles.
     */
    const showNotification = (
        mainMessage: string,
        type: 'success' | 'error' | 'warning',
        dataPayload?: BackendErrorPayload
    ) => {
        clearNotifications();

        if (type === 'error' || type === 'warning') {
            errorDialogMessage.value = mainMessage;

            if (dataPayload?.summary) {
                errorSummary.value = dataPayload.summary;
                if (errorSummary.value.mensaje_general) {
                    errorDialogMessage.value = errorSummary.value.mensaje_general;
                }
            }

            // Recolectar todos los detalles de errores disponibles
            const collectedDetails: any[] = [];
            if (dataPayload) {
                if (dataPayload.errors && typeof dataPayload.errors === 'object') {
                    for (const key in dataPayload.errors) {
                        if (Array.isArray(dataPayload.errors[key])) {
                            collectedDetails.push(...dataPayload.errors[key]);
                        }
                    }
                }
                if (dataPayload.validation_errors && Array.isArray(dataPayload.validation_errors)) {
                    collectedDetails.push(...dataPayload.validation_errors);
                }
                if (dataPayload.validation_failures && Array.isArray(dataPayload.validation_failures)) {
                    collectedDetails.push(...dataPayload.validation_failures);
                }
                if (dataPayload.processing_errors && Array.isArray(dataPayload.processing_errors)) {
                    collectedDetails.push(...dataPayload.processing_errors);
                }
                if (dataPayload.details) {
                    if (typeof dataPayload.details === 'string') {
                        collectedDetails.push(dataPayload.details);
                    } else if (Array.isArray(dataPayload.details)) {
                        collectedDetails.push(...dataPayload.details);
                    } else if (typeof dataPayload.details === 'object') {
                        collectedDetails.push(JSON.stringify(dataPayload.details, null, 2));
                    }
                }
            }
            errorDialogDetails.value = collectedDetails;

            // Mostrar el modal si hay errores o un resumen que mostrar
            if (errorDialogDetails.value.length > 0 || errorSummary.value) {
                showErrorDialog.value = true;
            } else {
                showToast.value = true;
                toastMessage.value = mainMessage;
                toastType.value = type;
            }

        } else {
            toastMessage.value = mainMessage;
            toastType.value = type;
            if (dataPayload?.summary?.mensaje_general) {
                toastDetails.value = dataPayload.summary.mensaje_general;
            } else if (dataPayload?.details) {
                toastDetails.value = typeof dataPayload.details === 'string' ? dataPayload.details : JSON.stringify(dataPayload.details, null, 2);
            }
            showToast.value = true;

            toastTimeout = setTimeout(() => {
                showToast.value = false;
                toastTimeout = null;
            }, 5000);
        }
    };

    return {
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
    };
}