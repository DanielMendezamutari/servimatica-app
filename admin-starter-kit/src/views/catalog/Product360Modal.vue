<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'

const props = defineProps({
  isDialogOpen: {
    type: Boolean,
    required: true,
  },
  product: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:isDialogOpen', 'share'])

// Lista de fotogramas secuenciales para el giro 360°
const frames = computed(() => {
  if (!props.product) return []
  const g = props.product.gallery_urls || props.product.galleryUrls || []
  if (Array.isArray(g) && g.length > 0) {
    return g
  }
  if (props.product.image_url || props.product.imageUrl) {
    return [props.product.image_url || props.product.imageUrl]
  }
  return []
})

const currentFrameIndex = ref(0)
const isDragging = ref(false)
const startX = ref(0)
const startIndex = ref(0)
const isAutoRotating = ref(false)
let autoRotateTimer = null

function prevFrame() {
  if (!frames.value.length) return
  stopAutoRotate()
  currentFrameIndex.value = (currentFrameIndex.value - 1 + frames.value.length) % frames.value.length
}

function nextFrame() {
  if (!frames.value.length) return
  stopAutoRotate()
  currentFrameIndex.value = (currentFrameIndex.value + 1) % frames.value.length
}

// Grados calculados según el fotograma activo
const currentDegrees = computed(() => {
  if (!frames.value.length) return 0
  const step = 360 / frames.value.length
  return Math.round(currentFrameIndex.value * step)
})

function close() {
  stopAutoRotate()
  emit('update:isDialogOpen', false)
}

// Interacción Táctil y Ratón (Turntable swipe fluido a 60 FPS)
function handlePointerDown(e) {
  if (!frames.value.length) return
  stopAutoRotate()
  isDragging.value = true
  startX.value = e.clientX || e.touches?.[0]?.clientX || 0
  startIndex.value = currentFrameIndex.value
}

function handlePointerMove(e) {
  if (!isDragging.value || !frames.value.length) return
  const clientX = e.clientX || e.touches?.[0]?.clientX || 0
  const deltaX = clientX - startX.value
  const sensitivity = 25 // Pixeles necesarios para rotar al siguiente fotograma
  const step = Math.floor(deltaX / sensitivity)
  const total = frames.value.length

  let newIndex = (startIndex.value - step) % total
  if (newIndex < 0) newIndex += total
  currentFrameIndex.value = newIndex
}

function handlePointerUp() {
  isDragging.value = false
}

// Giro automático para demostración en Live
function toggleAutoRotate() {
  if (isAutoRotating.value) {
    stopAutoRotate()
  } else {
    startAutoRotate()
  }
}

function startAutoRotate() {
  if (frames.value.length < 2) return
  isAutoRotating.value = true
  autoRotateTimer = setInterval(() => {
    currentFrameIndex.value = (currentFrameIndex.value + 1) % frames.value.length
  }, 450)
}

function stopAutoRotate() {
  isAutoRotating.value = false
  if (autoRotateTimer) {
    clearInterval(autoRotateTimer)
    autoRotateTimer = null
  }
}

watch(() => props.isDialogOpen, open => {
  if (open) {
    currentFrameIndex.value = 0
    // Pequeño giro de bienvenida para indicar interactividad
    startAutoRotate()
    setTimeout(() => {
      stopAutoRotate()
    }, 1800)
  } else {
    stopAutoRotate()
  }
})

onBeforeUnmount(() => {
  stopAutoRotate()
})
</script>

<template>
  <VDialog
    :model-value="isDialogOpen"
    max-width="650"
    @update:model-value="val => !val && close()"
  >
    <VCard
      v-if="product"
      class="turntable-card overflow-hidden rounded-xl"
    >
      <!-- Cabecera del Visor 360 -->
      <VCardTitle class="d-flex justify-space-between align-center pa-4 pb-2">
        <div class="d-flex align-center gap-2">
          <VIcon
            icon="ri-rotate-lock-line"
            color="primary"
            size="24"
          />
          <div>
            <div class="text-subtitle-1 font-weight-bold">
              Visor 360° Interactivo
            </div>
            <div class="text-caption text-medium-emphasis">
              Desliza tu pulgar o ratón para girar el equipo
            </div>
          </div>
        </div>

        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          @click="close"
        />
      </VCardTitle>

      <VDivider />

      <!-- Área Interactiva de Giro Turntable -->
      <div
        class="turntable-stage position-relative select-none cursor-grab bg-light pa-4 d-flex align-center justify-center"
        :class="{ 'cursor-grabbing': isDragging }"
        @mousedown="handlePointerDown"
        @mousemove="handlePointerMove"
        @mouseup="handlePointerUp"
        @mouseleave="handlePointerUp"
        @touchstart.passive="handlePointerDown"
        @touchmove.passive="handlePointerMove"
        @touchend="handlePointerUp"
      >
        <!-- Imagen Activa del Fotograma -->
        <VImg
          v-if="frames[currentFrameIndex]"
          :src="frames[currentFrameIndex]"
          max-height="360"
          contain
          class="turntable-image pointer-events-none"
        />

        <!-- Botones de Navegación Rápida de Ángulo -->
        <button
          v-if="frames.length > 1"
          type="button"
          class="nav-turntable-btn nav-btn-prev"
          title="Ángulo anterior"
          @click.stop="prevFrame"
        >
          <VIcon
            icon="ri-arrow-left-s-line"
            size="26"
          />
        </button>
        <button
          v-if="frames.length > 1"
          type="button"
          class="nav-turntable-btn nav-btn-next"
          title="Siguiente ángulo"
          @click.stop="nextFrame"
        >
          <VIcon
            icon="ri-arrow-right-s-line"
            size="26"
          />
        </button>

        <!-- Overlay de Guía y Grados -->
        <div class="position-absolute top-0 end-0 ma-3 z-index-1">
          <VChip
            size="small"
            color="primary"
            variant="elevated"
            class="font-weight-bold"
          >
            {{ currentDegrees }}° (Ángulo {{ currentFrameIndex + 1 }}/{{ frames.length }})
          </VChip>
        </div>

        <!-- Indicador de Swipe para celulares -->
        <div class="swipe-hint position-absolute bottom-0 start-50 translate-middle-x mb-3 text-caption text-medium-emphasis d-flex align-center gap-1 bg-surface px-3 py-1 rounded-pill elevation-1">
          <VIcon
            icon="ri-drag-move-fill"
            size="16"
          />
          <span>Desliza o usa las flechas</span>
        </div>
      </div>

      <!-- Barra de Control y Miniaturas -->
      <VCardText class="pa-4">
        <div class="d-flex align-center justify-space-between mb-3">
          <div class="d-flex align-center gap-2">
            <VBtn
              size="small"
              :variant="isAutoRotating ? 'elevated' : 'tonal'"
              :color="isAutoRotating ? 'primary' : 'default'"
              :prepend-icon="isAutoRotating ? 'ri-pause-circle-line' : 'ri-play-circle-line'"
              @click="toggleAutoRotate"
            >
              {{ isAutoRotating ? 'Pausar giro' : 'Auto rotar' }}
            </VBtn>
          </div>

          <div class="text-caption text-medium-emphasis">
            {{ product.name }}
          </div>
        </div>

        <!-- Tira de Miniaturas -->
        <div class="d-flex gap-2 overflow-x-auto pb-2 justify-center">
          <div
            v-for="(url, idx) in frames"
            :key="idx"
            class="thumbnail-dot rounded border overflow-hidden cursor-pointer"
            :class="{ 'active-dot': currentFrameIndex === idx }"
            style="width: 48px; height: 48px;"
            @click="currentFrameIndex = idx"
          >
            <VImg
              :src="url"
              width="48"
              height="48"
              cover
            />
          </div>
        </div>

        <VDivider class="my-3" />

        <!-- Acciones: Precio y WhatsApp -->
        <div class="d-flex align-center justify-space-between">
          <div>
            <div class="text-caption text-medium-emphasis">
              Precio al contado / QR
            </div>
            <div class="text-h6 font-weight-black text-primary">
              Bs. {{ Number(product.sale_price).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}
            </div>
          </div>

          <div class="d-flex align-center gap-2">
            <VBtn
              variant="tonal"
              color="primary"
              prepend-icon="ri-share-forward-line"
              title="Copiar enlace directo para Facebook o WhatsApp"
              @click="emit('share', product)"
            >
              Compartir
            </VBtn>
            <VBtn
              color="success"
              variant="elevated"
              prepend-icon="ri-whatsapp-line"
              :href="product.whatsapp_order_link"
              target="_blank"
              rel="noopener noreferrer"
            >
              Pedir por WhatsApp
            </VBtn>
          </div>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.turntable-stage {
  min-height: 380px;
  background: radial-gradient(circle, #f8f9fc 0%, #ebedf5 100%);
  user-select: none;
  -webkit-user-select: none;
  touch-action: pan-y;
}

.cursor-grab {
  cursor: grab;
}

.cursor-grabbing {
  cursor: grabbing;
}

.pointer-events-none {
  pointer-events: none;
}

.turntable-image {
  filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.15));
  transition: transform 0.1s ease-out;
}

.thumbnail-dot {
  opacity: 0.6;
  border: 2px solid transparent !important;
  transition: all 0.2s ease;
}

.thumbnail-dot:hover {
  opacity: 0.9;
}

.active-dot {
  opacity: 1;
  border-color: rgb(var(--v-theme-primary)) !important;
  transform: scale(1.05);
}

.swipe-hint {
  opacity: 0.85;
  animation: fadeInOut 2.5s infinite;
}

@keyframes fadeInOut {
  0%, 100% { opacity: 0.4; }
  50% { opacity: 0.9; }
}

.translate-middle-x {
  transform: translateX(-50%);
}

.z-index-1 {
  z-index: 2;
}

.nav-turntable-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(0, 0, 0, 0.1);
  color: rgb(var(--v-theme-primary));
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
  z-index: 5;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.nav-turntable-btn:hover {
  background: rgb(var(--v-theme-primary));
  color: #ffffff;
  transform: translateY(-50%) scale(1.1);
}

.nav-btn-prev {
  left: 12px;
}

.nav-btn-next {
  right: 12px;
}
</style>
