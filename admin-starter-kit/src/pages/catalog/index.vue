<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProductCard3D from '@/views/catalog/ProductCard3D.vue'
import Product360Modal from '@/views/catalog/Product360Modal.vue'
import { $api } from '@/utils/api'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const catalog = ref([])
const categories = ref([])
const brands = ref([])
const company = ref({})

const search = ref('')
const selectedCategoryId = ref(null)

const is360Open = ref(false)
const selected360Product = ref(null)
const shareNotice = ref('')

async function fetchCatalog() {
  loading.value = true
  try {
    const params = {}
    if (search.value.trim()) params.search = search.value.trim()
    if (selectedCategoryId.value) params.category_id = selectedCategoryId.value

    const res = await $api('/public/catalog', { query: params })
    catalog.value = res.data || []
    categories.value = res.categories || []
    brands.value = res.brands || []
    company.value = res.company || {}
  } catch (e) {
    console.error('Error fetching public catalog:', e)
  } finally {
    loading.value = false
  }
}

function onOpen360(product) {
  selected360Product.value = product
  is360Open.value = true
  router.replace({ query: { ...route.query, product: product.id } })
  if (product?.name) {
    document.title = `${product.name} (360°) • Servimática PC`
  }
}

function on360DialogClose(val) {
  is360Open.value = val
  if (!val) {
    const q = { ...route.query }
    delete q.product
    router.replace({ query: q })
    document.title = 'Servimática PC • Catálogo en Vivo y Vitrina 360°'
  }
}

function onShareProduct(product) {
  if (!product) return
  const url = `${window.location.origin}/catalogo?product=${product.id}`
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url)
  } else {
    const el = document.createElement('input')
    el.value = url
    document.body.appendChild(el)
    el.select()
    document.execCommand('copy')
    document.body.removeChild(el)
  }
  shareNotice.value = `¡Enlace de "${product.name}" copiado! Listo para pegar en Facebook o WhatsApp.`
}

function selectCategory(id) {
  selectedCategoryId.value = selectedCategoryId.value === id ? null : id
  const q = { ...route.query }
  if (selectedCategoryId.value) {
    q.category = selectedCategoryId.value
  } else {
    delete q.category
  }
  router.replace({ query: q })
  fetchCatalog()
}

let searchDebounce = null
function onSearchInput() {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    const q = { ...route.query }
    if (search.value.trim()) {
      q.search = search.value.trim()
    } else {
      delete q.search
    }
    router.replace({ query: q })
    fetchCatalog()
  }, 300)
}

const companyWhatsAppLink = computed(() => {
  const phone = company.value.mobile || '78901234'
  const cleanPhone = phone.replace(/\D+/g, '')
  const fullPhone = cleanPhone.length === 8 ? '591' + cleanPhone : cleanPhone
  return `https://wa.me/${fullPhone}?text=${encodeURIComponent('¡Hola Servimática! Estoy viendo su catálogo de TikTok Live y deseo consultar por un equipo. 🛍️')}`
})

onMounted(async () => {
  if (route.query.search) {
    search.value = String(route.query.search)
  }
  if (route.query.category) {
    selectedCategoryId.value = Number(route.query.category)
  }
  
  await fetchCatalog()

  // Deep linking: abrir automáticamente el visor 360 si la URL tiene ?product=ID
  if (route.query.product) {
    const targetId = Number(route.query.product)
    const target = catalog.value.find(p => p.id === targetId)
    if (target) {
      onOpen360(target)
    }
  }
})
</script>

<template>
  <div class="public-catalog-minimal min-vh-100 bg-background pb-12">
    <!-- Navbar Glassmorphism Minimalista -->
    <header class="catalog-navbar border-b px-4 py-2.5 sticky-top">
      <div class="d-flex align-center justify-space-between max-w-7xl mx-auto">
        <div class="d-flex align-center gap-2.5">
          <VAvatar
            size="36"
            rounded="lg"
            class="border"
          >
            <VImg
              v-if="company.logo_url"
              :src="company.logo_url"
              cover
            />
            <VIcon
              v-else
              icon="ri-computer-line"
              color="primary"
              size="20"
            />
          </VAvatar>

          <div class="d-flex align-center gap-2">
            <span class="store-name font-weight-black text-high-emphasis">
              {{ company.trade_name || 'SERVIMÁTICA' }}
            </span>
            <span class="live-pill d-inline-flex align-center gap-1 px-2 py-0.5 rounded-pill text-caption font-weight-bold">
              <span class="live-dot" />
              <span>LIVE</span>
            </span>
          </div>
        </div>

        <a
          :href="companyWhatsAppLink"
          target="_blank"
          rel="noopener noreferrer"
          class="btn-nav-wa d-inline-flex align-center gap-1.5 px-3 py-1.5 rounded-pill text-decoration-none"
        >
          <VIcon
            icon="ri-whatsapp-line"
            size="16"
          />
          <span class="text-caption font-weight-bold">WhatsApp</span>
        </a>
      </div>
    </header>

    <!-- Barra de Filtros Rápida y Limpia (Sin Hero Bloat) -->
    <div class="filter-bar px-4 py-4 max-w-7xl mx-auto">
      <div class="d-flex flex-column flex-sm-row align-center gap-3">
        <!-- Buscador integrado sobrio -->
        <div class="search-box-wrapper w-100 w-sm-auto flex-grow-1">
          <div class="search-input-container d-flex align-center px-3 py-1.5 rounded-pill border bg-surface">
            <VIcon
              icon="ri-search-line"
              size="18"
              color="medium-emphasis"
              class="me-2"
            />
            <input
              v-model="search"
              type="text"
              placeholder="Buscar laptop, modelo o procesador..."
              class="search-input flex-grow-1"
              @input="onSearchInput"
            >
            <button
              v-if="search"
              type="button"
              class="clear-btn"
              @click="search = ''; fetchCatalog();"
            >
              <VIcon
                icon="ri-close-circle-fill"
                size="16"
                color="medium-emphasis"
              />
            </button>
          </div>
        </div>

        <!-- Pestañas de Categoría Horizontales -->
        <div class="categories-pills d-flex align-center gap-1.5 overflow-x-auto w-100 w-sm-auto pb-1 pb-sm-0">
          <button
            type="button"
            class="filter-pill"
            :class="{ active: selectedCategoryId === null }"
            @click="selectCategory(null)"
          >
            Todos
          </button>

          <button
            v-for="cat in categories"
            :key="cat.id"
            type="button"
            class="filter-pill"
            :class="{ active: selectedCategoryId === cat.id }"
            @click="selectCategory(cat.id)"
          >
            {{ cat.name }}
          </button>
        </div>
      </div>
    </div>

    <!-- Contenido Principal: Grid de Productos -->
    <main class="max-w-7xl mx-auto px-4 mt-2">
      <!-- Loader sutil -->
      <div
        v-if="loading"
        class="d-flex justify-center py-16"
      >
        <VProgressCircular
          indeterminate
          color="primary"
          size="40"
          width="3"
        />
      </div>

      <!-- Cuadrícula de Tarjetas 3D -->
      <VRow
        v-else-if="catalog.length"
        class="match-height"
      >
        <VCol
          v-for="product in catalog"
          :key="product.id"
          cols="12"
          sm="6"
          md="4"
          lg="3"
        >
          <ProductCard3D
            :product="product"
            @open-360="onOpen360"
            @share="onShareProduct"
          />
        </VCol>
      </VRow>

      <!-- Estado Vacío Limpio -->
      <div
        v-else
        class="text-center py-16"
      >
        <VIcon
          icon="ri-inbox-line"
          size="48"
          color="medium-emphasis"
          class="mb-2"
        />
        <div class="text-body-1 font-weight-medium text-high-emphasis">
          Sin productos disponibles con este filtro
        </div>
        <div class="text-caption text-medium-emphasis mb-3">
          Prueba con otra palabra clave o selecciona "Todos".
        </div>
        <button
          type="button"
          class="btn-reset-filters text-caption font-weight-bold"
          @click="search = ''; selectedCategoryId = null; fetchCatalog();"
        >
          Mostrar todo el inventario
        </button>
      </div>
    </main>

    <!-- Modal Visor 360 -->
    <Product360Modal
      :is-dialog-open="is360Open"
      :product="selected360Product"
      @update:is-dialog-open="on360DialogClose"
      @share="onShareProduct"
    />

    <!-- Notificación Flotante de Enlace Copiado -->
    <VSnackbar
      :model-value="!!shareNotice"
      timeout="3500"
      color="primary"
      location="bottom center"
      @update:model-value="val => !val && (shareNotice = '')"
    >
      <div class="d-flex align-center gap-2">
        <VIcon
          icon="ri-share-forward-line"
          size="20"
        />
        <span class="text-body-2 font-weight-medium">{{ shareNotice }}</span>
      </div>
    </VSnackbar>
  </div>
</template>

<style scoped>
.public-catalog-minimal {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  background-color: #f8fafc;
}

.catalog-navbar {
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(12px);
  z-index: 100;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
}

.sticky-top {
  position: sticky;
  top: 0;
}

.max-w-7xl {
  max-width: 1280px;
}

.store-name {
  font-size: 1.05rem;
  letter-spacing: -0.01em;
}

.live-pill {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  font-size: 0.68rem;
  letter-spacing: 0.04em;
}

.live-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: #dc2626;
  animation: pulse-dot 1.5s infinite;
}

@keyframes pulse-dot {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.4); opacity: 0.5; }
  100% { transform: scale(1); opacity: 1; }
}

.btn-nav-wa {
  background: #25d366;
  color: #ffffff;
  box-shadow: 0 2px 6px rgba(37, 211, 102, 0.25);
  transition: all 0.2s ease;
}

.btn-nav-wa:hover {
  background: #1eb956;
}

.search-input-container {
  border: 1px solid #e2e8f0;
  background: #ffffff;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.search-input-container:focus-within {
  border-color: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 3px rgba(var(--v-theme-primary), 0.1);
}

.search-input {
  border: none;
  outline: none;
  font-size: 0.88rem;
  background: transparent;
  color: inherit;
  width: 100%;
}

.clear-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
}

.categories-pills {
  white-space: nowrap;
}

.filter-pill {
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #475569;
  font-size: 0.82rem;
  font-weight: 500;
  padding: 6px 14px;
  border-radius: 9999px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-pill:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.filter-pill.active {
  background: #0f172a;
  color: #ffffff;
  border-color: #0f172a;
  font-weight: 600;
}

.btn-reset-filters {
  background: none;
  border: 1px solid #cbd5e1;
  padding: 6px 16px;
  border-radius: 9999px;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-reset-filters:hover {
  background: #f1f5f9;
}
</style>
