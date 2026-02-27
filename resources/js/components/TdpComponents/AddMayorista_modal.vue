<!-- resources/js/components/AddMayorista_modal.vue -->
<script setup lang="ts">
import { ref, watch } from 'vue'
import Button from '../ui/button/Button.vue'
import BaseModal from '../BaseModal.vue'
import GlobalTable from '../GlobalTable.vue'
import Input from '../ui/input/Input.vue'
import { debounce } from 'lodash' 
import { type Mayoristas } from '@/types/interfaces';

// --- PROPS ---
const props = defineProps<{
  modelValue: boolean,
  mayoristas: {
    data: Mayoristas[],
    total: number,
    current_page: number,
    per_page: number
  }
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'select', row: Mayoristas): void
  (e: 'fetch', payload: { page: number, pageSize: string, search: string }): void
}>()

// --- ESTADO INTERNO DEL MODAL ---
const search = ref('')
const currentPage = ref(1)
const pageSize = ref('15')

const internal = ref(props.modelValue)
watch(() => props.modelValue, v => {
    internal.value = v
    if (v) {
        fetchData()
    }
})
watch(internal, v => emit('update:modelValue', v))

// --- LÓGICA DE BÚSQUEDA ---
const fetchData = debounce(() => {
    emit('fetch', {
        page: currentPage.value,
        pageSize: pageSize.value,
        search: search.value
    })
}, 300) 

// Observa los cambios en los filtros para lanzar una nueva búsqueda
watch([search, currentPage, pageSize], fetchData)
 
function agregar(row: Mayoristas) {
  emit('select', row)
  internal.value = false
}

</script>

<template >
  <BaseModal v-model="internal" title="Buscar Mayorista" size="lg">
    <template #default>
      <div class="mb-4">
        <Input v-model="search" type="text" placeholder="Buscar..." />
      </div>  
      <div class="max-h-[400px] overflow-y-auto">
        <GlobalTable
          :columns="[{ key: 'codigo', label: 'Código' }, { key: 'mayorista', label: 'Mayorista' }]" 
          :rows="props.mayoristas.data"  
          :total-records="props.mayoristas.total"
          :current-page="props.mayoristas.current_page"
          :page-size="String(props.mayoristas.per_page)"
          :actions="[{ key: 'add', handler: agregar }]"
          autoAddActionsColumn
          @update:page="currentPage = $event"
          @update:pageSize="pageSize = $event"                
        />
      </div>
    </template>
    <template #footer>
      <Button @click="internal = false">Cancelar</Button>
    </template>
  </BaseModal>
</template>