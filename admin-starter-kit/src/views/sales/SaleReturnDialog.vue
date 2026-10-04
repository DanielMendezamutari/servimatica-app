<script setup>
import { ref, computed, watch } from 'vue'
import { $api, apiError } from '@/utils/api'
import { useAbility } from '@casl/vue'

const props = defineProps({
  isDialogOpen: {
    type: Boolean,
    required: true,
  },
  saleId: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits(['update:isDialogOpen', 'returnCompleted'])

const ability = useAbility()
const isOwner = computed(() => ability.can('manage', 'all'))

const loading = ref(false)
const submitting = ref(false)
const error = ref('')
const saleData = ref(null)
const currentShift = ref(null)

const resolution = ref('cambio_fisico')
const reason = ref('')
const returnItems = ref([])

const availableResolutions = computed(() => {
  const list = [
    { title: '🔄 Cambio Físico 1 a 1 (Garantía)', value: 'cambio_fisico' },
    { title: '📄 Nota de Crédito / Saldo a Favor', value: 'nota_credito' },
  ]
  if (isOwner.value) {
    list.push({ title: '💵 Reembolso en Efectivo (Caja Chica)', value: 'reembolso_efectivo' })
  }
  return list
})

watch(() => props.isDialogOpen, async newVal => {
  if (newVal && props.saleId) {
    await initDialog()
  } else {
    saleData.value = null
    returnItems.value = []
    reason.value = ''
    resolution.value = 'cambio_fisico'
    error.value = ''
  }
})

async function initDialog() {
  loading.value = true
  error.value = ''
  try {
    const [warrantyRes, shiftRes] = await Promise.all([
      $api(`/sales/${props.saleId}/warranty-check`),
      $api('/cash-shifts/current').catch(() => ({ is_open: false, data: null })),
    ])

    saleData.value = warrantyRes.data
    currentShift.value = shiftRes?.is_open ? shiftRes.data : null

    // Preparar lista reactiva de items a devolver
    returnItems.value = (warrantyRes.data?.items || []).map(it => ({
      sale_item_id: it.sale_item_id,
      product_name: it.product_name,
      unit_price: Number(it.unit_price),
      remaining_quantity: it.remaining_quantity,
      warranty_days: it.warranty_days,
      is_warranty_valid: it.is_warranty_valid,
      days_remaining: it.days_remaining,
      selected: false,
      quantity: 1,
      condition: it.is_warranty_valid ? 'stock_defectuoso_rma' : 'stock_operativo',
      serial_number: it.serial_number || '',
    }))
  } catch (err) {
    error.value = apiError(err, 'Error al cargar detalles de la venta para devolución.')
  } finally {
    loading.value = false
  }
}

const selectedItemsToReturn = computed(() => {
  return returnItems.value.filter(it => it.selected && it.remaining_quantity > 0)
})

const totalRefundAmount = computed(() => {
  return selectedItemsToReturn.value.reduce((acc, it) => acc + (it.quantity * it.unit_price), 0)
})

const canSubmit = computed(() => {
  if (submitting.value || loading.value) return false
  if (selectedItemsToReturn.value.length === 0) return false
  if (!reason.value.trim()) return false
  if (resolution.value === 'reembolso_efectivo' && !currentShift.value) return false
  return true
})

async function submitReturn() {
  if (!canSubmit.value) return

  submitting.value = true
  error.value = ''

  const payload = {
    resolution: resolution.value,
    reason: reason.value.trim(),
    cash_shift_id: resolution.value === 'reembolso_efectivo' ? currentShift.value?.id : null,
    items: selectedItemsToReturn.value.map(it => ({
      sale_item_id: it.sale_item_id,
      quantity: Number(it.quantity),
      condition: it.condition,
      serial_number: it.serial_number?.trim() || null,
    })),
  }

  try {
    const res = await $api(`/sales/${props.saleId}/returns`, {
      method: 'POST',
      body: payload,
    })

    emit('returnCompleted', res.data)
    close()
  } catch (err) {
    error.value = apiError(err, 'Error al procesar la devolución.')
  } finally {
    submitting.value = false
  }
}

function close() {
  if (!submitting.value) {
    emit('update:isDialogOpen', false)
  }
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    max-width="850px"
    persistent
    @update:model-value="close"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 bg-var-theme-background">
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-arrow-go-back-line" color="warning" size="24" />
          <span class="text-h6 font-weight-bold">
            Procesar Devolución / Garantía - Factura {{ saleData?.invoice_number }}
          </span>
        </div>
        <VBtn
          icon
          variant="text"
          size="small"
          :disabled="submitting"
          @click="close"
        >
          <VIcon icon="ri-close-line" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-4">
        <div v-if="loading" class="text-center py-8">
          <VProgressCircular indeterminate color="warning" />
          <div class="text-caption mt-2">Cargando productos de la venta...</div>
        </div>

        <VAlert
          v-else-if="error"
          type="error"
          variant="tonal"
          class="mb-4"
        >
          {{ error }}
        </VAlert>

        <div v-else-if="saleData">
          <!-- Banner Resumen de Venta -->
          <div class="d-flex flex-wrap justify-space-between align-center mb-4 pa-3 rounded bg-var-theme-background">
            <div>
              <span class="text-caption text-medium-emphasis">Cliente:</span>
              <div class="font-weight-bold">{{ saleData.client_name }}</div>
            </div>
            <div>
              <span class="text-caption text-medium-emphasis">Fecha de Compra:</span>
              <div>{{ saleData.sale_date }}</div>
            </div>
            <div>
              <span class="text-caption text-medium-emphasis">Turno de Caja:</span>
              <div>
                <VChip
                  :color="currentShift ? 'success' : 'error'"
                  size="x-small"
                  label
                  class="font-weight-bold"
                >
                  {{ currentShift ? `Caja Abierta (#${currentShift.id})` : 'Caja Cerrada' }}
                </VChip>
              </div>
            </div>
          </div>

          <!-- Selección de Productos para Devolución -->
          <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center gap-1">
            <VIcon icon="ri-checkbox-multiple-line" size="18" />
            1. Seleccione los productos a devolver
          </div>

          <VTable density="compact" class="border rounded mb-4">
            <thead>
              <tr>
                <th style="width: 40px;"></th>
                <th>Producto & S/N</th>
                <th class="text-center">Disp.</th>
                <th style="width: 110px;" class="text-center">Cant. Devolver</th>
                <th>Condición del Producto</th>
                <th class="text-end">Monto</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="it in returnItems"
                :key="it.sale_item_id"
                :class="{ 'bg-var-theme-background': it.selected }"
              >
                <td>
                  <VCheckbox
                    v-model="it.selected"
                    :disabled="it.remaining_quantity <= 0"
                    density="compact"
                    hide-details
                  />
                </td>
                <td>
                  <div class="font-weight-medium">{{ it.product_name }}</div>
                  <div class="d-flex align-center gap-2 mt-1">
                    <VChip
                      v-if="it.warranty_days > 0"
                      :color="it.is_warranty_valid ? 'success' : 'secondary'"
                      size="x-small"
                      label
                    >
                      {{ it.is_warranty_valid ? `Garantía Activa (${it.days_remaining}d)` : 'Garantía Vencida' }}
                    </VChip>
                    <VTextField
                      v-if="it.selected"
                      v-model="it.serial_number"
                      placeholder="S/N"
                      density="compact"
                      variant="plain"
                      hide-details
                      prepend-inner-icon="ri-barcode-line"
                      style="max-width: 140px; font-size: 11px;"
                    />
                  </div>
                </td>
                <td class="text-center font-weight-bold">
                  {{ it.remaining_quantity }}
                </td>
                <td class="text-center">
                  <VTextField
                    v-if="it.selected"
                    v-model.number="it.quantity"
                    type="number"
                    min="1"
                    :max="it.remaining_quantity"
                    density="compact"
                    variant="outlined"
                    hide-details
                    style="max-width: 90px; margin: 0 auto;"
                  />
                  <span v-else class="text-disabled">-</span>
                </td>
                <td>
                  <VSelect
                    v-if="it.selected"
                    v-model="it.condition"
                    :items="[
                      { title: '🔧 Falla Técnica / Defectuoso (A Cuarentena RMA)', value: 'stock_defectuoso_rma' },
                      { title: '📦 Stock Operativo (Regresa a Venta)', value: 'stock_operativo' },
                    ]"
                    density="compact"
                    variant="outlined"
                    hide-details
                    style="min-width: 200px; font-size: 12px;"
                  />
                  <span v-else class="text-disabled">-</span>
                </td>
                <td class="text-end font-weight-bold">
                  <span v-if="it.selected">
                    Bs. {{ (it.quantity * it.unit_price).toFixed(2) }}
                  </span>
                  <span v-else class="text-disabled">-</span>
                </td>
              </tr>
            </tbody>
          </VTable>

          <!-- 2. Resolución Comercial y Motivo -->
          <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center gap-1">
            <VIcon icon="ri-hand-coin-line" size="18" />
            2. Resolución Comercial y Motivo de la Devolución
          </div>

          <VRow dense>
            <VCol cols="12" md="6">
              <VSelect
                v-model="resolution"
                :items="availableResolutions"
                label="Tipo de Resolución *"
                variant="outlined"
                density="compact"
                hide-details="auto"
                class="mb-3"
              />
              <div v-if="resolution === 'cambio_fisico'" class="text-caption text-info mb-2">
                ℹ️ Se descontará 1 unidad de stock vendible para entregar el reemplazo inmediato al cliente.
              </div>
              <div v-else-if="resolution === 'reembolso_efectivo'" class="text-caption text-warning mb-2">
                ⚠️ Se registrará un egreso de Bs. {{ totalRefundAmount.toFixed(2) }} en el turno de caja actual.
              </div>
              <div v-else-if="resolution === 'nota_credito'" class="text-caption text-primary mb-2">
                ℹ️ Se generará saldo a favor del cliente para futuras compras o cotizaciones.
              </div>
            </VCol>

            <VCol cols="12" md="6">
              <VCard variant="tonal" color="primary" class="pa-3 text-center">
                <div class="text-caption text-medium-emphasis">Monto Total de Liquidación</div>
                <div class="text-h4 font-weight-black text-primary">
                  Bs. {{ totalRefundAmount.toFixed(2) }}
                </div>
                <div class="text-caption">
                  {{ selectedItemsToReturn.length }} producto(s) seleccionado(s)
                </div>
              </VCard>
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="reason"
                label="Motivo o Diagnóstico Técnico de la Devolución *"
                placeholder="Describa el motivo de la devolución o la falla técnica verificada en mostrador..."
                rows="2"
                variant="outlined"
                density="compact"
                hide-details="auto"
              />
            </VCol>
          </VRow>
        </div>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4 d-flex justify-end gap-2">
        <VBtn
          variant="outlined"
          color="secondary"
          :disabled="submitting"
          @click="close"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="warning"
          :loading="submitting"
          :disabled="!canSubmit"
          prepend-icon="ri-check-line"
          @click="submitReturn"
        >
          Confirmar Devolución
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
