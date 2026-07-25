<!--resources/js/pages/Administrar/seccion-->
<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { configMap } from '@/configs/admin'
import AppLayout from '@/layouts/AppLayout.vue'
import GlobalTable from '@/components/GlobalTable.vue'
import Input from '@/components/ui/input/Input.vue'
import ModalGen from '@/components/Administrar/ModalGen.vue'
import { useDebounceFn } from '@vueuse/core'
import axios from 'axios'

const props = defineProps<{
  tipo: string;
  items: any;
  options: Record<string, any>;
  filters?: { search?: string };
}>()

// --- PERMISOS POR ROL ---
// El backend ya bloquea create/update/destroy segun el rol (defensa real);
// esto solo controla si se muestran los botones/columna en la UI.
const page = usePage()
const userRole = computed(() => (page.props.auth as any)?.role as string | undefined)
const canEdit = computed(() => {
  const roles = config.value.rolesQuePuedenEditar
  if (!roles) return true
  return !!userRole.value && roles.includes(userRole.value)
})

// --- CONFIGURACIÓN ---
const config = computed(() => configMap[props.tipo] || {
  title: 'Error',
  routePrefix: '', 
  breadcrumbs: [],
  columns: [], 
  fields: [] 
})

// --- ESTADO ---
const search = ref(props.filters?.search || '')
const showModal = ref(false)
const isEditing = ref(false)
const editId = ref<number | null>(null)

// --- FORMULARIO ---
const initialFormState = computed(() => {
  const state: Record<string, any> = {}
  if (config.value.fields) {
    config.value.fields.forEach(f => state[f.name] = '')
  }
  return state
})

const form = useForm(initialFormState.value)

// --- ACCIONES DEL MODAL ---
function openCreateModal() {
  isEditing.value = false
  editId.value = null
  
  // Resetear el formulario
  Object.keys(form.data()).forEach(key => {
    form[key] = ''
  })
  
  // Usar nextTick para asegurar que el DOM se actualice
  nextTick(() => {
    form.clearErrors()
    showModal.value = true
  })
}

async function openEditModal(row: any) {
  isEditing.value = true
  editId.value = row.id
  
  form.clearErrors()
  
  await nextTick()
  
  form.nombre = row.nombre_completo || ''
  form.email = row.email || ''
  
  if (row.metadata) {
    Object.keys(row.metadata).forEach(key => {
      const metaValue = row.metadata[key]
      
      if (metaValue && typeof metaValue === 'object' && 'value' in metaValue) {
        form[key] = metaValue.value
      } else {
        form[key] = metaValue || ''
      }
    })
  }
  
  await nextTick()
  showModal.value = true
}

function handleSubmit() {
  const url = `/${config.value.routePrefix}`

  if (isEditing.value) {
    form.put(`${url}/${editId.value}`, {
      onSuccess: () => {
        showModal.value = false
        nextTick(() => {
          form.reset()
        })
      },
      onError: (errors) => {
        console.error('Errores de validación:', errors)
      }
    })
  } else {
    form.post(url, {
      onSuccess: () => {
        showModal.value = false
        nextTick(() => {
          form.reset()
        })
      },
      onError: (errors) => {
        console.error('Errores de validación:', errors)
      }
    })
  }
}

// --- BUSCADOR ---
const handleSearch = useDebounceFn((val: string) => {
  router.get(window.location.pathname, { search: val }, {
    preserveState: true,
    replace: true
  })
}, 500)

watch(search, handleSearch)

// --- PAGINACION ---
// GlobalTable/TablePagination solo emiten estos eventos, no navegan por su
// cuenta: sin estos handlers, cambiar de pagina o de tamano no tiene efecto.
function handlePageChange(page: number) {
  router.get(window.location.pathname, {
    search: search.value,
    page,
    size: props.items.per_page,
  }, { preserveState: true, replace: true })
}

function handlePageSizeChange(size: string) {
  router.get(window.location.pathname, {
    search: search.value,
    size,
    page: 1,
  }, { preserveState: true, replace: true })
}

// --- TOGGLE ESTATUS EMPRESA ---
const togglingStatus = ref(false)

async function toggleEmpresaStatus(row: any) {
  const idFabricante = row.idFabricante || row.id
  const currentStatus = row.estatus === 'Activo' ? 'activa' : 'inactiva'
  const newAction = row.estatus === 'Activo' ? 'DESACTIVAR' : 'ACTIVAR'

  if (!confirm(`¿Está seguro de ${newAction} la empresa "${row.nombre_completo}"?\n\nEsto afectará a TODOS los usuarios de esta empresa.`)) {
    return
  }

  togglingStatus.value = true
  try {
    const response = await axios.patch(`/empresas/${idFabricante}/toggle-status`)
    alert(response.data.message)
    router.reload({ preserveScroll: true })
  } catch (err: any) {
    alert(err.response?.data?.error || 'Error al cambiar el estatus')
  } finally {
    togglingStatus.value = false
  }
}

// --- ACCIONES TABLA ---
const tableActions = computed(() => {
  if (!canEdit.value) return []

  return [
    { key: 'edit', handler: openEditModal },
    {
      key: 'delete',
      handler: (row: any) => {
        if (confirm(`¿Está seguro de eliminar a ${row.nombre_completo}?`)) {
          router.delete(`/${config.value.routePrefix}/${row.id}`, {
            preserveScroll: true
          })
        }
      }
    }
  ]
})
</script>

<template>
  <Head :title="config.title" />

  <AppLayout :breadcrumbs="config.breadcrumbs">
    <div class="p-6 space-y-6">
      
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
        {{ config.title }}
      </h1>

      <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border mb-4">
         <Input v-model="search" placeholder="Buscar..." class="max-w-sm"/>
      </div>

      <GlobalTable
        :columns="config.columns"
        :rows="items.data"
        :actions="tableActions"

        :links="items.meta ? items.meta.links : items.links"
        :total-records="items.total"
        :page-size="String(items.per_page)"
        :current-page="items.current_page"

        :show-add-button="canEdit"
        :add-button-label="`Agregar ${config.title.split(' ').pop()}`"
        add-button-label-short="+"

        :auto-add-actions-column="canEdit"

        @add="openCreateModal"
        @update:page="handlePageChange"
        @update:pageSize="handlePageSizeChange"
      >
        <template #cell-estatus="{ row }">
          <button
            @click="toggleEmpresaStatus(row)"
            :disabled="togglingStatus"
            class="px-3 py-1 rounded-full text-xs font-semibold transition-colors"
            :class="row.estatus === 'Activo'
              ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900 dark:text-green-300'
              : 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900 dark:text-red-300'"
          >
            {{ row.estatus }}
          </button>
        </template>
      </GlobalTable>

      <ModalGen
        v-model="showModal"
        :title="isEditing ? `Editar ${config.title}` : `Agregar ${config.title}`"
        :fields="config.fields || []"
        :form="form"
        :options="options"  
        :is-editing="isEditing"
        @confirm="handleSubmit"
      />

    </div>
  </AppLayout>
</template>