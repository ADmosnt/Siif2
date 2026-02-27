<script setup lang="ts">

// resources/js/components/Administrar/FormularioGen.vue

import { ref, computed, watch } from 'vue'
import { type FieldConfig } from '@/configs/admin'
import Input from '@/components/ui/input/Input.vue'
import GenericCombobox from '../GenericCombobox.vue';
import axios from 'axios';
import { onMounted } from 'vue';

const props = defineProps<{
  fields: FieldConfig[];
  form: any;
  options: Record<string, any>;
}>()


// Estado para opciones dinámicas
const dynamicOptions = ref<Record<string, Array<{ label: string; value: any }>>>({})
const loadingFields = ref<Record<string, boolean>>({})


// Campos que requieren búsqueda dinámica
const DYNAMIC_FIELDS = [
  'pais', 'estado', 'ciudad', 'supervisor',
  'linea', 'tipo_producto', 'mayorista'
]

// Computed para saber si un campo debe estar deshabilitado
const isFieldDisabled = computed(() => (fieldName: string) => {
  if (fieldName === 'estado') {
    return !props.form.pais
  }
  if (fieldName === 'ciudad') {
    return !props.form.estado
  }
  return false
})

const getOptionsForField = (fieldName: string) => {
  if (DYNAMIC_FIELDS.includes(fieldName)) {
    return dynamicOptions.value[fieldName] || []
  }
  
  if (!props.options) {
    console.warn('options prop is null or undefined')
    return []
  }
  
  return props.options[fieldName] || []
}


// Búsqueda dinámica con paginación
async function handleDynamicSearch(fieldName: string, term: string = '') {
  if (!DYNAMIC_FIELDS.includes(fieldName)) return

    // No buscar estados si no hay país seleccionado
  if (fieldName === 'estado' && !props.form.pais) {
    dynamicOptions.value[fieldName] = []
    return
  }

  // No buscar ciudades si no hay estado seleccionado
  if (fieldName === 'ciudad' && !props.form.estado) {
    dynamicOptions.value[fieldName] = []
    return
  }

  loadingFields.value[fieldName] = true

  try {
    // Preparar filtros para búsqueda en cascada
    const filters: Record<string, any> = {}
    if (fieldName === 'estado' && props.form.pais) {
      filters.pais = props.form.pais
    }
    if (fieldName === 'ciudad') {
      if (props.form.estado) filters.estado = props.form.estado
      else if (props.form.pais) filters.pais = props.form.pais
    }

    const response = await axios.get('/options/search', {
      params: {
        field: fieldName,
        term: term,
        filters: JSON.stringify(filters),
        page: 1
      }
    })

    dynamicOptions.value[fieldName] = response.data.data
  } catch (error) {
    console.error(`Error loading ${fieldName}:`, error)
    dynamicOptions.value[fieldName] = []
  } finally {
    loadingFields.value[fieldName] = false
  }
}


// Watchers para resetear campos dependientes
watch(() => props.form.pais, (newPais, oldPais) => {
  if (newPais !== oldPais) {
    // Resetear estado y ciudad cuando cambia el país
    props.form.estado = ''
    props.form.ciudad = ''
    dynamicOptions.value.estado = []
    dynamicOptions.value.ciudad = []
    
    // Cargar estados del nuevo país
    if (newPais) {
      handleDynamicSearch('estado', '')
    }
  }
})

watch(() => props.form.estado, (newEstado, oldEstado) => {
  if (newEstado !== oldEstado) {
    // Resetear ciudad cuando cambia el estado
    props.form.ciudad = ''
    dynamicOptions.value.ciudad = []
    
    // Cargar ciudades del nuevo estado
    if (newEstado) {
      handleDynamicSearch('ciudad', '')
    }
  }
})

// Cargar opciones iniciales para país y supervisor al montar
onMounted(() => {
  if (props.fields.some(f => f.name === 'pais')) {handleDynamicSearch('pais', '')}
  if (props.fields.some(f => f.name === 'supervisor')) {handleDynamicSearch('supervisor', '')}

  if (props.fields.some(f => f.name === 'linea')) handleDynamicSearch('linea', '')
  if (props.fields.some(f => f.name === 'tipo_producto')) handleDynamicSearch('tipo_producto', '')
  if (props.fields.some(f => f.name === 'mayorista')) handleDynamicSearch('mayorista', '')
  
  if (props.form.pais) {handleDynamicSearch('estado', '')}
  if (props.form.estado) {handleDynamicSearch('ciudad', '')}
})
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div 
      v-for="field in fields" 
      :key="field.name" 
      :class="field.class || 'md:col-span-1'"
    >
      <label 
        v-if="field.label" 
        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
      >
        {{ field.label }}
        <span v-if="isFieldDisabled(field.name)" class="text-xs text-gray-400 ml-1">
          ({{ field.name === 'estado' ? 'Seleccione un país' : 'Seleccione un estado' }})
        </span>
      </label>

      <!-- Input normal -->
      <Input
        v-if="['text', 'email', 'password', 'number', 'date'].includes(field.type)"
        v-model="form[field.name]"
        :type="field.type"
      />

      <!-- Combobox -->
      <GenericCombobox
        v-if="field.type === 'combobox'"
        v-model="form[field.name]"
        :options="getOptionsForField(field.name)"
        :placeholder="isFieldDisabled(field.name) ? 'No disponible' : 'Buscar ' + field.label"
        :disabled="isFieldDisabled(field.name)"
        :loading="loadingFields[field.name]"
        :dynamic-search="DYNAMIC_FIELDS.includes(field.name)"
        @dynamic-search="(term) => handleDynamicSearch(field.name, term)"
      />

      <!-- Radio buttons -->
      <div v-if="field.type === 'radio'" class="flex items-center gap-4 mt-2 h-10">
        <label 
          v-for="opt in field.options" 
          :key="opt.value" 
          class="flex items-center gap-2 cursor-pointer"
        >
          <input 
            type="radio" 
            :name="field.name" 
            :value="opt.value" 
            v-model="form[field.name]"
            class="text-blue-600 focus:ring-blue-500"
          />
          <span class="text-sm dark:text-gray-300">{{ opt.label }}</span>
        </label>
      </div>

      <!-- Error message -->
      <p v-if="form.errors[field.name]" class="text-red-500 text-xs mt-1">
        {{ form.errors[field.name] }}
      </p>
    </div>
  </div>
</template>