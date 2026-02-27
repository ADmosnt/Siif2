<script setup lang="ts">
/**
 * Este componente representa una vista de gestión de fabricantes.
 * Incluye funciones para buscar, agregar, editar y eliminar fabricantes
 * mostrados en una tabla con filtros en tiempo real.
 */

import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Button from '@/components/ui/button/Button.vue' // Componente de botón personalizado

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'SIIF', href: 'Administrar/Facturar' },
  { label: 'Facturar', href: 'Admin/Facturar' },
]

// Función disparada al presionar el botón "Agregar"
const onAgregar = () => {
  showModal.value = true
 // alert('Aquí abriría un modal o navegaría a una nueva vista si es que hubiese algun@')

}

// Controla si el modal está visible o no
const showModal = ref(false)

//Controla el registro de informacion del fabricante
const nuevoFabricante = ref<Record<string, string>>({
  nombre: '',
  apellido: '',
  tipo: '',
  documento: '',
  email: '',
  telefono: '',
  direccion: '',
  pais: '',
  estado: '',
  ciudad: ''
})


// Campo de búsqueda
const search = ref('')

// Datos de fabricantes simulados
const data = ref([
  {
    id: 1,
    nombre: 'KIMICEG LABORATORIO',
    documento: 'KC',
    email: 'bimodalitli@gmail.com',
    telefono: '04142039340',
    direccion: 'Sabana Grande',
  },
  {
    id: 2,
    nombre: 'OMGGG LABORATORIO',
    documento: 'DEMO',
    email: 'hXDjodimilta@gmail.com',
    telefono: '4242223254',
    direccion: 'dirección Añn',
  },
])

// Función para editar un fabricante (actualmente muestra confirmación)
const onEditar = (item: any) => {
  const confirmacion = confirm(`¿Deseas editar al fabricante "${item.nombre}"?`)
  if (confirmacion) {
    alert(`(Simulado) Editar: ${item.nombre}`)
    // Aquí iría navegación o modal para edición
  }
}

// Función para eliminar un fabricante del listado
const onEliminar = (item: any) => {
  const confirmacion = confirm(`¿Deseas eliminar al fabricante "${item.nombre}"?`)
  if (confirmacion) {
    data.value = data.value.filter((f) => f.id !== item.id)
  }
}

const guardarFabricante = () => {
  const nombreCompleto = `${nuevoFabricante.value.nombre} ${nuevoFabricante.value.apellido}`.trim()

  if (!nombreCompleto || !nuevoFabricante.value.documento) {
    alert('Por favor completa al menos el nombre y el documento.')
    return
  }

  data.value.push({
    id: Date.now(),
    nombre: nombreCompleto,
    documento: nuevoFabricante.value.documento,
    email: nuevoFabricante.value.email,
    telefono: nuevoFabricante.value.telefono,
    direccion: nuevoFabricante.value.direccion
  })

  // Limpiar los campos
Object.keys(nuevoFabricante.value).forEach((k) => {
  nuevoFabricante.value[k] = ''
})

// Cerrar el modal
showModal.value = false
}



// Filtro en tiempo real basado en cualquier campo
const filteredData = computed(() =>
  data.value.filter((item) =>
    Object.values(item).some((val) =>
      String(val).toLowerCase().includes(search.value.toLowerCase())
    )
  )
)
</script>



<template>
  <!-- Título de la pestaña del navegador -->
  <Head title="Lista-Persona" />

  <!-- Layout principal con navegación -->
  <AppLayout :breadcrumbs="breadcrumbs">

    <!-- Contenedor general -->
    <div class="grid grid-cols-1 container mx-auto px-4 py-6">

      <!-- Tarjeta de contenido -->
      <div class="bg-white border border-gray-300 rounded-xl shadow-md p-6">

        <!-- Título principal de la sección -->
        <h2 class="text-lg font-semibold mb-1">Agregar - Fabricante</h2>

        <!-- Subtítulo indicando el fabricante activo -->
        <p class="mb-4">
          <strong>Fabricante:</strong> SIIF
        </p>

        <!-- Botón para agregar nuevo fabricante -->
        <div class="flex justify-start mb-4">
          <Button @click="onAgregar">Agregar</Button>
        </div>

        <!-- Contenedor de búsqueda -->
        <div class="flex flex-col px-4 py-4 sm:px-6 md:px-8 gap-4">

          <!-- Campo de búsqueda con ícono -->
          <div class="w-full mb-4">
            <div class="relative w-full">
              <input
                v-model="search"
                type="text"
                placeholder="Buscar"
                class="w-full pl-10 pr-4 py-2 border-0 border-b-2 border-blue-500 focus:outline-none focus:border-blue-700"
              />
              <!-- Ícono de lupa -->
              <div class="absolute left-0 top-0 h-full flex items-center pl-2 text-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                  viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla de datos de fabricantes -->
        <div class="overflow-x-auto mt-4">
          <table class="min-w-full border divide-y divide-gray-200">
            
            <!-- Encabezado de la tabla -->
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-2 text-left">Nombre</th>
                <th class="px-4 py-2 text-left">Documento</th>
                <th class="px-4 py-2 text-left">Email</th>
                <th class="px-4 py-2 text-left">Teléfono</th>
                <th class="px-4 py-2 text-left">Dirección</th>
                <th class="px-4 py-2 text-center">Acciones</th>
              </tr>
            </thead>

            <!-- Cuerpo dinámico de la tabla -->
            <tbody>
              <!-- Filas generadas desde filteredData -->
              <tr v-for="item in filteredData" :key="item.id" class="hover:bg-gray-50">
                <td class="px-4 py-2">{{ item.nombre }}</td>
                <td class="px-4 py-2">{{ item.documento }}</td>
                <td class="px-4 py-2">{{ item.email }}</td>
                <td class="px-4 py-2">{{ item.telefono }}</td>
                <td class="px-4 py-2">{{ item.direccion }}</td>
                <td class="px-4 py-2 text-center space-x-2">
                  <!-- Botón de edición -->
                  <button
                    @click="onEditar(item)"
                    class="text-blue-600 hover:text-blue-800"
                    title="Editar"
                  >
                    ✎
                  </button>

                  <!-- Botón de eliminación -->
                  <button
                    @click="onEliminar(item)"
                    class="text-pink-600 hover:text-pink-800"
                    title="Eliminar"
                  >
                    🗑
                  </button>

    <!-- Modal completo -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white w-full max-w-3xl rounded-lg shadow-xl p-6 relative">

    <!-- Título -->
    <h3 class="text-xl font-semibold mb-6">Agregar</h3>

    <!-- Sección: Datos Personales -->
    <div class="mb-6">
      <h4 class="text-sm font-medium text-gray-700 mb-4">Datos Personales</h4>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="nuevoFabricante.nombre" type="text" class="border-b border-gray-400 focus:outline-none py-1" placeholder="Nombre" />
        <input v-model="nuevoFabricante.apellido" type="text" class="border-b border-gray-400 focus:outline-none py-1" placeholder="Apellido" />
        <select v-model="nuevoFabricante.tipo" class="border-b border-gray-400 focus:outline-none py-1">
          <option value="">Tipo</option>
          <option>Natural</option>
          <option>Juridico</option>
          <option>Fundacion</option>
          <option>Organizacion</option>
          <option>Gubernamental</option>
          <option>Sin Fines de Lucro</option>
        </select>
        <input v-model="nuevoFabricante.documento" type="text" class="border-b border-gray-400 focus:outline-none py-1" placeholder="Documento" />
        <input v-model="nuevoFabricante.email" type="email" class="border-b border-gray-400 focus:outline-none py-1" placeholder="Email" />
        <input v-model="nuevoFabricante.telefono" type="text" class="border-b border-gray-400 focus:outline-none py-1" placeholder="Teléfono" />
        <input v-model="nuevoFabricante.direccion" type="text" class="col-span-2 md:col-span-3 border-b border-gray-400 focus:outline-none py-1" placeholder="Dirección" />
      </div>
    </div>

    <!-- Sección: Ubicación -->
    <div class="mb-6">
      <h4 class="text-sm font-medium text-gray-700 mb-4">Ubicación</h4>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <select v-model="nuevoFabricante.pais" class="border-b border-gray-400 focus:outline-none py-1">
          <option value="">País</option>
        </select>
        <select v-model="nuevoFabricante.estado" class="border-b border-gray-400 focus:outline-none py-1">
          <option value="">Estado</option>
        </select>
        <select v-model="nuevoFabricante.ciudad" class="border-b border-gray-400 focus:outline-none py-1">
          <option value="">Ciudad</option>
        </select>
      </div>
    </div>

    <!-- Botones de acción -->
    <div class="flex justify-end gap-2 mt-6">
      <button
        @click="showModal = false"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded"
      >
        CANCELAR
      </button>
      <button
        class="px-4 py-2 bg-[#65bf10] hover:bg-[#57a80e] text-white text-sm font-bold rounded"
      >
        GUARDAR
      </button>
    </div>

    <!-- Botón cerrar (esquina superior) -->
    <button
      @click="showModal = false"
      class="absolute top-2 right-3 text-gray-400 hover:text-gray-600 text-2xl leading-none"
    >
      ×
    </button>
  </div>
</div>

                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
