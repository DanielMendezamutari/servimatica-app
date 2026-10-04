<script setup>
import QuickProductDialog from '@/views/purchases/QuickProductDialog.vue'
import SupplierDialog from '@/views/suppliers/SupplierDialog.vue'
import { $api, apiError } from '@/utils/api'

const router = useRouter()

const form = ref({
  supplier_id: null,
  invoice_number: '',
  purchase_date: new Date().toISOString().substring(0, 10),
  payment_condition: 'contado',
  payment_method: 'transferencia',
  payment_method_id: null,
  reference_number: '',
  due_date: '',
  notes: '',
  items: [],
})

const suppliers = ref([])
const products = ref([])
const paymentMethodOptions = ref([])
const productSearchText = ref('')
const selectedProductToAdd = ref(null)
const searchingProducts = ref(false)

// Diálogo Explorar Catálogo
const catalogDialog = ref(false)
const catalogSearch = ref('')
const catalogCategory = ref(null)
const categories = ref([])
const catalogLoading = ref(false)
const catalogProducts = ref([])

const quickProductDialog = ref(false)
const quickSupplierDialog = ref(false)

const busy = ref(false)
const error = ref('')
const errors = ref({})

const paymentConditions = [
  { title: 'Al Contado', value: 'contado' },
  { title: 'A Crédito', value: 'credito' },
]

const paymentMethods = [
  { title: 'Transferencia Bancaria', value: 'transferencia' },
  { title: 'Efectivo', value: 'efectivo' },
  { title: 'Otro', value: 'otro' },
]

function getProductCost(p) {
  if (!p) return 0
  if (p.costPrice !== undefined && p.costPrice !== null) return parseFloat(p.costPrice) || 0
  if (p.cost_price !== undefined && p.cost_price !== null) return parseFloat(p.cost_price) || 0
  return 0
}

function getProductSalePrice(p) {
  if (!p) return 0
  if (p.salePrice !== undefined && p.salePrice !== null) return parseFloat(p.salePrice) || 0
  if (p.sale_price !== undefined && p.sale_price !== null) return parseFloat(p.sale_price) || 0
  return 0
}

async function loadSuppliers() {
  try {
    const res = await $api('/suppliers/options')
    suppliers.value = res.data || []
  } catch (e) {
    console.error('Error loading suppliers options', e)
  }
}

async function loadCategories() {
  try {
    const res = await $api('/categories/options')
    categories.value = res.data || []
  } catch (e) {
    console.error('Error loading categories', e)
  }
}

async function loadInitialProducts() {
  searchingProducts.value = true
  try {
    const res = await $api('/products', { params: { per_page: 50 } })
    products.value = res.data || []
  } catch (e) {
    console.error('Error loading initial products', e)
  } finally {
    searchingProducts.value = false
  }
}

let searchTimer = null
function onSearchInput(val) {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(async () => {
    searchingProducts.value = true
    try {
      const term = val?.trim() || ''
      const res = await $api('/products', {
        params: { search: term || undefined, per_page: 50 },
      })
      products.value = res.data || []
    } catch (e) {
      console.error('Error searching products', e)
    } finally {
      searchingProducts.value = false
    }
  }, 250)
}

function onProductSelected(product) {
  if (!product) return

  addProductToItems(product)
  selectedProductToAdd.value = null
  productSearchText.value = ''
}

function addProductToItems(product) {
  const exists = form.value.items.find(i => i.product_id === product.id)
  if (exists) {
    exists.quantity += 1
  } else {
    const cost = getProductCost(product)
    const sale = getProductSalePrice(product)

    form.value.items.push({
      product_id: product.id,
      product_name: product.name,
      product_sku: product.sku || '',
      current_stock: product.stock ?? 0,
      previous_cost: cost,
      previous_sale_price: sale,
      quantity: 1,
      unit_cost: cost,
      new_sale_price: sale > 0 ? sale : null,
    })
  }
}

function onQuickProductCreated(newProduct) {
  loadInitialProducts()
  addProductToItems(newProduct)
}

function onQuickSupplierCreated(newSupplier) {
  loadSuppliers()
  form.value.supplier_id = newSupplier.id
}

function removeItem(index) {
  form.value.items.splice(index, 1)
}

// Diálogo Explorar Catálogo
async function openCatalogDialog() {
  catalogDialog.value = true
  await fetchCatalogProducts()
}

async function fetchCatalogProducts() {
  catalogLoading.value = true
  try {
    const res = await $api('/products', {
      params: {
        search: catalogSearch.value?.trim() || undefined,
        category_id: catalogCategory.value || undefined,
        per_page: 50,
      },
    })
    catalogProducts.value = res.data || []
  } catch (e) {
    console.error('Error fetching catalog', e)
  } finally {
    catalogLoading.value = false
  }
}

let catalogSearchTimer = null
function onCatalogSearchChange() {
  clearTimeout(catalogSearchTimer)
  catalogSearchTimer = setTimeout(fetchCatalogProducts, 300)
}

watch(catalogCategory, () => {
  fetchCatalogProducts()
})

function isItemAlreadyInPurchase(productId) {
  return form.value.items.some(i => i.product_id === productId)
}

const totalAmount = computed(() => {
  return form.value.items.reduce((sum, item) => {
    const qty = parseFloat(item.quantity) || 0
    const cost = parseFloat(item.unit_cost) || 0
    return sum + (qty * cost)
  }, 0)
})

const totalUnits = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0)
})

async function submitPurchase() {
  if (busy.value) return
  error.value = ''
  errors.value = {}

  if (!form.value.supplier_id) {
    error.value = 'Debe seleccionar un proveedor mayorista.'
    return
  }

  if (!form.value.invoice_number.trim()) {
    error.value = 'Debe ingresar el número de factura o nota de entrega.'
    return
  }

  if (!form.value.items.length) {
    error.value = 'Debe agregar al menos un producto a la compra.'
    return
  }

  if (form.value.payment_condition === 'credito' && !form.value.due_date) {
    error.value = 'Debe indicar la fecha de vencimiento para compras a crédito.'
    return
  }

  busy.value = true

  const payload = {
    supplier_id: form.value.supplier_id,
    invoice_number: form.value.invoice_number.trim(),
    purchase_date: form.value.purchase_date,
    payment_condition: form.value.payment_condition,
    payment_method: form.value.payment_method,
    payment_method_id: form.value.payment_method_id || null,
    reference_number: form.value.reference_number?.trim() || null,
    due_date: form.value.payment_condition === 'credito' ? form.value.due_date : null,
    notes: form.value.notes?.trim() || null,
    items: form.value.items.map(item => ({
      product_id: item.product_id,
      quantity: parseInt(item.quantity) || 1,
      unit_cost: parseFloat(item.unit_cost) || 0,
      new_sale_price: item.new_sale_price ? parseFloat(item.new_sale_price) : null,
    })),
  }

  try {
    const res = await $api('/purchases', {
      method: 'POST',
      body: payload,
    })

    router.push({
      path: '/purchases',
      query: { received: res.data?.purchase_number || '1' },
    })
  } catch (failure) {
    errors.value = failure.data?.errors || {}
    error.value = apiError(failure)
  } finally {
    busy.value = false
  }
}

async function loadPaymentMethods() {
  try {
    const res = await $api('/payment-methods/options?context=purchases')
    const list = res.data || []
    paymentMethodOptions.value = list.map(m => ({
      title: `${m.name}${m.bank_name ? ` (${m.bank_name})` : ''}`,
      value: m.id,
    }))
    if (paymentMethodOptions.value.length > 0 && !form.value.payment_method_id) {
      form.value.payment_method_id = paymentMethodOptions.value[0].value
    }
  } catch (e) {
    console.error('Error loading payment methods options', e)
  }
}

onMounted(() => {
  loadSuppliers()
  loadCategories()
  loadInitialProducts()
  loadPaymentMethods()
})
</script>

<template>
  <section>
    <!-- Header con botón volver -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div class="d-flex align-center">
        <VBtn
          icon="ri-arrow-left-line"
          variant="text"
          class="me-2"
          to="/purchases"
        />
        <div>
          <h4 class="text-h5 font-weight-bold mb-0">
            Recepción de Compra de Mercadería
          </h4>
          <span class="text-caption text-medium-emphasis">
            Ingreso formal a inventario físico, registro de costos mayoristas y actualización de precios de venta.
          </span>
        </div>
      </div>
    </div>

    <VAlert
      v-if="error"
      type="error"
      variant="tonal"
      class="mb-4"
      closable
    >
      {{ error }}
    </VAlert>

    <VRow>
      <!-- Datos de Facturación del Proveedor -->
      <VCol cols="12" lg="8">
        <VCard title="Datos del Comprobante y Proveedor" class="mb-4">
          <VCardText>
            <VRow>
              <VCol cols="12" md="8">
                <div class="d-flex gap-2 align-center">
                  <VSelect
                    v-model="form.supplier_id"
                    label="Proveedor Mayorista *"
                    :items="suppliers"
                    item-title="name"
                    item-value="id"
                    placeholder="Seleccione distribuidor"
                    :disabled="busy"
                    clearable
                  >
                    <template #item="{ props: itemProps, item }">
                      <VListItem v-bind="itemProps" :subtitle="item.raw.nit ? `NIT: ${item.raw.nit}` : 'Sin NIT'" />
                    </template>
                  </VSelect>
                  <VBtn
                    icon="ri-user-add-line"
                    variant="tonal"
                    color="primary"
                    title="Registrar Nuevo Proveedor"
                    @click="quickSupplierDialog = true"
                  />
                </div>
              </VCol>

              <VCol cols="12" md="4">
                <VTextField
                  v-model="form.invoice_number"
                  label="N° Factura / Nota Entrega *"
                  placeholder="Ej. FC-98432"
                  :disabled="busy"
                />
              </VCol>

              <VCol cols="12" md="4">
                <AppDateTimePicker
                  v-model="form.purchase_date"
                  label="Fecha de Emisión *"
                  placeholder="YYYY-MM-DD"
                  prepend-inner-icon="ri-calendar-line"
                  :disabled="busy"
                />
              </VCol>

              <VCol cols="12" md="4">
                <VSelect
                  v-model="form.payment_condition"
                  label="Condición de Pago *"
                  :items="paymentConditions"
                  :disabled="busy"
                />
              </VCol>

              <VCol cols="12" md="4">
                <VSelect
                  v-model="form.payment_method_id"
                  label="Cuenta / Método de Liquidación *"
                  :items="paymentMethodOptions"
                  item-title="title"
                  item-value="value"
                  :disabled="busy"
                  no-data-text="No hay métodos configurados"
                />
              </VCol>

              <VCol cols="12" md="4" v-if="form.payment_condition === 'credito'">
                <AppDateTimePicker
                  v-model="form.due_date"
                  label="Fecha de Vencimiento Crédito *"
                  placeholder="YYYY-MM-DD"
                  prepend-inner-icon="ri-calendar-line"
                  color="warning"
                  :disabled="busy"
                />
              </VCol>

              <VCol cols="12" md="4">
                <VTextField
                  v-model="form.reference_number"
                  label="N° Operación / Ref. Egreso"
                  placeholder="Ej. TRF-102938"
                  prepend-inner-icon="ri-file-text-line"
                  :disabled="busy"
                />
              </VCol>

              <VCol cols="12" :md="form.payment_condition === 'credito' ? 8 : 12">
                <VTextField
                  v-model="form.notes"
                  label="Observaciones / Garantía del Lote"
                  placeholder="Notas internas de la recepción..."
                  :disabled="busy"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- Detalle de Productos Ingresados -->
        <VCard title="Productos a Ingresar en Almacén">
          <VCardText>
            <div class="d-flex flex-wrap gap-2 align-center mb-4">
              <!-- Selector Autocomplete Inteligente -->
              <VAutocomplete
                v-model="selectedProductToAdd"
                v-model:search="productSearchText"
                label="Buscar producto por nombre o SKU..."
                :items="products"
                item-title="name"
                return-object
                density="compact"
                class="flex-grow-1"
                clearable
                :loading="searchingProducts"
                :no-filter="true"
                placeholder="Escriba para buscar o haga clic para ver opciones..."
                @update:search="onSearchInput"
                @update:model-value="onProductSelected"
              >
                <template #item="{ props: itemProps, item }">
                  <VListItem
                    v-bind="itemProps"
                    :title="item.raw.name"
                  >
                    <template #subtitle>
                      <div class="d-flex gap-2 text-caption mt-1">
                        <span>SKU: <strong>{{ item.raw.sku || 'N/A' }}</strong></span>
                        <span>| Stock actual: <strong class="text-info">{{ item.raw.stock ?? 0 }}</strong></span>
                        <span>| Costo anterior: <strong>Bs. {{ getProductCost(item.raw).toFixed(2) }}</strong></span>
                      </div>
                    </template>
                  </VListItem>
                </template>

                <template #no-data>
                  <div class="pa-3 text-center text-caption text-medium-emphasis">
                    No se encontraron productos. Puede hacer clic en <strong>"Explorar Catálogo"</strong> o <strong>"Alta Rápida de Producto"</strong>.
                  </div>
                </template>
              </VAutocomplete>

              <!-- Botón Explorar Catálogo Completo -->
              <VBtn
                variant="tonal"
                color="info"
                prepend-icon="ri-search-eye-line"
                @click="openCatalogDialog"
              >
                Explorar Catálogo
              </VBtn>

              <!-- Botón Alta Rápida -->
              <VBtn
                variant="tonal"
                color="primary"
                prepend-icon="ri-add-line"
                @click="quickProductDialog = true"
              >
                Alta Rápida
              </VBtn>
            </div>

            <!-- Tabla de Ítems en la Compra -->
            <VTable density="compact" class="text-no-wrap border rounded">
              <thead>
                <tr>
                  <th style="min-width: 220px;">Producto</th>
                  <th style="width: 100px;">Cant.</th>
                  <th style="width: 140px;">Costo Unit. (Bs.)</th>
                  <th style="width: 130px;">Subtotal (Bs.)</th>
                  <th style="width: 150px;">Nuevo P. Venta (Bs.)</th>
                  <th style="width: 50px;"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!form.items.length">
                  <td colspan="6" class="text-center py-6 text-medium-emphasis">
                    <VIcon icon="ri-inbox-line" size="32" class="mb-1 d-block mx-auto text-disabled" />
                    No ha agregado productos a la compra.<br>
                    <span class="text-caption text-medium-emphasis">
                      Busque arriba por nombre/SKU, explore el catálogo o presione "Alta Rápida de Producto".
                    </span>
                  </td>
                </tr>
                <tr v-for="(item, idx) in form.items" :key="idx">
                  <td>
                    <div class="d-flex flex-column py-2">
                      <span class="font-weight-medium text-body-2">{{ item.product_name }}</span>
                      <span class="text-caption text-medium-emphasis">
                        SKU: {{ item.product_sku || '-' }} | Stock actual: <strong class="text-info">{{ item.current_stock }}</strong>
                      </span>
                    </div>
                  </td>
                  <td>
                    <VTextField
                      v-model="item.quantity"
                      type="number"
                      min="1"
                      density="compact"
                      style="width: 80px;"
                      hide-details
                    />
                  </td>
                  <td>
                    <VTextField
                      v-model="item.unit_cost"
                      type="number"
                      step="0.01"
                      min="0"
                      prefix="Bs."
                      density="compact"
                      style="width: 120px;"
                      hide-details
                    />
                  </td>
                  <td class="font-weight-bold text-primary">
                    Bs. {{ (parseFloat(item.quantity || 0) * parseFloat(item.unit_cost || 0)).toFixed(2) }}
                  </td>
                  <td>
                    <VTextField
                      v-model="item.new_sale_price"
                      type="number"
                      step="0.01"
                      min="0"
                      prefix="Bs."
                      density="compact"
                      style="width: 130px;"
                      :placeholder="item.previous_sale_price ? item.previous_sale_price.toFixed(2) : 'Sin cambio'"
                      hide-details
                    />
                  </td>
                  <td class="text-right">
                    <VBtn
                      icon="ri-delete-bin-line"
                      variant="text"
                      color="error"
                      size="small"
                      @click="removeItem(idx)"
                    />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Resumen y Acción de Confirmación -->
      <VCol cols="12" lg="4">
        <VCard title="Resumen de Adquisición" class="position-sticky" style="top: 80px;">
          <VCardText>
            <div class="d-flex justify-space-between mb-2">
              <span class="text-body-2">Líneas de producto:</span>
              <span class="font-weight-medium">{{ form.items.length }}</span>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span class="text-body-2">Total unidades físicas:</span>
              <span class="font-weight-medium">{{ totalUnits }} u.</span>
            </div>
            <div class="d-flex justify-space-between mb-2">
              <span class="text-body-2">Condición de pago:</span>
              <VChip size="x-small" :color="form.payment_condition === 'contado' ? 'success' : 'warning'">
                {{ form.payment_condition === 'contado' ? 'Al Contado' : 'A Crédito' }}
              </VChip>
            </div>

            <VDivider class="my-4" />

            <div class="d-flex justify-space-between align-center mb-6">
              <span class="text-h6 font-weight-bold">Total Factura:</span>
              <span class="text-h5 font-weight-black text-primary">
                Bs. {{ totalAmount.toFixed(2) }}
              </span>
            </div>

            <VAlert
              type="info"
              variant="tonal"
              density="compact"
              class="mb-4 text-caption"
            >
              Al confirmar, el stock aumentará inmediatamente y los costos unitarios se registrarán en la trazabilidad inmutable del sistema.
            </VAlert>

            <VBtn
              block
              color="primary"
              size="large"
              prepend-icon="ri-check-line"
              :loading="busy"
              :disabled="!form.items.length || busy"
              @click="submitPurchase"
            >
              Confirmar e Ingresar a Stock
            </VBtn>

            <VBtn
              block
              variant="text"
              class="mt-2"
              to="/purchases"
              :disabled="busy"
            >
              Descartar
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Modal: Explorar Catálogo de Productos -->
    <VDialog
      v-model="catalogDialog"
      max-width="850"
    >
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-4">
          <span class="text-h6 font-weight-bold">
            <VIcon icon="ri-search-eye-line" class="me-2 text-primary" />
            Explorar Catálogo de Productos
          </span>
          <VBtn
            icon="ri-close-line"
            variant="text"
            size="small"
            @click="catalogDialog = false"
          />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-4">
          <div class="d-flex flex-wrap gap-3 align-center mb-3">
            <VTextField
              v-model="catalogSearch"
              label="Buscar por nombre o SKU..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              class="flex-grow-1"
              clearable
              style="min-width: 250px;"
              @update:model-value="onCatalogSearchChange"
            />

            <VSelect
              v-model="catalogCategory"
              label="Filtrar por Categoría"
              :items="categories"
              item-title="name"
              item-value="id"
              density="compact"
              clearable
              style="min-width: 200px;"
            />
          </div>

          <VTable density="compact" class="border rounded text-no-wrap">
            <thead>
              <tr>
                <th>Producto / SKU</th>
                <th>Categoría</th>
                <th class="text-center">Stock Actual</th>
                <th class="text-right">Costo Ant. (Bs.)</th>
                <th class="text-right">P. Venta (Bs.)</th>
                <th class="text-center" style="width: 120px;">Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="catalogLoading">
                <td colspan="6" class="text-center py-6">
                  <VProgressCircular indeterminate color="primary" size="24" class="me-2" />
                  Cargando productos del catálogo...
                </td>
              </tr>
              <tr v-else-if="!catalogProducts.length">
                <td colspan="6" class="text-center py-6 text-medium-emphasis">
                  No se encontraron productos registrados.
                </td>
              </tr>
              <tr v-for="prod in catalogProducts" :key="prod.id">
                <td>
                  <div class="font-weight-medium">{{ prod.name }}</div>
                  <div class="text-caption text-medium-emphasis">SKU: {{ prod.sku || '-' }}</div>
                </td>
                <td>
                  <span class="text-caption">{{ prod.categoryName || 'General' }}</span>
                </td>
                <td class="text-center">
                  <VChip size="x-small" :color="prod.stock > 0 ? 'success' : 'warning'">
                    {{ prod.stock ?? 0 }} u.
                  </VChip>
                </td>
                <td class="text-right">
                  Bs. {{ getProductCost(prod).toFixed(2) }}
                </td>
                <td class="text-right font-weight-medium">
                  Bs. {{ getProductSalePrice(prod).toFixed(2) }}
                </td>
                <td class="text-center">
                  <VBtn
                    size="small"
                    variant="tonal"
                    :color="isItemAlreadyInPurchase(prod.id) ? 'success' : 'primary'"
                    :prepend-icon="isItemAlreadyInPurchase(prod.id) ? 'ri-check-line' : 'ri-add-line'"
                    @click="addProductToItems(prod)"
                  >
                    {{ isItemAlreadyInPurchase(prod.id) ? '+1 Más' : 'Agregar' }}
                  </VBtn>
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4 justify-space-between">
          <span class="text-caption text-medium-emphasis">
            Seleccionados en la factura: <strong>{{ form.items.length }} productos</strong>
          </span>
          <VBtn
            color="primary"
            @click="catalogDialog = false"
          >
            Listo, volver a la factura
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Modales de Apoyo: Alta Rápida de Producto y Proveedor -->
    <QuickProductDialog
      v-model:is-dialog-open="quickProductDialog"
      @created="onQuickProductCreated"
    />

    <SupplierDialog
      v-model:is-dialog-open="quickSupplierDialog"
      @saved="onQuickSupplierCreated"
    />
  </section>
</template>
