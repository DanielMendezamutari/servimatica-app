<script setup>
import { $api, apiError } from '@/utils/api'

const loading = ref(false)
const exportLoading = ref(false)
const error = ref('')

// Fechas por defecto: Mes en curso
const now = new Date()
const firstDayOfMonth = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0]
const today = now.toISOString().split('T')[0]

const fromDate = ref(firstDayOfMonth)
const toDate = ref(today)
const activeTab = ref('timeline')

const reportData = ref(null)

// Métodos rápidos para selección de periodos predefinidos
function setPreset(preset) {
  const current = new Date()
  if (preset === 'today') {
    fromDate.value = current.toISOString().split('T')[0]
    toDate.value = fromDate.value
  } else if (preset === 'this_week') {
    const day = current.getDay()
    const diff = current.getDate() - day + (day === 0 ? -6 : 1) // Lunes
    const monday = new Date(current.setDate(diff))
    fromDate.value = monday.toISOString().split('T')[0]
    toDate.value = new Date().toISOString().split('T')[0]
  } else if (preset === 'this_month') {
    fromDate.value = new Date(current.getFullYear(), current.getMonth(), 1).toISOString().split('T')[0]
    toDate.value = new Date().toISOString().split('T')[0]
  } else if (preset === 'last_month') {
    const firstDayLastMonth = new Date(current.getFullYear(), current.getMonth() - 1, 1)
    const lastDayLastMonth = new Date(current.getFullYear(), current.getMonth(), 0)
    fromDate.value = firstDayLastMonth.toISOString().split('T')[0]
    toDate.value = lastDayLastMonth.toISOString().split('T')[0]
  } else if (preset === 'this_year') {
    fromDate.value = new Date(current.getFullYear(), 0, 1).toISOString().split('T')[0]
    toDate.value = new Date().toISOString().split('T')[0]
  }
  loadReport()
}

async function loadReport() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/reports/profitability', {
      params: {
        from: fromDate.value || undefined,
        to: toDate.value || undefined,
      },
    })
    reportData.value = res.data
  } catch (e) {
    error.value = apiError(e) || 'No se pudo cargar el reporte de rentabilidad.'
  } finally {
    loading.value = false
  }
}

async function exportCsv() {
  exportLoading.value = true
  try {
    const queryParams = new URLSearchParams({
      export: 'csv',
      ...(fromDate.value && { from: fromDate.value }),
      ...(toDate.value && { to: toDate.value }),
    })

    const token = useCookie('accessToken').value
    const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api'
    const downloadUrl = `${baseUrl}/reports/profitability?${queryParams.toString()}`

    const response = await fetch(downloadUrl, {
      headers: { Authorization: `Bearer ${token}` },
    })

    if (!response.ok) throw new Error('Error al descargar archivo')

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `rentabilidad_${fromDate.value}_${toDate.value}_${Date.now()}.csv`
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

onMounted(() => {
  loadReport()
})
</script>

<template>
  <div>
    <!-- Encabezado de la Vista -->
    <VRow class="mb-4" align="center" justify="space-between">
      <VCol cols="12" md="8">
        <h2 class="text-h4 font-weight-bold d-flex align-center gap-2">
          <VIcon icon="ri-funds-box-line" color="primary" class="me-2" />
          Rentabilidad y Utilidades
        </h2>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Análisis financiero consolidado de ingresos, costos de mercancía y margen de ganancia en Bolivianos (Bs.).
        </p>
      </VCol>
      <VCol cols="12" md="4" class="text-md-end">
        <VBtn
          v-if="reportData"
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

    <!-- Barra de Filtros y Presets de Fecha -->
    <VCard class="mb-6 elevation-1">
      <VCardText>
        <VRow align="center">
          <VCol cols="12" md="6" class="d-flex flex-wrap gap-2">
            <VBtn size="small" variant="tonal" @click="setPreset('today')">Hoy</VBtn>
            <VBtn size="small" variant="tonal" @click="setPreset('this_week')">Esta Semana</VBtn>
            <VBtn size="small" variant="tonal" @click="setPreset('this_month')">Este Mes</VBtn>
            <VBtn size="small" variant="tonal" @click="setPreset('last_month')">Mes Anterior</VBtn>
            <VBtn size="small" variant="tonal" @click="setPreset('this_year')">Año {{ now.getFullYear() }}</VBtn>
          </VCol>

          <VCol cols="12" sm="5" md="2">
            <VTextField
              v-model="fromDate"
              type="date"
              label="Desde"
              density="compact"
              hide-details="auto"
              @change="loadReport"
            />
          </VCol>

          <VCol cols="12" sm="5" md="2">
            <VTextField
              v-model="toDate"
              type="date"
              label="Hasta"
              density="compact"
              hide-details="auto"
              @change="loadReport"
            />
          </VCol>

          <VCol cols="12" sm="2" md="2" class="text-end">
            <VBtn
              color="primary"
              variant="elevated"
              prepend-icon="ri-filter-3-line"
              :loading="loading"
              @click="loadReport"
            >
              Consultar
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VAlert v-if="error" type="error" variant="tonal" closable class="mb-6" @click:close="error = ''">
      {{ error }}
    </VAlert>

    <!-- Loading Skeleton -->
    <VCard v-if="loading" class="p-6 mb-6">
      <VSkeletonLoader type="article, table-heading, table-row-divider@6" />
    </VCard>

    <div v-else-if="reportData">
      <!-- Tarjetas de KPIs Ejecutivos -->
      <VRow class="mb-6">
        <VCol cols="12" sm="6" md="3">
          <VCard class="h-100 elevation-1 border-s-4" style="border-left-color: #4CAF50 !important;">
            <VCardText class="d-flex align-center justify-space-between">
              <div>
                <span class="text-caption text-uppercase font-weight-bold text-medium-emphasis">Ventas Totales</span>
                <h3 class="text-h5 font-weight-bold text-success mt-1">
                  Bs. {{ Number(reportData.kpis.total_sales || 0).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}
                </h3>
                <span class="text-caption text-medium-emphasis">Ingresos brutos facturados</span>
              </div>
              <VAvatar color="success" variant="tonal" size="44">
                <VIcon icon="ri-money-dollar-circle-line" size="26" />
              </VAvatar>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" md="3">
          <VCard class="h-100 elevation-1 border-s-4" style="border-left-color: #FF9800 !important;">
            <VCardText class="d-flex align-center justify-space-between">
              <div>
                <span class="text-caption text-uppercase font-weight-bold text-medium-emphasis">Costo de Ventas (COGS)</span>
                <h3 class="text-h5 font-weight-bold text-warning mt-1">
                  Bs. {{ Number(reportData.kpis.total_cogs || 0).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}
                </h3>
                <span class="text-caption text-medium-emphasis">Costo de adquisición de stock</span>
              </div>
              <VAvatar color="warning" variant="tonal" size="44">
                <VIcon icon="ri-shopping-cart-2-line" size="26" />
              </VAvatar>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" md="3">
          <VCard class="h-100 elevation-1 border-s-4" style="border-left-color: #2196F3 !important;">
            <VCardText class="d-flex align-center justify-space-between">
              <div>
                <span class="text-caption text-uppercase font-weight-bold text-medium-emphasis">Utilidad Bruta</span>
                <h3 class="text-h5 font-weight-black text-primary mt-1">
                  Bs. {{ Number(reportData.kpis.gross_profit || 0).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}
                </h3>
                <span class="text-caption text-medium-emphasis">Ganancia neta en Bolivianos</span>
              </div>
              <VAvatar color="primary" variant="tonal" size="44">
                <VIcon icon="ri-wallet-3-line" size="26" />
              </VAvatar>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" md="3">
          <VCard class="h-100 elevation-1 border-s-4" style="border-left-color: #9C27B0 !important;">
            <VCardText class="d-flex align-center justify-space-between">
              <div>
                <span class="text-caption text-uppercase font-weight-bold text-medium-emphasis">Margen Promedio</span>
                <h3 class="text-h5 font-weight-bold text-purple mt-1">
                  {{ Number(reportData.kpis.profit_margin_percentage || 0).toFixed(2) }}%
                </h3>
                <span class="text-caption text-medium-emphasis">
                  {{ reportData.kpis.transactions_count }} ventas / {{ reportData.kpis.items_sold_count }} unid.
                </span>
              </div>
              <VAvatar color="purple" variant="tonal" size="44">
                <VIcon icon="ri-percent-line" size="26" />
              </VAvatar>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Pestañas Conmutables de Análisis -->
      <VCard class="elevation-1">
        <VTabs v-model="activeTab" bg-color="surface">
          <VTab value="timeline" class="font-weight-bold">
            <VIcon icon="ri-calendar-line" class="me-2" />
            Evolución por Fecha
          </VTab>
          <VTab value="products" class="font-weight-bold">
            <VIcon icon="ri-trophy-line" class="me-2" />
            Ranking por Producto (Rentabilidad)
          </VTab>
          <VTab value="categories" class="font-weight-bold">
            <VIcon icon="ri-folder-chart-line" class="me-2" />
            Resumen por Categoría
          </VTab>
        </VTabs>

        <VDivider />

        <VWindow v-model="activeTab">
          <!-- Pestaña 1: Timeline -->
          <VWindowItem value="timeline">
            <VTable hover density="comfortable">
              <thead>
                <tr class="bg-surface-variant text-uppercase text-caption font-weight-bold">
                  <th>Fecha</th>
                  <th class="text-center">N° Ventas</th>
                  <th class="text-center">Unidades</th>
                  <th class="text-end">Ventas Totales</th>
                  <th class="text-end">Costo de Ventas</th>
                  <th class="text-end text-primary font-weight-bold">Utilidad Bruta</th>
                  <th class="text-end">Margen (%)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in reportData.timeline" :key="t.date">
                  <td class="font-weight-medium">{{ t.date }}</td>
                  <td class="text-center">{{ t.transactions }}</td>
                  <td class="text-center">{{ t.items_count }}</td>
                  <td class="text-end font-weight-medium text-success">
                    Bs. {{ Number(t.sales).toFixed(2) }}
                  </td>
                  <td class="text-end text-warning">
                    Bs. {{ Number(t.cost).toFixed(2) }}
                  </td>
                  <td class="text-end font-weight-bold text-primary">
                    Bs. {{ Number(t.profit).toFixed(2) }}
                  </td>
                  <td class="text-end">
                    <VChip size="small" :color="t.margin_percentage >= 20 ? 'success' : 'warning'" variant="tonal">
                      {{ Number(t.margin_percentage).toFixed(2) }}%
                    </VChip>
                  </td>
                </tr>
                <tr v-if="reportData.timeline.length === 0">
                  <td colspan="7" class="text-center py-6 text-medium-emphasis">
                    No existen ventas registradas en el periodo seleccionado.
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VWindowItem>

          <!-- Pestaña 2: Productos -->
          <VWindowItem value="products">
            <VTable hover density="comfortable">
              <thead>
                <tr class="bg-surface-variant text-uppercase text-caption font-weight-bold">
                  <th>SKU</th>
                  <th>Producto</th>
                  <th>Categoría</th>
                  <th class="text-center">Unid. Vendidas</th>
                  <th class="text-end">Ingresos (Bs.)</th>
                  <th class="text-end">Costo Total (Bs.)</th>
                  <th class="text-end text-primary font-weight-bold">Ganancia Neta (Bs.)</th>
                  <th class="text-end">Margen (%)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in reportData.top_products" :key="p.id">
                  <td class="text-caption font-weight-bold">{{ p.sku }}</td>
                  <td class="font-weight-medium">{{ p.name }}</td>
                  <td class="text-body-2 text-medium-emphasis">{{ p.category_name }}</td>
                  <td class="text-center font-weight-bold">{{ p.units_sold }}</td>
                  <td class="text-end text-success font-weight-medium">
                    Bs. {{ Number(p.total_revenue).toFixed(2) }}
                  </td>
                  <td class="text-end text-warning">
                    Bs. {{ Number(p.total_cost).toFixed(2) }}
                  </td>
                  <td class="text-end font-weight-bold text-primary">
                    Bs. {{ Number(p.profit).toFixed(2) }}
                  </td>
                  <td class="text-end">
                    <VChip size="small" :color="p.margin_percentage >= 25 ? 'success' : 'info'" variant="tonal">
                      {{ Number(p.margin_percentage).toFixed(2) }}%
                    </VChip>
                  </td>
                </tr>
                <tr v-if="reportData.top_products.length === 0">
                  <td colspan="8" class="text-center py-6 text-medium-emphasis">
                    No hay productos vendidos en este periodo.
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VWindowItem>

          <!-- Pestaña 3: Categorías -->
          <VWindowItem value="categories">
            <VTable hover density="comfortable">
              <thead>
                <tr class="bg-surface-variant text-uppercase text-caption font-weight-bold">
                  <th>Categoría</th>
                  <th class="text-center">Unid. Vendidas</th>
                  <th class="text-end">Ingresos Totales (Bs.)</th>
                  <th class="text-end">Costo Total (Bs.)</th>
                  <th class="text-end text-primary font-weight-bold">Utilidad Aportada (Bs.)</th>
                  <th class="text-end">Margen (%)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in reportData.top_categories" :key="c.category_name">
                  <td class="font-weight-bold text-primary">{{ c.category_name }}</td>
                  <td class="text-center">{{ c.units_sold }}</td>
                  <td class="text-end font-weight-medium text-success">
                    Bs. {{ Number(c.total_revenue).toFixed(2) }}
                  </td>
                  <td class="text-end text-warning">
                    Bs. {{ Number(c.total_cost).toFixed(2) }}
                  </td>
                  <td class="text-end font-weight-bold text-primary">
                    Bs. {{ Number(c.profit).toFixed(2) }}
                  </td>
                  <td class="text-end">
                    <VChip size="small" :color="c.margin_percentage >= 20 ? 'success' : 'warning'" variant="tonal">
                      {{ Number(c.margin_percentage).toFixed(2) }}%
                    </VChip>
                  </td>
                </tr>
                <tr v-if="reportData.top_categories.length === 0">
                  <td colspan="6" class="text-center py-6 text-medium-emphasis">
                    No hay registros de categorías para este periodo.
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VWindowItem>
        </VWindow>
      </VCard>
    </div>
  </div>
</template>
