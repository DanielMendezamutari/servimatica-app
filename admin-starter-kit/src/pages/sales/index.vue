<script setup>
import { $api, apiError } from '@/utils/api'
import { useAbility } from '@casl/vue'
import WarrantyCheckDialog from '@/views/sales/WarrantyCheckDialog.vue'
import SaleReturnDialog from '@/views/sales/SaleReturnDialog.vue'

const ability = useAbility()
const isOwner = computed(() => ability.can('manage', 'all'))

const warrantyDialogOpen = ref(false)
const selectedWarrantySaleId = ref(null)

const returnDialogOpen = ref(false)
const selectedReturnSaleId = ref(null)

function openWarrantyDialog(saleId) {
  selectedWarrantySaleId.value = saleId
  warrantyDialogOpen.value = true
}

function openReturnDialog(saleId) {
  selectedReturnSaleId.value = saleId
  returnDialogOpen.value = true
}

function onReturnCompleted(returnData) {
  notice.value = `Devolución ${returnData.return_number} procesada exitosamente.`
  loadSales()
}

const currentTab = ref('sales')

// Historial de Ventas
const sales = ref([])
const totalSales = ref(0)
const loadingSales = ref(false)
const searchSales = ref('')
const selectedStatus = ref('all')
const startDate = ref('')
const endDate = ref('')
const pageSales = ref(1)
const perPageSales = ref(15)

// Reporte de Comisiones
const commissions = ref([])
const loadingCommissions = ref(false)
const commStartDate = ref('')
const commEndDate = ref('')

// Diálogo de Anulación
const cancelDialogOpen = ref(false)
const saleToCancel = ref(null)
const cancelReason = ref('')
const cancelingBusy = ref(false)

const notice = ref('')
const error = ref('')

const salesHeaders = [
  { title: 'N° Factura', key: 'invoice_number' },
  { title: 'Fecha', key: 'created_at' },
  { title: 'Vendedor', key: 'seller_name' },
  { title: 'Cliente', key: 'client_name' },
  { title: 'Total', key: 'total_amount', align: 'end' },
  { title: 'Pago', key: 'payment_method', align: 'center' },
  { title: 'Estado', key: 'status', align: 'center' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

const commHeaders = [
  { title: 'Vendedor', key: 'seller_name' },
  { title: 'Ventas Concretadas', key: 'sales_count', align: 'center' },
  { title: 'Monto Total Vendido', key: 'total_sales_amount', align: 'end' },
  { title: 'Comisión Generada', key: 'total_commission_amount', align: 'end' },
]

async function loadSales() {
  loadingSales.value = true
  error.value = ''
  try {
    const params = {
      page: pageSales.value,
      per_page: perPageSales.value,
      search: searchSales.value?.trim() || undefined,
      status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
      start_date: startDate.value || undefined,
      end_date: endDate.value || undefined,
    }
    const res = await $api('/sales', { params })
    sales.value = res.data || []
    totalSales.value = res.total || 0
  } catch (e) {
    error.value = apiError(e, 'Error al cargar las ventas.')
  } finally {
    loadingSales.value = false
  }
}

async function loadCommissions() {
  if (!isOwner.value) return
  loadingCommissions.value = true
  try {
    const params = {
      start_date: commStartDate.value || undefined,
      end_date: commEndDate.value || undefined,
    }
    const res = await $api('/sales/commissions-report', { params })
    commissions.value = res.data || []
  } catch (e) {
    console.error('Error cargando comisiones:', e)
  } finally {
    loadingCommissions.value = false
  }
}

function printReceipt(id) {
  window.open(`/api/sales/${id}/receipt?autoprint=1`, '_blank')
}

function openCancelDialog(sale) {
  saleToCancel.value = sale
  cancelReason.value = ''
  cancelDialogOpen.value = true
}

async function submitCancellation() {
  if (!saleToCancel.value || !cancelReason.value.trim() || cancelingBusy.value) return

  cancelingBusy.value = true
  error.value = ''

  try {
    await $api(`/sales/${saleToCancel.value.id}/cancel`, {
      method: 'POST',
      body: { reason: cancelReason.value.trim() },
    })

    notice.value = `Venta ${saleToCancel.value.invoice_number} anulada con éxito. El inventario ha sido repuesto.`
    cancelDialogOpen.value = false
    await loadSales()
    if (isOwner.value) await loadCommissions()
  } catch (e) {
    error.value = apiError(e, 'No se pudo anular la venta.')
  } finally {
    cancelingBusy.value = false
  }
}

function setDatePreset(preset, target = 'sales') {
  const now = new Date()
  const pad = n => String(n).padStart(2, '0')
  const formatDate = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

  let start = ''
  let end = ''

  if (preset === 'today') {
    start = formatDate(now)
    end = formatDate(now)
  } else if (preset === 'week') {
    const weekAgo = new Date(now)
    weekAgo.setDate(now.getDate() - 7)
    start = formatDate(weekAgo)
    end = formatDate(now)
  } else if (preset === 'month') {
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1)
    start = formatDate(monthStart)
    end = formatDate(now)
  } else if (preset === 'clear') {
    start = ''
    end = ''
  }

  if (target === 'sales') {
    startDate.value = start
    endDate.value = end
  } else {
    commStartDate.value = start
    commEndDate.value = end
  }
}

watch([searchSales, selectedStatus, startDate, endDate], () => {
  pageSales.value = 1
  loadSales()
})

watch([commStartDate, commEndDate], () => {
  loadCommissions()
})

onMounted(async () => {
  await loadSales()
  if (isOwner.value) await loadCommissions()
})
</script>

<template>
  <div>
    <!-- Notificaciones -->
    <VAlert v-if="notice" type="success" variant="tonal" closable class="mb-4" @click:close="notice = ''">
      {{ notice }}
    </VAlert>
    <VAlert v-if="error" type="error" variant="tonal" closable class="mb-4" @click:close="error = ''">
      {{ error }}
    </VAlert>

    <VCard class="mb-4">
      <VTabs v-model="currentTab">
        <VTab value="sales" prepend-icon="ri-file-list-3-line">
          Historial de Ventas
        </VTab>
        <VTab v-if="isOwner" value="commissions" prepend-icon="ri-pie-chart-line">
          Reporte de Comisiones
        </VTab>
      </VTabs>
    </VCard>

    <VWindow v-model="currentTab">
      <!-- Pestaña 1: Ventas -->
      <VWindowItem value="sales">
        <VCard>
          <VCardText class="d-flex flex-wrap gap-4 pb-2">
            <VTextField
              v-model="searchSales"
              placeholder="Buscar por N° o Cliente..."
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-search-line"
              clearable
              style="max-width: 280px;"
            />

            <VSelect
              v-model="selectedStatus"
              :items="[
                { title: 'Todos los estados', value: 'all' },
                { title: 'Completadas', value: 'completed' },
                { title: 'Anuladas', value: 'cancelled' },
              ]"
              density="compact"
              variant="outlined"
              style="max-width: 200px;"
            />

            <AppDateTimePicker
              v-model="startDate"
              label="Desde"
              placeholder="YYYY-MM-DD"
              prepend-inner-icon="ri-calendar-line"
              density="compact"
              variant="outlined"
              clearable
              style="min-width: 150px;"
            />

            <AppDateTimePicker
              v-model="endDate"
              label="Hasta"
              placeholder="YYYY-MM-DD"
              prepend-inner-icon="ri-calendar-line"
              density="compact"
              variant="outlined"
              clearable
              style="min-width: 150px;"
            />

            <VBtnToggle
              density="compact"
              variant="outlined"
              color="primary"
              divided
            >
              <VBtn size="small" @click="setDatePreset('today', 'sales')">Hoy</VBtn>
              <VBtn size="small" @click="setDatePreset('week', 'sales')">7d</VBtn>
              <VBtn size="small" @click="setDatePreset('month', 'sales')">Mes</VBtn>
              <VBtn size="small" @click="setDatePreset('clear', 'sales')">Todo</VBtn>
            </VBtnToggle>

            <VSpacer />

            <VBtn color="primary" prepend-icon="ri-computer-line" to="/pos">
              Ir al POS
            </VBtn>
          </VCardText>

          <VCardText>
            <VDataTable
              :headers="salesHeaders"
              :items="sales"
              :loading="loadingSales"
              item-value="id"
              no-data-text="No se registran ventas"
              hover
            >
              <template #item.invoice_number="{ item }">
                <span class="font-weight-bold text-primary">{{ item.invoice_number }}</span>
              </template>

              <template #item.total_amount="{ item }">
                <span class="font-weight-bold">Bs. {{ item.total_amount }}</span>
              </template>

              <template #item.payment_method="{ item }">
                <VChip size="small" variant="tonal" class="text-uppercase font-weight-medium">
                  {{ item.payment_method }}
                </VChip>
              </template>

              <template #item.status="{ item }">
                <VChip
                  :color="item.status === 'completed' ? 'success' : 'error'"
                  size="small"
                  label
                  class="text-uppercase font-weight-bold"
                >
                  {{ item.status === 'completed' ? 'Completada' : 'Anulada' }}
                </VChip>
              </template>

              <template #item.actions="{ item }">
                <div class="d-flex justify-end gap-1">
                  <!-- Imprimir Ticket -->
                  <VBtn
                    icon
                    size="small"
                    variant="text"
                    color="secondary"
                    title="Imprimir Ticket Térmico"
                    @click="printReceipt(item.id)"
                  >
                    <VIcon icon="ri-printer-line" />
                  </VBtn>

                  <!-- Verificar Garantía -->
                  <VBtn
                    icon
                    size="small"
                    variant="text"
                    color="info"
                    title="Verificar Garantía"
                    @click="openWarrantyDialog(item.id)"
                  >
                    <VIcon icon="ri-shield-check-line" />
                  </VBtn>

                  <!-- Devolución / Garantía -->
                  <VBtn
                    v-if="item.status === 'completed'"
                    icon
                    size="small"
                    variant="text"
                    color="warning"
                    title="Procesar Devolución / Garantía"
                    @click="openReturnDialog(item.id)"
                  >
                    <VIcon icon="ri-arrow-go-back-line" />
                  </VBtn>

                  <!-- Anular Venta (Exclusivo Dueño) -->
                  <VBtn
                    v-if="isOwner && item.status === 'completed'"
                    icon
                    size="small"
                    variant="text"
                    color="error"
                    title="Anular Venta"
                    @click="openCancelDialog(item)"
                  >
                    <VIcon icon="ri-forbid-line" />
                  </VBtn>
                </div>
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VWindowItem>

      <!-- Pestaña 2: Comisiones (Dueño) -->
      <VWindowItem v-if="isOwner" value="commissions">
        <VCard title="Reporte Consolidado de Comisiones de Vendedores">
          <VCardText class="d-flex flex-wrap gap-4 pb-2">
            <AppDateTimePicker
              v-model="commStartDate"
              label="Desde"
              placeholder="YYYY-MM-DD"
              prepend-inner-icon="ri-calendar-line"
              density="compact"
              variant="outlined"
              clearable
              style="min-width: 160px;"
            />
            <AppDateTimePicker
              v-model="commEndDate"
              label="Hasta"
              placeholder="YYYY-MM-DD"
              prepend-inner-icon="ri-calendar-line"
              density="compact"
              variant="outlined"
              clearable
              style="min-width: 160px;"
            />

            <VBtnToggle
              density="compact"
              variant="outlined"
              color="primary"
              divided
            >
              <VBtn size="small" @click="setDatePreset('today', 'commissions')">Hoy</VBtn>
              <VBtn size="small" @click="setDatePreset('week', 'commissions')">7d</VBtn>
              <VBtn size="small" @click="setDatePreset('month', 'commissions')">Mes</VBtn>
              <VBtn size="small" @click="setDatePreset('clear', 'commissions')">Todo</VBtn>
            </VBtnToggle>
          </VCardText>

          <VCardText>
            <VDataTable
              :headers="commHeaders"
              :items="commissions"
              :loading="loadingCommissions"
              item-value="seller_id"
              no-data-text="No hay comisiones acumuladas para el periodo seleccionado"
            >
              <template #item.seller_name="{ item }">
                <span class="font-weight-bold">{{ item.seller_name }}</span>
              </template>
              <template #item.total_sales_amount="{ item }">
                <span class="font-weight-medium">Bs. {{ item.total_sales_amount }}</span>
              </template>
              <template #item.total_commission_amount="{ item }">
                <span class="font-weight-bold text-success">Bs. {{ item.total_commission_amount }}</span>
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VWindowItem>
    </VWindow>

    <!-- Diálogo Modal de Anulación -->
    <VDialog v-model="cancelDialogOpen" max-width="500" persistent>
      <VCard class="pa-2">
        <VCardTitle class="d-flex align-center gap-2">
          <VAvatar color="error" variant="tonal" rounded size="36">
            <VIcon icon="ri-alert-line" size="22" />
          </VAvatar>
          <div class="text-h6 font-weight-bold">Anular Venta {{ saleToCancel?.invoice_number }}</div>
        </VCardTitle>

        <VCardText class="pt-2">
          <p class="text-body-2 text-medium-emphasis mb-3">
            Esta acción revertirá la venta, invalidará la comisión del vendedor y <strong>repondrá automáticamente las unidades al inventario</strong>.
          </p>

          <VTextarea
            v-model="cancelReason"
            label="Motivo de la Anulación *"
            placeholder="Ej. Devolución de mercadería / Error de facturación..."
            rows="3"
            variant="outlined"
            autofocus
          />
        </VCardText>

        <VCardActions class="pa-4 d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" :disabled="cancelingBusy" @click="cancelDialogOpen = false">
            Cancelar
          </VBtn>
          <VBtn
            color="error"
            :loading="cancelingBusy"
            :disabled="!cancelReason.trim()"
            prepend-icon="ri-forbid-line"
            @click="submitCancellation"
          >
            Confirmar Anulación
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Diálogo Modal de Verificación de Garantía -->
    <WarrantyCheckDialog
      v-model:isDialogOpen="warrantyDialogOpen"
      :sale-id="selectedWarrantySaleId"
    />

    <!-- Diálogo Modal de Devolución / Garantía -->
    <SaleReturnDialog
      v-model:isDialogOpen="returnDialogOpen"
      :sale-id="selectedReturnSaleId"
      @return-completed="onReturnCompleted"
    />
  </div>
</template>
