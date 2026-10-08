<script setup>
import AddProductDrawer from '@/views/products/AddProductDrawer.vue'
import StockAdjustDialog from '@/views/products/StockAdjustDialog.vue'
import { $api, apiError } from '@/utils/api'
import { currentUser } from '@/utils/session'

// Adaptado de admin-full-version/src/pages/apps/ecommerce/product/list/index.vue
const router = useRouter()

const isOwner = computed(() => ['dueno', 'administrador'].includes(currentUser.value?.role))

const products = ref([])
const categories = ref([])
const brands = ref([])
const loading = ref(false)
const exporting = ref(false)
const error = ref('')
const notice = ref('')

const search = ref('')
const selectedCategory = ref(null)
const selectedBrand = ref(null)
const selectedCondition = ref(null)
const selectedStatus = ref(null)

const page = ref(1)
const perPage = ref(15)
const totalItems = ref(0)

const drawer = ref(false)
const editingProduct = ref(null)

const stockDialogOpen = ref(false)
const adjustingProduct = ref(null)

const confirmingToggle = ref(null)
const toggling = ref(false)

const statusOptions = [
  { title: 'Todos los estados', value: null },
  { title: 'Solo Activos', value: 'active' },
  { title: 'Solo Inactivos', value: 'inactive' },
]

const conditionFilterOptions = [
  { title: 'Todas las condiciones', value: null },
  { title: 'Nuevo', value: 'nuevo' },
  { title: 'Seminuevo / Open Box', value: 'open_box' },
  { title: 'Usado', value: 'usado' },
  { title: 'Reacondicionado', value: 'reacondicionado' },
]

const headers = computed(() => {
  const base = [
    { title: 'Producto', key: 'name' },
    { title: 'Categoría', key: 'categoryName' },
    { title: 'Condición', key: 'condition', align: 'center' },
    { title: 'Garantía', key: 'warrantyDays', align: 'center' },
    { title: 'Precio Venta', key: 'salePrice', align: 'end' },
    { title: 'Stock', key: 'stock', align: 'center' },
  ]

  if (isOwner.value) {
    base.push(
      { title: 'Costo Compra', key: 'costPrice', align: 'end' },
      { title: 'Margen', key: 'margin', align: 'end' },
      { title: 'Stock Mín.', key: 'minStock', align: 'center' },
      { title: 'Estado', key: 'status', align: 'center' },
      { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
    )
  }

  return base
})

function formatWarrantyLabel(days) {
  const d = Number(days ?? 0)
  if (d <= 0) return 'Sin garantía'
  if (d === 15) return '15 días'
  if (d === 30) return '1 mes (30d)'
  if (d === 90) return '3 meses (90d)'
  if (d === 180) return '6 meses (180d)'
  if (d === 365) return '1 año (365d)'
  if (d === 730) return '2 años (730d)'
  return `${d} días`
}

function formatShortWarranty(days) {
  const d = Number(days ?? 0)
  if (d <= 0) return '0d'
  if (d === 15) return '15d'
  if (d === 30) return '1m'
  if (d === 90) return '3m'
  if (d === 180) return '6m'
  if (d === 365) return '1a'
  if (d === 730) return '2a'
  return `${d}d`
}

function conditionColor(cond) {
  switch (cond) {
    case 'nuevo': return 'success'
    case 'open_box': return 'info'
    case 'usado': return 'warning'
    case 'reacondicionado': return 'secondary'
    default: return 'default'
  }
}

function conditionLabel(cond) {
  switch (cond) {
    case 'nuevo': return 'Nuevo'
    case 'open_box': return 'Open Box'
    case 'usado': return 'Usado'
    case 'reacondicionado': return 'Reacondicionado'
    default: return cond || 'Nuevo'
  }
}

async function fetchCategories() {
  try {
    const res = await $api('/categories')
    categories.value = [
      { title: 'Todas las categorías', value: null },
      ...(res.data || []).map(c => ({ title: c.name, value: c.id })),
    ]
  } catch (e) {}
}

async function fetchBrands() {
  try {
    const res = await $api('/brands/options')
    brands.value = [
      { title: 'Todas las marcas', value: null },
      ...(res.data || []).map(b => ({ title: b.name, value: b.id })),
    ]
  } catch (e) {}
}

async function loadProducts() {
  loading.value = true
  error.value = ''
  try {
    const params = {
      page: page.value,
      per_page: perPage.value,
    }
    if (search.value) params.search = search.value.trim()
    if (selectedCategory.value) params.category_id = selectedCategory.value
    if (selectedBrand.value) params.brand_id = selectedBrand.value
    if (selectedCondition.value) params.condition = selectedCondition.value
    if (selectedStatus.value) params.status = selectedStatus.value

    const res = await $api('/products', { params })
    products.value = res.data || []
    totalItems.value = res.meta?.total || 0
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    loading.value = false
  }
}

async function exportExcel() {
  exporting.value = true
  error.value = ''
  try {
    const params = new URLSearchParams()
    if (search.value) params.append('search', search.value.trim())
    if (selectedCategory.value) params.append('category_id', selectedCategory.value)
    if (selectedBrand.value) params.append('brand_id', selectedBrand.value)
    if (selectedCondition.value) params.append('condition', selectedCondition.value)
    if (selectedStatus.value) params.append('status', selectedStatus.value)

    const token = localStorage.getItem('token') || sessionStorage.getItem('token')
    const baseURL = import.meta.env.VITE_API_BASE_URL || '/api'
    const res = await fetch(`${baseURL}/products/export-excel?${params.toString()}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    if (!res.ok) throw new Error('Error al descargar archivo')

    const blob = await res.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `inventario_servimatica_${new Date().toISOString().slice(0, 10)}.xlsx`
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
    notice.value = 'Inventario exportado exitosamente a Excel.'
  } catch (e) {
    error.value = 'No se pudo exportar el inventario a Excel. Verifique su conexión.'
  } finally {
    exporting.value = false
  }
}

function openAdd(product = null) {
  editingProduct.value = product
  drawer.value = true
}

function openAdjustStock(product) {
  adjustingProduct.value = product
  stockDialogOpen.value = true
}

function openHistory(product) {
  router.push(`/products/history/${product.id}`)
}

async function onProductSaved() {
  notice.value = editingProduct.value ? 'Producto actualizado correctamente.' : 'Producto registrado exitosamente.'
  await loadProducts()
}

async function onStockAdjusted() {
  notice.value = 'Stock ajustado correctamente.'
  await loadProducts()
}

async function toggleStatus() {
  if (toggling.value || !confirmingToggle.value) return
  toggling.value = true
  error.value = ''
  try {
    await $api(`/products/${confirmingToggle.value.id}/toggle-status`, { method: 'PATCH' })
    notice.value = 'Estado del producto actualizado.'
    confirmingToggle.value = null
    await loadProducts()
  } catch (failure) {
    error.value = apiError(failure)
    confirmingToggle.value = null
  } finally {
    toggling.value = false
  }
}

function calculateMargin(cost, sale) {
  const c = parseFloat(cost) || 0
  const s = parseFloat(sale) || 0
  const diff = s - c
  const pct = c > 0 ? ((diff / c) * 100).toFixed(0) : '0'
  return { bs: diff.toFixed(2), pct }
}

// Modo Hoja de Cálculo / Experiencia tipo Excel
const excelViewMode = ref(true)

const summaryMetrics = computed(() => {
  const list = products.value || []
  let totalStock = 0
  let totalCostValuation = 0
  let totalSaleValuation = 0
  let totalSalePriceSum = 0

  for (const p of list) {
    const s = Number(p.stock) || 0
    const sale = Number(p.salePrice ?? p.sale_price) || 0
    const cost = Number(p.costPrice ?? p.cost_price) || 0

    totalStock += s
    totalSaleValuation += sale * s
    totalCostValuation += cost * s
    totalSalePriceSum += sale
  }

  const projectedProfit = totalSaleValuation - totalCostValuation
  const avgSalePrice = list.length > 0 ? (totalSalePriceSum / list.length) : 0
  const avgMarginPct = totalCostValuation > 0 ? ((projectedProfit / totalCostValuation) * 100) : 0

  return {
    count: list.length,
    totalStock,
    totalCostValuation,
    totalSaleValuation,
    projectedProfit,
    avgSalePrice,
    avgMarginPct: avgMarginPct.toFixed(1),
  }
})

const hasActiveFilters = computed(() => {
  return !!(search.value || selectedCategory.value || selectedBrand.value || selectedCondition.value || selectedStatus.value)
})

function clearFilters() {
  search.value = ''
  selectedCategory.value = null
  selectedBrand.value = null
  selectedCondition.value = null
  selectedStatus.value = null
  page.value = 1
  loadProducts()
}

// Edición Rápida de Precio Inline (tipo celda Excel)
const quickPriceDialog = ref(false)
const quickPriceProduct = ref(null)
const newQuickPrice = ref('')
const quickPriceSaving = ref(false)

function openQuickPriceEdit(product) {
  if (!isOwner.value) return
  quickPriceProduct.value = product
  newQuickPrice.value = Number(product.salePrice ?? product.sale_price ?? 0).toFixed(2)
  quickPriceDialog.value = true
}

async function saveQuickPrice() {
  if (!quickPriceProduct.value || quickPriceSaving.value) return
  const val = parseFloat(newQuickPrice.value)
  if (isNaN(val) || val < 0) return

  quickPriceSaving.value = true
  try {
    const payload = {
      name: quickPriceProduct.value.name,
      sku: quickPriceProduct.value.sku,
      category_id: quickPriceProduct.value.categoryId ?? quickPriceProduct.value.category_id,
      brand_id: quickPriceProduct.value.brandId ?? quickPriceProduct.value.brand_id,
      sale_price: val,
      cost_price: quickPriceProduct.value.costPrice ?? quickPriceProduct.value.cost_price,
      min_stock: quickPriceProduct.value.minStock ?? quickPriceProduct.value.min_stock ?? 0,
      condition: quickPriceProduct.value.condition,
      description: quickPriceProduct.value.description,
    }

    await $api(`/products/${quickPriceProduct.value.id}`, {
      method: 'PUT',
      body: payload,
    })

    quickPriceProduct.value.salePrice = val
    quickPriceProduct.value.sale_price = val
    notice.value = `Precio de "${quickPriceProduct.value.name}" actualizado a Bs. ${val.toFixed(2)}.`
    quickPriceDialog.value = false
  } catch (err) {
    error.value = apiError(err, 'No se pudo actualizar el precio.')
  } finally {
    quickPriceSaving.value = false
  }
}

watch([selectedCategory, selectedBrand, selectedCondition, selectedStatus, page], loadProducts)

onMounted(async () => {
  await Promise.all([fetchCategories(), fetchBrands()])
  await loadProducts()
})
</script>

<template>
  <section>
    <!-- Tarjeta Unificada de Métricas (Estilo Profesional Materio) -->
    <VCard class="mb-6">
      <VCardText class="px-2">
        <VRow>
          <VCol
            cols="12"
            sm="6"
            :md="isOwner ? 3 : 6"
            class="px-6"
          >
            <div class="d-flex justify-space-between align-center">
              <div class="d-flex flex-column gap-y-1">
                <span class="text-caption text-medium-emphasis text-uppercase font-weight-medium">Productos en Catálogo</span>
                <h4 class="text-h4 font-weight-semibold">{{ summaryMetrics.count }} SKUs</h4>
                <span class="text-caption text-medium-emphasis">Registrados en tienda</span>
              </div>
              <VAvatar
                color="primary"
                variant="tonal"
                size="42"
                rounded
              >
                <VIcon icon="ri-computer-line" size="24" />
              </VAvatar>
            </div>
          </VCol>

          <VDivider v-if="$vuetify.display.mdAndUp" vertical inset />

          <VCol
            cols="12"
            sm="6"
            :md="isOwner ? 3 : 6"
            class="px-6"
          >
            <div class="d-flex justify-space-between align-center">
              <div class="d-flex flex-column gap-y-1">
                <span class="text-caption text-medium-emphasis text-uppercase font-weight-medium">Unidades Físicas</span>
                <h4 class="text-h4 font-weight-semibold">{{ summaryMetrics.totalStock }} unid.</h4>
                <span class="text-caption text-medium-emphasis">Existencias totales</span>
              </div>
              <VAvatar
                color="warning"
                variant="tonal"
                size="42"
                rounded
              >
                <VIcon icon="ri-archive-line" size="24" />
              </VAvatar>
            </div>
          </VCol>

          <template v-if="isOwner">
            <VDivider v-if="$vuetify.display.mdAndUp" vertical inset />

            <VCol
              cols="12"
              sm="6"
              md="3"
              class="px-6"
            >
              <div class="d-flex justify-space-between align-center">
                <div class="d-flex flex-column gap-y-1">
                  <span class="text-caption text-medium-emphasis text-uppercase font-weight-medium">Inversión al Costo</span>
                  <h4 class="text-h4 font-weight-semibold">Bs. {{ summaryMetrics.totalCostValuation.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</h4>
                  <span class="text-caption text-medium-emphasis">Capital en mercadería</span>
                </div>
                <VAvatar
                  color="secondary"
                  variant="tonal"
                  size="42"
                  rounded
                >
                  <VIcon icon="ri-money-dollar-box-line" size="24" />
                </VAvatar>
              </div>
            </VCol>

            <VDivider v-if="$vuetify.display.mdAndUp" vertical inset />

            <VCol
              cols="12"
              sm="6"
              md="3"
              class="px-6"
            >
              <div class="d-flex justify-space-between align-center">
                <div class="d-flex flex-column gap-y-1">
                  <span class="text-caption text-medium-emphasis text-uppercase font-weight-medium">Valor Proyectado Venta</span>
                  <h4 class="text-h4 font-weight-semibold">Bs. {{ summaryMetrics.totalSaleValuation.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</h4>
                  <span class="text-caption text-success font-weight-medium">+Bs. {{ summaryMetrics.projectedProfit.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} ({{ summaryMetrics.avgMarginPct }}%)</span>
                </div>
                <VAvatar
                  color="success"
                  variant="tonal"
                  size="42"
                  rounded
                >
                  <VIcon icon="ri-line-chart-line" size="24" />
                </VAvatar>
              </div>
            </VCol>
          </template>
        </VRow>
      </VCardText>
    </VCard>

    <VCard>
      <VCardItem class="pb-2">
        <div class="d-flex flex-wrap gap-4 align-center justify-space-between w-100">
          <div>
            <VCardTitle class="text-h5 font-weight-bold">Catálogo de Productos</VCardTitle>
            <VCardSubtitle class="text-body-1 mt-1">
              {{ isOwner ? 'Gestión integral del inventario, precios, costos y stock de tienda.' : 'Consulta rápida de catálogo, precios de venta y disponibilidad en inventario.' }}
            </VCardSubtitle>
          </div>

          <div class="d-flex flex-wrap gap-2 align-center">
            <VBtn
              :color="excelViewMode ? 'primary' : 'secondary'"
              :variant="excelViewMode ? 'tonal' : 'outlined'"
              :prepend-icon="excelViewMode ? 'ri-grid-line' : 'ri-list-check'"
              @click="excelViewMode = !excelViewMode"
            >
              {{ excelViewMode ? 'Vista Cuadrícula Excel' : 'Vista Estándar' }}
            </VBtn>

            <VBtn
              color="success"
              variant="outlined"
              prepend-icon="ri-file-excel-2-line"
              :loading="exporting"
              @click="exportExcel"
            >
              Exportar a Excel
            </VBtn>

            <VBtn
              v-if="isOwner"
              prepend-icon="ri-add-line"
              @click="openAdd(null)"
            >
              Nuevo producto
            </VBtn>
          </div>
        </div>
      </VCardItem>

      <VCardText>
        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          class="mb-4"
          role="alert"
        >
          {{ error }}
          <VBtn
            variant="text"
            class="ms-2"
            @click="loadProducts"
          >
            Reintentar
          </VBtn>
        </VAlert>

        <!-- Filtros y Búsqueda Responsive -->
        <VRow dense class="mb-2 align-center">
          <VCol cols="12" md="3">
            <VTextField
              v-model="search"
              placeholder="Buscar por nombre o SKU..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              hide-details
              clearable
              @update:model-value="loadProducts"
            />
          </VCol>

          <VCol cols="12" sm="6" md="2">
            <VSelect
              v-model="selectedCategory"
              label="Categoría"
              :items="categories"
              density="compact"
              hide-details
              clearable
            />
          </VCol>

          <VCol cols="12" sm="6" md="2">
            <VSelect
              v-model="selectedBrand"
              label="Marca"
              :items="brands"
              density="compact"
              hide-details
              clearable
            />
          </VCol>

          <VCol cols="12" sm="6" md="2">
            <VSelect
              v-model="selectedCondition"
              label="Condición"
              :items="conditionFilterOptions"
              density="compact"
              hide-details
              clearable
            />
          </VCol>

          <VCol
            v-if="isOwner"
            cols="12"
            sm="6"
            md="2"
          >
            <VSelect
              v-model="selectedStatus"
              label="Estado"
              :items="statusOptions"
              density="compact"
              hide-details
              clearable
            />
          </VCol>

          <VCol
            v-if="hasActiveFilters"
            cols="auto"
            class="d-flex align-center"
          >
            <VBtn
              size="small"
              variant="tonal"
              color="error"
              prepend-icon="ri-filter-off-line"
              title="Restablecer filtros"
              @click="clearFilters"
            >
              Limpiar
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <!-- Tabla de Productos Estilo Excel -->
      <VDataTable
        :headers="headers"
        :items="products"
        :loading="loading"
        :items-per-page="perPage"
        :density="excelViewMode ? 'compact' : 'default'"
        :class="['rounded border', { 'excel-table': excelViewMode }]"
        fixed-header
        hover
        no-data-text="No se encontraron productos coincidentes."
        loading-text="Cargando productos..."
      >
        <!-- Nombre, Imagen, Marca, Modelo y SKU -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3 py-1">
            <VAvatar
              size="42"
              rounded
              class="border bg-light flex-shrink-0"
            >
              <VImg
                :src="item.imageUrl || item.image_url"
                cover
              />
            </VAvatar>
            <div class="d-flex flex-column">
              <span class="font-weight-medium text-high-emphasis">{{ item.name }}</span>
              <div class="d-flex align-center gap-2 mt-0.5">
                <span class="text-caption text-medium-emphasis">SKU: {{ item.sku }}</span>
                <VChip
                  v-if="item.brandName"
                  size="x-small"
                  variant="outlined"
                  color="secondary"
                >
                  {{ item.brandName }} <span v-if="item.productModelName" class="ms-1 font-weight-bold">{{ item.productModelName }}</span>
                </VChip>
                <VChip
                  v-if="(item.galleryUrls || item.gallery_urls)?.length"
                  size="x-small"
                  color="info"
                  variant="tonal"
                  prepend-icon="ri-rotate-lock-line"
                >
                  360° ({{ (item.galleryUrls || item.gallery_urls).length }})
                </VChip>
              </div>
            </div>
          </div>
        </template>


        <!-- Categoría y Subfamilia -->
        <template #item.categoryName="{ item }">
          <div class="d-flex flex-column">
            <span class="text-body-1 font-weight-medium text-high-emphasis">{{ item.categoryName }}</span>
            <span
              v-if="item.subfamilyName"
              class="text-caption text-medium-emphasis"
            >
              ↳ {{ item.subfamilyName }}
            </span>
          </div>
        </template>

        <!-- Condición -->
        <template #item.condition="{ item }">
          <span v-if="!item.condition || item.condition === 'nuevo'" class="text-body-2 text-medium-emphasis">
            Nuevo
          </span>
          <VChip
            v-else
            size="x-small"
            variant="tonal"
            :color="conditionColor(item.condition)"
          >
            {{ conditionLabel(item.condition) }}
          </VChip>
        </template>

        <!-- Garantía Técnica Dual (Hardware y Software) -->
        <template #item.warrantyDays="{ item }">
          <div
            v-if="(Number(item.warrantyHardwareDays ?? item.warranty_hardware_days ?? item.warrantyDays ?? item.warranty_days ?? 0) > 0) || (Number(item.warrantySoftwareDays ?? item.warranty_software_days ?? 0) > 0)"
            class="d-flex flex-column align-center gap-1 py-1"
          >
            <VChip
              v-if="Number(item.warrantyHardwareDays ?? item.warranty_hardware_days ?? item.warrantyDays ?? item.warranty_days ?? 0) > 0"
              size="x-small"
              color="primary"
              variant="tonal"
              class="font-weight-bold"
              prepend-icon="ri-shield-keyhole-line"
              title="Garantía de Hardware (Física)"
            >
              HW: {{ formatShortWarranty(item.warrantyHardwareDays ?? item.warranty_hardware_days ?? item.warrantyDays ?? item.warranty_days) }}
            </VChip>
            <VChip
              v-if="Number(item.warrantySoftwareDays ?? item.warranty_software_days ?? 0) > 0"
              size="x-small"
              color="info"
              variant="tonal"
              class="font-weight-bold"
              prepend-icon="ri-computer-line"
              title="Garantía de Software (Soporte Lógico)"
            >
              SW: {{ formatShortWarranty(item.warrantySoftwareDays ?? item.warranty_software_days) }}
            </VChip>
          </div>
          <span
            v-else
            class="text-caption text-disabled"
          >
            Sin garantía
          </span>
        </template>

        <!-- Precio Venta (Editable rápido con clic para el Dueño) -->
        <template #item.salePrice="{ item }">
          <div
            class="d-flex align-center justify-end gap-1 font-mono"
            :class="{ 'cell-editable': isOwner }"
            :title="isOwner ? 'Clic para editar precio rápido' : ''"
            @click="isOwner ? openQuickPriceEdit(item) : null"
          >
            <span class="font-weight-medium text-high-emphasis">
              Bs. {{ parseFloat(item.salePrice ?? item.sale_price ?? 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </span>
            <VIcon
              v-if="isOwner"
              icon="ri-edit-2-line"
              size="14"
              class="text-medium-emphasis opacity-60"
            />
          </div>
        </template>

        <!-- Stock y Badges (Interactivo con clic para el Dueño) -->
        <template #item.stock="{ item }">
          <div
            class="d-flex flex-column align-center gap-0.5"
            :class="{ 'cell-editable': isOwner }"
            :title="isOwner ? 'Clic para ajustar stock' : ''"
            @click="isOwner ? openAdjustStock(item) : null"
          >
            <span
              class="font-weight-medium font-mono"
              :class="item.stock === 0 ? 'text-error font-weight-bold' : 'text-high-emphasis'"
            >
              {{ item.stock }} unid.
            </span>
            <span
              v-if="item.stock === 0"
              class="text-caption text-error font-weight-medium"
            >
              Agotado
            </span>
            <span
              v-else-if="isOwner && item.minStock && item.stock <= item.minStock"
              class="text-caption text-warning font-weight-medium"
            >
              Stock bajo
            </span>
            <span
              v-if="(Number(item.defective_stock || item.defectiveStock) || 0) > 0"
              class="text-caption text-warning mt-0.5"
              title="Mercadería en cuarentena técnica o defectuosa (RMA)"
            >
              ⚠️ {{ item.defective_stock || item.defectiveStock }} RMA
            </span>
          </div>
        </template>

        <!-- Costo Compra (Solo Dueño) -->
        <template
          v-if="isOwner"
          #item.costPrice="{ item }"
        >
          <span class="text-medium-emphasis font-mono">
            Bs. {{ parseFloat(item.costPrice ?? item.cost_price ?? 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
          </span>
        </template>

        <!-- Margen (Solo Dueño) -->
        <template
          v-if="isOwner"
          #item.margin="{ item }"
        >
          <div class="d-flex flex-column align-end font-mono">
            <span
              class="font-weight-medium"
              :class="parseFloat(item.salePrice ?? item.sale_price) < parseFloat(item.costPrice ?? item.cost_price) ? 'text-error' : 'text-high-emphasis'"
            >
              Bs. {{ calculateMargin(item.costPrice ?? item.cost_price, item.salePrice ?? item.sale_price).bs }}
            </span>
            <span class="text-caption text-medium-emphasis">
              ({{ calculateMargin(item.costPrice ?? item.cost_price, item.salePrice ?? item.sale_price).pct }}%)
            </span>
          </div>
        </template>

        <!-- Stock Mínimo (Solo Dueño) -->
        <template
          v-if="isOwner"
          #item.minStock="{ item }"
        >
          <span class="font-mono text-medium-emphasis">{{ item.minStock }}</span>
        </template>

        <!-- Estado (Solo Dueño) -->
        <template
          v-if="isOwner"
          #item.status="{ item }"
        >
          <VChip
            :color="item.status === 'active' ? 'success' : 'secondary'"
            size="x-small"
            variant="tonal"
          >
            {{ item.status === 'active' ? 'Activo' : 'Inactivo' }}
          </VChip>
        </template>

        <!-- Acciones (Solo Dueño) -->
        <template
          v-if="isOwner"
          #item.actions="{ item }"
        >
          <div class="d-flex gap-1 justify-end">
            <IconBtn
              size="small"
              title="Editar datos del producto"
              @click="openAdd(item)"
            >
              <VIcon icon="ri-edit-line" size="18" />
            </IconBtn>

            <IconBtn
              size="small"
              title="Ajustar stock (+/-)"
              @click="openAdjustStock(item)"
            >
              <VIcon icon="ri-exchange-line" size="18" />
            </IconBtn>

            <IconBtn
              size="small"
              title="Ver historial de movimientos"
              @click="openHistory(item)"
            >
              <VIcon icon="ri-history-line" size="18" />
            </IconBtn>

            <IconBtn
              size="small"
              :title="item.status === 'active' ? 'Desactivar producto' : 'Activar producto'"
              @click="confirmingToggle = item"
            >
              <VIcon
                :icon="item.status === 'active' ? 'ri-eye-off-line' : 'ri-eye-line'"
                size="18"
              />
            </IconBtn>
          </div>
        </template>
      </VDataTable>

      <!-- Barra de Estado Inferior Tipo Excel (KPIs de Recuento y Sumas) -->
      <div class="excel-status-bar d-flex flex-wrap align-center justify-space-between px-4 py-2 border-top bg-var-theme-background">
        <div class="d-flex flex-wrap gap-4 align-center text-caption font-weight-medium">
          <div class="d-flex align-center gap-1">
            <VIcon icon="ri-table-line" size="16" class="text-medium-emphasis" />
            <span>Recuento: <strong>{{ summaryMetrics.count }}</strong></span>
          </div>
          <div class="d-flex align-center gap-1">
            <VIcon icon="ri-archive-line" size="16" class="text-primary" />
            <span>Suma Stock: <strong class="text-primary">{{ summaryMetrics.totalStock }} unid.</strong></span>
          </div>
          <div v-if="isOwner" class="d-flex align-center gap-1">
            <VIcon icon="ri-money-dollar-box-line" size="16" class="text-medium-emphasis" />
            <span>Valuación Costo: <strong>Bs. {{ summaryMetrics.totalCostValuation.toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}</strong></span>
          </div>
          <div v-if="isOwner" class="d-flex align-center gap-1">
            <VIcon icon="ri-money-dollar-circle-line" size="16" class="text-success" />
            <span>Valuación Venta: <strong class="text-success">Bs. {{ summaryMetrics.totalSaleValuation.toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}</strong></span>
          </div>
          <div v-if="isOwner" class="d-flex align-center gap-1">
            <VIcon icon="ri-line-chart-line" size="16" class="text-info" />
            <span>Ganancia Proyectada: <strong class="text-info">Bs. {{ summaryMetrics.projectedProfit.toLocaleString('es-BO', { minimumFractionDigits: 2 }) }} ({{ summaryMetrics.avgMarginPct }}%)</strong></span>
          </div>
        </div>

        <div class="text-caption text-medium-emphasis">
          Promedio P. Venta: <strong>Bs. {{ summaryMetrics.avgSalePrice.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</strong>
        </div>
      </div>
    </VCard>

    <!-- Drawer de creación / edición -->
    <AddProductDrawer
      v-model:is-drawer-open="drawer"
      :product="editingProduct"
      @saved="onProductSaved"
    />

    <!-- Diálogo de ajuste de stock -->
    <StockAdjustDialog
      v-model:is-dialog-open="stockDialogOpen"
      :product="adjustingProduct"
      @saved="onStockAdjusted"
    />

    <!-- Diálogo Rápido de Edición de Precio (Celda Excel) -->
    <VDialog
      v-model="quickPriceDialog"
      max-width="420"
      persistent
    >
      <VCard class="pa-2">
        <VCardTitle class="d-flex align-center justify-space-between pb-2">
          <div class="d-flex align-center gap-2">
            <VAvatar color="primary" variant="tonal" rounded size="36">
              <VIcon icon="ri-price-tag-3-line" size="20" />
            </VAvatar>
            <div>
              <div class="text-subtitle-1 font-weight-bold">Editar Precio Rápido</div>
              <div class="text-caption text-medium-emphasis text-truncate" style="max-width: 250px;">
                {{ quickPriceProduct?.name }}
              </div>
            </div>
          </div>
          <VBtn icon variant="text" size="small" :disabled="quickPriceSaving" @click="quickPriceDialog = false">
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText class="pt-4">
          <div class="mb-3 text-caption text-medium-emphasis">
            SKU: <strong>{{ quickPriceProduct?.sku }}</strong> | Costo de Compra: <strong>Bs. {{ parseFloat(quickPriceProduct?.costPrice ?? 0).toFixed(2) }}</strong>
          </div>

          <VTextField
            v-model="newQuickPrice"
            label="Nuevo Precio de Venta (Bs.) *"
            placeholder="0.00"
            type="number"
            step="1"
            min="0"
            prefix="Bs."
            variant="outlined"
            density="comfortable"
            autofocus
            @keydown.enter="saveQuickPrice"
          />

          <div v-if="parseFloat(newQuickPrice) > 0" class="mt-2 text-caption">
            Margen Bruto Resultante:
            <strong :class="parseFloat(newQuickPrice) < parseFloat(quickPriceProduct?.costPrice ?? 0) ? 'text-error' : 'text-success'">
              Bs. {{ (parseFloat(newQuickPrice) - parseFloat(quickPriceProduct?.costPrice ?? 0)).toFixed(2) }}
            </strong>
          </div>
        </VCardText>

        <VCardActions class="pa-4 d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" :disabled="quickPriceSaving" @click="quickPriceDialog = false">
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            prepend-icon="ri-check-line"
            :loading="quickPriceSaving"
            :disabled="parseFloat(newQuickPrice) <= 0"
            @click="saveQuickPrice"
          >
            Guardar Precio
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Diálogo confirmación cambiar estado -->
    <VDialog
      :model-value="!!confirmingToggle"
      max-width="450"
      :persistent="toggling"
      @update:model-value="val => !val && !toggling && (confirmingToggle = null)"
    >
      <VCard :title="confirmingToggle?.status === 'active' ? 'Desactivar producto' : 'Activar producto'">
        <VCardText>
          ¿Desea cambiar el estado del producto <strong>{{ confirmingToggle?.name }}</strong> a
          <em>{{ confirmingToggle?.status === 'active' ? 'inactivo' : 'activo' }}</em>?
          <div
            v-if="confirmingToggle?.status === 'active'"
            class="text-caption text-warning mt-2"
          >
            ⚠️ El producto dejará de ser visible para los vendedores en mostrador.
          </div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            :disabled="toggling"
            variant="plain"
            @click="confirmingToggle = null"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            :loading="toggling"
            @click="toggleStatus"
          >
            Confirmar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Feedback snackbar -->
    <VSnackbar
      :model-value="!!notice"
      color="success"
      location="top end"
      @update:model-value="notice = ''"
    >
      {{ notice }}
    </VSnackbar>
  </section>
</template>

<style scoped>
/* Estilos Cuadrícula Tipo Hoja de Cálculo Excel */
.excel-table :deep(table) {
  border-collapse: collapse;
  width: 100%;
}

.excel-table :deep(th) {
  font-weight: 700 !important;
  text-transform: uppercase;
  font-size: 0.72rem !important;
  letter-spacing: 0.05em;
  border-bottom: 2px solid rgba(var(--v-border-color), 0.25) !important;
  border-right: 1px solid rgba(var(--v-border-color), 0.12) !important;
  padding: 8px 12px !important;
  white-space: nowrap;
}

.excel-table :deep(td) {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.15) !important;
  border-right: 1px solid rgba(var(--v-border-color), 0.1) !important;
  padding: 6px 12px !important;
  font-size: 0.85rem;
}

.excel-table :deep(tbody tr:nth-child(even)) {
  background-color: rgba(var(--v-theme-on-surface), 0.02);
}

.excel-table :deep(tbody tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.06) !important;
}

.font-mono {
  font-variant-numeric: tabular-nums;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.cell-editable {
  cursor: pointer;
  border-radius: 4px;
  padding: 2px 6px;
  transition: all 0.15s ease;
}

.cell-editable:hover {
  background-color: rgba(var(--v-theme-primary), 0.12);
  outline: 1px dashed rgb(var(--v-theme-primary));
}

.excel-status-bar {
  border-top: 1px solid rgba(var(--v-border-color), 0.15);
}
</style>
