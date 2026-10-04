<script setup>
import { useAbility } from '@casl/vue'
import { $api, apiError } from '@/utils/api'

const route = useRoute()
const router = useRouter()
const ability = useAbility()

// Dueño / Administrador ve columnas valorizadas; Cajeros solo ven cantidades físicas (Principio VI)
const isOwner = computed(() => ability.can('manage', 'all'))

const loading = ref(false)
const searchLoading = ref(false)
const exportLoading = ref(false)
const error = ref('')

const selectedProductId = ref(null)
const productSearch = ref('')
const productOptions = ref([])

const fromDate = ref('')
const toDate = ref('')
const selectedType = ref('all')

const typeOptions = [
  { title: 'Todos los movimientos', value: 'all' },
  { title: 'Solo Entradas (Compras / Devoluciones)', value: 'in' },
  { title: 'Solo Salidas (Ventas / Mermas)', value: 'out' },
]

const kardexData = ref(null)

// Cargar productos para el selector con autocompletado
async function searchProducts(query = '') {
  searchLoading.value = true
  try {
    const res = await $api('/products', {
      params: {
        search: query || undefined,
        per_page: 20,
        status: 'active',
      },
    })
    productOptions.value = res.data || []
  } catch (e) {
    console.error('Error buscando productos', e)
  } finally {
    searchLoading.value = false
  }
}

// Cargar Kardex del producto seleccionado
async function loadKardex() {
  if (!selectedProductId.value) return

  loading.value = true
  error.value = ''

  try {
    const res = await $api(`/kardex/${selectedProductId.value}`, {
      params: {
        from: fromDate.value || undefined,
        to: toDate.value || undefined,
        type: selectedType.value !== 'all' ? selectedType.value : undefined,
      },
    })
    kardexData.value = res.data
  } catch (e) {
    error.value = apiError(e) || 'No se pudo obtener el Kardex del producto.'
    kardexData.value = null
  } finally {
    loading.value = false
  }
}

// Exportar reporte a CSV (Excel compatible con BOM UTF-8)
async function exportCsv() {
  if (!selectedProductId.value) return
  exportLoading.value = true

  try {
    const queryParams = new URLSearchParams({
      export: 'csv',
      ...(fromDate.value && { from: fromDate.value }),
      ...(toDate.value && { to: toDate.value }),
      ...(selectedType.value !== 'all' && { type: selectedType.value }),
    })

    const token = useCookie('accessToken').value
    const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api'
    const downloadUrl = `${baseUrl}/kardex/${selectedProductId.value}?${queryParams.toString()}`

    const response = await fetch(downloadUrl, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    if (!response.ok) throw new Error('Error al descargar archivo')

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `kardex_${kardexData.value?.product?.sku || 'producto'}_${Date.now()}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Error al exportar', e)
    error.value = 'Ocurrió un error al exportar la hoja de cálculo.'
  } finally {
    exportLoading.value = false
  }
}

// Si la ruta trae ?productId=X preseleccionarlo
onMounted(async () => {
  await searchProducts()
  if (route.query.productId) {
    selectedProductId.value = Number(route.query.productId)
    await loadKardex()
  }
})

watch(selectedProductId, newId => {
  if (newId) loadKardex()
})
</script>

<template>
  <div>
    <!-- Encabezado de la Vista -->
    <VRow class="mb-4" align="center" justify="space-between">
      <VCol cols="12" md="7">
        <h2 class="text-h4 font-weight-bold d-flex align-center gap-2">
          <VIcon icon="ri-file-history-line" color="primary" class="me-2" />
          Kardex de Inventario
        </h2>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Auditoría física y económica de movimientos según Costo Promedio Ponderado (CPP).
        </p>
      </VCol>
      <VCol cols="12" md="5" class="text-md-end">
        <VBtn
          v-if="kardexData"
          color="success"
          variant="tonal"
          prepend-icon="ri-file-excel-2-line"
          :loading="exportLoading"
          @click="exportCsv"
        >
          Exportar a Excel / CSV
        </VBtn>
      </VCol>
    </VRow>

    <!-- Barra de Filtros y Selección de Producto -->
    <VCard class="mb-6 elevation-1">
      <VCardText>
        <VRow align="center">
          <VCol cols="12" md="5">
            <VAutocomplete
              v-model="selectedProductId"
              :items="productOptions"
              item-title="name"
              item-value="id"
              label="Seleccionar Producto a Auditar"
              placeholder="Escribe el nombre o código de barras..."
              prepend-inner-icon="ri-search-line"
              clearable
              :loading="searchLoading"
              hide-details="auto"
              @update:search="searchProducts"
            >
              <template #item="{ props, item }">
                <VListItem v-bind="props" :subtitle="`SKU: ${item.raw.sku} | Stock Actual: ${item.raw.stock} unid.`">
                  <template #prepend>
                    <VAvatar size="32" color="primary" variant="tonal" class="me-2">
                      <VIcon icon="ri-computer-line" size="20" />
                    </VAvatar>
                  </template>
                </VListItem>
              </template>
            </VAutocomplete>
          </VCol>

          <VCol cols="12" sm="6" md="2">
            <VTextField
              v-model="fromDate"
              type="date"
              label="Desde"
              density="compact"
              hide-details="auto"
              @change="loadKardex"
            />
          </VCol>

          <VCol cols="12" sm="6" md="2">
            <VTextField
              v-model="toDate"
              type="date"
              label="Hasta"
              density="compact"
              hide-details="auto"
              @change="loadKardex"
            />
          </VCol>

          <VCol cols="12" sm="6" md="2">
            <VSelect
              v-model="selectedType"
              :items="typeOptions"
              label="Tipo de Movimiento"
              density="compact"
              hide-details="auto"
              @update:model-value="loadKardex"
            />
          </VCol>

          <VCol cols="12" sm="6" md="1" class="text-end">
            <VBtn
              icon="ri-refresh-line"
              variant="text"
              color="primary"
              :loading="loading"
              @click="loadKardex"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VAlert v-if="error" type="error" variant="tonal" closable class="mb-6" @click:close="error = ''">
      {{ error }}
    </VAlert>

    <!-- Estado Inicial: Sin producto seleccionado -->
    <VCard v-if="!selectedProductId && !loading" class="text-center py-12 elevation-1">
      <VCardText>
        <VIcon icon="ri-scan-2-line" size="64" color="secondary" class="mb-4 opacity-50" />
        <h3 class="text-h6 font-weight-bold mb-2">Selecciona un producto del catálogo</h3>
        <p class="text-body-2 text-medium-emphasis mx-auto" style="max-width: 480px;">
          Busca un artículo por su nombre, modelo o código de barras en la barra superior para visualizar todo su historial de compras, ventas y saldos físicos y valorizados.
        </p>
      </VCardText>
    </VCard>

    <!-- Loading Skeleton -->
    <VCard v-else-if="loading" class="p-6">
      <VSkeletonLoader type="article, table-heading, table-row-divider@6" />
    </VCard>

    <!-- Ficha del Producto y Tabla de Kardex -->
    <div v-else-if="kardexData">
      <!-- Tarjetas de Información del Producto -->
      <VRow class="mb-6">
        <VCol cols="12" md="8">
          <VCard variant="tonal" color="primary" class="h-100">
            <VCardText class="d-flex align-center justify-space-between flex-wrap gap-4">
              <div>
                <span class="text-caption text-uppercase font-weight-bold text-primary">Artículo Auditado</span>
                <h3 class="text-h5 font-weight-bold mt-1 mb-1">{{ kardexData.product.name }}</h3>
                <div class="d-flex flex-wrap gap-4 text-body-2">
                  <span><strong>SKU:</strong> {{ kardexData.product.sku }}</span>
                  <span v-if="kardexData.product.category_name"><strong>Categoría:</strong> {{ kardexData.product.category_name }}</span>
                  <span v-if="kardexData.product.brand_name"><strong>Marca:</strong> {{ kardexData.product.brand_name }}</span>
                </div>
              </div>
              <div class="text-end">
                <VChip color="primary" size="large" class="font-weight-bold px-4 py-2">
                  <VIcon icon="ri-archive-line" class="me-2" />
                  Stock Físico: {{ kardexData.product.current_stock }} unid.
                </VChip>
                <div v-if="kardexData.product.defective_stock > 0" class="text-caption text-warning mt-1 font-weight-medium">
                  ⚠️ {{ kardexData.product.defective_stock }} unid. en garantía / cuarentena
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" md="4">
          <VCard class="h-100 elevation-1">
            <VCardText>
              <div class="text-caption text-uppercase font-weight-bold text-medium-emphasis mb-2">Valores Vigentes</div>
              <div class="d-flex justify-space-between mb-1">
                <span class="text-body-2 text-medium-emphasis">Precio de Venta al Público:</span>
                <span class="text-body-2 font-weight-bold text-success">Bs. {{ Number(kardexData.product.sale_price || 0).toFixed(2) }}</span>
              </div>
              <div v-if="isOwner && kardexData.product.cost_price !== undefined" class="d-flex justify-space-between mb-1">
                <span class="text-body-2 text-medium-emphasis">Último Costo de Compra:</span>
                <span class="text-body-2 font-weight-medium">Bs. {{ Number(kardexData.product.cost_price || 0).toFixed(2) }}</span>
              </div>
              <div v-if="isOwner && kardexData.product.current_cpp !== undefined" class="d-flex justify-space-between border-t pt-2 mt-2">
                <span class="text-body-2 font-weight-bold text-primary">Costo Promedio Ponderado (CPP):</span>
                <span class="text-body-2 font-weight-bold text-primary">Bs. {{ Number(kardexData.product.current_cpp || 0).toFixed(4) }}</span>
              </div>
              <div v-else-if="!isOwner" class="text-caption text-medium-emphasis font-italic mt-2">
                🛡️ Modo Cajero: Información financiera restringida.
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Tabla Maestra de Kardex -->
      <VCard class="elevation-1 overflow-hidden">
        <VTable hover density="comfortable" class="text-no-wrap">
          <thead>
            <tr class="bg-surface-variant text-uppercase text-caption font-weight-bold">
              <th rowspan="2" class="text-left border-e">Fecha y Hora</th>
              <th rowspan="2" class="text-left border-e">Movimiento / Concepto</th>
              <th rowspan="2" class="text-left border-e">Comprobante</th>
              <th rowspan="2" class="text-left border-e">Responsable</th>
              <th colspan="3" class="text-center border-e bg-primary-lighten-5">Cantidades Físicas</th>
              <th v-if="isOwner" colspan="5" class="text-center bg-info-lighten-5">Valores Monetarios (Bolivianos - Bs.)</th>
            </tr>
            <tr class="bg-surface-variant text-caption font-weight-bold border-b">
              <!-- Físicas -->
              <th class="text-end text-success border-e">Entrada</th>
              <th class="text-end text-warning border-e">Salida</th>
              <th class="text-end text-primary border-e font-weight-black">Saldo</th>
              <!-- Valorizadas (Dueño) -->
              <th v-if="isOwner" class="text-end">Costo Unit.</th>
              <th v-if="isOwner" class="text-end text-success">Debe</th>
              <th v-if="isOwner" class="text-end text-warning">Haber</th>
              <th v-if="isOwner" class="text-end text-info font-weight-bold">CPP Móvil</th>
              <th v-if="isOwner" class="text-end text-primary font-weight-black">Saldo Bs.</th>
            </tr>
          </thead>

          <tbody>
            <!-- Saldo Inicial -->
            <tr v-if="kardexData.initial_balance" class="bg-light-blue-lighten-5 font-italic">
              <td class="font-weight-bold text-medium-emphasis">SALDO INICIAL</td>
              <td colspan="3" class="text-medium-emphasis">Saldo acumulado anterior al rango de fecha</td>
              <td class="text-end text-medium-emphasis">-</td>
              <td class="text-end text-medium-emphasis">-</td>
              <td class="text-end font-weight-bold text-primary">{{ kardexData.initial_balance.quantity || 0 }}</td>
              <template v-if="isOwner">
                <td class="text-end font-weight-bold">Bs. {{ Number(kardexData.initial_balance.average_cost || 0).toFixed(4) }}</td>
                <td class="text-end">-</td>
                <td class="text-end">-</td>
                <td class="text-end">Bs. {{ Number(kardexData.initial_balance.average_cost || 0).toFixed(4) }}</td>
                <td class="text-end font-weight-black text-primary">Bs. {{ Number(kardexData.initial_balance.total_value || 0).toFixed(2) }}</td>
              </template>
            </tr>

            <!-- Movimientos Registrados -->
            <tr v-for="m in kardexData.movements" :key="m.id">
              <td class="text-body-2">{{ m.date }}</td>
              <td>
                <div class="d-flex align-center gap-2">
                  <VChip
                    size="x-small"
                    :color="m.type === 'in' ? 'success' : 'warning'"
                    variant="tonal"
                    class="font-weight-bold text-uppercase"
                  >
                    {{ m.type === 'in' ? 'Entrada' : 'Salida' }}
                  </VChip>
                  <span class="text-body-2 font-weight-medium">{{ m.reason }}</span>
                </div>
              </td>
              <td class="text-body-2">
                <span v-if="m.reference_type" class="text-caption font-weight-bold">
                  {{ m.reference_type.toUpperCase() }} #{{ m.reference_id }}
                </span>
                <span v-else class="text-medium-emphasis">-</span>
              </td>
              <td class="text-body-2 text-medium-emphasis">{{ m.user_name || 'Sistema' }}</td>

              <!-- Físicas -->
              <td class="text-end font-weight-bold text-success">
                {{ m.physical.entry > 0 ? `+${m.physical.entry}` : '-' }}
              </td>
              <td class="text-end font-weight-bold text-warning">
                {{ m.physical.exit > 0 ? `-${m.physical.exit}` : '-' }}
              </td>
              <td class="text-end font-weight-black text-primary bg-primary-lighten-5">
                {{ m.physical.balance }}
              </td>

              <!-- Valorizadas (Dueño) -->
              <template v-if="isOwner && m.financial">
                <td class="text-end text-body-2">
                  Bs. {{ Number(m.financial.unit_cost || 0).toFixed(4) }}
                </td>
                <td class="text-end text-body-2 text-success font-weight-medium">
                  {{ m.financial.debit > 0 ? `Bs. ${Number(m.financial.debit).toFixed(2)}` : '-' }}
                </td>
                <td class="text-end text-body-2 text-warning font-weight-medium">
                  {{ m.financial.credit > 0 ? `Bs. ${Number(m.financial.credit).toFixed(2)}` : '-' }}
                </td>
                <td class="text-end text-body-2 text-info font-weight-bold">
                  Bs. {{ Number(m.financial.average_cost || 0).toFixed(4) }}
                </td>
                <td class="text-end text-body-2 font-weight-black text-primary bg-info-lighten-5">
                  Bs. {{ Number(m.financial.balance_value || 0).toFixed(2) }}
                </td>
              </template>
            </tr>

            <!-- Sin Movimientos -->
            <tr v-if="kardexData.movements.length === 0">
              <td :colspan="isOwner ? 12 : 7" class="text-center py-6 text-medium-emphasis">
                No se registraron movimientos en el rango de fechas seleccionado.
              </td>
            </tr>
          </tbody>

          <!-- Totales Auditados -->
          <tfoot>
            <tr class="bg-surface-variant font-weight-bold border-t">
              <td colspan="4" class="text-uppercase text-end">Totales Acumulados:</td>
              <td class="text-end text-success font-weight-black">+{{ kardexData.totals.total_entries_quantity }}</td>
              <td class="text-end text-warning font-weight-black">-{{ kardexData.totals.total_exits_quantity }}</td>
              <td class="text-end text-primary font-weight-black bg-primary-lighten-5">{{ kardexData.totals.final_balance_quantity }} unid.</td>
              <template v-if="isOwner">
                <td class="text-end">-</td>
                <td class="text-end text-success font-weight-black">Bs. {{ Number(kardexData.totals.total_debit_amount || 0).toFixed(2) }}</td>
                <td class="text-end text-warning font-weight-black">Bs. {{ Number(kardexData.totals.total_credit_amount || 0).toFixed(2) }}</td>
                <td class="text-end">-</td>
                <td class="text-end text-primary font-weight-black bg-info-lighten-5">Bs. {{ Number(kardexData.totals.final_balance_value || 0).toFixed(2) }}</td>
              </template>
            </tr>
          </tfoot>
        </VTable>
      </VCard>
    </div>
  </div>
</template>
