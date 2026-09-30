<script setup>
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
  product: { type: Object, default: null },
})

const emit = defineEmits(['update:isDialogOpen', 'saved'])

const form = ref({
  type: 'in',
  quantity: 1,
  reason: '',
})

const busy = ref(false)
const error = ref('')
const refForm = ref()

const currentStock = computed(() => props.product?.stock ?? 0)

const resultingStock = computed(() => {
  const qty = parseInt(form.value.quantity, 10) || 0
  if (form.value.type === 'in') {
    return currentStock.value + qty
  }
  return currentStock.value - qty
})

const isInsufficientStock = computed(() => {
  return form.value.type === 'out' && resultingStock.value < 0
})

watch(() => props.isDialogOpen, open => {
  if (!open) return
  form.value = {
    type: 'in',
    quantity: 1,
    reason: '',
  }
  error.value = ''
  nextTick(() => refForm.value?.resetValidation())
})

function close() {
  if (!busy.value) emit('update:isDialogOpen', false)
}

async function submitAdjust() {
  if (busy.value || isInsufficientStock.value) return
  const { valid } = await refForm.value.validate()
  if (!valid) return

  busy.value = true
  error.value = ''

  try {
    await $api(`/products/${props.product.id}/stock`, {
      method: 'POST',
      body: {
        type: form.value.type,
        quantity: parseInt(form.value.quantity, 10),
        reason: form.value.reason.trim(),
      },
    })
    emit('saved')
    emit('update:isDialogOpen', false)
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <VDialog
    :model-value="isDialogOpen"
    max-width="500"
    :persistent="busy"
    @update:model-value="val => !val && close()"
  >
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center">
        <span>Ajuste de Stock</span>
        <DialogCloseBtn
          :disabled="busy"
          @click="close"
        />
      </VCardTitle>

      <VDivider />

      <VCardText>
        <div class="mb-4">
          <div class="text-subtitle-1 font-weight-bold text-high-emphasis">
            {{ product?.name }}
          </div>
          <div class="text-caption text-medium-emphasis">
            SKU: {{ product?.sku }} | Categoría: {{ product?.categoryName }}
          </div>
        </div>

        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          class="mb-4"
          role="alert"
        >
          {{ error }}
        </VAlert>

        <!-- Resumen de Stock Actual y Proyectado -->
        <VRow
          class="mb-3"
          dense
        >
          <VCol cols="6">
            <VCard
              variant="tonal"
              color="secondary"
              class="pa-3 text-center"
            >
              <div class="text-caption">
                Stock actual
              </div>
              <div class="text-h6 font-weight-bold">
                {{ currentStock }} unid.
              </div>
            </VCard>
          </VCol>
          <VCol cols="6">
            <VCard
              variant="tonal"
              :color="isInsufficientStock ? 'error' : (form.type === 'in' ? 'success' : 'warning')"
              class="pa-3 text-center"
            >
              <div class="text-caption">
                Stock resultante
              </div>
              <div class="text-h6 font-weight-bold">
                {{ resultingStock }} unid.
              </div>
            </VCard>
          </VCol>
        </VRow>

        <VAlert
          v-if="isInsufficientStock"
          type="error"
          variant="tonal"
          class="mb-4"
        >
          La cantidad a retirar supera el stock disponible en tienda ({{ currentStock }} unid.).
        </VAlert>

        <VForm
          ref="refForm"
          @submit.prevent="submitAdjust"
        >
          <VRadioGroup
            v-model="form.type"
            inline
            class="mb-2"
            :disabled="busy"
          >
            <VRadio
              label="Ingreso (+) Recepción / Compra"
              value="in"
              color="success"
            />
            <VRadio
              label="Egreso (-) Merma / Ajuste"
              value="out"
              color="error"
            />
          </VRadioGroup>

          <VTextField
            v-model.number="form.quantity"
            label="Cantidad a ajustar *"
            type="number"
            min="1"
            class="mb-4"
            :rules="[v => (Number(v) >= 1) || 'Debe ser al menos 1 unidad.']"
            :disabled="busy"
          />

          <VTextarea
            v-model="form.reason"
            label="Motivo o justificación del ajuste *"
            maxlength="255"
            rows="2"
            counter
            :rules="[v => !!String(v || '').trim() || 'El motivo es obligatorio.']"
            :disabled="busy"
            placeholder="Ej. Recepción de lote proveedor, merma por exhibición..."
          />
        </VForm>
      </VCardText>

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="plain"
          :disabled="busy"
          @click="close"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="primary"
          :loading="busy"
          :disabled="isInsufficientStock"
          @click="submitAdjust"
        >
          Registrar Ajuste
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
