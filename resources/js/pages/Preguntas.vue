<!-- resources/js/pages/Preguntas.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import FaqItem from '@/components/Preguntas/FaqItem.vue'
import { ref } from 'vue'
import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF', href: '/dashboard' },
  { label: 'Preguntas Frecuentes'},
]

interface Faq { 
  id: number; 
  q: string; 
  a: string;
  category?: string;
}

// Organizar FAQs por categorías
const faqs = ref<Faq[]>([
  {
    id: 1,
    q: '¿Cómo me registro?',
    a: 'Se le creará un usuario y clave de un solo uso a su correo electrónico, con previo acuerdo con su empresa contratante.',
    category: 'registro'
  },
  {
    id: 2,
    q: '¿En cuántos dispositivos puedo descargar la APP?',
    a: 'La APP SIIF se puede instalar por cada equipo autorizado para la función de ventas (FV). Una vez iniciada sesión en un dispositivo, no podrá usarse el mismo usuario en otro, salvo que exista un acuerdo para más de un equipo por representante.',
    category: 'uso'
  },
  {
    id: 3,
    q: '¿Puedo usar la aplicación sin WIFI o Datos de Internet?',
    a: 'Para sincronizar, reportar o usar funciones básicas de la aplicación se requiere conexión a Internet.',
    category: 'conectividad'
  },
  {
    id: 4,
    q: '¿Puedo reportar clientes que no pertenezcan a mi fichero o aparezcan en la APP?',
    a: 'Sí. Repórtelos como CLIENTE NO FICHADO indicando nombre y apellido. Con ese reporte se enviará la solicitud de ALTA para incorporarlo al fichero y sincronizarlo; así podrá seleccionarlo en el siguiente ciclo.',
    category: 'clientes'
  },
  {
    id: 5,
    q: 'Diferencias entre MÓDULO RTR y MÓDULO CT',
    a: `RTR está orientado a reportar actividades y dar seguimiento a la cobertura según el plan acordado con su empresa contratante.

CT está orientado a procesar y dar seguimiento a cualquier toma de pedido o transferencia, conforme a lo acordado con su empresa contratante.`,
    category: 'modulos'
  },
  {
    id: 6,
    q: '¿Qué visualizo en el botón u opción PERFIL?',
    a: 'Sus datos, y un resumen de actividades realizadas hasta el momento con la aplicación.',
    category: 'perfil'
  },
  {
    id: 7,
    q: 'EN EL CASO DE:',
    a: `No visualizo mi nombre correcto en la aplicación.
No se visualizan los productos correctos.
No se visualizan los mayoristas correctos.
No se visualizan mis reportes de representantes o FV.
No puedo entrar en la aplicación.
Alguna opción no funciona correctamente.
No puedo descargar la aplicación desde el e-mail.
Da error al instalar.
Se cierra sin aviso.
Cualquier inconveniente adicional: por favor, contáctenos al e-mail empresarial. Responderemos a la brevedad.`,
    category: 'soporte'
  },
])

const openId = ref<number | null>(null)

function toggle(id: number) {
  openId.value = openId.value === id ? null : id
}

</script>

<template>
  <Head title="Preguntas Frecuentes" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class=" bg-gray-50 dark:bg-[#18171d] transition-colors duration-200">
      <div class="container mx-auto px-4 sm:px-6 py-8 max-w-4xl">

        <div class="text-center mb-8">
          <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Preguntas Frecuentes
          </h1>
          <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
            Encuentra respuestas a las preguntas más comunes sobre el uso de SIIF
          </p>
        </div>

        <section class="space-y-4">
          <FaqItem
            v-for="(f, i) in faqs"
            :key="f.id"
            :index="i + 1"
            :question="f.q"
            :answer="f.a"
            :open="openId === f.id"
            @toggle="toggle(f.id)"
          />
        </section>

        <div class="mt-12 p-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
          <div class="text-center">
            <h3 class="text-xl font-semibold text-blue-900 dark:text-blue-100 mb-2">
              ¿No encontraste lo que buscabas?
            </h3>
            <p class="text-blue-700 dark:text-blue-300 mb-4">
              Contáctanos y te ayudaremos con tu consulta
            </p>
            <a
              href="/Contacto"
              class="inline-flex items-center px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition-colors duration-200"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              Contactar Soporte
            </a>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>