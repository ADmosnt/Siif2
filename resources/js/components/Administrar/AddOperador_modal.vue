<!-- resources/js/components/AddOperador_modal.vue -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import Button from '@/components/ui/button/Button.vue'
import BaseModal from '@/components/BaseModal.vue'
import Input from '../ui/input/Input.vue'
import GlobalSelect from '../GlobalSelect.vue'

interface Operadores {
  nombre: string
  apellido: string
  tipo: string
  documento: number
  email: string
  tlf: number
  addr: string

}

const Tipo = ref(['V','E'])
const TipoSeleccionado = ref()

const Pais = ref(['Venezuela'])
const PaisSeleccionado = ref()

const Estado = ref(['Anzoátegui'])
const EstadoSeleccionado = ref()

const Ciudad = ref(['Barcelona'])
const CiudadSeleccionada = ref()

const nombre = ref('')
const apellido = ref('')
const documento = ref('')
const email = ref('')
const tlf = ref('')
const addr = ref('')

// Props / v-model
const props = defineProps({
  modelValue: { type: Boolean, default: false }
})
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm', item: Operadores): void 
}>()


function resetForm() {
  nombre.value = ''
  apellido.value = ''
  documento.value = ''
  email.value = ''
  tlf.value = ''
  addr.value = ''
  TipoSeleccionado.value = undefined
  PaisSeleccionado.value = undefined
  EstadoSeleccionado.value = undefined
  CiudadSeleccionada.value = undefined
}

function agregarOperador() {
  const operador = {
    nombre: `${nombre.value} ${apellido.value}`,
    documento: documento.value,
    email: email.value,
    tlf: tlf.value,
    addr: addr.value,
  }
  emit('confirm', operador)
  resetForm()
  internal.value = false
}

const internal = ref(props.modelValue)
watch(() => props.modelValue, v => internal.value = v)
watch(internal, v => emit('update:modelValue', v))

</script>

<template>
  <BaseModal
    v-model="internal"
    title="Agregar Operador"
    size="lg"
  >
    <template #default>
      <div class="flex flex-col px-6 py-4 gap-6">

        <!-- Datos personales -->
        <div>
          <h3 class="text-sm font-medium mb-2">Datos Personales</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Input placeholder="Nombre" v-model="nombre" />
            <Input placeholder="Apellido" v-model="apellido" />

            <GlobalSelect
              v-model="TipoSeleccionado"
              :options="Tipo"
              placeholder="Tipo de Documento"
            />

            <Input placeholder="Documento" v-model="documento" />
            <Input placeholder="Email" v-model="email" />
            <Input placeholder="Teléfono" v-model="tlf" />

            <Input placeholder="Dirección" v-model="addr" class="md:col-span-3" />

            <div class="flex items-center gap-4 col-span-3">
              <span class="text-sm font-medium">Género</span>
              <label class="flex items-center gap-2">
                <input type="radio" name="genero" value="F" />
                <span>Femenino</span>
              </label>
              <label class="flex items-center gap-2">
                <input type="radio" name="genero" value="M" />
                <span>Masculino</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Datos por grupo -->
        <div>
          <h3 class="text-sm font-medium mb-2">Datos por Grupo</h3>
          <Input placeholder="Ubicación" />
        </div>

        <!-- Ubicación -->
        <div>
          <h3 class="text-sm font-medium mb-2">Ubicación</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <GlobalSelect
              v-model="PaisSeleccionado"
              :options="Pais"
              placeholder="País"
            />

            <GlobalSelect
              v-model="EstadoSeleccionado"
              :options="Estado"
              placeholder="Estado"
            />

            <GlobalSelect
              v-model="CiudadSeleccionada"
              :options="Ciudad"
              placeholder="Ciudad"
            />

          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-end gap-2 px-6 py-4">
        <Button variant="outline" @click="internal = false">Cancelar</Button>
        <Button variant="default" @click="agregarOperador">Agregar</Button>

      </div>
    </template>
  </BaseModal>
</template>
