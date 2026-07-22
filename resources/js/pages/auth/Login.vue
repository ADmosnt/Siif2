<!-- resources/js/pages/auth/Login.vue -->
<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, AlertCircle, WifiOff } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import SiifIcon from '@/components/icons/SiifIconBC3.png';
import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
} from '@/components/ui/field';
import { verifyOfflineToken, createOfflineSession } from '@/offline/authService';
import { useOfflineStore } from '@/stores/offlineStore';

defineProps<{
  status?: string;
}>();

const form = useForm({
  name: '',
  password: '',
});

const generalError = ref<string | null>(null);
const isSubmitting = ref(false);
const offlineMode = ref(!navigator.onLine);

window.addEventListener('online', () => { offlineMode.value = false })
window.addEventListener('offline', () => { offlineMode.value = true })

const submitOffline = async () => {
  generalError.value = null;
  isSubmitting.value = true;

  try {
    const result = await verifyOfflineToken(form.name);

    if (!result.success) {
      generalError.value = result.error || 'Error de autenticacion offline.';
      return;
    }

    const session = createOfflineSession(result.user!);
    const store = useOfflineStore();
    store.setOfflineSession(session);

    window.location.href = '/tdp';
  } catch (err) {
    generalError.value = 'Error verificando credenciales localmente.';
  } finally {
    isSubmitting.value = false;
  }
};

const submitOnline = () => {
  generalError.value = null;
  isSubmitting.value = true;

  form.post(route('login'), {
    onFinish: () => {
      form.reset('password');
      isSubmitting.value = false;
    },
    onError: (errors) => {
      isSubmitting.value = false;
      if (!errors.name && !errors.password) {
        generalError.value = 'Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo.';
      }
    },
    onSuccess: () => {
      generalError.value = null;
      const store = useOfflineStore();
      store.cacheAllOnLogin().catch(() => {});
    },
    preserveScroll: true,
  });
};

const submit = () => {
  if (offlineMode.value) {
    submitOffline();
  } else {
    submitOnline();
  }
};

const clearGeneralError = () => {
  generalError.value = null;
};
</script>

<template>
  <Head title="Log in" />

  <div class="bg-muted flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
    <div class="w-full max-w-sm md:max-w-4xl">
      <div class="flex flex-col gap-6">
        <Card class="overflow-hidden p-0">
          <CardContent class="grid p-0 md:grid-cols-2">
            <!-- Formulario (columna izquierda) -->
            <form @submit.prevent="submit" class="p-6 md:p-8">
              <FieldGroup>
                <!-- Header del formulario -->
                <div class="flex flex-col items-center gap-2 text-center mb-6">
                  <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Bienvenido a SIIF
                  </h1>
                  <p class="text-muted-foreground text-balance py-2">
                    Introduce tus credenciales para acceder a tu cuenta.
                  </p>
                </div>

                <!-- Indicador offline -->
                <div v-if="offlineMode" class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                  <div class="flex items-center text-sm text-amber-700 dark:text-amber-300">
                    <WifiOff class="h-4 w-4 mr-2 flex-shrink-0" />
                    <span>Modo offline — se usara la sesion guardada en este dispositivo (no se verifica contraseña).</span>
                  </div>
                </div>

                <!-- Mensaje de status -->
                <div v-if="status" class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                  <div class="text-sm text-green-700 dark:text-green-300 text-center">
                    {{ status }}
                  </div>
                </div>

                <!-- Error general -->
                <div v-if="generalError" class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                  <div class="flex items-center">
                    <AlertCircle class="h-4 w-4 text-red-500 mr-2" />
                    <div class="text-sm text-red-700 dark:text-red-300">
                      {{ generalError }}
                    </div>
                    <button
                      @click="clearGeneralError"
                      class="ml-auto text-red-500 hover:text-red-700"
                      type="button"
                    >
                      ×
                    </button>
                  </div>
                </div>

                <!-- Campo Username -->
                <Field>
                  <FieldLabel for="name" class="text-gray-700 dark:text-gray-300">
                    Usuario
                  </FieldLabel>
                  <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    autocomplete="username"
                    v-model="form.name"
                    placeholder="Enter your username"
                    @input="clearGeneralError"
                    :disabled="form.processing || isSubmitting"
                    :class="{
                      'border-red-500 dark:border-red-400': form.errors.name
                    }"
                  />
                  <div v-if="form.errors.name" class="text-sm text-red-600 dark:text-red-400 mt-1">
                    {{ form.errors.name }}
                  </div>
                </Field>

                <!-- Campo Password (no aplica en modo offline: el acceso se valida con el token guardado) -->
                <Field v-if="!offlineMode">
                  <div class="flex items-center">
                    <FieldLabel for="password" class="text-gray-700 dark:text-gray-300">
                      Contraseña
                    </FieldLabel>
                  </div>
                  <Input
                    id="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    v-model="form.password"
                    placeholder="Enter your password"
                    @input="clearGeneralError"
                    :disabled="form.processing || isSubmitting"
                    :class="{
                      'border-red-500 dark:border-red-400': form.errors.password
                    }"
                  />
                  <div v-if="form.errors.password" class="text-sm text-red-600 dark:text-red-400 mt-1">
                    {{ form.errors.password }}
                  </div>
                </Field>

                <!-- Botón de Login -->
                <Field>
                  <Button
                    type="submit"
                    :disabled="form.processing || isSubmitting"
                  >
                    <LoaderCircle
                      v-if="form.processing || isSubmitting"
                      class="h-4 w-4 animate-spin mr-2"
                    />
                    {{ (form.processing || isSubmitting)
                      ? 'Iniciando sesión...'
                      : offlineMode
                        ? 'Iniciar sesión (offline)'
                        : 'Iniciar sesión'
                    }}
                  </Button>
                </Field>

                <!-- Información adicional -->
                <FieldDescription class="text-center text-sm text-gray-600 dark:text-gray-400 mt-4">
                  ¿No tienes una cuenta? Ponte en contacto con tu administrador.
                </FieldDescription>
              </FieldGroup>
            </form>

            <!-- Columna derecha con imagen -->
            <div class="bg-linear-to-br from-blue-600 to-blue-800 relative hidden md:block">
              <div class="absolute inset-0 flex items-center justify-center p-8">
                <div class="text-center text-white">
                  <div class="w-16 h-16 bg-white/60 rounded-full flex items-center justify-center mx-auto mb-4">
                    <img
                      :src="SiifIcon"
                      alt="SIIF Logo"
                    />
                  </div>
                  <h2 class="text-xl font-bold mb-2">Sistema SIIF</h2>
                  <p class="text-blue-100">Sales Intelligence & Information Framework</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Footer adicional -->
        <FieldDescription class="px-6 text-center text-sm text-gray-500 dark:text-gray-400">
          © 2025 Bimodal Technology. Todos los derechos reservados.
        </FieldDescription>
      </div>
    </div>
  </div>
</template>
