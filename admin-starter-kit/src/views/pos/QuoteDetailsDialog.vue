<script setup>
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
  quoteId: { type: Number, default: null },
})

const emit = defineEmits(['update:isDialogOpen', 'loadToPos'])

const quote = ref(null)
const loading = ref(false)
const error = ref('')

async function fetchQuote() {
  if (!props.quoteId) return
  loading.value = true
  error.value = ''
  try {
    const res = await $api(`/quotes/${props.quoteId}`)
    quote.value = res.data
  } catch (e) {
    error.value = apiError(e, 'No se pudo cargar el detalle de la proforma.')
  } finally {
    loading.value = false
  }
}

watch(() => props.isDialogOpen, open => {
  if (open && props.quoteId) {
    fetchQuote()
  } else {
    quote.value = null
  }
})

function close() {
  emit('update:isDialogOpen', false)
}

function openWhatsApp() {
  if (quote.value?.whatsapp_link) {
    window.open(quote.value.whatsapp_link, '_blank')
  }
}

function printQuote() {
  if (!quote.value) return
  const token = quote.value.public_token
  const target = token
    ? `/api/quotes/public/${token}/print?autoprint=1`
    : `/api/quotes/${quote.value.id}/print?autoprint=1`
  window.open(target, '_blank')
}

function loadIntoPos() {
  if (quote.value) {
    emit('loadToPos', quote.value)
    close()
  }
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    max-width="700"
    @update:model-value="close"
  >
    <VCard class="pa-2">
      <VCardTitle class="d-flex align-center justify-space-between pb-2">
        <div class="d-flex align-center gap-2">
          <VAvatar color="info" variant="tonal" rounded size="38">
            <VIcon icon="ri-file-text-line" size="24" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-bold">
              Cotización {{ quote?.quote_number || '' }}
            </div>
            <div class="text-caption text-medium-emphasis">Detalle formal de proforma</div>
          </div>
        </div>
        <VBtn icon variant="text" size="small" @click="close">
          <VIcon icon="ri-close-line" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pt-4">
        <div v-if="loading" class="text-center py-6">
          <VProgressCircular indeterminate color="primary" />
          <div class="text-caption mt-2">Cargando proforma...</div>
        </div>

        <VAlert v-else-if="error" type="error" variant="tonal" class="mb-4">
          {{ error }}
        </VAlert>

        <template v-else-if="quote">
          <!-- Cabecera de datos -->
          <VRow class="mb-4">
            <VCol cols="12" sm="6">
              <div class="text-caption text-medium-emphasis">Cliente</div>
              <div class="text-body-1 font-weight-bold">{{ quote.client_name }}</div>
              <div v-if="quote.client_phone" class="text-caption text-medium-emphasis">
                Teléfono: {{ quote.client_phone }}
              </div>
            </VCol>
            <VCol cols="12" sm="6" class="text-sm-right">
              <div class="text-caption text-medium-emphasis">Válido Hasta</div>
              <div class="text-body-1 font-weight-bold">{{ quote.valid_until }}</div>
              <div class="text-caption text-medium-emphasis">
                Atendido por: {{ quote.seller_name || 'Vendedor' }}
              </div>
            </VCol>
          </VRow>

          <!-- Tabla de Ítems -->
          <VTable density="compact" class="border rounded mb-4">
            <thead>
              <tr>
                <th>Producto</th>
                <th class="text-center" style="width: 80px;">Cant.</th>
                <th class="text-end" style="width: 120px;">P. Unit</th>
                <th class="text-end" style="width: 120px;">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in quote.items" :key="item.id">
                <td class="font-weight-medium">{{ item.product_name }}</td>
                <td class="text-center">{{ item.quantity }}</td>
                <td class="text-end">Bs. {{ item.unit_price }}</td>
                <td class="text-end font-weight-bold">Bs. {{ item.subtotal }}</td>
              </tr>
            </tbody>
          </VTable>

          <!-- Desglose Totales -->
          <VCard variant="tonal" color="primary" class="pa-3 mb-4">
            <div class="d-flex justify-space-between mb-1">
              <span class="text-medium-emphasis">Subtotal:</span>
              <span class="font-weight-medium">Bs. {{ quote.subtotal }}</span>
            </div>
            <div v-if="parseFloat(quote.discount_amount) > 0" class="d-flex justify-space-between mb-1 text-error">
              <span>Descuento:</span>
              <span class="font-weight-bold">- Bs. {{ quote.discount_amount }}</span>
            </div>
            <VDivider class="my-2" />
            <div class="d-flex justify-space-between align-center">
              <span class="text-h6 font-weight-bold">TOTAL:</span>
              <span class="text-h5 font-weight-bold text-primary">Bs. {{ quote.total_amount }}</span>
            </div>
          </VCard>

          <div v-if="quote.notes" class="text-caption text-medium-emphasis mb-2">
            <strong>Observaciones:</strong> {{ quote.notes }}
          </div>
        </template>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4 d-flex flex-wrap justify-space-between gap-2">
        <VBtn
          color="success"
          variant="tonal"
          prepend-icon="ri-whatsapp-line"
          :disabled="!quote"
          @click="openWhatsApp"
        >
          Enviar por WhatsApp
        </VBtn>

        <div class="d-flex gap-2">
          <VBtn
            color="secondary"
            variant="outlined"
            prepend-icon="ri-printer-line"
            :disabled="!quote"
            @click="printQuote"
          >
            Imprimir PDF
          </VBtn>

          <VBtn
            color="primary"
            prepend-icon="ri-shopping-cart-line"
            :disabled="!quote || quote.status === 'converted'"
            @click="loadIntoPos"
          >
            Cobrar en POS
          </VBtn>
        </div>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
