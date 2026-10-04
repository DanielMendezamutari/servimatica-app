<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
  shift: { type: Object, default: null },
})

const emit = defineEmits(['update:isDialogOpen', 'shiftClosed'])

const form = ref({
  closing_amount: '',
  notes: '',
})

const busy = ref(false)
const error = ref('')
const refForm = ref()

const openingAmount = computed(() => parseFloat(props.shift?.opening_amount || 0))
const totalCashSales = computed(() => parseFloat(props.shift?.total_cash_sales || 0))
const totalQrSales = computed(() => parseFloat(props.shift?.total_qr_sales || 0))
const expectedAmount = computed(() => openingAmount.value + totalCashSales.value)

const digitalTotals = computed(() => {
  return props.shift?.digital_totals_by_method || []
})

const countedAmount = computed(() => {
  const val = parseFloat(form.value.closing_amount)
  return isNaN(val) ? null : val
})

const difference = computed(() => {
  if (countedAmount.value === null) return 0
  return countedAmount.value - expectedAmount.value
})

const diffStatus = computed(() => {
  if (countedAmount.value === null) return 'pending'
  const diff = Math.round(difference.value * 100) / 100
  if (diff === 0) return 'exact'
  if (diff > 0) return 'surplus'
  return 'shortage'
})

watch(() => props.isDialogOpen, open => {
  if (!open) return
  form.value = {
    closing_amount: '',
    notes: '',
  }
  error.value = ''
  nextTick(() => refForm.value?.resetValidation())
})

function close() {
  if (!busy.value) emit('update:isDialogOpen', false)
}

async function submitClose() {
  if (busy.value) return
  const { valid } = await refForm.value.validate()
  if (!valid) return

  busy.value = true
  error.value = ''

  try {
    const res = await $api('/cash-shifts/close', {
      method: 'POST',
      body: {
        closing_amount: parseFloat(form.value.closing_amount) || 0,
        notes: form.value.notes?.trim() || null,
      },
    })

    emit('shiftClosed', res.data)
    emit('update:isDialogOpen', false)
  } catch (e) {
    error.value = apiError(e, 'No se pudo cerrar el turno de caja.')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    max-width="640"
    persistent
    @update:model-value="close"
  >
    <VCard class="pa-2">
      <VCardTitle class="d-flex align-center justify-space-between pb-2">
        <div class="d-flex align-center gap-2">
          <VAvatar color="warning" variant="tonal" rounded size="38">
            <VIcon icon="ri-lock-line" size="24" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-bold">Arqueo y Cierre de Caja</div>
            <div class="text-caption text-medium-emphasis">Conciliación de Efectivo Físico vs. Cobros Digitales</div>
          </div>
        </div>
        <VBtn
          icon
          variant="text"
          size="small"
          :disabled="busy"
          @click="close"
        >
          <VIcon icon="ri-close-line" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pt-4">
        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          closable
          class="mb-4"
          @click:close="error = ''"
        >
          {{ error }}
        </VAlert>

        <!-- Bloque 1: Resumen de Efectivo Físico en Gaveta -->
        <VCard variant="tonal" color="primary" class="pa-3 mb-3">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-subtitle-2 font-weight-bold">
              <VIcon icon="ri-money-dollar-circle-line" class="mr-1" /> Dinero Físico en Gaveta (Efectivo)
            </span>
            <span class="text-caption text-medium-emphasis">Solo billetes y monedas</span>
          </div>
          <VRow dense>
            <VCol cols="4">
              <div class="text-caption text-medium-emphasis">Fondo Inicial</div>
              <div class="text-body-1 font-weight-bold">Bs. {{ openingAmount.toFixed(2) }}</div>
            </VCol>
            <VCol cols="4">
              <div class="text-caption text-medium-emphasis">Ventas Efectivo</div>
              <div class="text-body-1 font-weight-bold text-success">+ Bs. {{ totalCashSales.toFixed(2) }}</div>
            </VCol>
            <VCol cols="4">
              <div class="text-caption text-medium-emphasis">Esperado en Gaveta</div>
              <div class="text-h6 font-weight-bold text-primary">Bs. {{ expectedAmount.toFixed(2) }}</div>
            </VCol>
          </VRow>
        </VCard>

        <!-- Bloque 2: Desglose de Cobros Digitales / Bancos -->
        <VCard variant="outlined" class="pa-3 mb-4 border-dashed bg-surface">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-subtitle-2 font-weight-bold">
              <VIcon icon="ri-bank-line" class="mr-1" /> Cobros Digitales y Bancos (No van en gaveta)
            </span>
            <VChip size="x-small" color="info" variant="tonal">
              Total: Bs. {{ totalQrSales.toFixed(2) }}
            </VChip>
          </div>

          <div v-if="digitalTotals.length > 0" class="d-flex flex-column gap-1">
            <div
              v-for="(dt, idx) in digitalTotals"
              :key="idx"
              class="d-flex align-center justify-space-between py-1 px-2 rounded bg-light-info text-caption"
            >
              <div class="d-flex align-center gap-2">
                <VIcon :icon="dt.type === 'qr' ? 'ri-qr-code-line' : 'ri-bank-line'" size="16" color="info" />
                <span class="font-weight-medium">{{ dt.name }}</span>
                <span v-if="dt.bank_name" class="text-disabled">({{ dt.bank_name }})</span>
              </div>
              <div>
                <span class="text-disabled mr-2">({{ dt.count }} trans.)</span>
                <span class="font-weight-bold text-info">Bs. {{ dt.total_amount }}</span>
              </div>
            </div>
          </div>
          <div v-else class="text-caption text-medium-emphasis py-1 text-center">
            No se registraron cobros digitales en este turno.
          </div>
        </VCard>

        <VForm ref="refForm" @submit.prevent="submitClose">
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="form.closing_amount"
                label="Monto Físico Contado en Gaveta (Bs.) *"
                placeholder="0.00"
                type="number"
                step="0.10"
                min="0"
                prefix="Bs."
                variant="outlined"
                autofocus
                :rules="[
                  v => (v !== null && v !== '') || 'Debe ingresar el monto físico contado',
                  v => parseFloat(v) >= 0 || 'El monto no puede ser negativo',
                ]"
              />
              <span class="text-caption text-medium-emphasis">
                Ingrese únicamente el total de billetes y monedas que tiene en la gaveta física.
              </span>
            </VCol>

            <!-- Indicador en tiempo real de diferencia -->
            <VCol cols="12" v-if="countedAmount !== null">
              <VCard
                variant="tonal"
                :color="diffStatus === 'exact' ? 'success' : (diffStatus === 'surplus' ? 'info' : 'error')"
                class="pa-3 text-center"
              >
                <div class="text-caption text-uppercase font-weight-bold">
                  {{ diffStatus === 'exact' ? 'Caja Exacta (Sin diferencia)' : (diffStatus === 'surplus' ? 'Sobrante en Gaveta' : 'Faltante en Gaveta') }}
                </div>
                <div class="text-h6 font-weight-bold mt-1">
                  {{ difference > 0 ? '+ ' : '' }}Bs. {{ difference.toFixed(2) }}
                </div>
              </VCard>
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.notes"
                label="Observaciones del Cierre (Opcional)"
                placeholder="Indique cualquier eventualidad, justificación de sobrante/faltante, etc."
                rows="2"
                variant="outlined"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4 d-flex justify-end gap-2">
        <VBtn
          variant="outlined"
          color="secondary"
          :disabled="busy"
          @click="close"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="warning"
          :loading="busy"
          prepend-icon="ri-check-line"
          @click="submitClose"
        >
          Confirmar y Cerrar Caja
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.bg-light-info {
  background-color: rgba(var(--v-theme-info), 0.08);
}
</style>
