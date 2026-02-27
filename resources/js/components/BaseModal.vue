<!-- resources/js/components/BaseModal.vue -->
<script setup lang="ts">
import { ref, watch } from 'vue'
import { useMediaQuery } from '@vueuse/core'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogTrigger,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
  DialogClose,
} from '@/components/ui/dialog'
import {
  Drawer,
  DrawerTrigger,
  DrawerContent,
  DrawerHeader,
  DrawerTitle,
  DrawerDescription,
  DrawerFooter,
  DrawerClose,
} from '@/components/ui/drawer'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  size: { type: String, default: 'md' }, // 'sm', 'md', 'lg', 'xl'
})
const emit = defineEmits(['update:modelValue'])

const isDesktop = useMediaQuery('(min-width: 768px)')
const open = ref(props.modelValue)

watch(() => props.modelValue, v => open.value = v)
watch(open, v => emit('update:modelValue', v))

const sizeClasses = {
  sm: 'sm:max-w-[360px]',
  md: 'sm:max-w-[425px]',
  lg: 'sm:max-w-[800px]',
  xl: 'w-[90vw] max-w-[1200px]'
}[props.size] || 'sm:max-w-[425px]'
</script>

<template>
  <div>
    <slot name="trigger" />

    <!-- Desktop Dialog -->
    <Dialog v-if="isDesktop" v-model:open="open">
      <DialogContent :class="`${sizeClasses} fixed left-1/2 top-1/2 z-50 grid w-full -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg duration-200 sm:rounded-lg overflow-x-auto`">
        <DialogHeader>
          <DialogTitle>{{ title }}</DialogTitle>
          <DialogDescription v-if="description">
            {{ description }}
          </DialogDescription>
        </DialogHeader>

        <div class="mt-4 space-y-4">
          <slot />
        </div>

        <DialogFooter class="mt-6 text-right">
          <slot name="footer">
            <DialogClose as-child>
              <Button variant="ghost">Cerrar</Button>
            </DialogClose>
          </slot>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Mobile Drawer -->
    <Drawer v-else v-model:open="open">
      <DrawerContent class="max-h-[90vh] flex flex-col">
        <div class="flex-1 overflow-y-auto px-4 space-y-6">
          <DrawerHeader>
            <DrawerTitle>{{ title }}</DrawerTitle>
            <DrawerDescription v-if="description">
              {{ description }}
            </DrawerDescription>
          </DrawerHeader>

          <slot />
        </div>

        <DrawerFooter class="pt-4 text-right px-4">
          <slot name="footer">
            <DrawerClose as-child>
              <Button variant="ghost">Cerrar</Button>              
              <Button variant="ghost">Agregar</Button>
            </DrawerClose>
          </slot>
        </DrawerFooter>
      </DrawerContent>

    </Drawer>
  </div>
</template>
