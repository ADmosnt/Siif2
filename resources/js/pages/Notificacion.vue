<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from "@inertiajs/vue3";
import { type BreadcrumbItem } from "@/types";
import { Textarea } from '@/components/ui/textarea'
import { ref } from 'vue'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

import Button from "@/components/ui/button/Button.vue";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title:  'Notificacion',
        href:   '/notificacion',
    },

];

const descripcion = ref('')

const maxChars = 255

const empresas = ref([
  { id: 1, nombre: 'Empresa A' },
  { id: 2, nombre: 'Empresa B' },
  { id: 3, nombre: 'Empresa C' },
])

const seleccionada = ref(null)

</script>

<template>
    <Head title="Notificacion"/>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col h-full p-6 gap-6">
        
        <!-- 1) Fila de controles -->
            <div class="flex flex-col md:flex-row items-start md:items-center gap-2 w-full">
                <!-- Select Empresa -->
                <Select >
                                
                    <SelectTrigger class="w-full md:w-56">
                    <SelectValue placeholder="Seleccione una Empresa" />
                    </SelectTrigger>

                    <SelectContent class="w-full md:w-56" >
                    <SelectGroup>
                        <SelectItem
                        v-for="empresa in empresas"
                        :key="empresa.id"
                        :value="empresa.id"
                        >
                        {{ empresa.nombre }}
                        </SelectItem>
                    </SelectGroup>
                    </SelectContent>

                </Select>

                <!-- Botón Seleccionar -->
                <Button class="w-full md:w-auto">Seleccionar</Button>

                <!-- Select RFV -->
                <div class="w-full md:w-56 md:ml-auto">
                
                    <Select>
                        <SelectTrigger class="w-full md:w-56 md:ml-auto">
                        <SelectValue placeholder="RFV" />
                        </SelectTrigger>
                    </Select>

                </div>
                
            </div>
            <!-- TextArea Descripcion -->
            <div class="grid w-full gap-2">

                <Textarea v-model="descripcion" placeholder="Descripcion...":maxlength=maxChars />
                <!-- Contador de caracteres -->
                <p class="text-sm text-gray-500">
                {{ descripcion.length }} / {{ maxChars }} caracteres
                </p>                
                
            </div>
            <!-- Nota -->
            <div>

                <p>
                    <b>Nota:</b>
                     De no especificar el código, la notificación sera enviada a todos .
                </p> 

            </div>

            <div class="ml-auto mt-10 flex gap-2 justify-end">
                <Button>Enviar</Button>
            </div>

        </div>
    </AppLayout>
</template>
