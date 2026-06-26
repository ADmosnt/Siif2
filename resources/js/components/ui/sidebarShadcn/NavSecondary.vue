<script setup lang="ts">
import type { Component } from 'vue'

import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar'

const props = withDefaults(defineProps<{
  items: {
    title: string
    url: string
    icon: Component
  }[]
  isOnline?: boolean
  isUrlOfflineEnabled?: (url: string) => boolean
}>(), {
  isOnline: true,
  isUrlOfflineEnabled: () => false,
})

function isDisabled(url: string): boolean {
  return !props.isOnline && !props.isUrlOfflineEnabled(url)
}
</script>

<template>
  <SidebarGroup>
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="item in items" :key="item.title">
          <SidebarMenuButton
            as-child
            size="sm"
            :class="{ 'opacity-40 pointer-events-none': isDisabled(item.url) }"
          >
            <a :href="isDisabled(item.url) ? undefined : item.url">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>
