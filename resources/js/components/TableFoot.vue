<script setup lang="ts">
import {
  Table,
  TableCell,
  TableRow,
  TableFooter
} from '@/components/ui/table'

import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectGroup,
  SelectItem
} from '@/components/ui/select'


import { ref, watch, computed } from 'vue'

// PROPS
const props = defineProps<{
  totalRecords: number
}>()

const emit = defineEmits<{
  (e: 'update:page', value: number): void
  (e: 'update:pageSize', value: string): void
}>()

const currentPage = ref(1)
const pageSize = ref('15')

const perPage = computed(() =>
  pageSize.value === 'all'
    ? props.totalRecords
    : Number(pageSize.value)
)

const totalPages = computed(() =>
  pageSize.value === 'all'
    ? 1
    : Math.ceil(props.totalRecords / perPage.value)
)

watch(pageSize, () => {
  currentPage.value = 1
  emit('update:pageSize', pageSize.value)
  emit('update:page', 1)
})

watch(currentPage, () => {
  emit('update:page', currentPage.value)
})

function prevPage() {
  if (currentPage.value > 1) currentPage.value--
}
function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++
}
</script>

<template>
    <Table>
    <TableFooter>
        <TableRow>
        <!-- colspan = total de columnas de tu tabla (aquí 12) -->
            <TableCell colspan="12" class="px-4 py-3">
              <!-- Contenedor principal: siempre fila, items centrados, espacio máximo entre ellos -->
              <div class="flex items-center justify-between w-full">

                <!-- Bloque IZQUIERDO: Reg./pág. + Select -->
                <div class="flex items-center gap-2">
                  <span class="whitespace-nowrap text-sm">Reg./pág.</span>
                  <Select v-model="pageSize">
                    <SelectTrigger class="w-20">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectItem value="15">15</SelectItem>
                        <SelectItem value="25">25</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                        <SelectItem value="all">All</SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                </div>

                <!-- Bloque DERECHO: Paginación -->
                <div class="flex items-center space-x-4">
                  <button
                    class="p-1 rounded hover:bg-gray-200"
                    :disabled="currentPage === 1 || pageSize === 'all'"
                    @click="prevPage"
                  >‹</button>

                  <span>{{ pageSize==='all' ? 1 : currentPage }} / {{ totalPages }}</span>

                  <button
                    class="p-1 rounded hover:bg-gray-200"
                    :disabled="currentPage === totalPages || pageSize === 'all'"
                    @click="nextPage"
                  >›</button>
                </div>

              </div>
            </TableCell>
        </TableRow>
    </TableFooter>
    </Table>
</template>