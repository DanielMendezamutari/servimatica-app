<script setup>
import { ref, watch } from 'vue'
import { $api, apiError } from '@/utils/api'

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

const emit = defineEmits(['update:isDialogOpen'])

const loading = ref(false)
const error = ref('')
const warrantyData = ref(null)

watch(() => props.isDialogOpen, newVal => {
  if (newVal && props.saleId) {
    loadWarrantyInfo()
  } else {
    warrantyData.value = null
    error.value = ''
  }
})

async function loadWarrantyInfo() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api(`/sales/${props.saleId}/warranty-check`)
    warrantyData.value = res.data
  } catch (err) {
    error.value = apiError(err, 'Error al consultar estado de garantía de la venta.')
  } finally {
    loading.value = false
  }
}

function close() {
  emit('update:isDialogOpen', false)
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    max-width="750px"
    @update:model-value="close"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 bg-var-theme-background">
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-shield-check-line" color="primary" size="24" />
          <span class="text-h6 font-weight-bold">Verificación de Garantía Técnica</span>
        </div>
        <VBtn
          icon
          variant="text"
          size="small"
          @click="close"
        >
          <VIcon icon="ri-close-line" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-4">
        <div v-if="loading" class="text-center py-8">
          <VProgressCircular indeterminate color="primary" />
          <div class="text-caption mt-2">Consultando cobertura de garantía...</div>
        </div>

        <VAlert
          v-else-if="error"
          type="error"
          variant="tonal"
          class="mb-4"
        >
          {{ error }}
        </VAlert>

        <div v-else-if="warrantyData">
          <!-- Datos de la Venta -->
          <div class="d-flex flex-wrap justify-space-between align-center mb-4 pa-3 rounded bg-var-theme-background">
            <div>
              <div class="text-caption text-medium-emphasis">Venta / Factura:</div>
              <div class="text-subtitle-1 font-weight-bold text-primary">{{ warrantyData.invoice_number }}</div>
            </div>
            <div>
              <div class="text-caption text-medium-emphasis">Cliente:</div>
              <div class="text-body-2 font-weight-medium">{{ warrantyData.client_name }}</div>
            </div>
            <div>
              <div class="text-caption text-medium-emphasis">Fecha de Compra:</div>
              <div class="text-body-2">{{ warrantyData.sale_date }}</div>
            </div>
          </div>

          <!-- Tabla de Artículos y Cobertura -->
          <VTable density="compact" hover class="border rounded">
            <thead>
              <tr>
                <th class="font-weight-bold">Producto</th>
                <th class="font-weight-bold">S/N Serie</th>
                <th class="font-weight-bold text-center">Cant.</th>
                <th class="font-weight-bold text-center">Garantía</th>
                <th class="font-weight-bold text-center">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in warrantyData.items" :key="it.sale_item_id">
                <td>
                  <div class="font-weight-medium">{{ it.product_name }}</div>
                  <div class="text-caption text-medium-emphasis">Bs. {{ Number(it.unit_price).toFixed(2) }} c/u</div>
                </td>
                <td>
                  <span v-if="it.serial_number" class="font-weight-bold font-monospace text-caption">
                    {{ it.serial_number }}
                  </span>
                  <span v-else class="text-caption text-disabled italic">
                    Sin S/N
                  </span>
                </td>
                <td class="text-center">
                  <span class="font-weight-bold">{{ it.original_quantity }}</span>
                  <div v-if="it.returned_quantity > 0" class="text-caption text-warning">
                    ({{ it.returned_quantity }} dev.)
                  </div>
                </td>
                <td class="text-center">
                  <div v-if="it.warranty_days > 0">
                    <span class="font-weight-bold">{{ it.warranty_days }} días</span>
                    <div class="text-caption text-disabled">Vence: {{ it.warranty_expires_at }}</div>
                  </div>
                  <div v-else class="text-caption text-disabled">
                    Sin garantía
                  </div>
                </td>
                <td class="text-center">
                  <VChip
                    v-if="it.warranty_days > 0 && it.is_warranty_valid"
                    color="success"
                    size="small"
                    label
                    class="font-weight-bold"
                  >
                    Vigente ({{ it.days_remaining }}d)
                  </VChip>
                  <VChip
                    v-else-if="it.warranty_days > 0 && !it.is_warranty_valid"
                    color="error"
                    size="small"
                    label
                    class="font-weight-bold"
                  >
                    Expirada
                  </VChip>
                  <VChip
                    v-else
                    color="secondary"
                    size="small"
                    variant="tonal"
                    label
                  >
                    N/A
                  </VChip>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn variant="tonal" color="secondary" @click="close">
          Cerrar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
