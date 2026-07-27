<!-- resources/js/components/TablePagination.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  Select, SelectTrigger, SelectValue, SelectContent, SelectGroup, SelectItem
} from '@/components/ui/select'
import Button from "@/components/ui/button/Button.vue";

// --- 1. PROPS HÍBRIDAS ---
const props = defineProps<{
  // --- MODO INERTIA (NUEVO) ---
  links?: Array<{ url: string | null; label: string; active: boolean }>
  
  // --- MODO CALCULADO (ANTIGUO) ---
  currentPage?: number
  
  // --- PROPS COMUNES ---
  totalRecords: number
  pageSize: string
  
  // Props de configuración
  addButtonLabel?: string
  addButtonLabelShort?: string
  showAddButton?: boolean
}>()

// --- 2. EMITS ---
const emit = defineEmits<{
  (e: 'update:page', value: number): void
  (e: 'update:pageSize', value: string): void
  (e: 'add'): void
}>()


// --- 3. LÓGICA ---

// NOTA: La función 'onPageSizeChange' ya no es necesaria y ha sido eliminada.

function handleAdd() {
  emit('add')
}

// Lógica para el modo "calculado" (cuando no se usan links)
const totalPages = computed(() => {
  if (props.pageSize === 'all' || props.totalRecords === 0) return 1;
  return Math.ceil(props.totalRecords / Number(props.pageSize));
});

function prevPage() {
  if (props.currentPage && props.currentPage > 1) {
    emit('update:page', props.currentPage - 1);
  }
}

function nextPage() {
  if (props.currentPage && props.currentPage < totalPages.value) {
    emit('update:page', props.currentPage + 1);
  }
}

</script>

<template>
  <div class="flex items-center justify-between px-4 py-3 border-t">
    <!-- Sección Izquierda: Selector de Tamaño -->
    <div class="flex items-center gap-2">
      <span class="text-sm text-gray-700">Filas/pág.</span>
      <Select 
        :model-value="props.pageSize" 
        @update:model-value="(newValue) => { if (newValue) emit('update:pageSize', String(newValue)) }"
      >
        <SelectTrigger class="w-20"><SelectValue/></SelectTrigger>
        <SelectContent>
          <SelectGroup>
            <SelectItem value="15">15</SelectItem>
            <SelectItem value="25">25</SelectItem>
            <SelectItem value="50">50</SelectItem>
          </SelectGroup>
        </SelectContent>
      </Select>
    </div>
    
    <!-- Sección Central: Botón de Añadir (si aplica) -->
    <div v-if="props.showAddButton" class="flex justify-center">
      <Button @click="handleAdd">
        <span class="sm:hidden">{{ props.addButtonLabelShort || 'Btn.' }}</span>
        <span class="hidden sm:inline">{{ props.addButtonLabel || 'Botón' }}</span>
      </Button>
    </div>

    <!-- Sección Derecha: Botones de Paginación -->
    <div>
      <!-- MODO 1: Si existen 'links', usamos la paginación de Inertia -->
      <div  v-if="links" class="flex items-center space-x-1">
        <template v-for="(link, index) in links" :key="index">
          <Link
            v-if="link.url"
            :href="link.url"
            preserve-scroll
            v-html="link.label"
            class="px-3 py-1.5 text-sm rounded-md"
            :class="{
              'bg-blue-600 text-white hover:bg-blue-700': link.active,
              'hover:bg-gray-100 dark:hover:bg-gray-700': !link.active,
            }"
          />
          <span
            v-else
            v-html="link.label"
            class="px-3 py-1.5 text-sm text-gray-400 cursor-not-allowed"
          />
        </template>
      </div>

      <!-- MODO 2: Si no hay 'links', usamos la paginación calculada antigua -->
      <div v-else class="flex items-center justify-end space-x-4">
        <button
          class="p-1 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!currentPage || currentPage === 1"
          @click="prevPage"
        >‹</button>
        
        <span>{{ currentPage || 1 }} / {{ totalPages }}</span>
        
        <button
          class="p-1 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!currentPage || currentPage === totalPages"
          @click="nextPage"
        >›</button>
      </div>
    </div>

  </div>
</template>
