  <script setup lang="ts">
  import { ref } from 'vue'
  import AppLayout from '@/layouts/AppLayout.vue'
  import GenericTable from '@/components/GenericTable.vue'
  import { type BreadcrumbItem } from '@/types'
  import { Head } from '@inertiajs/vue3'
  
  const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Dashboard', href: 'dashboard' },
    { label: 'Demo' },
  ]
  
  // Datos base
  const rows = Array.from({ length: 20 }, (_, i) => ({
    id:    i + 1,
    name:  `Persona ${i+1}`,
    age:   20 + (i % 30),
  }))
  const cols = [
    { key: 'id',   label: 'ID',    className: 'px-4' },
    { key: 'name', label: 'Nombre',className: 'px-4' },
    { key: 'age',  label: 'Edad',  className: 'px-4' },
  ]
  
  // Para fecha
  const colsWithDate = [
    ...cols,
    { key: 'date', label: 'Fecha', className: 'px-4' }
  ]
  const rowsWithDate = rows.map(r => ({
    ...r,
    date: new Date(2025, (r.id % 12), ((r.id*3)%28)+1)
              .toISOString().substr(0,10)
  }))
  
  // Refresh
  function fetchRows() {
    console.log('refrescar filas…')
  }
  
  // Selección múltiple
  const selected = ref<typeof rows>([])
  
  // Select + Acción
  const clientOptions = [
    { label: 'Cliente A', value: 'A' },
    { label: 'Cliente B', value: 'B' },
    { label: 'Cliente C', value: 'C' },
  ]
  const chosenClient = ref<string|null>(null)
  function onClientSelect(val: string) {
    chosenClient.value = val
  }
  
  </script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 space-y-12">

      <!-- 1) Filtro + Refresh -->
      <section>
        <h2 class="text-xl font-semibold mb-2"></h2>
        <GenericTable
          :columns="colsWithDate"
          :rows="rowsWithDate"
          showFilter
          filterPlaceholder="Buscar por nombre o fecha..."
          showRefresh
          :onRefresh="fetchRows"
        />
      </section>

      <!-- 2) Selección múltiple -->
      <section>
        <h2 class="text-xl font-semibold mb-2"></h2>
        <GenericTable
          :columns="cols"
          :rows="rows"
          showFilter
          selectable
          v-model="selected"
        />
      </section>

      <!-- 3) Select + Acción -->
      <section>
        <h2 class="text-xl font-semibold mb-2"></h2>
        <GenericTable
          :columns="cols"
          :rows="rows"
          showSelectAction
          :selectOptions="clientOptions"
          :onSelect="onClientSelect"
          showSaveButton
        />
        <p>Cliente elegido: {{ chosenClient }}</p>
      </section>

      <!-- 4) Combinado -->
      <section>
        <h2 class="text-xl font-semibold mb-2"></h2>
        <GenericTable
          :columns="colsWithDate"
          :rows="rowsWithDate"
          showFilter
          filterPlaceholder="Filtro general..."
          showRefresh
          :onRefresh="fetchRows"
          selectable
          v-model="selected"
          showSelectAction
          :selectOptions="clientOptions"
          :onSelect="onClientSelect"
          showSaveButton
        />
        <p>Seleccionadas: <pre>{{ selected }}</pre></p>
        <p>Cliente: {{ chosenClient }}</p>
      </section>

    </div>
  </AppLayout>
</template>

