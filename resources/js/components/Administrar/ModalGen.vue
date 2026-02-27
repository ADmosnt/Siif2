<script setup lang="ts">
import { ref, watch } from 'vue'
import Button from '@/components/ui/button/Button.vue'
import BaseModal from '@/components/BaseModal.vue'
import FormularioGen from './FormularioGen.vue';
import { type FieldConfig } from '@/configs/admin'

const props = defineProps<{
  modelValue: boolean;
  title: string;
  fields: FieldConfig[];
  form: any;
  options: any;
  isEditing: boolean;
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm'): void
}>()

const internal = ref(props.modelValue)

watch(() => props.modelValue, (v) => internal.value = v)
watch(internal, (v) => emit('update:modelValue', v))

function handleConfirm() {
  emit('confirm')
  // No cerramos automáticamente aquí, esperamos a que la petición termine
}
</script>

<template>
  <BaseModal
    v-model="internal"
    :title="title"
    size="lg"
  >
    <template #default>
      <div class="px-6 py-4">
        <h3 class="text-sm font-medium mb-4 text-gray-500 uppercase tracking-wider">
          Información del Registro
        </h3>

        <FormularioGen 
          :fields="fields" 
          :form="form"
          :options="options"
        />
      </div>
    </template>

    <template #footer>
      <div class="flex justify-end gap-2 px-6 py-4">
        <Button variant="outline" @click="internal = false">
          Cancelar
        </Button>
          <Button 
            variant="default" 
            @click="handleConfirm"
            :disabled="form.processing" 
            class="bg-green-600 hover:bg-green-700 text-white"
          >
            <span v-if="form.processing">Procesando...</span>
            <span v-else>{{ isEditing ? 'Guardar Cambios' : 'Agregar' }}</span>
          </Button>
      </div>
    </template>
  </BaseModal>
</template>