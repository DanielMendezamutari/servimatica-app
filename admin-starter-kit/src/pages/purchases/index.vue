<script setup>
import PurchaseDetailsDialog from '@/views/purchases/PurchaseDetailsDialog.vue'
import { $api, apiError } from '@/utils/api'

const route = useRoute()
const router = useRouter()

const purchases = ref([])
const suppliers = ref([])
const loading = ref(false)
const error = ref('')
const notice = ref('')

const search = ref('')
const selectedSupplier = ref(null)
const selectedStatus = ref('all')
const startDate = ref('')
const endDate = ref('')
const page = ref(1)
const perPage = ref(15)
const totalItems = ref(0)

const detailsDialog = ref(false)
const selectedPurchaseId = ref(null)

const statusOptions = [
  { title: 'Todos los estados', value: 'all' },
  { title: 'Solo Recepcionadas', value: 'received' },
  { title: 'Solo Anuladas', value: 'cancelled' },
]

const headers = [
  { title: 'N° Compra', key: 'purchase_number' },
  { title: 'N° Factura', key: 'invoice_number' },
  { title: 'Proveedor Mayorista', key: 'supplier_name' },
  { title: 'Fecha', key: 'purchase_date' },
  { title: 'Condición', key: 'payment_condition' },
  { title: 'Total Facturado', key: 'total_amount', align: 'end' },
  { title: 'Estado', key: 'status', align: 'center' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

async function loadSuppliers() {
  try {
    const res = await $api('/suppliers/options')
    suppliers.value = res.data || []
  } catch (e) {
    console.error('Error loading suppliers', e)
  }
}

async function loadPurchases() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/purchases', {
      params: {
        page: page.value,
        per_page: perPage.value,
        search: search.value || undefined,
        supplier_id: selectedSupplier.value || undefined,
        status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
      },
    })
    purchases.value = res.data || []
    totalItems.value = res.total || 0
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    loading.value = false
  }
}

function openDetails(purchase) {
  selectedPurchaseId.value = purchase.id
  detailsDialog.value = true
}

function printReceipt(purchase) {
  window.open(`/api/purchases/${purchase.id}/receipt`, '_blank')
}

function onPurchaseCancelled() {
  notice.value = 'Compra anulada y stock devuelto exitosamente.'
  loadPurchases()
}

let searchTimer = null
function onSearchChange() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    loadPurchases()
  }, 350)
}

function setDateFilter(preset) {
  const now = new Date()
  const pad = n => String(n).padStart(2, '0')
  const formatDate = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

  if (preset === 'today') {
    const today = formatDate(now)
    startDate.value = today
    endDate.value = today
  } else if (preset === 'week') {
    const weekAgo = new Date(now)
    weekAgo.setDate(now.getDate() - 7)
    startDate.value = formatDate(weekAgo)
    endDate.value = formatDate(now)
  } else if (preset === 'month') {
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1)
    startDate.value = formatDate(monthStart)
    endDate.value = formatDate(now)
  } else if (preset === 'clear') {
    startDate.value = ''
    endDate.value = ''
  }
}

watch([selectedSupplier, selectedStatus, startDate, endDate], () => {
  page.value = 1
  loadPurchases()
})

onMounted(() => {
  if (route.query.received) {
    notice.value = `¡Compra ${route.query.received} recepcionada e ingresada a inventario exitosamente!`
    router.replace({ query: {} })
  }
  loadSuppliers()
  loadPurchases()
})
</script>

<template>
  <section>
    <VCard title="Órdenes de Compra y Recepción de Mercadería">
      <VCardText>
        <p class="text-body-1 mb-4 text-medium-emphasis">
          Historial de compras mayoristas, comprobantes fiscales de proveedores e ingreso formal a inventario físico.
        </p>

        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          class="mb-4"
          closable
        >
          {{ error }}
        </VAlert>

        <VAlert
          v-if="notice"
          type="success"
          variant="tonal"
          class="mb-4"
          closable
        >
          {{ notice }}
        </VAlert>

        <!-- Filtros y botón de acción -->
        <div class="d-flex flex-wrap gap-3 align-center justify-space-between mb-4">
          <div class="d-flex flex-wrap gap-3 align-center flex-grow-1">
            <VTextField
              v-model="search"
              label="Buscar por N° Compra o Factura..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              clearable
              style="min-width: 220px;"
              @update:model-value="onSearchChange"
            />

            <VSelect
              v-model="selectedSupplier"
              label="Filtrar por Proveedor"
              :items="suppliers"
              item-title="name"
              item-value="id"
              density="compact"
              clearable
              style="min-width: 200px;"
            />

            <VSelect
              v-model="selectedStatus"
              :items="statusOptions"
              density="compact"
              style="min-width: 170px;"
            />

            <AppDateTimePicker
              v-model="startDate"
              label="Desde"
              placeholder="YYYY-MM-DD"
              prepend-inner-icon="ri-calendar-line"
              density="compact"
              clearable
              style="min-width: 150px;"
            />

            <AppDateTimePicker
              v-model="endDate"
              label="Hasta"
              placeholder="YYYY-MM-DD"
              prepend-inner-icon="ri-calendar-line"
              density="compact"
              clearable
              style="min-width: 150px;"
            />

            <VBtnToggle
              density="compact"
              variant="outlined"
              color="primary"
              divided
            >
              <VBtn size="small" @click="setDateFilter('today')">Hoy</VBtn>
              <VBtn size="small" @click="setDateFilter('week')">7d</VBtn>
              <VBtn size="small" @click="setDateFilter('month')">Mes</VBtn>
              <VBtn size="small" @click="setDateFilter('clear')">Todo</VBtn>
            </VBtnToggle>
          </div>

          <VBtn
            to="/purchases/create"
            color="primary"
            prepend-icon="ri-add-line"
          >
            Nueva Recepción
          </VBtn>
        </div>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="purchases"
        :loading="loading"
        :items-per-page="perPage"
        no-data-text="No se encontraron compras registradas."
        loading-text="Cargando historial de compras..."
        items-per-page-text="Filas por página"
      >
        <template #item.purchase_number="{ item }">
          <span class="font-weight-bold text-primary cursor-pointer" @click="openDetails(item)">
            {{ item.purchase_number }}
          </span>
        </template>

        <template #item.invoice_number="{ item }">
          <span class="font-weight-medium">
            {{ item.invoice_number }}
          </span>
        </template>

        <template #item.supplier_name="{ item }">
          <div class="d-flex flex-column py-1">
            <span class="font-weight-medium text-body-2">{{ item.supplier_name }}</span>
            <span v-if="item.supplier_nit" class="text-caption text-medium-emphasis">
              NIT: {{ item.supplier_nit }}
            </span>
          </div>
        </template>

        <template #item.purchase_date="{ item }">
          <span>{{ item.purchase_date }}</span>
        </template>

        <template #item.payment_condition="{ item }">
          <div class="d-flex align-center gap-1">
            <VChip
              size="x-small"
              :color="item.payment_condition === 'contado' ? 'success' : 'warning'"
              variant="tonal"
            >
              {{ item.payment_condition === 'contado' ? 'Contado' : 'Crédito' }}
            </VChip>
            <span class="text-caption text-medium-emphasis text-capitalize">({{ item.payment_method }})</span>
          </div>
        </template>

        <template #item.total_amount="{ item }">
          <span class="font-weight-bold text-body-2 text-primary">
            Bs. {{ item.total_amount }}
          </span>
        </template>

        <template #item.status="{ item }">
          <VChip
            size="small"
            variant="tonal"
            :color="item.status === 'received' ? 'success' : 'error'"
          >
            {{ item.status === 'received' ? 'Recepcionada' : 'Anulada' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-end">
            <VBtn
              size="small"
              variant="text"
              color="primary"
              icon="ri-eye-line"
              title="Ver Detalle"
              @click="openDetails(item)"
            />
            <VBtn
              size="small"
              variant="text"
              color="secondary"
              icon="ri-printer-line"
              title="Imprimir Nota de Recepción"
              @click="printReceipt(item)"
            />
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Diálogo de Detalle y Anulación -->
    <PurchaseDetailsDialog
      v-model:is-dialog-open="detailsDialog"
      :purchase-id="selectedPurchaseId"
      @cancelled="onPurchaseCancelled"
    />
  </section>
</template>
