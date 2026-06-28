<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import SignaturePadLib from 'signature_pad'

const props = withDefaults(defineProps<{
  width?: number
  height?: number
  disabled?: boolean
}>(), {
  width: 400,
  height: 200,
  disabled: false,
})

const emit = defineEmits<{
  (e: 'update:signature', data: string | null): void
}>()

const canvasRef = ref<HTMLCanvasElement | null>(null)
const containerRef = ref<HTMLDivElement | null>(null)
let signaturePad: SignaturePadLib | null = null

function resizeCanvas() {
  const canvas = canvasRef.value
  const container = containerRef.value
  if (!canvas || !container) return

  const data = signaturePad?.toData()
  const ratio = Math.max(window.devicePixelRatio || 1, 1)
  const canvasWidth = container.clientWidth
  const canvasHeight = props.height

  canvas.width = canvasWidth * ratio
  canvas.height = canvasHeight * ratio
  canvas.style.width = `${canvasWidth}px`
  canvas.style.height = `${canvasHeight}px`

  const ctx = canvas.getContext('2d')
  if (ctx) {
    ctx.scale(ratio, ratio)
    // Re-fill white background after resize
    ctx.fillStyle = 'rgb(255, 255, 255)'
    ctx.fillRect(0, 0, canvasWidth, canvasHeight)
  }

  if (data && data.length > 0) {
    signaturePad?.fromData(data)
  }
}

function clear() {
  signaturePad?.clear()
  emit('update:signature', null)
}

function getSignatureData(): string | null {
  if (!signaturePad || signaturePad.isEmpty()) return null
  return signaturePad.toDataURL('image/png')
}

onMounted(async () => {
  await nextTick()
  if (!canvasRef.value) return

  signaturePad = new SignaturePadLib(canvasRef.value, {
    backgroundColor: 'rgb(255, 255, 255)',
    penColor: 'rgb(0, 0, 0)',
  })

  signaturePad.addEventListener('endStroke', () => {
    emit('update:signature', getSignatureData())
  })

  resizeCanvas()
  window.addEventListener('resize', resizeCanvas)

  if (props.disabled) {
    signaturePad.off()
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', resizeCanvas)
  signaturePad?.off()
})

defineExpose({ clear, getSignatureData })
</script>

<template>
  <div class="space-y-2">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
      Firma del cliente *
    </label>
    <div
      ref="containerRef"
      class="rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden bg-white"
    >
      <canvas
        ref="canvasRef"
        class="block w-full touch-none cursor-crosshair"
        :class="{ 'pointer-events-none opacity-50': disabled }"
      />
    </div>
    <div class="flex items-center justify-between">
      <p class="text-xs text-gray-500 dark:text-gray-400">
        Dibuje la firma del cliente en el recuadro
      </p>
      <button
        type="button"
        :disabled="disabled"
        class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium disabled:opacity-50"
        @click="clear"
      >
        Limpiar firma
      </button>
    </div>
  </div>
</template>
