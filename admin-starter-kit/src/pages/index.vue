<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAbility } from '@casl/vue'
import { $api, apiError } from '@/utils/api'
import { currentUser } from '@/utils/session'

const ability = useAbility()
const isOwner = computed(() => {
  const role = currentUser.value?.role
  return role === 'dueno' || role === 'dueño' || role === 'administrador' || ability.can('manage', 'all')
})

const loading = ref(false)
const error = ref('')
const selectedPeriod = ref('today')

const dashboardData = ref({
  period: 'today',
  period_label: '',
  kpis: {
    total_sales_bs: 0,
    sales_count: 0,
    average_ticket_bs: 0,
    previous_period_sales_bs: 0,
    sales_trend_percentage: 0,
    total_profit_bs: null,
    profit_margin_percentage: null,
  },
  cash_shift: {
    has_active_shift: false,
    shift_id: null,
    cashier_name: null,
    opened_at: null,
    initial_cash_bs: 0,
    current_cash_sales_bs: 0,
    current_total_collected_bs: 0,
  },
  critical_stock: [],
  top_products: [],
})

async function loadDashboard() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/dashboard/summary', {
      params: { period: selectedPeriod.value },
    })
    dashboardData.value = res.data
  } catch (e) {
    console.error('Error al cargar dashboard', e)
    error.value = apiError(e) || 'No se pudo cargar el resumen del dashboard.'
  } finally {
    loading.value = false
  }
}

function setPeriod(period) {
  if (selectedPeriod.value === period) return
  selectedPeriod.value = period
  loadDashboard()
}

onMounted(() => {
  loadDashboard()
})
</script>

<template>
  <div>
    <!-- Encabezado de Bienvenida y Selector de Periodo -->
    <VCard elevation="2" class="mb-6">
      <VCardText class="d-flex flex-wrap align-center justify-space-between gap-4 py-4 px-6">
        <div>
          <div class="d-flex align-center gap-3">
            <VAvatar color="primary" variant="tonal" size="44">
              <VIcon icon="ri-dashboard-3-line" size="26" />
            </VAvatar>
            <div>
              <h4 class="text-h4 font-weight-bold">
                ¡Hola, {{ currentUser?.name || 'Usuario' }}! 👋
              </h4>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Centro de mando en tiempo real • <span class="font-weight-medium text-primary">{{ dashboardData.period_label || 'Cargando periodo...' }}</span>
              </p>
            </div>
          </div>
        </div>

        <div class="d-flex align-center flex-wrap gap-2">
          <!-- Selector Rápido de Periodo -->
          <div class="d-flex align-center gap-2">
            <VBtn
              size="small"
              :variant="selectedPeriod === 'today' ? 'flat' : 'tonal'"
              color="primary"
              @click="setPeriod('today')"
            >
              Hoy
            </VBtn>
            <VBtn
              size="small"
              :variant="selectedPeriod === 'this_week' ? 'flat' : 'tonal'"
              color="primary"
              @click="setPeriod('this_week')"
            >
              Esta Semana
            </VBtn>
            <VBtn
              size="small"
              :variant="selectedPeriod === 'this_month' ? 'flat' : 'tonal'"
              color="primary"
              @click="setPeriod('this_month')"
            >
              Este Mes
            </VBtn>
          </div>

          <VBtn
            icon="ri-refresh-line"
            variant="tonal"
            color="secondary"
            size="small"
            title="Actualizar datos"
            :loading="loading"
            @click="loadDashboard"
          />
        </div>
      </VCardText>
    </VCard>

    <!-- Error Alert -->
    <VAlert
      v-if="error"
      type="error"
      variant="tonal"
      closable
      class="mb-6"
      @click:close="error = ''"
    >
      {{ error }}
    </VAlert>

    <!-- Fila de Tarjetas de Métricas (KPIs) -->
    <VRow class="mb-6">
      <!-- 1. Total Ventas -->
      <VCol cols="12" sm="6" :md="isOwner ? 3 : 4">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis text-uppercase font-weight-medium">
                Ventas del Periodo
              </span>
              <h4 class="text-h4 font-weight-bold text-primary mt-1">
                Bs. {{ Number(dashboardData.kpis.total_sales_bs || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </h4>
              <div class="d-flex align-center gap-1 mt-1">
                <VChip
                  size="x-small"
                  :color="dashboardData.kpis.sales_trend_percentage >= 0 ? 'success' : 'error'"
                  variant="tonal"
                >
                  <VIcon
                    :icon="dashboardData.kpis.sales_trend_percentage >= 0 ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"
                    size="12"
                    class="mr-1"
                  />
                  {{ Math.abs(dashboardData.kpis.sales_trend_percentage || 0) }}%
                </VChip>
                <span class="text-caption text-medium-emphasis">vs periodo ant.</span>
              </div>
            </div>
            <VAvatar color="primary" variant="tonal" size="52" rounded>
              <VIcon icon="ri-shopping-cart-2-line" size="28" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 2. Utilidad Bruta Estimada (Solo Dueño - Principio VI) -->
      <VCol v-if="isOwner" cols="12" sm="6" md="3">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis text-uppercase font-weight-medium">
                Utilidad Estimada
              </span>
              <h4 class="text-h4 font-weight-bold text-success mt-1">
                Bs. {{ Number(dashboardData.kpis.total_profit_bs || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
              </h4>
              <span class="text-caption text-success font-weight-medium d-block mt-1">
                Margen: {{ Number(dashboardData.kpis.profit_margin_percentage || 0).toFixed(1) }}% sobre ventas
              </span>
            </div>
            <VAvatar color="success" variant="tonal" size="52" rounded>
              <VIcon icon="ri-line-chart-line" size="28" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 3. Comprobantes / Tickets -->
      <VCol cols="12" sm="6" :md="isOwner ? 3 : 4">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis text-uppercase font-weight-medium">
                Tickets Emitidos
              </span>
              <h4 class="text-h4 font-weight-bold text-info mt-1">
                {{ dashboardData.kpis.sales_count || 0 }} transacciones
              </h4>
              <span class="text-caption text-medium-emphasis d-block mt-1">
                Promedio: Bs. {{ Number(dashboardData.kpis.average_ticket_bs || 0).toFixed(2) }}
              </span>
            </div>
            <VAvatar color="info" variant="tonal" size="52" rounded>
              <VIcon icon="ri-receipt-line" size="28" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- 4. Turno de Caja Chica -->
      <VCol cols="12" sm="6" :md="isOwner ? 3 : 4">
        <VCard elevation="2" class="h-100">
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <span class="text-caption text-medium-emphasis text-uppercase font-weight-medium">
                Caja Chica
              </span>
              <div class="d-flex align-center gap-2 mt-1">
                <h5 class="text-h5 font-weight-bold" :class="dashboardData.cash_shift.has_active_shift ? 'text-success' : 'text-warning'">
                  {{ dashboardData.cash_shift.has_active_shift ? 'Turno Abierto' : 'Caja Cerrada' }}
                </h5>
                <VChip
                  size="x-small"
                  :color="dashboardData.cash_shift.has_active_shift ? 'success' : 'warning'"
                  variant="tonal"
                >
                  {{ dashboardData.cash_shift.has_active_shift ? 'Activa' : 'Inactiva' }}
                </VChip>
              </div>
              <span v-if="dashboardData.cash_shift.has_active_shift" class="text-caption text-medium-emphasis d-block mt-1">
                {{ dashboardData.cash_shift.cashier_name }} (desde {{ dashboardData.cash_shift.opened_at }})
              </span>
              <span v-else class="text-caption text-medium-emphasis d-block mt-1">
                No hay turno activo hoy
              </span>
            </div>
            <VAvatar :color="dashboardData.cash_shift.has_active_shift ? 'success' : 'warning'" variant="tonal" size="52" rounded>
              <VIcon :icon="dashboardData.cash_shift.has_active_shift ? 'ri-safe-2-line' : 'ri-lock-2-line'" size="28" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Fila de Accesos Rápidos (Quick Launchpad) -->
    <VCard elevation="2" class="mb-6">
      <VCardItem class="py-3 px-6 border-b">
        <VCardTitle class="text-subtitle-1 font-weight-bold d-flex align-center gap-2">
          <VIcon icon="ri-flashlight-line" size="20" color="primary" />
          Accesos Rápidos de Operación
        </VCardTitle>
      </VCardItem>
      <VCardText class="pa-6">
        <VRow>
          <VCol cols="12" sm="6" md="3">
            <VCard
              elevation="1"
              variant="tonal"
              color="primary"
              class="h-100 cursor-pointer text-center pa-4 transition-swing"
              to="/pos"
            >
              <VAvatar color="primary" size="48" class="mb-2">
                <VIcon icon="ri-shopping-cart-2-line" size="26" color="white" />
              </VAvatar>
              <h6 class="text-subtitle-1 font-weight-bold text-high-emphasis">
                Punto de Venta (POS)
              </h6>
              <p class="text-caption text-medium-emphasis mb-0 mt-1">
                Facturar ventas rápidas en mostrador
              </p>
            </VCard>
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VCard
              elevation="1"
              variant="tonal"
              color="info"
              class="h-100 cursor-pointer text-center pa-4 transition-swing"
              to="/quotes"
            >
              <VAvatar color="info" size="48" class="mb-2">
                <VIcon icon="ri-file-list-3-line" size="26" color="white" />
              </VAvatar>
              <h6 class="text-subtitle-1 font-weight-bold text-high-emphasis">
                Cotizaciones / Proformas
              </h6>
              <p class="text-caption text-medium-emphasis mb-0 mt-1">
                Generar proforma para WhatsApp o PDF
              </p>
            </VCard>
          </VCol>

          <VCol v-if="ability.can('manage', 'Purchase')" cols="12" sm="6" md="3">
            <VCard
              elevation="1"
              variant="tonal"
              color="warning"
              class="h-100 cursor-pointer text-center pa-4 transition-swing"
              to="/purchases"
            >
              <VAvatar color="warning" size="48" class="mb-2">
                <VIcon icon="ri-archive-line" size="26" color="white" />
              </VAvatar>
              <h6 class="text-subtitle-1 font-weight-bold text-high-emphasis">
                Compras y Recepción
              </h6>
              <p class="text-caption text-medium-emphasis mb-0 mt-1">
                Abastecer existencias y costos
              </p>
            </VCard>
          </VCol>

          <VCol cols="12" sm="6" :md="ability.can('manage', 'Purchase') ? 3 : 6">
            <VCard
              elevation="1"
              variant="tonal"
              color="secondary"
              class="h-100 cursor-pointer text-center pa-4 transition-swing"
              to="/reports/kardex"
            >
              <VAvatar color="secondary" size="48" class="mb-2">
                <VIcon icon="ri-history-line" size="26" color="white" />
              </VAvatar>
              <h6 class="text-subtitle-1 font-weight-bold text-high-emphasis">
                Kardex de Inventario
              </h6>
              <p class="text-caption text-medium-emphasis mb-0 mt-1">
                Trazabilidad física y valorizada
              </p>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Fila Media: Alertas de Stock Crítico y Top 5 Productos -->
    <VRow>
      <!-- Alertas de Stock Crítico -->
      <VCol cols="12" md="7">
        <VCard elevation="2" class="h-100">
          <VCardItem class="py-3 px-6 border-b">
            <div class="d-flex align-center justify-space-between">
              <VCardTitle class="text-subtitle-1 font-weight-bold d-flex align-center gap-2">
                <VIcon icon="ri-alarm-warning-line" size="20" color="warning" />
                Alertas de Stock Crítico
              </VCardTitle>
              <VChip
                v-if="dashboardData.critical_stock.length > 0"
                size="small"
                color="warning"
                variant="tonal"
                class="font-weight-medium"
              >
                {{ dashboardData.critical_stock.length }} por reponer
              </VChip>
            </div>
          </VCardItem>

          <VTable v-if="dashboardData.critical_stock.length > 0" hover class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-start">PRODUCTO / SKU</th>
                <th class="text-center">DISPONIBLE</th>
                <th class="text-center">MÍNIMO</th>
                <th class="text-end">ACCIÓN</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in dashboardData.critical_stock" :key="item.product_id">
                <td>
                  <div class="font-weight-medium text-high-emphasis">{{ item.name }}</div>
                  <span class="text-caption text-medium-emphasis">{{ item.sku }}</span>
                </td>
                <td class="text-center">
                  <VChip
                    size="small"
                    :color="item.current_stock <= 0 ? 'error' : 'warning'"
                    variant="tonal"
                    class="font-weight-bold"
                  >
                    {{ item.current_stock }} unid.
                  </VChip>
                </td>
                <td class="text-center text-caption text-medium-emphasis">
                  {{ item.min_stock }} unid.
                </td>
                <td class="text-end">
                  <VBtn
                    v-if="ability.can('manage', 'Purchase')"
                    size="small"
                    variant="outlined"
                    color="primary"
                    prepend-icon="ri-truck-line"
                    to="/purchases"
                  >
                    Pedir
                  </VBtn>
                  <VChip v-else size="small" variant="tonal" color="secondary">
                    Alerta
                  </VChip>
                </td>
              </tr>
            </tbody>
          </VTable>

          <div v-else class="text-center py-8 text-medium-emphasis px-4">
            <VIcon icon="ri-checkbox-circle-line" size="48" color="success" class="mb-2 d-block mx-auto" />
            <h6 class="text-subtitle-1 font-weight-bold text-success">
              Inventario en Niveles Saludables
            </h6>
            <p class="text-caption mb-0">
              No hay productos con existencias por debajo del umbral mínimo de seguridad.
            </p>
          </div>
        </VCard>
      </VCol>

      <!-- Top 5 Productos Más Vendidos -->
      <VCol cols="12" md="5">
        <VCard elevation="2" class="h-100">
          <VCardItem class="py-3 px-6 border-b">
            <VCardTitle class="text-subtitle-1 font-weight-bold d-flex align-center gap-2">
              <VIcon icon="ri-medal-line" size="20" color="info" />
              Más Vendidos en el Periodo
            </VCardTitle>
          </VCardItem>

          <VTable v-if="dashboardData.top_products.length > 0" class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-start">#</th>
                <th class="text-start">PRODUCTO</th>
                <th class="text-center">UNIDADES</th>
                <th class="text-end">TOTAL (Bs.)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(p, idx) in dashboardData.top_products" :key="p.product_id">
                <td>
                  <VAvatar
                    size="24"
                    :color="idx === 0 ? 'warning' : idx === 1 ? 'secondary' : 'default'"
                    variant="tonal"
                    class="font-weight-bold text-caption"
                  >
                    {{ idx + 1 }}
                  </VAvatar>
                </td>
                <td>
                  <div class="font-weight-medium text-high-emphasis text-truncate" style="max-inline-size: 160px;">
                    {{ p.name }}
                  </div>
                  <span class="text-caption text-medium-emphasis">{{ p.category_name }}</span>
                </td>
                <td class="text-center font-weight-bold">
                  {{ p.units_sold }}
                </td>
                <td class="text-end font-weight-medium text-success">
                  Bs. {{ Number(p.total_revenue_bs).toFixed(2) }}
                </td>
              </tr>
            </tbody>
          </VTable>

          <div v-else class="text-center py-8 text-medium-emphasis px-4">
            <VIcon icon="ri-inbox-line" size="48" class="mb-2 d-block mx-auto opacity-50" />
            <p class="text-caption mb-0">
              No hay registros de ventas para el intervalo seleccionado.
            </p>
          </div>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
