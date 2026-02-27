<script setup lang="ts">
import type { HTMLAttributes, computed } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { useSidebar } from './utils'
import { PanelLeftCloseIcon, PanelLeftOpenIcon } from '@/components/icons'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { toggleSidebar, open } = useSidebar()
</script> 

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon"
    :class="cn('h-7 w-7', props.class)"
    @click="toggleSidebar"
  >
  <PanelLeftCloseIcon v-if="open" color="white" />
  <PanelLeftOpenIcon v-else color="white" />

    <span class="sr-only">Toggle Sidebar</span>
  </Button>
</template>
