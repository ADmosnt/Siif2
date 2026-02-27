<!-- resources/js/pages/Contacto.vue -->
<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/app/AppSidebarLayout.vue'
import type { BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import Input from '@/components/ui/input/Input.vue'
import { Textarea } from '@/components/ui/textarea'
import { computed } from 'vue'
import { PageProps } from '@inertiajs/core'

interface FlashMessages {
  success?: string;
  error?: string;
}

interface InertiaPageProps extends PageProps {
  flash: FlashMessages;
}

const breadcrumbs: BreadcrumbItemType[] = [
  { label: 'SIIF', href: '/home' },
  { label: 'Contacto', href: '/Contacto' },
]

const maxChars = 1000

const form = useForm({
  nombre: '',
  email: '',
  asunto: '',
  mensaje: '',
})

const page = usePage<InertiaPageProps>()
const flashSuccess = computed(() => page.props.flash.success)
const flashError = computed(() => page.props.flash.error)

function submit() {
  form.post(route?.('contact.send') ?? '/contacto', {
    preserveScroll: true,
    onSuccess: () => {
      if (page.props.flash.success) {
        form.reset()
      }
    },
  })
}
</script>

<template>
  <Head title="Contacto" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen bg-gray-50 dark:bg-[#18171d] transition-colors duration-200">
      <div class="container mx-auto px-4 sm:px-6 py-8 max-w-6xl">
        <!-- Header de la página -->
        <div class="text-center mb-12">
          <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Contáctanos
          </h1>
          <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
            Estamos aquí para ayudarte. Completa el formulario y te responderemos a la brevedad.
          </p>
        </div>

                <!-- Mensaje de éxito desde el servidor -->
        <div 
          v-if="flashSuccess"
          class="mb-8 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"
        >
          <div class="flex items-center">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="text-green-800 dark:text-green-300 font-medium">
              {{ flashSuccess }}
            </p>
          </div>
        </div>

        <!-- Mensaje de error desde el servidor -->
        <div 
          v-if="flashError"
          class="mb-8 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"
        >
          <div class="flex items-center">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="text-red-800 dark:text-red-300 font-medium">
              {{ flashError }}
            </p>
          </div>
        </div>

        <!-- Contenedor principal -->
        <div class="grid gap-8 lg:grid-cols-3">
          <!-- Información de contacto -->
          <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                Información de contacto
              </h2>
              
              <div class="space-y-4">

                <!-- Soporte -->
                <div class="flex items-start">
                  <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                      <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Soporte</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Lun - Vie, 9:00 - 15:00</p>
                  </div>
                </div>

                <!-- Respuesta -->
                <div class="flex items-start">
                  <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                      <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Respuesta</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">En 24-48 horas</p>
                  </div>
                </div>
              </div>

              <!-- Separador -->
              <div class="my-6 border-t border-gray-200 dark:border-gray-700"></div>

              <!-- Información adicional -->
              <div class="text-sm text-gray-600 dark:text-gray-300">
                <p class="mb-2">¿Necesitas ayuda inmediata?</p>
                <p>Revisa nuestras <a href="/preguntas" class="text-blue-600 dark:text-blue-400 hover:underline">preguntas frecuentes</a> para respuestas rápidas.</p>
              </div>
            </div>
          </div>

          <!-- Formulario -->
          <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                Envíanos un mensaje
              </h2>

              <form @submit.prevent="submit" class="space-y-6">
                <!-- Grid de campos -->
                <div class="grid gap-6 sm:grid-cols-2">
                  <!-- Nombre -->
                  <div class="space-y-2">
                    <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Nombre y apellido *
                    </label>
                    <Input
                      id="nombre"
                      v-model="form.nombre"
                      type="text"
                      required
                      placeholder="Tu nombre completo"
                      :class="{
                        'border-red-500 dark:border-red-400': form.errors.nombre
                      }"
                    />
                    <p v-if="form.errors.nombre" class="text-sm text-red-600 dark:text-red-400">
                      {{ form.errors.nombre }}
                    </p>
                  </div>

                  <!-- Email -->
                  <div class="space-y-2">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Correo electrónico *
                    </label>
                    <Input
                      id="email"
                      v-model="form.email"
                      type="email"
                      required
                      placeholder="tu@email.com"
                      :class="{
                        'border-red-500 dark:border-red-400': form.errors.email
                      }"
                    />
                    <p v-if="form.errors.email" class="text-sm text-red-600 dark:text-red-400">
                      {{ form.errors.email }}
                    </p>
                  </div>
                </div>

                <!-- Asunto -->
                <div class="space-y-2">
                  <label for="asunto" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Asunto *
                  </label>
                  <Input
                    id="asunto"
                    v-model="form.asunto"
                    type="text"
                    required
                    placeholder="¿En qué podemos ayudarte?"
                    :class="{
                      'border-red-500 dark:border-red-400': form.errors.asunto
                    }"
                  />
                  <p v-if="form.errors.asunto" class="text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.asunto }}
                  </p>
                </div>

                <!-- Mensaje -->
                <div class="space-y-2">
                  <label for="mensaje" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Mensaje *
                  </label>
                  <Textarea
                    id="mensaje"
                    v-model="form.mensaje"
                    :maxlength="maxChars"
                    required
                    placeholder="Describe tu consulta o solicitud..."
                    :class="`min-h-32 ${
                      form.errors.mensaje ? 'border-red-500 dark:border-red-400' : ''
                    }`"
                  />
                  <!-- Contador de caracteres -->
                  <div class="flex justify-between items-center">
                    <p v-if="form.errors.mensaje" class="text-sm text-red-600 dark:text-red-400">
                      {{ form.errors.mensaje }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 ml-auto">
                      {{ form.mensaje.length }} / {{ maxChars }} caracteres
                    </p>
                  </div>
                </div>

                <!-- Botón de envío -->
                <div class="flex justify-end pt-4">
                  <Button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full sm:w-auto px-8 py-3 text-base font-medium"
                    :class="{
                      'opacity-50 cursor-not-allowed': form.processing
                    }"
                  >
                    <template v-if="form.processing">
                      <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Enviando...
                    </template>
                    <template v-else>
                      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                      </svg>
                      Enviar mensaje
                    </template>
                  </Button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>