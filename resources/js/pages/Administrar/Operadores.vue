<!-- resources/js/pages/Administrar/Operadores.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed, watch } from 'vue'
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import GenericTable from '@/components/GenericTable.vue';
import AddOperador_modal from '@/components/Administrar/AddOperador_modal.vue';

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'Dashboard', href: 'dashboard' },
  { label: 'Agregar Operadores' },
]

interface Operadores {
  nombre: string
  apellido: string
  documento: number
  email: string
  tlf: number
  addr: string

}

const rowsOperadores = ref<Operadores[]>([])

const colsOperadores = [
  { key: 'nombre',        label: 'Nombre',   className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'documento',    label: 'Documento de Identidad',     className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'email',  label: 'Email',   className: 'px-4 py-2 whitespace-nowrap text-left' },
  { key: 'tlf',  label: 'Telefono',   className: 'px-4 py-2 whitespace-nowrap text-right' },
  { key: 'addr',    label: 'Direccion',     className: 'px-4 py-2 whitespace-nowrap text-right' },
]

const showOperadorModal = ref(false)
function openOperadorModal() { showOperadorModal.value = true }

function agregarOperador(nuevo: Operadores) {
  rowsOperadores.value.push(nuevo)
}
function deleteOperadores(row: Operadores) {
  const idx = rowsOperadores.value.findIndex(r => r.id === row.id)
  if (idx !== -1) rowsOperadores.value.splice(idx, 1)
}

</script>

<template>
    <Head title="Operadores" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">

        <GenericTable
            :columns="colsOperadores"
            :rows="rowsOperadores"
            :actions="[{ key: 'delete', handler: deleteOperadores },
                       { key: 'edit'}]"
            autoAddActionsColumn
            showFilter
            filterPlaceholder="Buscar ..."
            showAddButton
            addButtonLabel="Agregar Operador"
            addButtonLabelShort="Add.O"
            @add="openOperadorModal"
        />

        <AddOperador_modal v-model="showOperadorModal" @confirm="agregarOperador" />           

        </div>
    </AppLayout>
</template>
