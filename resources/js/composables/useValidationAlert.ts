import { ref, Ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface AlertState {
  success: Ref<boolean>;
  error: Ref<boolean>;
  warning: Ref<boolean>;
  message: Ref<string>;
  showWarning(message: string): void;
  showError(message: string): void;
  showSuccess(message: string): void;
  clear(): void;
}

export function useValidationAlert(): AlertState {
  const page = usePage();
  const success = ref(false);
  const error = ref(false);
  const warning = ref(false);
  const message = ref('');
  
  // Trackeamos el origen del mensaje
  const messageOrigin = ref<'local' | 'flash'>('local');

  // Función centralizada para limpiar estados
  const clear = () => {
    success.value = false;
    error.value = false;
    warning.value = false;
    message.value = '';
    messageOrigin.value = 'local';
  };

  const showWarning = (msg: string) => {
    // Solo limpia si el mensaje actual es local
    if (messageOrigin.value === 'local') {
      clear();
    }
    messageOrigin.value = 'local';
    warning.value = true;
    message.value = msg;
  };

  const showError = (msg: string) => {
    if (messageOrigin.value === 'local') {
      clear();
    }
    messageOrigin.value = 'local';
    error.value = true;
    message.value = msg;
  };

  const showSuccess = (msg: string) => {
    if (messageOrigin.value === 'local') {
      clear();
    }
    messageOrigin.value = 'local';
    success.value = true;
    message.value = msg;
  };
  
  // === INTEGRACIÓN DE INERTIA MEJORADA ===
  watch(() => page.props.flash, (flashMessages) => {
      // Solo procesar si hay mensajes flash nuevos
      if (flashMessages?.success || flashMessages?.error || flashMessages?.warning) {
          // Limpia cualquier mensaje local previo
          clear();
          messageOrigin.value = 'flash';
          
          if (flashMessages.success) {
              success.value = true;
              message.value = flashMessages.success;
          } else if (flashMessages.error) {
              error.value = true;
              message.value = flashMessages.error;
          } else if (flashMessages.warning) {
              warning.value = true;
              message.value = flashMessages.warning;
          }

          // Auto-limpiar después de 5 segundos solo para flash messages
          setTimeout(() => {
              if (messageOrigin.value === 'flash') {
                  clear();
                  // También limpia los flash messages en page props
                  page.props.flash = {};
              }
          }, 5000);
      }
  }, { deep: true, immediate: true });
  // ==============================

  return {
    success,
    error,
    warning,
    message,
    showWarning,
    showError,
    showSuccess,
    clear,
  };
}