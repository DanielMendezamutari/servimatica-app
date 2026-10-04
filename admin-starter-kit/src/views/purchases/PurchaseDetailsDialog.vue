<script setup>
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
  purchaseId: { type: Number, default: null },
})

const emit = defineEmits(['update:isDialogOpen', 'cancelled'])

const purchase = ref(null)
const loading = ref(false)
const error = ref('')

// Cancel modal state
const cancelDialog = ref(false)
const cancelReason = ref('')
const cancelling = ref(false)
const cancelError = ref('')

watch(() => props.isDialogOpen, async open => {
  if (!open || !props.purchaseId) {
    purchase.value = null
    return
  }
  await loadPurchaseDetails()
})

async function loadPurchaseDetails() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api(`/purchases/${props.purchaseId}`)
    purchase.value = res.data
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    loading.value = false
  }
}

function printReceipt() {
  if (!purchase.value) return
  window.open(`/api/purchases/${purchase.value.id}/receipt`, '_blank')
}

function openCancel() {
  cancelReason.value = ''
  cancelError.value = ''
  cancelDialog.value = true
}

async function confirmCancel() {
  if (!cancelReason.value.trim()) {
    cancelError.value = 'Debe indicar el motivo justificado de la anulación.'
    return
  }

  cancelling.value = true
  cancelError.value = ''
  try {
    await $api(`/purchases/${purchase.value.id}/cancel`, {
      method: 'POST',
      body: { reason: cancelReason.value.trim() },
    })

    cancelDialog.value = false
    emit('cancelled')
    await loadPurchaseDetails()
  } catch (failure) {
    cancelError.value = apiError(failure)
  } finally {
    cancelling.value = false
  }
}

function close() {
  emit('update:isDialogOpen', false)
}
</script>

<template>
  <VDialog
    :model-value="isDialogOpen"
    max-width="850"
    @update:model-value="val => !val && close()"
  >
    <VCard v-if="loading" class="pa-8 text-center">
      <VProgressCircular indeterminate color="primary" class="mb-3" />
      <div class="text-body-2 text-medium-emphasis">Cargando comprobante de compra...</div>
    </VCard>

    <VCard v-else-if="purchase">
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <div class="d-flex align-center gap-3">
          <VIcon icon="ri-file-list-3-line" class="text-primary" size="24" />
          <div>
            <span class="text-h6 font-weight-bold">{{ purchase.purchase_number }}</span>
            <span class="text-caption text-medium-emphasis ms-2">Factura: {{ purchase.invoice_number }}</span>
          </div>
          <VChip
            :color="purchase.status === 'received' ? 'success' : 'error'"
            size="small"
            variant="tonal"
            class="ms-2"
          >
            {{ purchase.status === 'received' ? 'Recepcionada' : 'Anulada' }}
          </VChip>
        </div>

        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          @click="close"
        />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-4">
        <!-- Banner de Compra Anulada -->
        <VAlert
          v-if="purchase.status === 'cancelled'"
          type="error"
          variant="tonal"
          class="mb-4"
        >
          <strong>⚠️ Compra Anulada</strong><br>
          Motivo: {{ purchase.cancellation_reason }}<br>
          <span class="text-caption">
            Anulado por {{ purchase.cancelled_by_name || 'Administración' }} el {{ purchase.cancelled_at }}
          </span>
        </VAlert>

        <VRow class="mb-2">
          <VCol cols="12" sm="6" md="4">
            <div class="text-caption text-medium-emphasis">Proveedor</div>
            <div class="font-weight-bold text-body-1">{{ purchase.supplier_name }}</div>
            <div v-if="purchase.supplier_nit" class="text-caption text-medium-emphasis">
              NIT: {{ purchase.supplier_nit }}
            </div>
          </VCol>

          <VCol cols="12" sm="6" md="4">
            <div class="text-caption text-medium-emphasis">Fecha Emisión</div>
            <div class="font-weight-medium text-body-2">{{ purchase.purchase_date }}</div>
            <div class="text-caption text-medium-emphasis">
              Registrado por: {{ purchase.user_name || 'Dueño' }}
            </div>
          </VCol>

          <VCol cols="12" sm="6" md="4">
            <div class="text-caption text-medium-emphasis">Condición y Pago</div>
            <div class="d-flex align-center gap-2">
              <VChip size="x-small" :color="purchase.payment_condition === 'contado' ? 'success' : 'warning'">
                {{ purchase.payment_condition === 'contado' ? 'Contado' : 'Crédito' }}
              </VChip>
              <span class="text-caption font-weight-medium">{{ purchase.payment_method_name || purchase.payment_method }}</span>
            </div>
            <div v-if="purchase.reference_number" class="text-caption text-primary font-weight-bold mt-1">
              Ref/Comp: {{ purchase.reference_number }}
            </div>
            <div v-if="purchase.payment_condition === 'credito' && purchase.due_date" class="text-caption text-warning mt-1">
              Vence: {{ purchase.due_date }}
            </div>
          </VCol>

          <VCol v-if="purchase.notes" cols="12" class="pt-0">
            <div class="text-caption text-medium-emphasis">Observaciones</div>
            <div class="text-body-2">{{ purchase.notes }}</div>
          </VCol>
        </VRow>

        <!-- Tabla de ítems comprados -->
        <h6 class="text-subtitle-2 font-weight-bold mb-2">Artículos Recepcionados en Inventario</h6>
        <VTable density="compact" class="border rounded text-no-wrap mb-4">
          <thead>
            <tr>
              <th>Producto</th>
              <th class="text-center">Cant.</th>
              <th class="text-right">Costo Unit. (Bs.)</th>
              <th class="text-right">Subtotal (Bs.)</th>
              <th class="text-right">Costo Anterior</th>
              <th class="text-right">P. Venta Fijado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in purchase.items" :key="item.id">
              <td>
                <div class="font-weight-medium">{{ item.product_name }}</div>
                <div class="text-caption text-medium-emphasis">SKU: {{ item.product_sku || '-' }}</div>
              </td>
              <td class="text-center font-weight-bold">{{ item.quantity }}</td>
              <td class="text-right">Bs. {{ item.unit_cost }}</td>
              <td class="text-right font-weight-bold text-primary">Bs. {{ item.subtotal }}</td>
              <td class="text-right text-caption text-medium-emphasis">
                {{ item.previous_cost ? `Bs. ${item.previous_cost}` : '-' }}
              </td>
              <td class="text-right">
                <span v-if="item.new_sale_price" class="text-success font-weight-medium">
                  Bs. {{ item.new_sale_price }}
                </span>
                <span v-else class="text-caption text-disabled">-</span>
              </td>
            </tr>
          </tbody>
        </VTable>

        <div class="d-flex justify-end">
          <div style="min-width: 240px;">
            <div class="d-flex justify-space-between text-body-2 mb-1">
              <span>Subtotal:</span>
              <span>Bs. {{ purchase.subtotal }}</span>
            </div>
            <div class="d-flex justify-space-between text-h6 font-weight-black text-primary border-top pt-2">
              <span>Total Compra:</span>
              <span>Bs. {{ purchase.total_amount }}</span>
            </div>
          </div>
        </div>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4 justify-space-between">
        <VBtn
          v-if="purchase.status === 'received'"
          color="error"
          variant="tonal"
          prepend-icon="ri-close-circle-line"
          @click="openCancel"
        >
          Anular Compra
        </VBtn>
        <div v-else></div>

        <div class="d-flex gap-2">
          <VBtn
            variant="outlined"
            prepend-icon="ri-printer-line"
            @click="printReceipt"
          >
            Imprimir Comprobante
          </VBtn>
          <VBtn
            color="primary"
            @click="close"
          >
            Cerrar
          </VBtn>
        </div>
      </VCardActions>
    </VCard>

    <!-- Diálogo Modal de Anulación con Motivo -->
    <VDialog
      v-model="cancelDialog"
      max-width="500"
      :persistent="cancelling"
    >
      <VCard>
        <VCardTitle class="pa-4 text-h6 text-error">
          <VIcon icon="ri-alert-line" class="me-2" />
          Anulación de Compra de Mercadería
        </VCardTitle>

        <VCardText class="pa-4 pt-0">
          <p class="text-body-2">
            Esta acción descontará las unidades ingresadas del stock actual en almacén y registrará la trazabilidad inmutable del egreso.
          </p>

          <VAlert
            v-if="cancelError"
            type="error"
            variant="tonal"
            class="mb-3"
            closable
          >
            {{ cancelError }}
          </VAlert>

          <VTextarea
            v-model="cancelReason"
            label="Motivo Justificado de Anulación *"
            placeholder="Ej. Factura rechazada por errores de costo, mercadería devuelta al transportista..."
            rows="3"
            :disabled="cancelling"
            autofocus
          />
        </VCardText>

        <VCardActions class="pa-4 justify-end">
          <VBtn
            variant="text"
            :disabled="cancelling"
            @click="cancelDialog = false"
          >
            Regresar
          </VBtn>
          <VBtn
            color="error"
            :loading="cancelling"
            @click="confirmCancel"
          >
            Confirmar Anulación
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VDialog>
</template>
