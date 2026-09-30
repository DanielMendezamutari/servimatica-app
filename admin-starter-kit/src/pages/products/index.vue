<script setup>
import AddProductDrawer from '@/views/products/AddProductDrawer.vue'
import StockAdjustDialog from '@/views/products/StockAdjustDialog.vue'
import { $api, apiError } from '@/utils/api'
import { currentUser } from '@/utils/session'

// Adaptado de admin-full-version/src/pages/apps/ecommerce/product/list/index.vue
const router = useRouter()

const isOwner = computed(() => currentUser.value?.role === 'dueno')

const products = ref([])
const categories = ref([])
const loading = ref(false)
const error = ref('')
const notice = ref('')

const search = ref('')
const selectedCategory = ref(null)
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

const headers = computed(() => {
  const base = [
    { title: 'Producto', key: 'name' },
    { title: 'Categoría', key: 'categoryName' },
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

async function fetchCategories() {
  try {
    const res = await $api('/categories')
    categories.value = [
      { title: 'Todas las categorías', value: null },
      ...(res.data || []).map(c => ({ title: c.name, value: c.id })),
    ]
  } catch (e) {
    // Silently continue
  }
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

watch([selectedCategory, selectedStatus, page], loadProducts)

onMounted(async () => {
  await fetchCategories()
  await loadProducts()
})
</script>

<template>
  <section>
    <VCard title="Catálogo de Productos">
      <VCardText>
        <p class="text-body-1 mb-4">
          {{ isOwner ? 'Gestión integral del inventario, precios, costos y stock de tienda.' : 'Consulta rápida de catálogo, precios de venta y disponibilidad en inventario.' }}
        </p>

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

        <!-- Filtros y Búsqueda -->
        <div class="d-flex flex-wrap gap-4 align-center justify-space-between mb-2">
          <div class="d-flex flex-wrap gap-3 align-center flex-grow-1">
            <VTextField
              v-model="search"
              placeholder="Buscar por nombre o SKU..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              style="min-width: 240px; max-width: 320px;"
              clearable
              @update:model-value="loadProducts"
            />

            <VSelect
              v-model="selectedCategory"
              placeholder="Categoría"
              :items="categories"
              density="compact"
              style="min-width: 180px; max-width: 220px;"
            />

            <VSelect
              v-if="isOwner"
              v-model="selectedStatus"
              placeholder="Estado"
              :items="statusOptions"
              density="compact"
              style="min-width: 160px; max-width: 180px;"
            />
          </div>

          <VBtn
            v-if="isOwner"
            prepend-icon="ri-add-line"
            @click="openAdd(null)"
          >
            Nuevo producto
          </VBtn>
        </div>
      </VCardText>

      <!-- Tabla de Productos -->
      <VDataTable
        :headers="headers"
        :items="products"
        :loading="loading"
        :items-per-page="perPage"
        no-data-text="No se encontraron productos coincidentes."
        loading-text="Cargando productos..."
      >
        <!-- Nombre y SKU -->
        <template #item.name="{ item }">
          <div class="d-flex flex-column py-1">
            <span class="font-weight-medium text-high-emphasis">{{ item.name }}</span>
            <span class="text-caption text-medium-emphasis">SKU: {{ item.sku }}</span>
          </div>
        </template>

        <!-- Categoría -->
        <template #item.categoryName="{ item }">
          <VChip
            size="small"
            variant="tonal"
            color="primary"
          >
            {{ item.categoryName }}
          </VChip>
        </template>

        <!-- Precio Venta -->
        <template #item.salePrice="{ item }">
          <span class="font-weight-bold text-high-emphasis">
            Bs. {{ parseFloat(item.salePrice).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
          </span>
        </template>

        <!-- Stock y Badges -->
        <template #item.stock="{ item }">
          <div class="d-flex flex-column align-center gap-1">
            <span class="font-weight-medium">
              {{ item.stock }} unid.
            </span>
            <VChip
              v-if="item.stock === 0"
              color="error"
              size="x-small"
            >
              Agotado
            </VChip>
            <VChip
              v-else-if="isOwner && item.minStock && item.stock <= item.minStock"
              color="warning"
              size="x-small"
            >
              Stock bajo
            </VChip>
          </div>
        </template>

        <!-- Costo Compra (Solo Dueño) -->
        <template
          v-if="isOwner"
          #item.costPrice="{ item }">
          <span class="text-medium-emphasis">
            Bs. {{ parseFloat(item.costPrice).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
          </span>
        </template>

        <!-- Margen (Solo Dueño) -->
        <template
          v-if="isOwner"
          #item.margin="{ item }">
          <div class="d-flex flex-column align-end">
            <span
              class="font-weight-medium"
              :class="parseFloat(item.salePrice) < parseFloat(item.costPrice) ? 'text-error' : 'text-success'"
            >
              Bs. {{ calculateMargin(item.costPrice, item.salePrice).bs }}
            </span>
            <span class="text-caption text-medium-emphasis">
              ({{ calculateMargin(item.costPrice, item.salePrice).pct }}%)
            </span>
          </div>
        </template>

        <!-- Stock Mínimo (Solo Dueño) -->
        <template
          v-if="isOwner"
          #item.minStock="{ item }">
          <span>{{ item.minStock }}</span>
        </template>

        <!-- Estado (Solo Dueño) -->
        <template
          v-if="isOwner"
          #item.status="{ item }">
          <VChip
            :color="item.status === 'active' ? 'success' : 'secondary'"
            size="small"
          >
            {{ item.status === 'active' ? 'Activo' : 'Inactivo' }}
          </VChip>
        </template>

        <!-- Acciones (Solo Dueño) -->
        <template
          v-if="isOwner"
          #item.actions="{ item }">
          <div class="d-flex gap-1 justify-end">
            <VBtn
              size="small"
              variant="text"
              color="primary"
              icon="ri-edit-line"
              title="Editar datos del producto"
              @click="openAdd(item)"
            />

            <VBtn
              size="small"
              variant="text"
              color="info"
              icon="ri-exchange-line"
              title="Ajustar stock (+/-)"
              @click="openAdjustStock(item)"
            />

            <VBtn
              size="small"
              variant="text"
              color="secondary"
              icon="ri-history-line"
              title="Ver historial de movimientos"
              @click="openHistory(item)"
            />

            <VBtn
              size="small"
              variant="text"
              :color="item.status === 'active' ? 'warning' : 'success'"
              :icon="item.status === 'active' ? 'ri-eye-off-line' : 'ri-eye-line'"
              :title="item.status === 'active' ? 'Desactivar producto' : 'Activar producto'"
              @click="confirmingToggle = item"
            />
          </div>
        </template>
      </VDataTable>
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
