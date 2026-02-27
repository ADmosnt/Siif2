<!-- resources/js/components/ActionCard.vue -->
<script setup lang="ts">
import { type Component, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

interface ActionCardProps {
  icon: Component
  label: string
  to?: string
  bgColor?: string
  footerColor?: string
  size?: 'xxs' | 'sm' | 'md' | 'lg'
  variant?: 'raised' | 'flat'
  labelColor?: string
  footerTextColor?: string
}

const props = withDefaults(defineProps<ActionCardProps>(), {
  to: '#',
  bgColor: '#007aff',
  footerColor: '#165797',
  size: 'md',
  variant: 'raised',
  labelColor: '#171717',
  footerTextColor: '#ffffff',
})

/* Tamaños optimizados para MD */
const sz = computed(() => {
  const map = {
    xxs: { 
      headerPad: 'p-2', 
      minH: 'min-h-10', 
      icon: 'w-5 h-5', 
      label: 'text-[12px] leading-none', 
      footPad: 'px-2.5 py-0.5', 
      footText: 'text-[10px]',
      iconPosition: 'bottom-1 left-1'
    },
    sm: { 
      headerPad: 'p-3', 
      minH: 'min-h-12', 
      icon: 'w-6 h-6', 
      label: 'text-sm', 
      footPad: 'px-3.5 py-1', 
      footText: 'text-xs',
      iconPosition: 'bottom-2 left-2'
    },
    md: {  // Tamaño MD optimizado
      headerPad: 'p-6',           // Más padding que el original
      minH: 'min-h-[100px]',      // Altura intermedia
      icon: 'w-12 h-12',          // Icono más grande
      label: 'text-lg font-semibold', // Texto más prominente
      footPad: 'px-4 py-2', 
      footText: 'text-sm',
      iconPosition: 'bottom-3 left-3' // Posición mejorada
    },
    lg: {  
      headerPad: 'p-8', 
      minH: 'min-h-[120px]', 
      icon: 'w-16 h-16', 
      label: 'text-xl md:text-2xl', 
      footPad: 'px-6 py-2', 
      footText: 'text-sm',
      iconPosition: 'bottom-4 left-4'
    },
  } as const
  return map[props.size]
})

/* Clases del Link con tema oscuro */
const linkClass = computed(() => [
  'w-full flex flex-col rounded-lg overflow-hidden cursor-pointer block',
  'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500',
  'dark:border dark:border-gray-600', // Borde sutil en tema oscuro
  props.size === 'lg' ? 'h-full' : '',
  props.variant === 'raised'
    ? 'shadow-lg hover:scale-[1.02] transition-transform duration-200 dark:shadow-gray-900/30'
    : 'shadow-sm hover:scale-100 dark:shadow-gray-800/20'
].join(' '))
</script>

<template>
  <Link :href="props.to" :aria-label="props.label" :class="linkClass">
    <!-- Header con tema oscuro -->
    <div
      class="flex items-center relative grow transition-colors duration-200"
      :class="[sz.headerPad, sz.minH]"
      :style="{ backgroundColor: props.bgColor }"
    >
      <!-- Icono con posición corregida -->
      <component
        :is="props.icon"
        :class="['text-white opacity-30 absolute transition-opacity duration-200', sz.iconPosition, sz.icon]"
        aria-hidden="true"
      />
      <span
        :class="['ml-auto font-medium text-right transition-colors duration-200', sz.label]"
        :style="{ color: props.labelColor }"
      >
        {{ props.label }}
      </span>
    </div>

    <!-- Footer con tema oscuro -->
    <div
      class="flex justify-between items-center transition-colors duration-200"
      :class="[sz.footText, sz.footPad]"
      :style="{ 
        backgroundColor: props.footerColor, 
        color: props.footerTextColor 
      }"
    >
      <span>IR</span>
      <span>&gt;</span>
    </div>
  </Link>
</template>