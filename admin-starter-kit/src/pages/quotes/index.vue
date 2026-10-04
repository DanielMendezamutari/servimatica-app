<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { $api, apiError } from '@/utils/api'
import QuoteDetailsDialog from '@/views/pos/QuoteDetailsDialog.vue'
import QuoteWhatsAppDialog from '@/views/quotes/QuoteWhatsAppDialog.vue'

const router = useRouter()

const quotes = ref([])
const totalQuotes = ref(0)
const loading = ref(false)
const error = ref('')
const search = ref('')
const page = ref(1)
const perPage = ref(10)

const selectedQuoteId = ref(null)
const detailsDialogOpen = ref(false)

const selectedQuoteForWhatsApp = ref(null)
const whatsAppDialogOpen = ref(false)

const headers = [
  { title: 'N° Cotización', key: 'quote_number' },
  { title: 'Fecha', key: 'created_at' },
  { title: 'Cliente', key: 'client_name' },
  { title: 'Teléfono', key: 'client_phone' },
  { title: 'Total', key: 'total_amount', align: 'end' },
  { title: 'Válido Hasta', key: 'valid_until' },
  { title: 'Estado', key: 'status', align: 'center' },
  { title: 'Vendedor', key: 'seller_name' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

async function loadQuotes() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/quotes', {
      params: {
        page: page.value,
        per_page: perPage.value,
        search: search.value?.trim() || undefined,
      },
    })
    quotes.value = res.data || []
    totalQuotes.value = res.total || 0
  } catch (e) {
    error.value = apiError(e, 'Error al cargar las proformas.')
  } finally {
    loading.value = false
  }
}

function openDetails(id) {
  selectedQuoteId.value = id
  detailsDialogOpen.value = true
}

function sendWhatsApp(quote) {
  selectedQuoteForWhatsApp.value = quote
  whatsAppDialogOpen.value = true
}

function printQuote(quote) {
  if (!quote) return
  const token = quote.public_token
  const target = token
    ? `/api/quotes/public/${token}/print?autoprint=1`
    : `/api/quotes/${quote.id || quote}/print?autoprint=1`
  window.open(target, '_blank')
}

function onLoadToPos(quote) {
  router.push({ path: '/pos', query: { quoteId: quote.id } })
}

watch(search, () => {
  page.value = 1
  loadQuotes()
})

onMounted(() => {
  loadQuotes()
})
</script>

<template>
  <div>
    <VCard title="Proformas y Cotizaciones Formales">
      <VCardText class="d-flex flex-wrap align-center justify-space-between gap-4">
        <div class="d-flex align-center gap-3" style="max-width: 380px; width: 100%;">
          <VTextField
            v-model="search"
            placeholder="Buscar por N°, cliente o teléfono..."
            density="compact"
            variant="outlined"
            clearable
            prepend-inner-icon="ri-search-line"
          />
        </div>

        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          to="/pos"
        >
          Nueva Cotización en POS
        </VBtn>
      </VCardText>

      <VCardText>
        <VAlert v-if="error" type="error" variant="tonal" class="mb-4">
          {{ error }}
        </VAlert>

        <VDataTable
          :headers="headers"
          :items="quotes"
          :loading="loading"
          item-value="id"
          no-data-text="No se encontraron cotizaciones registradas"
          hover
        >
          <template #item.quote_number="{ item }">
            <span class="font-weight-bold text-primary">{{ item.quote_number }}</span>
          </template>

          <template #item.client_name="{ item }">
            <span class="font-weight-medium">{{ item.client_name }}</span>
          </template>

          <template #item.total_amount="{ item }">
            <span class="font-weight-bold text-high-emphasis">Bs. {{ item.total_amount }}</span>
          </template>

          <template #item.status="{ item }">
            <VChip
              :color="item.status === 'active' ? 'success' : (item.status === 'converted' ? 'info' : 'secondary')"
              size="small"
              label
              class="text-uppercase font-weight-bold"
            >
              {{ item.status === 'active' ? 'Activa' : (item.status === 'converted' ? 'Vendida' : item.status) }}
            </VChip>
          </template>

          <template #item.actions="{ item }">
            <div class="d-flex justify-end gap-1">
              <!-- Botón WhatsApp -->
              <VBtn
                icon
                size="small"
                variant="text"
                color="success"
                title="Enviar por WhatsApp"
                @click="sendWhatsApp(item)"
              >
                <VIcon icon="ri-whatsapp-line" />
              </VBtn>

              <!-- Botón Imprimir PDF -->
              <VBtn
                icon
                size="small"
                variant="text"
                color="secondary"
                title="Imprimir proforma Carta / PDF"
                @click="printQuote(item)"
              >
                <VIcon icon="ri-printer-line" />
              </VBtn>

              <!-- Botón Ver Detalle -->
              <VBtn
                icon
                size="small"
                variant="text"
                color="primary"
                title="Ver detalle"
                @click="openDetails(item.id)"
              >
                <VIcon icon="ri-eye-line" />
              </VBtn>

              <!-- Botón Cargar a POS para Cobro -->
              <VBtn
                v-if="item.status === 'active'"
                icon
                size="small"
                variant="text"
                color="info"
                title="Cargar al POS para cobrar"
                @click="onLoadToPos(item)"
              >
                <VIcon icon="ri-shopping-cart-line" />
              </VBtn>
            </div>
          </template>
        </VDataTable>
      </VCardText>
    </VCard>

    <!-- Diálogo de Detalle -->
    <QuoteDetailsDialog
      v-model:is-dialog-open="detailsDialogOpen"
      :quote-id="selectedQuoteId"
      @load-to-pos="onLoadToPos"
    />

    <!-- Diálogo de WhatsApp -->
    <QuoteWhatsAppDialog
      v-model:is-dialog-open="whatsAppDialogOpen"
      :quote="selectedQuoteForWhatsApp"
    />
  </div>
</template>
