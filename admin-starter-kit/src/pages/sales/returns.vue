<script setup>
import { ref, watch, onMounted } from 'vue'
import { $api, apiError } from '@/utils/api'

const returns = ref([])
const totalReturns = ref(0)
const loading = ref(false)
const search = ref('')
const selectedResolution = ref('all')
const page = ref(1)
const perPage = ref(15)
const error = ref('')

// Modal Detalle
const detailDialogOpen = ref(false)
const selectedReturn = ref(null)

const headers = [
  { title: 'N° Devolución', key: 'return_number' },
  { title: 'Factura Origen', key: 'invoice_number' },
  { title: 'Cliente', key: 'client_name' },
  { title: 'Resolución', key: 'resolution', align: 'center' },
  { title: 'Total Liquidado', key: 'total_refund_amount', align: 'end' },
  { title: 'Atendido Por', key: 'user_name' },
  { title: 'Fecha', key: 'created_at' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

async function loadReturns() {
  loading.value = true
  error.value = ''
  try {
    const params = {
      page: page.value,
      per_page: perPage.value,
      search: search.value?.trim() || undefined,
      resolution: selectedResolution.value !== 'all' ? selectedResolution.value : undefined,
    }
    const res = await $api('/sale-returns', { params })
    returns.value = res.data || []
    totalReturns.value = res.total || 0
  } catch (err) {
    error.value = apiError(err, 'Error al cargar el historial de devoluciones.')
  } finally {
    loading.value = false
  }
}

watch([search, selectedResolution], () => {
  page.value = 1
  loadReturns()
})

watch(page, () => {
  loadReturns()
})

onMounted(() => {
  loadReturns()
})

function openDetail(item) {
  selectedReturn.value = item
  detailDialogOpen.value = true
}

function printReceipt(id, format = 'thermal') {
  window.open(`/api/sale-returns/${id}/receipt?format=${format}`, '_blank')
}

function getResolutionColor(res) {
  switch (res) {
    case 'cambio_fisico': return 'success'
    case 'reembolso_efectivo': return 'error'
    case 'nota_credito': return 'primary'
    default: return 'secondary'
  }
}

function getResolutionLabel(res) {
  switch (res) {
    case 'cambio_fisico': return 'Cambio Físico'
    case 'reembolso_efectivo': return 'Reembolso Efectivo'
    case 'nota_credito': return 'Nota de Crédito'
    default: return res
  }
}
</script>

<template>
  <div>
    <!-- Encabezado de la Página -->
    <VCard class="mb-4">
      <VCardText class="d-flex flex-wrap align-center justify-space-between gap-4">
        <div>
          <h2 class="text-h5 font-weight-bold d-flex align-center gap-2">
            <VIcon icon="ri-arrow-go-back-line" color="warning" size="28" />
            Devoluciones y Garantías Técnicas
          </h2>
          <div class="text-caption text-medium-emphasis">
            Historial de notas de cambio, devoluciones de inventario y garantías procesadas.
          </div>
        </div>

        <div class="d-flex gap-2">
          <VBtn
            variant="tonal"
            color="primary"
            prepend-icon="ri-shopping-cart-2-line"
            to="/sales"
          >
            Ver Ventas
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <!-- Filtros y Tabla -->
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4 pb-2">
        <VTextField
          v-model="search"
          placeholder="Buscar por N° Devolución, Cliente o Factura..."
          density="compact"
          variant="outlined"
          prepend-inner-icon="ri-search-line"
          clearable
          style="max-width: 340px;"
        />

        <VSelect
          v-model="selectedResolution"
          :items="[
            { title: 'Todas las resoluciones', value: 'all' },
            { title: 'Cambio Físico 1 a 1', value: 'cambio_fisico' },
            { title: 'Reembolso en Efectivo', value: 'reembolso_efectivo' },
            { title: 'Nota de Crédito', value: 'nota_credito' },
          ]"
          density="compact"
          variant="outlined"
          style="max-width: 240px;"
        />
      </VCardText>

      <VAlert
        v-if="error"
        type="error"
        variant="tonal"
        class="ma-4"
      >
        {{ error }}
      </VAlert>

      <VCardText>
        <VDataTable
          :headers="headers"
          :items="returns"
          :loading="loading"
          item-value="id"
          no-data-text="No se registran devoluciones ni notas de cambio"
          hover
        >
          <!-- N° Devolución -->
          <template #item.return_number="{ item }">
            <span class="font-weight-bold text-warning">{{ item.return_number }}</span>
          </template>

          <!-- Factura Origen -->
          <template #item.invoice_number="{ item }">
            <span class="font-weight-medium text-primary">{{ item.invoice_number || 'S/N' }}</span>
          </template>

          <!-- Resolución -->
          <template #item.resolution="{ item }">
            <VChip
              :color="getResolutionColor(item.resolution)"
              size="small"
              label
              class="font-weight-bold"
            >
              {{ getResolutionLabel(item.resolution) }}
            </VChip>
          </template>

          <!-- Total Liquidado -->
          <template #item.total_refund_amount="{ item }">
            <span class="font-weight-bold">Bs. {{ Number(item.total_refund_amount).toFixed(2) }}</span>
          </template>

          <!-- Acciones -->
          <template #item.actions="{ item }">
            <div class="d-flex justify-end gap-1">
              <!-- Ver Detalle -->
              <VBtn
                icon
                size="small"
                variant="text"
                color="secondary"
                title="Ver Detalle de Devolución"
                @click="openDetail(item)"
              >
                <VIcon icon="ri-eye-line" />
              </VBtn>

              <!-- Imprimir Ticket 80mm -->
              <VBtn
                icon
                size="small"
                variant="text"
                color="primary"
                title="Imprimir Ticket Térmico (80mm)"
                @click="printReceipt(item.id, 'thermal')"
              >
                <VIcon icon="ri-printer-line" />
              </VBtn>

              <!-- Imprimir Carta Formal -->
              <VBtn
                icon
                size="small"
                variant="text"
                color="info"
                title="Imprimir Carta Formal A4"
                @click="printReceipt(item.id, 'letter')"
              >
                <VIcon icon="ri-file-text-line" />
              </VBtn>
            </div>
          </template>
        </VDataTable>
      </VCardText>
    </VCard>

    <!-- Diálogo Modal de Detalle de Devolución -->
    <VDialog
      v-model="detailDialogOpen"
      max-width="750px"
    >
      <VCard v-if="selectedReturn">
        <VCardTitle class="d-flex align-center justify-space-between pa-4 bg-var-theme-background">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-file-list-3-line" color="warning" size="24" />
            <span class="text-h6 font-weight-bold">
              Detalle de Devolución {{ selectedReturn.return_number }}
            </span>
          </div>
          <VBtn icon variant="text" size="small" @click="detailDialogOpen = false">
            <VIcon icon="ri-close-line" />
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-4">
          <div class="d-flex flex-wrap justify-space-between mb-4 pa-3 rounded bg-var-theme-background">
            <div>
              <div class="text-caption text-medium-emphasis">Factura de Origen:</div>
              <div class="font-weight-bold text-primary">{{ selectedReturn.invoice_number }}</div>
            </div>
            <div>
              <div class="text-caption text-medium-emphasis">Cliente:</div>
              <div class="font-weight-bold">{{ selectedReturn.client_name }}</div>
            </div>
            <div>
              <div class="text-caption text-medium-emphasis">Atendido por:</div>
              <div>{{ selectedReturn.user_name || 'Personal' }}</div>
            </div>
            <div>
              <div class="text-caption text-medium-emphasis">Resolución:</div>
              <div>
                <VChip :color="getResolutionColor(selectedReturn.resolution)" size="x-small" label class="font-weight-bold">
                  {{ getResolutionLabel(selectedReturn.resolution) }}
                </VChip>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <div class="text-caption text-medium-emphasis font-weight-bold mb-1">Motivo / Diagnóstico:</div>
            <div class="pa-2 border rounded bg-var-theme-background text-body-2">
              {{ selectedReturn.reason }}
            </div>
          </div>

          <div class="text-subtitle-2 font-weight-bold mb-2">Artículos Devueltos</div>
          <VTable density="compact" class="border rounded">
            <thead>
              <tr>
                <th>Producto & S/N</th>
                <th class="text-center">Cant.</th>
                <th>Destino Inventario</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in selectedReturn.items" :key="it.id">
                <td>
                  <div class="font-weight-medium">{{ it.product_name }}</div>
                  <div v-if="it.serial_number" class="text-caption text-disabled font-monospace">
                    S/N: {{ it.serial_number }}
                  </div>
                </td>
                <td class="text-center font-weight-bold">
                  {{ it.quantity }}x
                </td>
                <td>
                  <VChip
                    :color="it.condition === 'stock_operativo' ? 'success' : 'error'"
                    size="x-small"
                    variant="tonal"
                    label
                    class="font-weight-bold"
                  >
                    {{ it.condition === 'stock_operativo' ? 'Stock Operativo' : 'Falla Técnica (RMA)' }}
                  </VChip>
                </td>
                <td class="text-end font-weight-bold">
                  Bs. {{ Number(it.subtotal).toFixed(2) }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="3" class="text-end font-weight-bold pt-2">Total Liquidado:</td>
                <td class="text-end font-weight-bold text-h6 text-warning pt-2">
                  Bs. {{ Number(selectedReturn.total_refund_amount).toFixed(2) }}
                </td>
              </tr>
            </tfoot>
          </VTable>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4 d-flex justify-space-between">
          <div class="d-flex gap-2">
            <VBtn
              variant="tonal"
              color="primary"
              size="small"
              prepend-icon="ri-printer-line"
              @click="printReceipt(selectedReturn.id, 'thermal')"
            >
              Ticket 80mm
            </VBtn>
            <VBtn
              variant="tonal"
              color="info"
              size="small"
              prepend-icon="ri-file-text-line"
              @click="printReceipt(selectedReturn.id, 'letter')"
            >
              Carta Formal
            </VBtn>
          </div>

          <VBtn variant="outlined" color="secondary" @click="detailDialogOpen = false">
            Cerrar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
