<!-- resources/js/components/LayoutComponents/AppSidebarHeader.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { SidebarTrigger } from '@/components/ui/sidebar'
import type { BreadcrumbItemType } from '@/types'
import responsiveBreadCrumb from '@/components/LayoutComponents/responsiveBreadCrumb.vue'
import CurrentViewPill from '@/components/LayoutComponents/CurrentViewPill.vue'

const props = defineProps<{
  breadcrumbs?: BreadcrumbItemType[]
}>()

const currentView = computed(() => {
  if (!props.breadcrumbs || props.breadcrumbs.length === 0) return ''
  return props.breadcrumbs[props.breadcrumbs.length - 1].label
})
</script>

<template>
  <header
    class="header-with-gradient flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4 rounded-t-xl 
           bg-linear-to-r from-blue-600 to-blue-700 
           dark:bg-linear-to-r dark:from-blue-800 dark:to-blue-950" 
  >
    <div class="flex items-center gap-2">
      <SidebarTrigger class="-ml-1" />
      <template v-if="breadcrumbs && breadcrumbs.length > 0">
        <responsiveBreadCrumb 
          :items="breadcrumbs" 
          class="header-breadcrumbs"
        />
      </template>
    </div>

    <!-- Píldora centrada usando las clases CSS -->
    <CurrentViewPill :current-view="currentView" />
  </header>
</template>