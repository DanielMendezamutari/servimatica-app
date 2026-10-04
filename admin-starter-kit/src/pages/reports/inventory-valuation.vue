<script setup>
import { $api, apiError } from '@/utils/api'

const loading = ref(false)
const exportLoading = ref(false)
const error = ref('')

const searchQuery = ref('')
const selectedCategory = ref(null)
const categories = ref([])

const reportData = ref({
  summary: {
    total_products_count: 0,
    total_sellable_units: 0,
    total_defective_units: 0,
    sellable_valuation_bs: 0,
    defective_valuation_bs: 0,
    total_inventory_valuation_bs: 0,
  },
  products: [],
})

// Paginación y filtro local
const itemsPerPage = ref(15)
const currentPage = ref(1)

async function loadCategories() {
  try {
    const res = await $api('/categories')
    categories.value = res.data || []
  } catch (e) {
    console.error('Error cargando categorías', e)
  }
}

async function loadReport() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/reports/inventory-valuation', {
      params: {
        search: searchQuery.value || undefined,
        category_id: selectedCategory.value || undefined,
      },
    })
    reportData.value = res.data
    currentPage.value = 1
  } catch (e) {
    error.value = apiError(e) || 'No se pudo cargar el reporte de valoración de inventario.'
  } finally {
    loading.value = false
  }
}

function clearFilters() {
  searchQuery.value = ''
  selectedCategory.value = null
  loadReport()
}

async function exportCsv() {
  exportLoading.value = true
  try {
    const queryParams = new URLSearchParams({
      export: 'csv',
      ...(searchQuery.value && { search: searchQuery.value }),
      ...(selectedCategory.value && { category_id: selectedCategory.value }),
    })

    const token = useCookie('accessToken').value
    const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api'
    const downloadUrl = `${baseUrl}/reports/inventory-valuation?${queryParams.toString()}`

    const response = await fetch(downloadUrl, {
      headers: { Authorization: `Bearer ${token}` },
    })

    if (!response.ok) throw new Error('Error al descargar el archivo')

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `valoracion_inventario_${Date.now()}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Error al exportar inventario', e)
    error.value = 'Ocurrió un error al exportar la valoración de inventario a CSV.'
  } finally {
    exportLoading.value = false
  }
}

const filteredProducts = computed(() => {
  return reportData.value?.products || []
})

const totalPages = computed(() => {
  if (itemsPerPage.value === -1) return 1
  return Math.ceil(filteredProducts.value.length / itemsPerPage.value) || 1
})

const paginatedProducts = computed(() => {
  if (itemsPerPage.value === -1) return filteredProducts.value
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredProducts.value.slice(start, start + itemsPerPage.value)
})

onMounted(async () => {
  await Promise.all([loadCategories(), loadReport()])
})
</script>

<template>
  <div>
    <!-- Encabezado de la Vista -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-archive-stack-line" size="28" color="primary" />
          <h4 class="text-h4 font-weight-bold">
            Valoración Global de Inventario
          </h4>
        </div>
        <p class="text-body-1 text-medium-emphasis mb-0 mt-1">
          Auditoría patrimonial del capital inmovilizado en existencias vendibles y mercadería en garantía.
        </p>
      </div>

      <div class="d-flex align-center gap-3">
        <VBtn
          color="primary"
          variant="outlined"
          prepend-icon="ri-refresh-line"
          :loading="loading"
          @click="loadReport"
        >
          Actualizar
        </VBtn>
        <VBtn
          color="success"
          prepend-icon="ri-file-excel-2-line"
          :loading="exportLoading"
          @click="exportCsv"
        >
          Exportar Excel (CSV)
        </VBtn>
      </div>
    </div>

    <!-- Error Alert -->
    <VAlert
      v-if="error"
      type="error"
      variant="tonal"
      closable
      class="mb-6"
      @click:close="error = ''"
    >
      {{ error }}
    </VAlert>

    <!-- Tarjetas de Resumen Patrimonial (KPIs) -->
    <VRow class="mb-6">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <p class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-1">
                Capital Total Inmovilizado
              </p>
              <h4 class="text-h4 font-weight-bold text-primary">
                Bs. {{ Number(reportData.summary.total_inventory_valuation_bs || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </h4>
              <span class="text-caption text-medium-emphasis">
                {{ Number(reportData.summary.total_sellable_units || 0) + Number(reportData.summary.total_defective_units || 0) }} existencias físicas totales
              </span>
            </div>
            <VAvatar color="primary" variant="tonal" size="52" rounded>
              <VIcon icon="ri-bank-line" size="28" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <p class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-1">
                Capital Vendible Activo
              </p>
              <h4 class="text-h4 font-weight-bold text-success">
                Bs. {{ Number(reportData.summary.sellable_valuation_bs || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </h4>
              <span class="text-caption text-success font-weight-medium">
                {{ reportData.summary.total_sellable_units || 0 }} unidades en vitrina
              </span>
            </div>
            <VAvatar color="success" variant="tonal" size="52" rounded>
              <VIcon icon="ri-store-2-line" size="28" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <p class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-1">
                Capital en Garantía / Merma
              </p>
              <h4 class="text-h4 font-weight-bold text-warning">
                Bs. {{ Number(reportData.summary.defective_valuation_bs || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </h4>
              <span class="text-caption text-warning font-weight-medium">
                {{ reportData.summary.total_defective_units || 0 }} unidades en taller
              </span>
            </div>
            <VAvatar color="warning" variant="tonal" size="52" rounded>
              <VIcon icon="ri-alarm-warning-line" size="28" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <p class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-1">
                Total de Artículos
              </p>
              <h4 class="text-h4 font-weight-bold text-info">
                {{ reportData.summary.total_products_count || 0 }}
              </h4>
              <span class="text-caption text-medium-emphasis">
                Catálogo activo de productos
              </span>
            </div>
            <VAvatar color="info" variant="tonal" size="52" rounded>
              <VIcon icon="ri-price-tag-3-line" size="28" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Filtros de Consulta -->
    <VCard elevation="2" class="mb-6">
      <VCardText>
        <VRow align="center">
          <VCol cols="12" md="6">
            <VTextField
              v-model="searchQuery"
              label="Buscar por Producto, SKU o Código de Barras"
              placeholder="Ej. LAP-HP, Cable HDMI, 77501..."
              prepend-inner-icon="ri-search-line"
              clearable
              density="compact"
              variant="outlined"
              hide-details
              @keydown.enter="loadReport"
            />
          </VCol>

          <VCol cols="12" sm="6" md="4">
            <VSelect
              v-model="selectedCategory"
              :items="categories"
              item-title="name"
              item-value="id"
              label="Filtrar por Categoría"
              placeholder="Todas las categorías"
              clearable
              density="compact"
              variant="outlined"
              hide-details
              @update:model-value="loadReport"
            />
          </VCol>

          <VCol cols="12" sm="6" md="2" class="d-flex gap-2">
            <VBtn
              color="primary"
              variant="tonal"
              block
              prepend-icon="ri-filter-3-line"
              :loading="loading"
              @click="loadReport"
            >
              Filtrar
            </VBtn>
            <VBtn
              variant="outlined"
              color="secondary"
              icon="ri-close-line"
              title="Limpiar filtros"
              @click="clearFilters"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Tabla Detallada de Valoración -->
    <VCard elevation="2">
      <VCardItem class="border-b py-3 px-4">
        <div class="d-flex align-center justify-space-between flex-wrap gap-2">
          <VCardTitle class="text-h6 font-weight-bold">
            Inventario Valorizado por Producto
          </VCardTitle>
          <div class="d-flex align-center gap-2">
            <span class="text-caption text-medium-emphasis">Mostrar por pág:</span>
            <VSelect
              v-model="itemsPerPage"
              :items="[10, 15, 25, 50, 100, { title: 'Todos', value: -1 }]"
              density="compact"
              variant="outlined"
              hide-details
              style="max-inline-size: 110px;"
            />
          </div>
        </div>
      </VCardItem>

      <VTable hover class="text-no-wrap">
        <thead>
          <tr>
            <th class="text-start">SKU / CÓDIGO</th>
            <th class="text-start">PRODUCTO / MARCA</th>
            <th class="text-start">CATEGORÍA</th>
            <th class="text-center text-success font-weight-bold">STOCK VENDIBLE</th>
            <th class="text-center text-warning font-weight-bold">STOCK GARANTÍA</th>
            <th class="text-end">COSTO UNIT. (Bs.)</th>
            <th class="text-end">PRECIO VTA. (Bs.)</th>
            <th class="text-end text-success font-weight-bold">VALOR VENDIBLE (Bs.)</th>
            <th class="text-end text-warning font-weight-bold">VALOR GARANTÍA (Bs.)</th>
            <th class="text-end text-primary font-weight-bold">TOTAL CAPITAL (Bs.)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in paginatedProducts" :key="item.id">
            <td class="font-weight-medium">
              <div>{{ item.sku }}</div>
              <div v-if="item.barcode" class="text-caption text-medium-emphasis">
                {{ item.barcode }}
              </div>
            </td>
            <td>
              <div class="font-weight-bold text-high-emphasis">
                {{ item.name }}
              </div>
              <div v-if="item.brand_name" class="text-caption text-medium-emphasis">
                Marca: {{ item.brand_name }}
              </div>
            </td>
            <td>
              <VChip size="small" variant="tonal" color="primary">
                {{ item.category_name }}
              </VChip>
            </td>
            <td class="text-center">
              <VChip
                size="small"
                :color="item.stock > 0 ? 'success' : 'secondary'"
                variant="tonal"
                class="font-weight-bold"
              >
                {{ item.stock }}
              </VChip>
            </td>
            <td class="text-center">
              <VChip
                size="small"
                :color="item.defective_stock > 0 ? 'warning' : 'secondary'"
                variant="tonal"
                class="font-weight-bold"
              >
                {{ item.defective_stock }}
              </VChip>
            </td>
            <td class="text-end font-weight-medium">
              Bs. {{ Number(item.average_cost).toFixed(2) }}
            </td>
            <td class="text-end text-medium-emphasis">
              Bs. {{ Number(item.sale_price).toFixed(2) }}
            </td>
            <td class="text-end text-success font-weight-bold">
              Bs. {{ Number(item.sellable_value_bs).toFixed(2) }}
            </td>
            <td class="text-end text-warning font-weight-bold">
              Bs. {{ Number(item.defective_value_bs).toFixed(2) }}
            </td>
            <td class="text-end text-primary font-weight-bold">
              Bs. {{ Number(item.total_value_bs).toFixed(2) }}
            </td>
          </tr>

          <tr v-if="loading">
            <td colspan="10" class="text-center py-8">
              <VProgressCircular indeterminate color="primary" class="mr-2" />
              <span class="text-medium-emphasis">Calculando valoración patrimonial...</span>
            </td>
          </tr>

          <tr v-else-if="paginatedProducts.length === 0">
            <td colspan="10" class="text-center py-8 text-medium-emphasis">
              <VIcon icon="ri-inbox-line" size="36" class="mb-2 d-block mx-auto" />
              No se encontraron artículos con los criterios seleccionados.
            </td>
          </tr>
        </tbody>
      </VTable>

      <!-- Paginación -->
      <VCardText v-if="totalPages > 1" class="d-flex align-center justify-space-between pt-4 border-t">
        <span class="text-caption text-medium-emphasis">
          Mostrando {{ paginatedProducts.length }} de {{ filteredProducts.length }} productos
        </span>
        <VPagination
          v-model="currentPage"
          :length="totalPages"
          rounded="circle"
          total-visible="7"
          density="comfortable"
        />
      </VCardText>
    </VCard>
  </div>
</template>
