<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
  cart: { type: Array, default: () => [] },
  subtotal: { type: Number, default: 0 },
  cashShiftId: { type: Number, default: null },
  clientId: { type: Number, default: null },
  clientName: { type: String, default: 'Cliente Mostrador' },
  clientNitCi: { type: String, default: '' },
  quoteId: { type: Number, default: null },
})

const emit = defineEmits(['update:isDialogOpen', 'saleCompleted'])

const paymentMethods = ref([])
const loadingMethods = ref(false)
const selectedMethodId = ref(null)
const referenceNumber = ref('')

const discountAmount = ref('0.00')
const cashTendered = ref('')
const busy = ref(false)
const error = ref('')

const quickBills = [10, 20, 50, 100, 200]

const selectedMethod = computed(() => {
  return paymentMethods.value.find(m => m.id === selectedMethodId.value) || null
})

const isCash = computed(() => {
  return selectedMethod.value?.type === 'cash'
})

const isQr = computed(() => {
  return selectedMethod.value?.type === 'qr'
})

const isBankTransfer = computed(() => {
  return selectedMethod.value?.type === 'bank_transfer'
})

const requiresReference = computed(() => {
  return !!selectedMethod.value?.requires_reference || !!selectedMethod.value?.requiresReference
})

const isReferenceMissing = computed(() => {
  if (!requiresReference.value) return false
  return !referenceNumber.value.trim()
})

const parsedDiscount = computed(() => {
  const d = parseFloat(discountAmount.value)
  return isNaN(d) || d < 0 ? 0 : d
})

const totalToPay = computed(() => {
  const tot = props.subtotal - parsedDiscount.value
  return tot > 0 ? tot : 0
})

const parsedTendered = computed(() => {
  if (!isCash.value) return totalToPay.value
  const t = parseFloat(cashTendered.value)
  return isNaN(t) ? 0 : t
})

const changeDue = computed(() => {
  if (!isCash.value) return 0
  const diff = parsedTendered.value - totalToPay.value
  return diff > 0 ? diff : 0
})

const isCashInsufficient = computed(() => {
  if (!isCash.value) return false
  return parsedTendered.value < totalToPay.value
})

const canConfirm = computed(() => {
  if (busy.value || totalToPay.value <= 0) return false
  if (isCash.value && isCashInsufficient.value) return false
  if (isReferenceMissing.value) return false
  return true
})

async function fetchPaymentMethods() {
  loadingMethods.value = true
  try {
    const res = await $api('/payment-methods/options?context=sales')
    paymentMethods.value = res.data || res || []
    
    // Auto-seleccionar primer método (preferiblemente efectivo o el primero en la lista)
    if (paymentMethods.value.length > 0 && !selectedMethodId.value) {
      const cashMethod = paymentMethods.value.find(m => m.type === 'cash')
      selectedMethodId.value = cashMethod ? cashMethod.id : paymentMethods.value[0].id
    }
  } catch (err) {
    console.error('Error al cargar métodos de pago:', err)
  } finally {
    loadingMethods.value = false
  }
}

watch(() => props.isDialogOpen, open => {
  if (!open) return
  fetchPaymentMethods()
  discountAmount.value = '0.00'
  referenceNumber.value = ''
  cashTendered.value = totalToPay.value.toFixed(2)
  error.value = ''
})

function setExactAmount() {
  cashTendered.value = totalToPay.value.toFixed(2)
}

function setBill(bill) {
  cashTendered.value = bill.toFixed(2)
}

function close() {
  if (!busy.value) emit('update:isDialogOpen', false)
}

async function submitPayment() {
  if (!canConfirm.value) return
  if (!props.cashShiftId) {
    error.value = 'Debe abrir un turno de caja antes de realizar ventas.'
    return
  }

  busy.value = true
  error.value = ''

  const method = selectedMethod.value
  const fallbackType = method?.type === 'cash' ? 'efectivo' : (method?.type === 'qr' ? 'qr' : 'transferencia')

  const payload = {
    cash_shift_id: props.cashShiftId,
    payment_method: fallbackType,
    payment_method_id: method?.id || null,
    reference_number: requiresReference.value || referenceNumber.value.trim() ? referenceNumber.value.trim() : null,
    client_id: props.clientId || null,
    client_name: props.clientName?.trim() || 'Cliente Mostrador',
    client_nit_ci: props.clientNitCi?.trim() || null,
    discount_amount: parsedDiscount.value,
    cash_tendered: isCash.value ? parsedTendered.value : totalToPay.value,
    quote_id: props.quoteId || null,
    items: props.cart.map(item => {
      const hwDays = Number(item.warranty_hardware_days ?? item.warranty_days ?? 0)
      const swDays = Number(item.warranty_software_days ?? 0)
      return {
        product_id: item.id,
        quantity: item.quantity,
        unit_price: Number(item.sale_price ?? item.salePrice ?? 0),
        warranty_days: hwDays,
        warranty_hardware_days: hwDays,
        warranty_software_days: swDays,
        serial_number: item.serial_number?.trim() || null,
      }
    }),
  }

  try {
    const res = await $api('/sales', {
      method: 'POST',
      body: payload,
    })

    emit('saleCompleted', res.data)
    emit('update:isDialogOpen', false)
  } catch (e) {
    error.value = apiError(e, 'Error al procesar el cobro.')
  } finally {
    busy.value = false
  }
}

onMounted(() => {
  fetchPaymentMethods()
})
</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    max-width="700"
    persistent
    @update:model-value="close"
  >
    <VCard class="pa-2">
      <!-- Encabezado -->
      <VCardTitle class="d-flex align-center justify-space-between pb-2">
        <div class="d-flex align-center gap-2">
          <VAvatar color="success" variant="tonal" rounded size="38">
            <VIcon icon="ri-money-dollar-circle-line" size="24" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-bold">Cobro en Mostrador</div>
            <div class="text-caption text-medium-emphasis">
              {{ clientName }} {{ clientNitCi ? `(NIT/CI: ${clientNitCi})` : '' }}
            </div>
          </div>
        </div>
        <VBtn icon variant="text" size="small" :disabled="busy" @click="close">
          <VIcon icon="ri-close-line" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pt-4">
        <VAlert v-if="error" type="error" variant="tonal" closable class="mb-4" @click:close="error = ''">
          {{ error }}
        </VAlert>

        <!-- Selección Dinámica de Formas de Pago -->
        <div class="mb-4">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-subtitle-2 font-weight-bold">Seleccionar Forma de Pago</span>
            <span v-if="loadingMethods" class="text-caption text-medium-emphasis">
              <VProgressCircular indeterminate size="12" class="mr-1" /> Cargando métodos...
            </span>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <VBtn
              v-for="m in paymentMethods"
              :key="m.id"
              :variant="selectedMethodId === m.id ? 'elevated' : 'outlined'"
              :color="selectedMethodId === m.id ? 'primary' : 'secondary'"
              class="flex-grow-1"
              density="comfortable"
              @click="selectedMethodId = m.id"
            >
              <VIcon
                :icon="m.type === 'cash' ? 'ri-money-dollar-circle-line' : (m.type === 'qr' ? 'ri-qr-code-line' : (m.type === 'card' ? 'ri-bank-card-line' : 'ri-bank-line'))"
                class="mr-1"
              />
              {{ m.name }}
            </VBtn>
          </div>
        </div>

        <!-- Resumen de Totales y Descuento -->
        <VRow class="mb-2">
          <VCol cols="12" sm="6">
            <VTextField
              v-model="discountAmount"
              label="Descuento Libre (Bs.)"
              type="number"
              step="1"
              min="0"
              prefix="Bs."
              variant="outlined"
              density="comfortable"
              hint="Descuento directo al total"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <VCard variant="tonal" color="primary" class="pa-3 text-center">
              <div class="text-caption text-medium-emphasis text-uppercase font-weight-bold">
                Total a Cobrar
              </div>
              <div class="text-h4 font-weight-bold text-primary">
                Bs. {{ totalToPay.toFixed(2) }}
              </div>
            </VCard>
          </VCol>
        </VRow>

        <!-- PAGO EN EFECTIVO -->
        <template v-if="isCash">
          <VDivider class="my-3" />

          <div class="mb-2 d-flex align-center justify-space-between">
            <span class="text-subtitle-2 font-weight-bold">Efectivo Recibido</span>
            <VBtn size="x-small" variant="text" color="primary" @click="setExactAmount">
              Cobro Exacto
            </VBtn>
          </div>

          <VTextField
            v-model="cashTendered"
            label="Monto Entregado por el Cliente (Bs.)"
            placeholder="0.00"
            type="number"
            step="0.50"
            min="0"
            prefix="Bs."
            variant="outlined"
            autofocus
            class="mb-3"
            :error="isCashInsufficient"
            :error-messages="isCashInsufficient ? 'El efectivo entregado es menor al total a pagar' : ''"
          />

          <!-- Billetes rápidos bolivianos -->
          <div class="d-flex flex-wrap gap-2 mb-4">
            <span class="text-caption text-medium-emphasis align-self-center mr-1">Billetes:</span>
            <VBtn
              v-for="bill in quickBills"
              :key="bill"
              size="small"
              variant="tonal"
              color="secondary"
              @click="setBill(bill)"
            >
              Bs. {{ bill }}
            </VBtn>
          </div>

          <!-- Tarjeta de Cambio / Vuelto -->
          <VCard
            variant="tonal"
            :color="isCashInsufficient ? 'error' : (changeDue > 0 ? 'success' : 'info')"
            class="pa-3 text-center"
          >
            <div class="text-caption text-uppercase font-weight-bold">
              {{ isCashInsufficient ? 'Faltan Bs. ' + (totalToPay - parsedTendered).toFixed(2) : (changeDue > 0 ? 'Cambio / Vuelto para el Cliente' : 'Sin Vuelto (Monto Exacto)') }}
            </div>
            <div class="text-h4 font-weight-bold mt-1">
              Bs. {{ changeDue.toFixed(2) }}
            </div>
          </VCard>
        </template>

        <!-- PAGO CON CÓDIGO QR (PROYECCIÓN VISUAL) -->
        <template v-else-if="isQr">
          <VDivider class="my-3" />

          <VCard variant="outlined" class="pa-4 text-center my-3 bg-light-primary border-primary">
            <div class="d-flex flex-column align-center">
              <div class="text-subtitle-1 font-weight-bold mb-1">
                {{ selectedMethod.name }}
              </div>
              <div v-if="selectedMethod.bank_name || selectedMethod.bankName" class="text-caption text-medium-emphasis mb-3">
                {{ selectedMethod.bank_name || selectedMethod.bankName }}
                <span v-if="selectedMethod.account_holder || selectedMethod.accountHolder">
                  — {{ selectedMethod.account_holder || selectedMethod.accountHolder }}
                </span>
              </div>

              <!-- Imagen QR Oficial proyectada en grande -->
              <div v-if="selectedMethod.qr_image_url || selectedMethod.qrImageUrl" class="pa-3 bg-white rounded elevation-2 mb-3">
                <VImg
                  :src="selectedMethod.qr_image_url || selectedMethod.qrImageUrl"
                  width="220"
                  height="220"
                  aspect-ratio="1"
                  cover
                  class="rounded"
                />
              </div>
              <div v-else class="pa-6 border-dashed rounded text-medium-emphasis mb-3">
                <VIcon icon="ri-qr-code-line" size="64" class="mb-2" />
                <div class="text-caption">No se subió imagen de QR para esta cuenta.</div>
              </div>

              <div class="text-body-2 font-weight-medium">
                Pídale al cliente que escanee el código por el monto exacto de:
              </div>
              <div class="text-h5 font-weight-bold text-primary mt-1">
                Bs. {{ totalToPay.toFixed(2) }}
              </div>
            </div>
          </VCard>
        </template>

        <!-- PAGO CON TRANSFERENCIA BANCARIA -->
        <template v-else-if="isBankTransfer">
          <VDivider class="my-3" />

          <VCard variant="tonal" color="info" class="pa-4 my-3">
            <div class="d-flex align-center gap-3 mb-2">
              <VAvatar color="info" variant="elevated" rounded size="36">
                <VIcon icon="ri-bank-line" size="20" />
              </VAvatar>
              <div>
                <div class="text-subtitle-1 font-weight-bold">{{ selectedMethod.name }}</div>
                <div class="text-caption">{{ selectedMethod.bank_name || selectedMethod.bankName || 'Entidad Bancaria' }}</div>
              </div>
            </div>

            <VDivider class="my-2" />

            <div class="text-body-2 mb-1">
              <strong>N° de Cuenta:</strong> {{ selectedMethod.account_number || selectedMethod.accountNumber || 'S/N' }}
            </div>
            <div v-if="selectedMethod.account_holder || selectedMethod.accountHolder" class="text-body-2 mb-1">
              <strong>Titular:</strong> {{ selectedMethod.account_holder || selectedMethod.accountHolder }}
            </div>
            <div class="text-body-2 font-weight-bold mt-2 text-info">
              Monto a Transferir: Bs. {{ totalToPay.toFixed(2) }}
            </div>
          </VCard>
        </template>

        <!-- CAMPO OBLIGATORIO / OPCIONAL DE COMPROBANTE -->
        <template v-if="requiresReference || !isCash">
          <VDivider class="my-3" />

          <div class="mb-2">
            <VTextField
              v-model="referenceNumber"
              :label="requiresReference ? 'N° de Comprobante / Referencia Bancaria *' : 'N° de Comprobante / Referencia (Opcional)'"
              placeholder="Ej. TRF-10293847"
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-file-text-line"
              :rules="requiresReference ? [v => !!v.trim() || 'El número de comprobante es obligatorio para este método'] : []"
              :error="isReferenceMissing"
              :error-messages="isReferenceMissing ? 'Debe ingresar el código de comprobante para continuar' : ''"
            />
            <span class="text-caption text-medium-emphasis">
              {{ requiresReference ? 'El Dueño configuró este método exigiendo comprobante para validar la liquidación.' : 'Puede registrar el código de operación como respaldo del cobro digital.' }}
            </span>
          </div>
        </template>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4 d-flex justify-end gap-2">
        <VBtn variant="outlined" color="secondary" :disabled="busy" @click="close">
          Cancelar
        </VBtn>
        <VBtn
          color="success"
          size="large"
          prepend-icon="ri-check-line"
          :loading="busy"
          :disabled="!canConfirm"
          @click="submitPayment"
        >
          Confirmar Venta y Cobro
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.bg-light-primary {
  background-color: rgba(var(--v-theme-primary), 0.05);
}
</style>
