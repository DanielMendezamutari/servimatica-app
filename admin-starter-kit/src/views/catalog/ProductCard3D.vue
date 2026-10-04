<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['open-360', 'share'])

const cardRef = ref(null)
const rotateX = ref(0)
const rotateY = ref(0)
const isHovered = ref(false)

const imageList = computed(() => {
  const g = props.product?.gallery_urls || props.product?.galleryUrls || []
  if (Array.isArray(g) && g.length > 0) {
    return g
  }
  if (props.product?.image_url || props.product?.imageUrl) {
    return [props.product.image_url || props.product.imageUrl]
  }
  return []
})

const currentFrameIndex = ref(0)
const currentImage = computed(() => {
  if (!imageList.value.length) return props.product?.image_url || ''
  return imageList.value[currentFrameIndex.value] || imageList.value[0]
})

function handleMouseMove(e) {
  if (!cardRef.value) return
  const rect = cardRef.value.getBoundingClientRect()
  const x = e.clientX - rect.left
  const y = e.clientY - rect.top
  const centerX = rect.width / 2
  const centerY = rect.height / 2

  // Inclinación 3D sutil y elegante
  rotateX.value = -((y - centerY) / centerY) * 7
  rotateY.value = ((x - centerX) / centerX) * 7
  isHovered.value = true

  // Rotación interactiva 360° al mover el cursor horizontalmente
  if (imageList.value.length > 1) {
    const fraction = Math.max(0, Math.min(0.999, x / rect.width))
    currentFrameIndex.value = Math.floor(fraction * imageList.value.length)
  }
}

function handleMouseLeave() {
  rotateX.value = 0
  rotateY.value = 0
  isHovered.value = false
  currentFrameIndex.value = 0
}

function handleTouchMove(e) {
  if (!cardRef.value || !e.touches[0]) return
  const rect = cardRef.value.getBoundingClientRect()
  const touch = e.touches[0]
  const x = touch.clientX - rect.left
  const y = touch.clientY - rect.top
  const centerX = rect.width / 2
  const centerY = rect.height / 2

  rotateX.value = -((y - centerY) / centerY) * 6
  rotateY.value = ((x - centerX) / centerX) * 6
  isHovered.value = true

  // Rotación táctil 360° en celular
  if (imageList.value.length > 1) {
    const fraction = Math.max(0, Math.min(0.999, x / rect.width))
    currentFrameIndex.value = Math.floor(fraction * imageList.value.length)
  }
}

function handleTouchEnd() {
  rotateX.value = 0
  rotateY.value = 0
  isHovered.value = false
}

const cardStyle = computed(() => {
  return {
    transform: `perspective(1000px) rotateX(${rotateX.value}deg) rotateY(${rotateY.value}deg) ${isHovered.value ? 'translateY(-4px)' : 'translateY(0)'}`,
    transition: isHovered.value ? 'transform 0.08s ease-out' : 'transform 0.4s ease-out, box-shadow 0.4s ease',
  }
})

function formatWarranty(days) {
  const d = Number(days || 0)
  if (d <= 0) return 'Garantía tienda'
  if (d === 365) return 'Garantía 1 año'
  if (d === 730) return 'Garantía 2 años'
  if (d % 30 === 0) return `Garantía ${d / 30}m`
  return `Garantía ${d}d`
}

function conditionLabel(cond) {
  switch (cond) {
    case 'nuevo': return 'Nuevo'
    case 'open_box': return 'Open Box'
    case 'usado': return 'Seminuevo'
    case 'reacondicionado': return 'Reacondicionado'
    default: return ''
  }
}
</script>

<template>
  <div
    ref="cardRef"
    class="product-card-minimal-wrapper h-100"
    :style="cardStyle"
    @mousemove="handleMouseMove"
    @mouseleave="handleMouseLeave"
    @touchmove.passive="handleTouchMove"
    @touchend="handleTouchEnd"
  >
    <div class="product-card-minimal d-flex flex-column h-100 bg-surface rounded-xl overflow-hidden border">
      <!-- Foto de Estudio Fotográfico Limpio con Giro 360° -->
      <div
        class="image-stage position-relative d-flex align-center justify-center overflow-hidden cursor-pointer"
        title="Clic para abrir el visor interactivo 360°"
        @click="emit('open-360', product)"
      >
        <VImg
          :src="currentImage"
          height="190"
          contain
          class="product-img transition-all"
        />

        <!-- Badges Minimalistas Superiores -->
        <div class="badges-top">
          <span
            v-if="conditionLabel(product.condition)"
            class="badge-pill badge-neutral font-weight-medium"
          >
            {{ conditionLabel(product.condition) }}
          </span>
          <span
            v-if="product.brand_name"
            class="badge-pill badge-brand font-weight-bold"
          >
            {{ product.brand_name }}
          </span>
        </div>

        <!-- Indicador de Puntos de la Galería 360° -->
        <div
          v-if="imageList.length > 1"
          class="gallery-dots-indicator"
        >
          <span
            v-for="(_, idx) in imageList"
            :key="idx"
            class="dot-pill"
            :class="{ active: currentFrameIndex === idx }"
          />
        </div>

        <!-- Botón 360 flotante discreto -->
        <button
          v-if="product.has_360 || imageList.length > 1"
          type="button"
          class="btn-360-pill"
          title="Ver producto en 360°"
          @click.stop="emit('open-360', product)"
        >
          <VIcon
            icon="ri-rotate-lock-line"
            size="14"
          />
          <span>360° ({{ imageList.length }} fotos)</span>
        </button>
      </div>

      <!-- Ficha de Datos Minimalista -->
      <div class="pa-4 d-flex flex-column flex-grow-1">
        <div class="text-caption text-uppercase font-weight-bold text-medium-emphasis tracking-wider mb-1">
          {{ product.category_name }}
        </div>

        <h3 class="product-title font-weight-bold text-high-emphasis mb-1">
          {{ product.name }}
        </h3>

        <p
          v-if="product.description"
          class="product-desc text-caption text-medium-emphasis mb-3 line-clamp-2"
        >
          {{ product.description }}
        </p>
        <div
          v-else
          class="mb-3"
        />

        <!-- Garantía y Stock sin truncamiento -->
        <div class="d-flex align-center gap-2 mb-4 flex-wrap">
          <span class="badge-pill badge-warranty d-inline-flex align-center gap-1">
            <VIcon
              icon="ri-shield-check-line"
              size="13"
            />
            {{ formatWarranty(product.warranty_days) }}
          </span>
          <span class="badge-pill badge-stock">
            Stock: {{ product.stock }}
          </span>
        </div>

        <div class="mt-auto pt-3 border-t d-flex align-center justify-space-between">
          <div>
            <div class="price-val font-weight-black text-high-emphasis">
              Bs. {{ Number(product.sale_price).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}
            </div>
          </div>

          <div class="d-flex align-center gap-1.5">
            <button
              type="button"
              class="btn-share-icon d-inline-flex align-center justify-center rounded-circle border"
              title="Copiar enlace para Facebook / WhatsApp"
              @click.stop="emit('share', product)"
            >
              <VIcon
                icon="ri-share-forward-line"
                size="15"
                color="primary"
              />
            </button>

            <a
              :href="product.whatsapp_order_link"
              target="_blank"
              rel="noopener noreferrer"
              class="btn-order-wa d-inline-flex align-center gap-1 px-3 py-1.5 rounded-pill text-decoration-none"
            >
              <VIcon
                icon="ri-whatsapp-line"
                size="16"
              />
              <span>Pedir</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-card-minimal-wrapper {
  transform-style: preserve-3d;
  will-change: transform;
}

.product-card-minimal {
  border: 1px solid rgba(0, 0, 0, 0.08) !important;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.product-card-minimal-wrapper:hover .product-card-minimal {
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08) !important;
  border-color: rgba(var(--v-theme-primary), 0.25) !important;
}

.image-stage {
  position: relative;
  height: 190px;
  background: radial-gradient(circle, #fafbfc 0%, #f1f4f8 100%);
  padding: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.badges-top {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 2;
  display: flex;
  gap: 4px;
  pointer-events: none;
}

.product-img {
  transition: transform 0.4s ease;
  max-height: 100%;
  object-fit: contain;
}

.product-card-minimal-wrapper:hover .product-img {
  transform: scale(1.04);
}

.product-title {
  font-size: 0.95rem;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-desc {
  font-size: 0.78rem;
  line-height: 1.35;
  min-height: 30px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.price-val {
  font-size: 1.15rem;
  letter-spacing: -0.02em;
}

.badge-pill {
  font-size: 0.72rem;
  padding: 2px 8px;
  border-radius: 9999px;
  white-space: nowrap;
}

.badge-neutral {
  background: #f1f5f9;
  color: #334155;
}

.badge-brand {
  background: rgba(var(--v-theme-primary), 0.1);
  color: rgb(var(--v-theme-primary));
}

.badge-warranty {
  background: #ecfdf5;
  color: #065f46;
}

.badge-stock {
  background: #f8fafc;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.btn-360-pill {
  position: absolute;
  bottom: 8px;
  right: 8px;
  z-index: 3;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(255, 255, 255, 0.94);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(0, 0, 0, 0.1);
  color: rgb(var(--v-theme-primary));
  font-size: 0.75rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 9999px;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
  transition: all 0.2s ease;
}

.btn-360-pill:hover {
  background: rgb(var(--v-theme-primary));
  color: #ffffff;
}

.btn-order-wa {
  background: #25d366;
  color: #ffffff;
  font-size: 0.82rem;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(37, 211, 102, 0.3);
  transition: all 0.2s ease;
}

.btn-order-wa:hover {
  background: #1eb956;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
}

.gallery-dots-indicator {
  position: absolute;
  bottom: 10px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 4px;
  z-index: 2;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  padding: 3px 8px;
  border-radius: 9999px;
  pointer-events: none;
}

.dot-pill {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.5);
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.btn-share-icon {
  width: 32px;
  height: 32px;
  background: #f8fafc;
  border-color: #e2e8f0 !important;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-share-icon:hover {
  background: rgba(var(--v-theme-primary), 0.1);
  border-color: rgba(var(--v-theme-primary), 0.3) !important;
  transform: scale(1.08);
}
</style>
