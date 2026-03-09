<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import { Textarea } from '@/components/ui/textarea'
import { ref, computed, onMounted } from 'vue'
import GenericCombobox from '@/components/GenericCombobox.vue'
import { type SelectOption } from '@/components/GenericCombobox.vue'
import Button from '@/components/ui/button/Button.vue'

interface EmpresaOption {
    idPersona: string
    nombre_completo_razon_social: string
    idFabricante: string
}

interface RepresentanteOption {
    id: string
    nombre: string
}

interface TipoNotificacion {
    id: number
    titulo: string
}

interface NotificacionItem {
    idNotificacion: number
    descripcion: string
    tipo: string | null
    fecha: string
    idestatus: number
    idPersona: string
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notificacion',
        href: '/notificacion',
    },
]

const props = defineProps<{
    empresas: EmpresaOption[]
    representantes: { data: RepresentanteOption[] }
    tipos: TipoNotificacion[]
    notificaciones: NotificacionItem[]
    activeCompanyId: string | null
    usuario: { idgrupo_persona: string; nombre: string }
}>()

const page = usePage()

// Estado del formulario
const descripcion = ref('')
const maxChars = 500
const empresaSeleccionada = ref<SelectOption | null>(null)
const rfvSeleccionado = ref<SelectOption | null>(null)
const tipoSeleccionado = ref<SelectOption | null>(null)
const enviando = ref(false)

// Es SIIF?
const esSiif = computed(() => props.usuario.idgrupo_persona === 'SIIF')

// Puede enviar notificaciones? (SIIF, GRT, SUP)
const puedeEnviar = computed(() =>
    ['SIIF', 'GRT', 'SUP'].includes(props.usuario.idgrupo_persona)
)

// Opciones del selector de empresa (igual que Dashboard)
const empresaOptions = computed(() => {
    const options = props.empresas.map(emp => ({
        label: emp.nombre_completo_razon_social,
        value: emp.idPersona,
    }))

    if (esSiif.value) {
        options.unshift({
            label: 'TODAS LAS EMPRESAS (MODO GLOBAL)',
            value: '',
        })
    }

    return options
})

// Opciones del selector de RFV
const rfvOptions = computed(() => {
    return props.representantes.data.map(rep => ({
        label: rep.nombre,
        value: rep.id,
    }))
})

// Opciones del selector de tipo de notificación
const tipoOptions = computed(() => {
    return props.tipos.map(tipo => ({
        label: tipo.titulo,
        value: String(tipo.id),
    }))
})

// Inicializar empresa seleccionada según contexto activo
onMounted(() => {
    if (props.activeCompanyId) {
        const encontrada = props.empresas.find(e => e.idPersona === props.activeCompanyId)
        if (encontrada) {
            empresaSeleccionada.value = {
                label: encontrada.nombre_completo_razon_social,
                value: encontrada.idPersona,
            }
        }
    } else if (esSiif.value) {
        empresaSeleccionada.value = { label: 'TODAS LAS EMPRESAS (MODO GLOBAL)', value: null }
    }
})

// Cambiar contexto de empresa (como Dashboard)
function seleccionarEmpresa() {
    const valor = empresaSeleccionada.value?.value
    const idAEnviar = valor === '' ? null : valor

    router.post(route('context.switch'), {
        id: idAEnviar,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            rfvSeleccionado.value = null
        },
    })
}

// Enviar notificación
function enviarNotificacion() {
    if (!descripcion.value.trim()) return

    enviando.value = true

    router.post(route('notificacion.enviar'), {
        descripcion: descripcion.value,
        idtipo: tipoSeleccionado.value?.value ? Number(tipoSeleccionado.value.value) : null,
        idPersona_destino: rfvSeleccionado.value?.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            descripcion.value = ''
            rfvSeleccionado.value = null
            tipoSeleccionado.value = null
            enviando.value = false
        },
        onError: () => {
            enviando.value = false
        },
    })
}

// Formato de fecha para la bandeja
function formatFecha(fecha: string): string {
    if (!fecha) return ''
    const d = new Date(fecha)
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// Mensajes flash
const flash = computed(() => (page.props as any).flash || {})
</script>

<template>
    <Head title="Notificacion" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-6">

            <!-- Mensaje flash -->
            <div v-if="flash.success" class="rounded-md border border-green-400 bg-green-100 px-4 py-3 text-sm text-green-700">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-md border border-red-400 bg-red-100 px-4 py-3 text-sm text-red-700">
                {{ flash.error }}
            </div>

            <!-- Alerta si no tiene permisos -->
            <div v-if="!puedeEnviar" class="rounded-md border border-yellow-400 bg-yellow-100 px-4 py-3 text-sm text-yellow-700">
                <b>Nota:</b> Solo usuarios SIIF, GRT y SUP pueden enviar notificaciones.
            </div>

            <!-- Selector de empresa (solo SIIF puede cambiar) -->
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Empresa</h3>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="w-full sm:w-80">
                        <GenericCombobox
                            v-model="empresaSeleccionada"
                            :options="empresaOptions"
                            :disabled="!esSiif"
                            placeholder="Seleccione la empresa"
                        />
                    </div>

                    <Button
                        v-if="esSiif"
                        @click="seleccionarEmpresa"
                        :disabled="!empresaSeleccionada || empresaSeleccionada.value === activeCompanyId"
                        class="w-full whitespace-nowrap sm:w-auto"
                    >
                        {{ empresaSeleccionada?.value === activeCompanyId ? 'SELECCIONADA' : 'SELECCIONAR' }}
                    </Button>
                </div>
            </section>

            <!-- Formulario de notificación (solo SIIF/GRT/SUP) -->
            <template v-if="puedeEnviar">
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Enviar Notificación</h3>

                    <div class="flex flex-col gap-4">
                        <!-- Fila: Tipo + RFV -->
                        <div class="flex flex-col gap-4 md:flex-row">
                            <!-- Tipo de notificación -->
                            <div class="w-full md:w-64">
                                <label class="mb-1 block text-xs text-gray-500 dark:text-gray-400">Tipo</label>
                                <GenericCombobox
                                    v-model="tipoSeleccionado"
                                    :options="tipoOptions"
                                    placeholder="Tipo de notificación"
                                />
                            </div>

                            <!-- Selector de RFV -->
                            <div class="w-full md:w-80">
                                <label class="mb-1 block text-xs text-gray-500 dark:text-gray-400">Vendedor (RFV)</label>
                                <GenericCombobox
                                    v-model="rfvSeleccionado"
                                    :options="rfvOptions"
                                    placeholder="Todos los vendedores"
                                />
                            </div>
                        </div>

                        <!-- Textarea -->
                        <div class="grid w-full gap-2">
                            <Textarea
                                v-model="descripcion"
                                placeholder="Escriba el mensaje de la notificación..."
                                :maxlength="maxChars"
                                rows="4"
                            />
                            <p class="text-sm text-gray-500">
                                {{ descripcion.length }} / {{ maxChars }} caracteres
                            </p>
                        </div>

                        <!-- Nota -->
                        <div class="rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-400">
                            <b>Nota:</b> De no seleccionar un vendedor, la notificación será enviada a todos los RFV
                            <template v-if="esSiif && (!activeCompanyId)">
                                de <b>todas las empresas</b>.
                            </template>
                            <template v-else>
                                de la empresa seleccionada.
                            </template>
                        </div>

                        <!-- Botón enviar -->
                        <div class="ml-auto flex gap-2">
                            <Button
                                @click="enviarNotificacion"
                                :disabled="!descripcion.trim() || enviando"
                            >
                                {{ enviando ? 'Enviando...' : 'Enviar Notificación' }}
                            </Button>
                        </div>
                    </div>
                </section>
            </template>

            <!-- Bandeja de notificaciones -->
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Bandeja de Notificaciones</h3>

                <div v-if="notificaciones.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    No hay notificaciones registradas.
                </div>

                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="notif in notificaciones"
                        :key="notif.idNotificacion"
                        class="flex flex-col gap-1 py-3 sm:flex-row sm:items-start sm:gap-4"
                    >
                        <!-- Indicador de estado -->
                        <div class="flex shrink-0 items-center gap-2 sm:w-32">
                            <span
                                class="inline-block h-2 w-2 rounded-full"
                                :class="notif.idestatus === 1 ? 'bg-green-500' : 'bg-gray-400'"
                            />
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatFecha(notif.fecha) }}
                            </span>
                        </div>

                        <!-- Contenido -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="notif.tipo"
                                    class="inline-block rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                                >
                                    {{ notif.tipo }}
                                </span>
                                <span
                                    v-if="notif.idPersona"
                                    class="text-xs text-gray-400 dark:text-gray-500"
                                >
                                    Para: {{ notif.idPersona }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                {{ notif.descripcion }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
