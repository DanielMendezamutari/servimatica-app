<script setup>
import { $api, apiError } from '@/utils/api'
import OpenCashShiftDialog from '@/views/pos/OpenCashShiftDialog.vue'
import CloseCashShiftDialog from '@/views/pos/CloseCashShiftDialog.vue'

const shifts = ref([])
const totalShifts = ref(0)
const loading = ref(false)
const error = ref('')
const notice = ref('')
const page = ref(1)
const perPage = ref(10)

const currentShift = ref(null)
const isCurrentOpen = ref(false)
const checkingCurrent = ref(false)

const openShiftDialogOpen = ref(false)
const closeShiftDialogOpen = ref(false)

const headers = [
  { title: 'Cajero / Usuario', key: 'user_name' },
  { title: 'Apertura', key: 'opened_at' },
  { title: 'Fondo Inicial', key: 'opening_amount', align: 'end' },
  { title: 'Ventas Efectivo', key: 'total_cash_sales', align: 'end' },
  { title: 'Esperado', key: 'expected_amount', align: 'end' },
  { title: 'Cierre Físico', key: 'closing_amount', align: 'end' },
  { title: 'Diferencia', key: 'difference', align: 'center' },
  { title: 'Ventas QR', key: 'total_qr_sales', align: 'end' },
  { title: 'Estado', key: 'status', align: 'center' },
]

async function loadCurrentShift() {
  checkingCurrent.value = true
  try {
    const res = await $api('/cash-shifts/current')
    isCurrentOpen.value = res.is_open
    currentShift.value = res.data
  } catch (e) {
    console.error('Error cargando turno actual:', e)
  } finally {
    checkingCurrent.value = false
  }
}

async function loadHistory() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/cash-shifts', {
      params: {
        page: page.value,
        per_page: perPage.value,
      },
    })
    shifts.value = res.data || []
    totalShifts.value = res.total || 0
  } catch (failure) {
    error.value = apiError(failure, 'Error al cargar el historial de cajas.')
  } finally {
    loading.value = false
  }
}

async function onShiftOpened(newShift) {
  notice.value = 'Turno de caja abierto exitosamente.'
  await loadCurrentShift()
  await loadHistory()
}

async function onShiftClosed(closedShift) {
  notice.value = 'Turno de caja cerrado y arqueo registrado exitosamente.'
  await loadCurrentShift()
  await loadHistory()
}

onMounted(async () => {
  await Promise.all([loadCurrentShift(), loadHistory()])
})
</script>

<template>
  <div>
    <!-- Alertas -->
    <VAlert
      v-if="notice"
      type="success"
      variant="tonal"
      closable
      class="mb-4"
      @click:close="notice = ''"
    >
      {{ notice }}
    </VAlert>

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

    <!-- Tarjeta de Estado del Turno Actual -->
    <VCard class="mb-6">
      <VCardText class="d-flex flex-wrap align-center justify-space-between gap-4">
        <div class="d-flex align-center gap-4">
          <VAvatar
            :color="isCurrentOpen ? 'success' : 'secondary'"
            variant="tonal"
            rounded
            size="48"
          >
            <VIcon :icon="isCurrentOpen ? 'tabler-cash-register' : 'tabler-lock'" size="28" />
          </VAvatar>
          <div>
            <div class="d-flex align-center gap-2">
              <span class="text-h6 font-weight-bold">
                {{ isCurrentOpen ? 'Caja Abierta (Turno Activo)' : 'Caja Cerrada' }}
              </span>
              <VChip
                :color="isCurrentOpen ? 'success' : 'secondary'"
                size="small"
                label
                class="text-uppercase font-weight-bold"
              >
                {{ isCurrentOpen ? 'Abierta' : 'Cerrada' }}
              </VChip>
            </div>
            <div class="text-caption text-medium-emphasis">
              <template v-if="isCurrentOpen">
                Iniciado por <strong>{{ currentShift?.user_name }}</strong> el {{ currentShift?.opened_at }}
              </template>
              <template v-else>
                No hay un turno de caja abierto en este momento para su usuario.
              </template>
            </div>
          </div>
        </div>

        <div class="d-flex align-center gap-2">
          <VBtn
            v-if="!isCurrentOpen"
            color="primary"
            prepend-icon="tabler-lock-open"
            @click="openShiftDialogOpen = true"
          >
            Abrir Turno de Caja
          </VBtn>
          <VBtn
            v-else
            color="warning"
            prepend-icon="tabler-lock"
            @click="closeShiftDialogOpen = true"
          >
            Cerrar Caja / Arqueo
          </VBtn>
        </div>
      </VCardText>

      <!-- Métricas en vivo si la caja está abierta -->
      <template v-if="isCurrentOpen && currentShift">
        <VDivider />
        <VCardText class="bg-var-theme-background">
          <VRow>
            <VCol cols="12" sm="6" md="3">
              <div class="text-caption text-medium-emphasis">Fondo Inicial</div>
              <div class="text-h6 font-weight-bold">Bs. {{ currentShift.opening_amount }}</div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div class="text-caption text-medium-emphasis">Ventas Efectivo</div>
              <div class="text-h6 font-weight-bold text-success">+ Bs. {{ currentShift.total_cash_sales }}</div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div class="text-caption text-medium-emphasis">Total Esperado Físico</div>
              <div class="text-h6 font-weight-bold text-primary">
                Bs. {{ (parseFloat(currentShift.opening_amount) + parseFloat(currentShift.total_cash_sales)).toFixed(2) }}
              </div>
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <div class="text-caption text-medium-emphasis">Ventas QR (Banco)</div>
              <div class="text-h6 font-weight-bold text-info">Bs. {{ currentShift.total_qr_sales }}</div>
            </VCol>
          </VRow>
        </VCardText>
      </template>
    </VCard>

    <!-- Historial de Turnos y Arqueos -->
    <VCard title="Historial de Arqueos y Turnos de Caja">
      <VCardText>
        <VDataTable
          :headers="headers"
          :items="shifts"
          :loading="loading"
          item-value="id"
          no-data-text="No se registran turnos de caja"
          hover
        >
          <template #item.user_name="{ item }">
            <span class="font-weight-medium">{{ item.user_name || 'Desconocido' }}</span>
          </template>

          <template #item.opening_amount="{ item }">
            <span>Bs. {{ item.opening_amount }}</span>
          </template>

          <template #item.total_cash_sales="{ item }">
            <span class="text-success font-weight-medium">+ Bs. {{ item.total_cash_sales }}</span>
          </template>

          <template #item.expected_amount="{ item }">
            <span v-if="item.expected_amount">Bs. {{ item.expected_amount }}</span>
            <span v-else class="text-medium-emphasis">-</span>
          </template>

          <template #item.closing_amount="{ item }">
            <span v-if="item.closing_amount" class="font-weight-bold">Bs. {{ item.closing_amount }}</span>
            <span v-else class="text-medium-emphasis">-</span>
          </template>

          <template #item.difference="{ item }">
            <template v-if="item.difference !== null">
              <VChip
                :color="parseFloat(item.difference) === 0 ? 'success' : (parseFloat(item.difference) > 0 ? 'info' : 'error')"
                size="small"
                label
                class="font-weight-bold"
              >
                {{ parseFloat(item.difference) > 0 ? '+' : '' }}Bs. {{ item.difference }}
              </VChip>
            </template>
            <span v-else class="text-medium-emphasis">-</span>
          </template>

          <template #item.total_qr_sales="{ item }">
            <span class="text-info">Bs. {{ item.total_qr_sales }}</span>
          </template>

          <template #item.status="{ item }">
            <VChip
              :color="item.status === 'open' ? 'success' : 'secondary'"
              size="small"
              label
              class="text-uppercase font-weight-bold"
            >
              {{ item.status === 'open' ? 'Abierta' : 'Cerrada' }}
            </VChip>
          </template>
        </VDataTable>
      </VCardText>
    </VCard>

    <!-- Diálogos -->
    <OpenCashShiftDialog
      v-model:is-dialog-open="openShiftDialogOpen"
      @shift-opened="onShiftOpened"
    />

    <CloseCashShiftDialog
      v-model:is-dialog-open="closeShiftDialogOpen"
      :shift="currentShift"
      @shift-closed="onShiftClosed"
    />
  </div>
</template>
