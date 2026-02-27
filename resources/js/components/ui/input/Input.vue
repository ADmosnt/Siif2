<!-- components/ui/Input.vue -->
<script setup lang="ts">
import { useVModel } from '@vueuse/core'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  modelValue?: string | number
  class?: HTMLAttributes['class']
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: string | number): void
}>()

const model = useVModel(props, 'modelValue', emit)
</script>

<template>
  <input
    v-model="model"
    :class="cn(
      'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-0',
      props.class
    )"
  />
</template>
