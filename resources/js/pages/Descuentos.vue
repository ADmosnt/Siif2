<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import vueNumberInput from '@/components/vue-number-input.vue';

const breadcrumbs: BreadcrumbItem[] = [
  { label: 'Dashboard', href: 'dashboard' },
  { label: 'Descuentos' }
]

const value = ref(1)

</script>

<template>
    <Head title="Descuentos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
          <p>Value: {{ value }}</p>
          <vueNumberInput 
            v-model="value" 
            size="small" 
            :min="1" 
            inline 
            center 
            controls
          />
        </div>
    </AppLayout>
</template>
