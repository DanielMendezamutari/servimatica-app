<script setup>
import { ref, computed, onMounted } from 'vue'
import { $api, apiError } from '@/utils/api'
import PaymentMethodDialog from '@/views/payment-methods/PaymentMethodDialog.vue'

const items = ref([])
const loading = ref(false)
const error = ref('')
const snackbar = ref({ show: false, text: '', color: 'success' })

const isDialogOpen = ref(false)
const selectedMethod = ref(null)

const previewQrDialog = ref(false)
const previewQrUrl = ref('')
const previewQrTitle = ref('')

const typeLabels = {
  cash: { label: 'Efectivo', color: 'success', icon: 'ri-money-dollar-circle-line' },
  qr: { label: 'Código QR', color: 'primary', icon: 'ri-qr-code-line' },
  bank_transfer: { label: 'Transferencia', color: 'info', icon: 'ri-bank-line' },
  card: { label: 'Tarjeta', color: 'warning', icon: 'ri-bank-card-line' },
  other: { label: 'Otro', color: 'secondary', icon: 'ri-more-line' },
}

const appliesToLabels = {
  both: { label: 'Ventas y Compras', color: 'primary' },
  sales: { label: 'Solo Ventas', color: 'info' },
  purchases: { label: 'Solo Compras', color: 'warning' },
}

const stats = computed(() => {
  const total = items.value.length
  const active = items.value.filter(i => i.is_active || i.isActive).length
  const withQr = items.value.filter(i => i.type === 'qr' && (i.qr_image_url || i.qrImageUrl)).length
  const cash = items.value.filter(i => i.type === 'cash').length

  return { total, active, withQr, cash }
})

async function fetchPaymentMethods() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/payment-methods')
    items.value = res.data || res || []
  } catch (err) {
    error.value = apiError(err).message
  } finally {
    loading.value = false
  }
}

function openCreateDialog() {
  selectedMethod.value = null
  isDialogOpen.value = true
}

function openEditDialog(item) {
  selectedMethod.value = { ...item }
  isDialogOpen.value = true
}

function openQrPreview(item) {
  previewQrUrl.value = item.qr_image_url || item.qrImageUrl
  previewQrTitle.value = item.name
  previewQrDialog.value = true
}

async function toggleStatus(item) {
  const original = item.is_active ?? item.isActive
  try {
    const res = await $api(`/payment-methods/${item.id}/toggle-status`, {
      method: 'PATCH',
    })
    const updated = res.data || res
    item.is_active = updated.is_active ?? updated.isActive
    item.isActive = item.is_active
    snackbar.value = {
      show: true,
      text: `Estado de "${item.name}" actualizado a ${item.is_active ? 'Activo' : 'Inactivo'}`,
      color: 'success',
    }
  } catch (err) {
    item.is_active = original
    item.isActive = original
    snackbar.value = {
      show: true,
      text: apiError(err).message,
      color: 'error',
    }
  }
}

function onSaved(savedItem) {
  snackbar.value = {
    show: true,
    text: `Forma de pago "${savedItem.name}" guardada con éxito.`,
    color: 'success',
  }
  fetchPaymentMethods()
}

onMounted(() => {
  fetchPaymentMethods()
})
</script>

<template>
  <div class="payment-methods-page">
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold mb-1">
          Formas de Pago y Cuentas Bancarias
        </h4>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Administre las cuentas de cobro/pago, imágenes de QR oficiales y requerimiento de comprobantes.
        </p>
      </div>

      <VBtn
        color="primary"
        prepend-icon="ri-add-line"
        @click="openCreateDialog"
      >
        Nueva Forma de Pago
      </VBtn>
    </div>

    <!-- Stats Cards -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard variant="tonal" color="primary">
          <VCardText class="d-flex align-center justify-space-between pa-4">
            <div>
              <div class="text-caption text-medium-emphasis">Total Métodos</div>
              <div class="text-h5 font-weight-bold">{{ stats.total }}</div>
            </div>
            <VAvatar color="primary" variant="elevated" rounded size="42">
              <VIcon icon="ri-wallet-3-line" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard variant="tonal" color="success">
          <VCardText class="d-flex align-center justify-space-between pa-4">
            <div>
              <div class="text-caption text-medium-emphasis">Activos para Operar</div>
              <div class="text-h5 font-weight-bold">{{ stats.active }}</div>
            </div>
            <VAvatar color="success" variant="elevated" rounded size="42">
              <VIcon icon="ri-checkbox-circle-line" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard variant="tonal" color="info">
          <VCardText class="d-flex align-center justify-space-between pa-4">
            <div>
              <div class="text-caption text-medium-emphasis">Con QR Oficial Subido</div>
              <div class="text-h5 font-weight-bold">{{ stats.withQr }}</div>
            </div>
            <VAvatar color="info" variant="elevated" rounded size="42">
              <VIcon icon="ri-qr-code-line" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard variant="tonal" color="warning">
          <VCardText class="d-flex align-center justify-space-between pa-4">
            <div>
              <div class="text-caption text-medium-emphasis">Efectivo en Caja</div>
              <div class="text-h5 font-weight-bold">{{ stats.cash }}</div>
            </div>
            <VAvatar color="warning" variant="elevated" rounded size="42">
              <VIcon icon="ri-money-dollar-circle-line" size="24" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Error Alert -->
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

    <!-- Main Table Card -->
    <VCard>
      <VCardTitle class="pa-4 d-flex align-center justify-space-between">
        <span class="text-h6 font-weight-bold">Catálogo de Métodos de Pago</span>
        <VBtn
          icon="ri-refresh-line"
          variant="text"
          density="comfortable"
          :loading="loading"
          @click="fetchPaymentMethods"
        />
      </VCardTitle>

      <VDivider />

      <VTable density="compact" hover>
        <thead>
          <tr>
            <th class="text-center" style="width: 60px;">ORDEN</th>
            <th>MÉTODO / TIPO</th>
            <th>DATOS BANCARIOS</th>
            <th class="text-center" style="width: 100px;">CÓDIGO QR</th>
            <th class="text-center">EXIGE COMPROBANTE</th>
            <th class="text-center">ÁMBITO</th>
            <th class="text-center" style="width: 110px;">ESTADO</th>
            <th class="text-center" style="width: 90px;">ACCIONES</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading && items.length === 0">
            <td colspan="8" class="text-center py-6 text-medium-emphasis">
              <VProgressCircular indeterminate color="primary" size="24" class="mr-2" />
              Cargando formas de pago...
            </td>
          </tr>

          <tr v-else-if="items.length === 0">
            <td colspan="8" class="text-center py-6 text-medium-emphasis">
              No se han registrado formas de pago en el sistema.
            </td>
          </tr>

          <tr v-for="item in items" :key="item.id">
            <!-- Orden -->
            <td class="text-center font-weight-medium">
              {{ item.sort_order ?? item.sortOrder ?? 0 }}
            </td>

            <!-- Método y Tipo -->
            <td>
              <div class="d-flex align-center gap-2">
                <VAvatar
                  :color="typeLabels[item.type]?.color || 'primary'"
                  variant="tonal"
                  size="32"
                  rounded
                >
                  <VIcon :icon="typeLabels[item.type]?.icon || 'ri-wallet-line'" size="18" />
                </VAvatar>
                <div>
                  <div class="font-weight-bold text-subtitle-2">{{ item.name }}</div>
                  <VChip
                    size="x-small"
                    :color="typeLabels[item.type]?.color || 'primary'"
                    variant="tonal"
                  >
                    {{ typeLabels[item.type]?.label || item.type }}
                  </VChip>
                </div>
              </div>
            </td>

            <!-- Datos Bancarios -->
            <td>
              <template v-if="item.bank_name || item.bankName">
                <div class="text-caption font-weight-bold">{{ item.bank_name || item.bankName }}</div>
                <div class="text-caption text-medium-emphasis">
                  Cta: {{ item.account_number || item.accountNumber || 'S/N' }}
                </div>
                <div v-if="item.account_holder || item.accountHolder" class="text-caption text-disabled">
                  Titular: {{ item.account_holder || item.accountHolder }}
                </div>
              </template>
              <template v-else>
                <span class="text-caption text-disabled">N/A</span>
              </template>
            </td>

            <!-- Miniatura QR -->
            <td class="text-center">
              <template v-if="item.qr_image_url || item.qrImageUrl">
                <VTooltip text="Ver QR en grande" location="top">
                  <template #activator="{ props: tooltipProps }">
                    <VAvatar
                      v-bind="tooltipProps"
                      size="36"
                      rounded
                      class="cursor-pointer border"
                      @click="openQrPreview(item)"
                    >
                      <VImg :src="item.qr_image_url || item.qrImageUrl" cover />
                    </VAvatar>
                  </template>
                </VTooltip>
              </template>
              <span v-else class="text-caption text-disabled">—</span>
            </td>

            <!-- Exige Referencia -->
            <td class="text-center">
              <VChip
                size="small"
                :color="(item.requires_reference ?? item.requiresReference) ? 'warning' : 'default'"
                variant="tonal"
              >
                {{ (item.requires_reference ?? item.requiresReference) ? 'Obligatorio' : 'Opcional' }}
              </VChip>
            </td>

            <!-- Ámbito -->
            <td class="text-center">
              <VChip
                size="small"
                :color="appliesToLabels[item.applies_to || item.appliesTo]?.color || 'default'"
                variant="tonal"
              >
                {{ appliesToLabels[item.applies_to || item.appliesTo]?.label || 'Ambos' }}
              </VChip>
            </td>

            <!-- Switch Activo -->
            <td class="text-center">
              <VSwitch
                :model-value="!!(item.is_active ?? item.isActive)"
                color="success"
                density="compact"
                hide-details
                @update:model-value="toggleStatus(item)"
              />
            </td>

            <!-- Acciones -->
            <td class="text-center">
              <VBtn
                icon="ri-edit-line"
                size="small"
                variant="text"
                color="primary"
                @click="openEditDialog(item)"
              />
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>

    <!-- Diálogo Formulario -->
    <PaymentMethodDialog
      v-model:is-dialog-open="isDialogOpen"
      :payment-method="selectedMethod"
      @saved="onSaved"
    />

    <!-- Diálogo Previsualización QR en grande -->
    <VDialog v-model="previewQrDialog" max-width="380">
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-4 pb-2">
          <span class="text-subtitle-1 font-weight-bold">{{ previewQrTitle }}</span>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="previewQrDialog = false" />
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4 d-flex flex-column align-center">
          <VImg
            :src="previewQrUrl"
            max-width="300"
            max-height="300"
            aspect-ratio="1"
            class="rounded border bg-surface mb-2"
          />
          <span class="text-caption text-medium-emphasis text-center">
            Código QR oficial para cobro inmediato en mostrador
          </span>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Snackbar de notificaciones -->
    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      timeout="3000"
      location="top right"
    >
      {{ snackbar.text }}
    </VSnackbar>
  </div>
</template>

<style scoped>
.payment-methods-page {
  width: 100%;
}
</style>
