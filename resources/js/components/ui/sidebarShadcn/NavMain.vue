<script setup lang="ts">
import { ChevronRight, type LucideIcon } from 'lucide-vue-next'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuAction,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/components/ui/sidebar'
import type { Component } from 'vue'

const props = withDefaults(defineProps<{
  items: {
    title: string
    url: string
    icon: Component
    isActive?: boolean
    items?: {
      title: string
      url: string
    }[]
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

function hasOfflineChildren(item: { items?: { url: string }[] }): boolean {
  if (!item.items) return false
  return item.items.some(sub => props.isUrlOfflineEnabled(sub.url))
}
</script>

<template>
  <SidebarGroup>
    <SidebarGroupLabel>SIIF</SidebarGroupLabel>
    <SidebarMenu>
      <Collapsible v-for="item in items" :key="item.title" as-child :default-open="item.isActive">
        <SidebarMenuItem>
          <SidebarMenuButton
            as-child
            :tooltip="item.title"
            :class="{ 'opacity-40 pointer-events-none': !item.items && isDisabled(item.url) }"
          >
            <a
              :href="isDisabled(item.url) && !item.items ? undefined : item.url"
            >
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </a>
          </SidebarMenuButton>
          <template v-if="item.items?.length">
            <CollapsibleTrigger as-child>
              <SidebarMenuAction class="data-[state=open]:rotate-90">
                <ChevronRight />
                <span class="sr-only">Toggle</span>
              </SidebarMenuAction>
            </CollapsibleTrigger>
            <CollapsibleContent>
              <SidebarMenuSub>
                <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                  <SidebarMenuSubButton
                    as-child
                    :class="{ 'opacity-40 pointer-events-none': isDisabled(subItem.url) }"
                  >
                    <a
                      :href="isDisabled(subItem.url) ? undefined : subItem.url"
                    >
                      <span>{{ subItem.title }}</span>
                    </a>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>
              </SidebarMenuSub>
            </CollapsibleContent>
          </template>
        </SidebarMenuItem>
      </Collapsible>
    </SidebarMenu>
  </SidebarGroup>
</template>
