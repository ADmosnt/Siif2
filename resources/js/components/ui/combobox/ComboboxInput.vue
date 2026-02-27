<script setup lang="ts">
import { cn } from '@/lib/utils'
import { ComboboxInput, type ComboboxInputEmits, type ComboboxInputProps, useForwardPropsEmits } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

const props = defineProps<ComboboxInputProps & {
  class?: HTMLAttributes['class']
}>()

const emits = defineEmits<ComboboxInputEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <ComboboxInput
    v-bind="forwarded"
    :class="cn(
     'flex h-10 w-full bg-transparent px-3 py-2 text-sm transition-colors',
     'border-none focus:ring-0 shadow-none', props.class)"
  >
    <slot />
  </ComboboxInput>
</template>
  