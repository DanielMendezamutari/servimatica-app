<script setup>
import { $api, apiError } from '@/utils/api'

const route = useRoute()
const router = useRouter()

const productId = computed(() => route.params.id)
const product = ref(null)
const movements = ref([])
const loading = ref(false)
const error = ref('')
const selectedType = ref('')
const page = ref(1)
const perPage = ref(20)
const totalItems = ref(0)

const typeOptions = [
  { title: 'Todos los movimientos', value: '' },
  { title: 'Solo Ingresos (+)', value: 'in' },
  { title: 'Solo Egresos (-)', value: 'out' },
]

const headers = [
  { title: 'Fecha y Hora', key: 'createdAt' },
  { title: 'Tipo', key: 'type', align: 'center' },
  { title: 'Cantidad', key: 'quantity', align: 'center' },
  { title: 'Variación de Stock', key: 'stockVariation', align: 'center' },
  { title: 'Motivo', key: 'reason' },
  { title: 'Responsable', key: 'userName' },
]

async function loadHistory() {
  loading.value = true
  error.value = ''
  try {
    const params = {
      page: page.value,
      per_page: perPage.value,
    }
    if (selectedType.value) params.type = selectedType.value

    const res = await $api(`/products/${productId.value}/stock-history`, { params })
    movements.value = res.data || []
    product.value = res.product || null
    totalItems.value = res.meta?.total || 0
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    loading.value = false
  }
}

function formatDate(isoStr) {
  if (!isoStr) return '—'
  const d = new Date(isoStr)
  return d.toLocaleString('es-BO', {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
}

watch([selectedType, page], loadHistory)
onMounted(loadHistory)
</script>

<template>
  <section>
    <div class="d-flex align-center gap-3 mb-4">
      <VBtn
        icon="ri-arrow-left-line"
        variant="text"
        title="Volver a productos"
        @click="router.push({ name: 'products' })"
      />
      <div>
        <h2 class="text-h5 font-weight-bold">
          Historial de Movimientos de Stock
        </h2>
        <div
          v-if="product"
          class="text-body-2 text-medium-emphasis"
        >
          Producto: <strong>{{ product.name }}</strong> (SKU: {{ product.sku }}) — Stock actual: <strong>{{ product.currentStock }} unid.</strong>
        </div>
      </div>
    </div>

    <VCard>
      <VCardText>
        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          class="mb-4"
          role="alert"
        >
          {{ error }}
          <VBtn
            variant="text"
            class="ms-2"
            @click="loadHistory"
          >
            Reintentar
          </VBtn>
        </VAlert>

        <div class="d-flex flex-wrap gap-4 align-center justify-space-between">
          <div style="max-width: 250px;">
            <VSelect
              v-model="selectedType"
              label="Filtrar por tipo"
              :items="typeOptions"
              density="compact"
              hide-details
            />
          </div>

          <div
            v-if="product"
            class="d-flex gap-2 align-center"
          >
            <VChip
              color="primary"
              variant="tonal"
              size="default"
              prepend-icon="ri-archive-line"
            >
              Stock disponible: <strong>{{ product.currentStock }} unidades</strong>
            </VChip>
          </div>
        </div>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="movements"
        :loading="loading"
        :items-per-page="perPage"
        no-data-text="No hay movimientos registrados para este producto."
        loading-text="Cargando historial de stock..."
      >
        <template #item.createdAt="{ item }">
          <span class="text-body-2 font-weight-medium">
            {{ formatDate(item.createdAt) }}
          </span>
        </template>

        <template #item.type="{ item }">
          <VChip
            :color="item.type === 'in' ? 'success' : 'error'"
            size="small"
            variant="tonal"
          >
            {{ item.type === 'in' ? '▲ Ingreso' : '▼ Egreso' }}
          </VChip>
        </template>

        <template #item.quantity="{ item }">
          <span class="font-weight-bold">
            {{ item.type === 'in' ? '+' : '-' }}{{ item.quantity }}
          </span>
        </template>

        <template #item.stockVariation="{ item }">
          <span class="text-body-2 text-medium-emphasis">
            {{ item.previousStock }} ➔ <strong>{{ item.newStock }}</strong>
          </span>
        </template>

        <template #item.reason="{ item }">
          <span>{{ item.reason }}</span>
        </template>

        <template #item.userName="{ item }">
          <span class="text-caption font-weight-medium text-high-emphasis">
            {{ item.userName }}
          </span>
        </template>
      </VDataTable>
    </VCard>
  </section>
</template>
