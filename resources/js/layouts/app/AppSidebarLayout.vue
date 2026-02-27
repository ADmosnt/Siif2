<script setup lang="ts">
import AppContent from '@/components/LayoutComponents/AppContent.vue';
import AppShell from '@/components/LayoutComponents/AppShell.vue';
import AppSidebar from '@/components/ui/sidebarShadcn/AppSidebar.vue';
import AppSidebarHeader from '@/components/LayoutComponents/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
