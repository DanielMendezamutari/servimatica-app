<script setup>
import { $api, apiError } from '@/utils/api'
import OpenCashShiftDialog from '@/views/pos/OpenCashShiftDialog.vue'
import CloseCashShiftDialog from '@/views/pos/CloseCashShiftDialog.vue'
import QuickClientDialog from '@/views/pos/QuickClientDialog.vue'
import CheckoutDialog from '@/views/pos/CheckoutDialog.vue'
import ReceiptPrintDialog from '@/views/pos/ReceiptPrintDialog.vue'

const route = useRoute()
const router = useRouter()

// Control de Caja
const currentShift = ref(null)
const isShiftOpen = ref(false)
const checkingShift = ref(true)
const openShiftDialogOpen = ref(false)
const closeShiftDialogOpen = ref(false)

// Catálogo de Productos
const products = ref([])
const categories = ref([])
const selectedCategory = ref(null)
const searchQuery = ref('')
const loadingProducts = ref(false)

// Carrito
const cart = ref([])
const selectedClient = ref(null)
const clientOptions = ref([])
const loadingClients = ref(false)
const quickClientDialogOpen = ref(false)

// Diálogos de Cobro y Ticket
const checkoutDialogOpen = ref(false)
const receiptDialogOpen = ref(false)
const completedSale = ref(null)
const loadedQuoteId = ref(null)

// Modal Ergonómico de Garantía y Serie (S/N)
const warrantyModalOpen = ref(false)
const selectedCartItem = ref(null)
const editingHardwareDays = ref(0)
const editingSoftwareDays = ref(0)
const editingSerialNumber = ref('')
const warrantySerialInputRef = ref(null)

const posWarrantyPresets = [
  { title: 'Sin garantía (0d)', value: 0 },
  { title: '15 días', value: 15 },
  { title: '30 días (1m)', value: 30 },
  { title: '90 días (3m)', value: 90 },
  { title: '180 días (6m)', value: 180 },
  { title: '365 días (1a)', value: 365 },
  { title: '730 días (2a)', value: 730 },
]

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

function openWarrantyModal(item) {
  selectedCartItem.value = item
  editingHardwareDays.value = item.warranty_hardware_days ?? item.warranty_days ?? 0
  editingSoftwareDays.value = item.warranty_software_days ?? 0
  editingSerialNumber.value = item.serial_number ?? ''
  warrantyModalOpen.value = true
  nextTick(() => {
    warrantySerialInputRef.value?.focus()
  })
}

function saveWarrantyModal() {
  if (selectedCartItem.value) {
    const hw = Number(editingHardwareDays.value) || 0
    const sw = Number(editingSoftwareDays.value) || 0
    selectedCartItem.value.warranty_days = hw
    selectedCartItem.value.warranty_hardware_days = hw
    selectedCartItem.value.warranty_software_days = sw
    selectedCartItem.value.serial_number = editingSerialNumber.value ? editingSerialNumber.value.trim() : ''
  }
  warrantyModalOpen.value = false
  selectedCartItem.value = null
}

const notice = ref('')
const error = ref('')

// Totales del Carrito
const cartSubtotal = computed(() => {
  return cart.value.reduce((acc, item) => acc + (item.quantity * item.sale_price), 0)
})

const cartTotalQuantity = computed(() => {
  return cart.value.reduce((acc, item) => acc + item.quantity, 0)
})

async function checkCashShift() {
  checkingShift.value = true
  try {
    const res = await $api('/cash-shifts/current')
    isShiftOpen.value = res.is_open
    currentShift.value = res.data
  } catch (e) {
    console.error('Error verificando turno de caja:', e)
  } finally {
    checkingShift.value = false
  }
}

async function loadProducts() {
  loadingProducts.value = true
  try {
    const params = {
      search: searchQuery.value?.trim() || undefined,
      categoryId: selectedCategory.value || undefined,
      perPage: 40,
    }
    const res = await $api('/products', { params })
    products.value = res.data || []
  } catch (e) {
    console.error('Error cargando productos:', e)
  } finally {
    loadingProducts.value = false
  }
}

async function loadCategories() {
  try {
    const res = await $api('/categories/options')
    categories.value = res.data || []
  } catch (e) {
    console.error('Error cargando categorías:', e)
  }
}

async function searchClients(query = '') {
  loadingClients.value = true
  try {
    const res = await $api('/clients', { params: { search: query, per_page: 20 } })
    clientOptions.value = res.data || []
  } catch (e) {
    console.error('Error cargando clientes:', e)
  } finally {
    loadingClients.value = false
  }
}

function addToCart(product) {
  if (product.stock <= 0) return

  const existing = cart.value.find(item => item.id === product.id)
  if (existing) {
    if (existing.quantity < product.stock) {
      existing.quantity++
    } else {
      error.value = `Stock máximo disponible para ${product.name}: ${product.stock} unidades.`
    }
  } else {
    const hwDays = Number(product.warranty_hardware_days ?? product.warrantyHardwareDays ?? product.warranty_days ?? product.warrantyDays ?? 0)
    const swDays = Number(product.warranty_software_days ?? product.warrantySoftwareDays ?? 0)
    cart.value.push({
      id: product.id,
      name: product.name,
      sku: product.sku,
      sale_price: Number(product.salePrice ?? product.sale_price ?? 0),
      stock: product.stock,
      quantity: 1,
      warranty_days: hwDays,
      warranty_hardware_days: hwDays,
      warranty_software_days: swDays,
      serial_number: '',
    })
  }
}

function updateCartQuantity(item, delta) {
  const newQty = item.quantity + delta
  if (newQty <= 0) {
    removeFromCart(item.id)
  } else if (newQty > item.stock) {
    error.value = `No hay suficiente stock. Disponible: ${item.stock} unidades.`
  } else {
    item.quantity = newQty
  }
}

function removeFromCart(id) {
  cart.value = cart.value.filter(item => item.id !== id)
}

function clearCart() {
  cart.value = []
  loadedQuoteId.value = null
  selectedClient.value = null
}

function onClientCreated(newClient) {
  clientOptions.value.unshift(newClient)
  selectedClient.value = newClient
  notice.value = `Cliente ${newClient.name} seleccionado.`
}

function onShiftOpened(shift) {
  isShiftOpen.value = true
  currentShift.value = shift
  notice.value = 'Turno de caja abierto correctamente. ¡Listo para vender!'
}

function onShiftClosed() {
  isShiftOpen.value = false
  currentShift.value = null
  notice.value = 'Turno de caja cerrado y arqueo registrado.'
}

function onSaleCompleted(sale) {
  completedSale.value = sale
  receiptDialogOpen.value = true
  clearCart()
  loadProducts() // Actualizar catálogo para reflejar stock descontado
}

function onNewSale() {
  receiptDialogOpen.value = false
  completedSale.value = null
}

async function saveAsQuote() {
  if (cart.value.length === 0) return

  try {
    const payload = {
      client_id: selectedClient.value?.id || null,
      client_name: selectedClient.value?.name || 'Cliente General',
      client_phone: selectedClient.value?.phone || null,
      items: cart.value.map(item => ({
        product_id: item.id,
        product_name: item.name,
        quantity: item.quantity,
        unit_price: item.sale_price,
      })),
    }

    const res = await $api('/quotes', {
      method: 'POST',
      body: payload,
    })

    notice.value = `Cotización ${res.data.quote_number} generada correctamente.`
    if (res.data.whatsapp_link) {
      window.open(res.data.whatsapp_link, '_blank')
    }
  } catch (e) {
    error.value = apiError(e, 'No se pudo generar la proforma.')
  }
}

// Cargar proforma si viene por query param ?quoteId=
async function checkLoadedQuote() {
  const qId = route.query.quoteId
  if (!qId) return

  try {
    const res = await $api(`/quotes/${qId}`)
    const q = res.data
    loadedQuoteId.value = q.id

    if (q.client_id) {
      selectedClient.value = {
        id: q.client_id,
        name: q.client_name,
        phone: q.client_phone,
      }
    } else {
      selectedClient.value = {
        id: null,
        name: q.client_name,
        phone: q.client_phone,
      }
    }

    cart.value = q.items.map(it => ({
      id: it.product_id,
      name: it.product_name,
      sku: '',
      sale_price: parseFloat(it.unit_price) || 0,
      stock: 999, // Se validará contra la BD al momento de cobrar
      quantity: it.quantity,
    }))

    notice.value = `Proforma ${q.quote_number} cargada en el carrito.`
  } catch (e) {
    console.error('Error cargando proforma:', e)
  }
}

watch(searchQuery, () => {
  loadProducts()
})

watch(selectedCategory, () => {
  loadProducts()
})

onMounted(async () => {
  await Promise.all([checkCashShift(), loadProducts(), loadCategories(), searchClients()])
  await checkLoadedQuote()
})
</script>

<template>
  <div>
    <!-- Notificaciones -->
    <VAlert v-if="notice" type="success" variant="tonal" closable class="mb-3" @click:close="notice = ''">
      {{ notice }}
    </VAlert>
    <VAlert v-if="error" type="error" variant="tonal" closable class="mb-3" @click:close="error = ''">
      {{ error }}
    </VAlert>

    <!-- Banner de Alerta si la Caja está Cerrada -->
    <VAlert
      v-if="!checkingShift && !isShiftOpen"
      type="warning"
      variant="tonal"
      class="mb-4 d-flex align-center justify-space-between"
    >
      <div class="d-flex align-center gap-3">
        <VIcon icon="ri-alert-line" size="28" />
        <div>
          <div class="font-weight-bold">Caja Cerrada</div>
          <div class="text-caption">Para poder procesar cobros y ventas en mostrador, debe abrir un turno de caja.</div>
        </div>
      </div>
      <template #append>
        <VBtn color="warning" prepend-icon="ri-lock-unlock-line" @click="openShiftDialogOpen = true">
          Abrir Caja Ahora
        </VBtn>
      </template>
    </VAlert>

    <!-- Barra Superior de Estado POS -->
    <VCard class="mb-4">
      <VCardText class="d-flex flex-wrap align-center justify-space-between gap-4 py-3">
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" rounded size="40">
            <VIcon icon="ri-computer-line" size="24" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-bold">Punto de Venta (POS)</div>
            <div class="text-caption text-medium-emphasis">Ventas y Cotizaciones en Mostrador</div>
          </div>
        </div>

        <div class="d-flex align-center gap-3">
          <template v-if="isShiftOpen">
            <VChip color="success" label class="font-weight-bold">
              <VIcon start icon="ri-checkbox-circle-line" />
              Caja Abierta (Fondo: Bs. {{ currentShift?.opening_amount }})
            </VChip>
            <VBtn
              size="small"
              variant="outlined"
              color="warning"
              prepend-icon="ri-lock-line"
              @click="closeShiftDialogOpen = true"
            >
              Cerrar Turno / Arqueo
            </VBtn>
          </template>
          <template v-else>
            <VBtn
              color="primary"
              size="small"
              prepend-icon="ri-lock-unlock-line"
              @click="openShiftDialogOpen = true"
            >
              Abrir Caja
            </VBtn>
          </template>

          <VBtn
            variant="text"
            color="secondary"
            size="small"
            to="/cash-shifts"
            prepend-icon="ri-history-line"
          >
            Historial Cajas
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <!-- Layout Dividido POS: Catálogo (Izquierda) y Carrito / Checkout (Derecha) -->
    <VRow>
      <!-- Panel de Catálogo de Productos (Cols 7 o 8) -->
      <VCol cols="12" md="7" lg="8">
        <VCard>
          <VCardText class="pb-2">
            <VRow dense>
              <VCol cols="12" sm="7">
                <VTextField
                  v-model="searchQuery"
                  placeholder="Buscar por nombre o código SKU..."
                  density="compact"
                  variant="outlined"
                  prepend-inner-icon="ri-search-line"
                  clearable
                />
              </VCol>
              <VCol cols="12" sm="5">
                <VSelect
                  v-model="selectedCategory"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  placeholder="Todas las categorías"
                  density="compact"
                  variant="outlined"
                  clearable
                />
              </VCol>
            </VRow>
          </VCardText>

          <VDivider />

          <!-- Cuadrícula de Productos -->
          <VCardText style="max-height: 650px; overflow-y: auto;">
            <div v-if="loadingProducts" class="text-center py-8">
              <VProgressCircular indeterminate color="primary" />
              <div class="text-caption mt-2">Cargando catálogo...</div>
            </div>

            <div v-else-if="products.length === 0" class="text-center py-8 text-medium-emphasis">
              <VIcon icon="ri-inbox-line" size="48" class="mb-2 text-disabled" />
              <div>No se encontraron productos disponibles.</div>
            </div>

            <VRow v-else dense>
              <VCol
                v-for="prod in products"
                :key="prod.id"
                cols="12"
                sm="6"
                md="6"
                lg="4"
              >
                <VCard
                  variant="outlined"
                  class="h-100 product-card d-flex flex-column justify-space-between transition-swing elevation-hover cursor-pointer"
                  :class="{ 'opacity-60': prod.stock <= 0 }"
                  :ripple="prod.stock > 0"
                  @click="prod.stock > 0 ? addToCart(prod) : null"
                >
                  <VCardText class="pa-3">
                    <div class="d-flex align-center justify-space-between mb-1">
                      <span class="text-caption text-primary font-weight-bold">{{ prod.sku }}</span>
                      <VChip
                        :color="prod.stock > 5 ? 'success' : (prod.stock > 0 ? 'warning' : 'error')"
                        size="x-small"
                        label
                        class="font-weight-bold"
                      >
                        {{ prod.stock > 0 ? `${prod.stock} disp.` : 'Agotado' }}
                      </VChip>
                    </div>

                    <div class="text-body-2 font-weight-bold text-truncate-2 mb-2" style="min-height: 40px;" :title="prod.name">
                      {{ prod.name }}
                    </div>

                    <div class="d-flex align-center justify-space-between mt-auto pt-2 border-top">
                      <span class="text-h6 font-weight-black text-primary">
                        Bs. {{ Number(prod.salePrice ?? prod.sale_price ?? 0).toFixed(2) }}
                      </span>
                      <VBtn
                        icon
                        size="small"
                        color="primary"
                        variant="tonal"
                        :disabled="prod.stock <= 0"
                        title="Agregar al Carrito"
                      >
                        <VIcon icon="ri-add-line" size="18" />
                      </VBtn>
                    </div>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Panel de Carrito de Venta y Cobro (Cols 5 o 4) -->
      <VCol cols="12" md="5" lg="4">
        <VCard class="d-flex flex-column" style="min-height: 650px;">
          <!-- Encabezado Cliente -->
          <VCardText class="pb-2">
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-subtitle-2 font-weight-bold">Cliente</span>
              <VBtn
                size="x-small"
                variant="text"
                color="primary"
                prepend-icon="ri-user-add-line"
                @click="quickClientDialogOpen = true"
              >
                Nuevo
              </VBtn>
            </div>

            <VAutocomplete
              v-model="selectedClient"
              :items="clientOptions"
              item-title="name"
              return-object
              placeholder="Cliente Mostrador (General)"
              density="compact"
              variant="outlined"
              clearable
              hide-details
            >
              <template #item="{ props, item }">
                <VListItem v-bind="props" :subtitle="item.raw.phone ? `Tel: ${item.raw.phone}` : (item.raw.nit_ci ? `NIT: ${item.raw.nit_ci}` : '')" />
              </template>
            </VAutocomplete>
          </VCardText>

          <VDivider />

          <!-- Lista de Ítems en Carrito -->
          <div class="flex-grow-1 pa-3" style="max-height: 380px; overflow-y: auto;">
            <div v-if="cart.length === 0" class="text-center py-10 text-medium-emphasis">
              <VIcon icon="ri-shopping-cart-2-line" size="48" class="mb-2 text-disabled" />
              <div class="text-body-2 font-weight-medium">El carrito de venta está vacío</div>
              <div class="text-caption">Haga clic en los productos para agregarlos</div>
            </div>

            <div v-else class="d-flex flex-column gap-2">
              <div
                v-for="item in cart"
                :key="item.id"
                class="pa-2 border rounded bg-var-theme-background d-flex flex-column"
              >
                <div class="d-flex align-center justify-space-between mb-1">
                  <div class="flex-grow-1 pr-2" style="max-width: 170px;">
                    <div class="text-body-2 font-weight-medium text-truncate" :title="item.name">{{ item.name }}</div>
                    <div class="text-caption font-weight-bold text-primary">Bs. {{ item.sale_price.toFixed(2) }} c/u</div>
                  </div>

                  <div class="d-flex align-center gap-1">
                    <VBtn
                      icon
                      size="x-small"
                      variant="tonal"
                      color="secondary"
                      @click="updateCartQuantity(item, -1)"
                    >
                      <VIcon icon="ri-subtract-line" size="14" />
                    </VBtn>

                    <span class="font-weight-bold px-1" style="min-width: 24px; text-align: center;">
                      {{ item.quantity }}
                    </span>

                    <VBtn
                      icon
                      size="x-small"
                      variant="tonal"
                      color="secondary"
                      @click="updateCartQuantity(item, 1)"
                    >
                      <VIcon icon="ri-add-line" size="14" />
                    </VBtn>

                    <VBtn
                      icon
                      size="x-small"
                      variant="text"
                      color="error"
                      class="ml-1"
                      title="Quitar"
                      @click="removeFromCart(item.id)"
                    >
                      <VIcon icon="ri-delete-bin-line" size="16" />
                    </VBtn>
                  </div>
                </div>

                <!-- Fila Compacta de Garantía y Serie (Ergonomía POS) -->
                <div class="d-flex align-center justify-space-between pt-1 border-top mt-1">
                  <div class="d-flex align-center gap-1 flex-wrap">
                    <VChip
                      size="x-small"
                      :variant="(item.warranty_hardware_days ?? item.warranty_days) > 0 ? 'tonal' : 'outlined'"
                      :color="(item.warranty_hardware_days ?? item.warranty_days) > 0 ? 'primary' : 'secondary'"
                      class="cursor-pointer"
                      prepend-icon="ri-shield-keyhole-line"
                      title="Garantía de Hardware (Física)"
                      @click="openWarrantyModal(item)"
                    >
                      {{ (item.warranty_hardware_days ?? item.warranty_days) > 0 ? `HW: ${formatShortWarranty(item.warranty_hardware_days ?? item.warranty_days)}` : 'Sin HW' }}
                    </VChip>

                    <VChip
                      size="x-small"
                      :variant="item.warranty_software_days > 0 ? 'tonal' : 'outlined'"
                      :color="item.warranty_software_days > 0 ? 'info' : 'secondary'"
                      class="cursor-pointer"
                      prepend-icon="ri-computer-line"
                      title="Garantía de Software (Soporte Lógico)"
                      @click="openWarrantyModal(item)"
                    >
                      {{ item.warranty_software_days > 0 ? `SW: ${formatShortWarranty(item.warranty_software_days)}` : 'Sin SW' }}
                    </VChip>
                  </div>

                  <div class="d-flex align-center gap-1">
                    <span
                      v-if="item.serial_number"
                      class="text-caption font-mono text-truncate text-medium-emphasis"
                      style="max-width: 120px;"
                      :title="`S/N: ${item.serial_number}`"
                    >
                      <VIcon icon="ri-barcode-line" size="13" class="me-0.5 text-success" />{{ item.serial_number }}
                    </span>

                    <VBtn
                      size="x-small"
                      variant="text"
                      :color="item.serial_number ? 'success' : 'secondary'"
                      icon
                      title="Configurar garantía y número de serie"
                      @click="openWarrantyModal(item)"
                    >
                      <VIcon :icon="item.serial_number ? 'ri-barcode-box-line' : 'ri-barcode-line'" size="16" />
                    </VBtn>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <VDivider />

          <!-- Resumen y Botones de Cobro / Cotización -->
          <VCardText class="pa-4 bg-var-theme-background">
            <div class="d-flex justify-space-between mb-1">
              <span class="text-body-2 text-medium-emphasis">Total Artículos:</span>
              <span class="font-weight-medium">{{ cartTotalQuantity }}</span>
            </div>

            <div class="d-flex justify-space-between align-center my-2">
              <span class="text-h6 font-weight-bold">SUBTOTAL:</span>
              <span class="text-h5 font-weight-black text-primary">Bs. {{ cartSubtotal.toFixed(2) }}</span>
            </div>

            <div class="d-flex flex-column gap-2 mt-3">
              <VBtn
                color="success"
                size="large"
                block
                prepend-icon="ri-money-dollar-circle-line"
                :disabled="cart.length === 0 || !isShiftOpen"
                @click="checkoutDialogOpen = true"
              >
                Cobrar (Bs. {{ cartSubtotal.toFixed(2) }})
              </VBtn>

              <div class="d-flex gap-2">
                <VBtn
                  color="info"
                  variant="tonal"
                  class="flex-grow-1"
                  prepend-icon="ri-file-text-line"
                  :disabled="cart.length === 0"
                  @click="saveAsQuote"
                >
                  Cotización / WhatsApp
                </VBtn>

                <VBtn
                  color="secondary"
                  variant="outlined"
                  icon
                  title="Vaciar Carrito"
                  :disabled="cart.length === 0"
                  @click="clearCart"
                >
                  <VIcon icon="ri-delete-bin-line" />
                </VBtn>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Diálogos Modales -->
    <OpenCashShiftDialog
      v-model:is-dialog-open="openShiftDialogOpen"
      @shift-opened="onShiftOpened"
    />

    <CloseCashShiftDialog
      v-model:is-dialog-open="closeShiftDialogOpen"
      :shift="currentShift"
      @shift-closed="onShiftClosed"
    />

    <QuickClientDialog
      v-model:is-dialog-open="quickClientDialogOpen"
      @client-created="onClientCreated"
    />

    <CheckoutDialog
      v-model:is-dialog-open="checkoutDialogOpen"
      :cart="cart"
      :subtotal="cartSubtotal"
      :cash-shift-id="currentShift?.id"
      :client-id="selectedClient?.id"
      :client-name="selectedClient?.name || 'Cliente Mostrador'"
      :client-nit-ci="selectedClient?.nit_ci || ''"
      :quote-id="loadedQuoteId"
      @sale-completed="onSaleCompleted"
    />

    <ReceiptPrintDialog
      v-model:is-dialog-open="receiptDialogOpen"
      :sale="completedSale"
      @new-sale="onNewSale"
    />

    <!-- Modal Ergonómico de Garantía y Series (S/N) en Carrito POS -->
    <VDialog
      v-model="warrantyModalOpen"
      max-width="480"
    >
      <VCard v-if="selectedCartItem">
        <VCardTitle class="d-flex align-center justify-space-between pb-2">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-shield-keyhole-line" color="primary" />
            <span class="text-subtitle-1 font-weight-bold">Garantía y Serie Técnica</span>
          </div>
          <VBtn
            icon
            variant="text"
            size="small"
            @click="warrantyModalOpen = false"
          >
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>

        <VCardSubtitle class="text-truncate">
          {{ selectedCartItem.name }} (Cant: {{ selectedCartItem.quantity }})
        </VCardSubtitle>

        <VCardText class="pt-3">
          <!-- Garantía de Hardware -->
          <div class="mb-4">
            <div class="d-flex align-center gap-1 mb-1">
              <VIcon icon="ri-shield-check-line" size="18" color="primary" />
              <VLabel class="text-caption font-weight-bold">Garantía de Hardware:</VLabel>
            </div>
            <div class="d-flex flex-wrap gap-1 mb-2">
              <VChip
                v-for="p in posWarrantyPresets"
                :key="'hw-' + p.value"
                size="small"
                :variant="editingHardwareDays === p.value ? 'elevated' : 'outlined'"
                :color="editingHardwareDays === p.value ? 'primary' : 'default'"
                class="cursor-pointer"
                @click="editingHardwareDays = p.value"
              >
                {{ p.title }}
              </VChip>
            </div>
            <VTextField
              v-model.number="editingHardwareDays"
              label="Días de hardware"
              type="number"
              min="0"
              density="compact"
              variant="outlined"
              suffix="días"
              hide-details
            />
          </div>

          <!-- Garantía de Software -->
          <div class="mb-4">
            <div class="d-flex align-center gap-1 mb-1">
              <VIcon icon="ri-code-box-line" size="18" color="info" />
              <VLabel class="text-caption font-weight-bold">Garantía de Software:</VLabel>
            </div>
            <div class="d-flex flex-wrap gap-1 mb-2">
              <VChip
                v-for="p in posWarrantyPresets"
                :key="'sw-' + p.value"
                size="small"
                :variant="editingSoftwareDays === p.value ? 'elevated' : 'outlined'"
                :color="editingSoftwareDays === p.value ? 'info' : 'default'"
                class="cursor-pointer"
                @click="editingSoftwareDays = p.value"
              >
                {{ p.title }}
              </VChip>
            </div>
            <VTextField
              v-model.number="editingSoftwareDays"
              label="Días de software"
              type="number"
              min="0"
              density="compact"
              variant="outlined"
              suffix="días"
              hide-details
            />
          </div>

          <div>
            <VLabel class="text-caption font-weight-bold mb-1">
              Número(s) de Serie (S/N):
            </VLabel>
            <VTextarea
              ref="warrantySerialInputRef"
              v-model="editingSerialNumber"
              placeholder="Escanee o escriba el número de serie. Si son varios ítems, separe por coma o salto de línea..."
              rows="2"
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-barcode-line"
              autofocus
              hide-details
            />
            <span class="text-caption text-medium-emphasis mt-1 d-block">
              {{ selectedCartItem.quantity > 1 ? `Se venden ${selectedCartItem.quantity} unidades. Puede registrar las series separadas por coma.` : 'Pistolee con el lector de código de barras para capturar la serie directamente.' }}
            </span>
          </div>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-3">
          <VSpacer />
          <VBtn
            variant="text"
            color="secondary"
            @click="warrantyModalOpen = false"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            variant="elevated"
            @click="saveWarrantyModal"
          >
            Guardar Cambios
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.product-card {
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
