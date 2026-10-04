<script setup>
import { $api } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
  sale: { type: Object, default: null },
})

const emit = defineEmits(['update:isDialogOpen', 'newSale'])

const companyInfo = ref(null)

async function loadCompanyInfo() {
  try {
    const res = await $api('/company-settings/public')
    companyInfo.value = res.data || res
  } catch (e) {
    // fallback
  }
}

watch(() => props.isDialogOpen, open => {
  if (open && !companyInfo.value) {
    loadCompanyInfo()
  }
})

onMounted(() => {
  loadCompanyInfo()
})

function formatWarrantyExpiry(expiresAt, saleCreatedAt, warrantyDays) {
  if (expiresAt) {
    const d = new Date(expiresAt)
    return d.toLocaleDateString('es-BO')
  }
  if (warrantyDays && warrantyDays > 0) {
    const base = saleCreatedAt ? new Date(saleCreatedAt) : new Date()
    const exp = new Date(base.getTime() + warrantyDays * 86400000)
    return exp.toLocaleDateString('es-BO')
  }
  return ''
}

function printThermal() {
  if (props.sale?.id) {
    window.open(`/api/sales/${props.sale.id}/receipt?autoprint=1`, '_blank')
  }
}

function handleNewSale() {
  emit('update:isDialogOpen', false)
  emit('newSale')
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    max-width="440"
    persistent
  >
    <VCard class="pa-2">
      <VCardTitle class="d-flex align-center justify-space-between pb-2">
        <div class="d-flex align-center gap-2">
          <VAvatar color="success" variant="tonal" rounded size="36">
            <VIcon icon="ri-checkbox-circle-line" size="22" />
          </VAvatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold">¡Venta Registrada!</div>
            <div class="text-caption text-medium-emphasis">Comprobante {{ sale?.invoice_number }}</div>
          </div>
        </div>
      </VCardTitle>

      <VDivider />

      <VCardText class="pt-4">
        <!-- Vista previa del ticket térmico 80mm -->
        <div class="receipt-preview pa-4 border rounded bg-surface">
          <div class="text-center mb-3">
            <div class="font-weight-bold text-subtitle-2">{{ companyInfo?.trade_name || 'SERVIMÁTICA COMPUTACIÓN' }}</div>
            <div class="text-caption text-medium-emphasis">{{ companyInfo?.slogan || 'Venta de Equipos y Accesorios' }}</div>
            <div class="text-caption font-weight-bold mt-1">{{ sale?.invoice_number }}</div>
            <div class="text-caption text-medium-emphasis">{{ sale?.created_at }}</div>
          </div>

          <VDivider class="border-dashed my-2" />

          <div class="text-caption mb-1 d-flex justify-space-between">
            <span class="text-medium-emphasis">Cliente:</span>
            <span class="font-weight-medium">{{ sale?.client_name }}</span>
          </div>
          <div v-if="sale?.client_nit_ci" class="text-caption mb-1 d-flex justify-space-between">
            <span class="text-medium-emphasis">NIT/CI:</span>
            <span>{{ sale?.client_nit_ci }}</span>
          </div>
          <div class="text-caption mb-1 d-flex justify-space-between">
            <span class="text-medium-emphasis">Forma de Pago:</span>
            <span class="font-weight-bold">{{ sale?.payment_method_name || sale?.payment_method }}</span>
          </div>
          <div v-if="sale?.reference_number" class="text-caption mb-2 d-flex justify-space-between">
            <span class="text-medium-emphasis">N° Comprobante/Ref:</span>
            <span class="font-weight-bold text-primary">{{ sale?.reference_number }}</span>
          </div>

          <VDivider class="border-dashed my-2" />

          <!-- Productos y Garantía Técnica -->
          <div v-for="it in sale?.items" :key="it.id" class="mb-2">
            <div class="text-caption d-flex justify-space-between">
              <span class="font-weight-medium">{{ it.quantity }}x {{ it.product_name }}</span>
              <span class="font-weight-bold">Bs. {{ it.subtotal }}</span>
            </div>
            <div v-if="it.serial_number || it.warranty_days > 0" class="text-caption text-medium-emphasis ps-1" style="font-size: 10px;">
              <div v-if="it.warranty_days > 0">
                🛡️ Garantía: {{ it.warranty_days }} días (Vence: {{ formatWarrantyExpiry(it.warranty_expires_at, sale?.created_at, it.warranty_days) }})
              </div>
              <div v-if="it.serial_number" class="font-mono text-high-emphasis font-weight-medium">
                S/N: {{ it.serial_number }}
              </div>
            </div>
          </div>

          <VDivider class="border-dashed my-2" />

          <div class="d-flex justify-space-between text-caption mb-1">
            <span class="text-medium-emphasis">Subtotal:</span>
            <span>Bs. {{ sale?.subtotal }}</span>
          </div>
          <div v-if="parseFloat(sale?.discount_amount) > 0" class="d-flex justify-space-between text-caption text-error mb-1">
            <span>Descuento:</span>
            <span>- Bs. {{ sale?.discount_amount }}</span>
          </div>
          <div class="d-flex justify-space-between text-subtitle-2 font-weight-bold mt-2">
            <span>TOTAL:</span>
            <span class="text-primary font-weight-bold">Bs. {{ sale?.total_amount }}</span>
          </div>

          <template v-if="sale?.payment_method === 'efectivo'">
            <div class="d-flex justify-space-between text-caption text-medium-emphasis mt-1">
              <span>Efectivo:</span>
              <span>Bs. {{ sale?.cash_tendered }}</span>
            </div>
            <div class="d-flex justify-space-between text-caption font-weight-bold text-success">
              <span>Vuelto:</span>
              <span>Bs. {{ sale?.change_due }}</span>
            </div>
          </template>

          <VDivider class="border-dashed my-2" />

          <!-- Pie y Términos Institucionales de Garantía -->
          <div class="footer text-center" style="font-size: 10px;">
            <div class="font-weight-medium">¡Muchas gracias por su preferencia!</div>
            <div class="text-medium-emphasis mt-1">{{ companyInfo?.receipt_footer_message || 'Conserve este comprobante para reclamos y garantía.' }}</div>
            <div v-if="companyInfo?.warranty_terms" class="mt-2 text-start pa-2 border rounded bg-var-theme-background" style="font-size: 8.5px; line-height: 1.2;">
              <div class="font-weight-bold text-center mb-1">POLÍTICAS DE GARANTÍA TÉCNICA</div>
              <div style="white-space: pre-line;">{{ companyInfo.warranty_terms }}</div>
            </div>
          </div>
        </div>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4 d-flex justify-space-between gap-2">
        <VBtn
          color="primary"
          variant="tonal"
          prepend-icon="ri-printer-line"
          @click="printThermal"
        >
          Imprimir Ticket
        </VBtn>

        <VBtn
          color="success"
          prepend-icon="ri-add-line"
          @click="handleNewSale"
        >
          Nueva Venta
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.receipt-preview {
  font-family: 'Courier New', Courier, monospace;
}
.border-dashed {
  border-style: dashed !important;
}
</style>
