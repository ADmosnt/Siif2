<script setup>
import { ref, watch, computed } from 'vue'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const props = defineProps({
  /**
   * Text label displayed atop the options list
   */
  label: {
    type: String,
    default: '',
  },
  /**
   * Array of items to select from
   */
  items: {
    type: Array,
    required: true,
  },
  /**
   * Property name for item value
   */
  itemValueKey: {
    type: String,
    default: 'id',
  },
  /**
   * Property name for item text
   */
  itemTextKey: {
    type: String,
    default: 'nombre',
  },
  /**
   * Placeholder text when no value is selected
   */
  placeholder: {
    type: String,
    default: 'Seleccione una opción',
  },
  /**
   * Custom classes for trigger and content
   */
  triggerClass: {
    type: [String, Array, Object],
    default: 'w-full',
  },
  contentClass: {
    type: [String, Array, Object],
    default: 'w-full',
  },
  /**
   * v-model binding value
   */
  modelValue: {
    type: [String, Number, null],
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

// internal v-model sync
const internalValue = ref(props.modelValue)

watch(() => props.modelValue, val => {
  internalValue.value = val
})

watch(internalValue, val => {
  emit('update:modelValue', val)
})
</script>

<template>
  <Select v-model:value="internalValue">
    <SelectTrigger :class="triggerClass">
      <SelectValue :placeholder="placeholder" />
    </SelectTrigger>
    <SelectContent :class="contentClass">
      <SelectLabel v-if="label">{{ label }}</SelectLabel>
      <SelectGroup>
        <SelectItem
          v-for="item in items"
          :key="item[itemValueKey]"
          :value="item[itemValueKey]"
        >
          {{ item[itemTextKey] }}
        </SelectItem>
      </SelectGroup>
    </SelectContent>
  </Select>
</template>

