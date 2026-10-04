<script setup>
import { $api, apiError } from '@/utils/api'

// Vista adaptada para auditoría de accesos e inicios de sesión
const logs = ref([])
const loading = ref(false)
const error = ref('')

const search = ref('')
const selectedStatus = ref('')
const dateFrom = ref('')
const dateTo = ref('')

const page = ref(1)
const perPage = ref(15)
const totalItems = ref(0)

const statusOptions = [
  { title: 'Todos los resultados', value: '' },
  { title: 'Exitosos', value: 'success' },
  { title: 'Fallidos (Credenciales)', value: 'failed_credentials' },
  { title: 'Fallidos (Cuenta inactiva)', value: 'failed_inactive_user' },
]

const headers = [
  { title: 'Fecha y Hora', key: 'created_at' },
  { title: 'Usuario / Alias', key: 'attempted_username' },
  { title: 'Dirección IP', key: 'ip_address' },
  { title: 'Navegador / Cliente', key: 'user_agent' },
  { title: 'Resultado', key: 'status', align: 'center' },
]

function statusColor(status) {
  switch (status) {
    case 'success': return 'success'
    case 'failed_credentials': return 'error'
    case 'failed_inactive':
    case 'failed_inactive_user': return 'warning'
    default: return 'secondary'
  }
}

function statusText(status) {
  switch (status) {
    case 'success': return 'Acceso Exitoso'
    case 'failed_credentials': return 'Credenciales Inválidas'
    case 'failed_inactive':
    case 'failed_inactive_user': return 'Cuenta Inactiva'
    default: return status || 'Desconocido'
  }
}

function formatDate(isoStr) {
  if (!isoStr) return '—'
  const d = new Date(isoStr)
  return d.toLocaleString('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

function parseUserAgent(ua) {
  if (!ua) return 'Cliente HTTP'
  if (ua.includes('Postman')) return 'Postman / API Test'
  if (ua.includes('Edg/')) return 'Microsoft Edge'
  if (ua.includes('Chrome/')) return 'Google Chrome'
  if (ua.includes('Firefox/')) return 'Mozilla Firefox'
  if (ua.includes('Safari/')) return 'Apple Safari'
  if (ua.includes('Android')) return 'Android App / Mobile'
  return ua.length > 35 ? ua.slice(0, 32) + '...' : ua
}

async function loadLogs() {
  loading.value = true
  error.value = ''
  try {
    const params = {
      page: page.value,
      per_page: perPage.value,
    }
    if (search.value) params.search = search.value.trim()
    if (selectedStatus.value) params.status = selectedStatus.value
    if (dateFrom.value) params.date_from = dateFrom.value
    if (dateTo.value) params.date_to = dateTo.value

    const res = await $api('/audit/logins', { params })
    logs.value = res.data || []
    totalItems.value = res.meta?.total || 0
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    loading.value = false
  }
}

function setDatePreset(preset) {
  const now = new Date()
  const pad = n => String(n).padStart(2, '0')
  const formatDate = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

  if (preset === 'today') {
    const today = formatDate(now)
    dateFrom.value = today
    dateTo.value = today
  } else if (preset === 'week') {
    const weekAgo = new Date(now)
    weekAgo.setDate(now.getDate() - 7)
    dateFrom.value = formatDate(weekAgo)
    dateTo.value = formatDate(now)
  } else if (preset === 'month') {
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1)
    dateFrom.value = formatDate(monthStart)
    dateTo.value = formatDate(now)
  } else if (preset === 'clear') {
    dateFrom.value = ''
    dateTo.value = ''
  }
}

watch([selectedStatus, dateFrom, dateTo, page], loadLogs)

onMounted(loadLogs)
</script>

<template>
  <section>
    <VCard title="Auditoría de Inicios de Sesión">
      <VCardText>
        <p class="text-body-1 mb-4">
          Registro inmutable y cronológico de todos los intentos de acceso al sistema con IP, dispositivo y motivo de fallo.
        </p>

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
            @click="loadLogs"
          >
            Reintentar
          </VBtn>
        </VAlert>

        <!-- Filtros de Auditoría -->
        <div class="d-flex flex-wrap gap-4 align-center justify-space-between mb-4">
          <div class="d-flex flex-wrap gap-3 align-center flex-grow-1">
            <VTextField
              v-model="search"
              placeholder="Buscar por usuario o IP..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              style="min-width: 220px; max-width: 280px;"
              clearable
              @update:model-value="loadLogs"
            />

            <VSelect
              v-model="selectedStatus"
              placeholder="Resultado de acceso"
              :items="statusOptions"
              density="compact"
              style="min-width: 200px; max-width: 240px;"
            />

            <AppDateTimePicker
              v-model="dateFrom"
              label="Desde"
              placeholder="YYYY-MM-DD"
              prepend-inner-icon="ri-calendar-line"
              density="compact"
              style="min-width: 150px;"
              clearable
            />

            <AppDateTimePicker
              v-model="dateTo"
              label="Hasta"
              placeholder="YYYY-MM-DD"
              prepend-inner-icon="ri-calendar-line"
              density="compact"
              style="min-width: 150px;"
              clearable
            />

            <VBtnToggle
              density="compact"
              variant="outlined"
              color="primary"
              divided
            >
              <VBtn size="small" @click="setDatePreset('today')">Hoy</VBtn>
              <VBtn size="small" @click="setDatePreset('week')">7d</VBtn>
              <VBtn size="small" @click="setDatePreset('month')">Mes</VBtn>
              <VBtn size="small" @click="setDatePreset('clear')">Todo</VBtn>
            </VBtnToggle>
          </div>

          <VBtn
            variant="tonal"
            prepend-icon="ri-refresh-line"
            :loading="loading"
            @click="loadLogs"
          >
            Actualizar
          </VBtn>
        </div>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="logs"
        :loading="loading"
        :items-per-page="perPage"
        no-data-text="No se encontraron eventos de inicio de sesión registrados."
        loading-text="Consultando bitácora de seguridad..."
      >
        <!-- Fecha y Hora -->
        <template #item.created_at="{ item }">
          <span class="font-weight-medium text-high-emphasis">
            {{ formatDate(item.created_at) }}
          </span>
        </template>

        <!-- Usuario / Alias -->
        <template #item.attempted_username="{ item }">
          <div class="d-flex align-center gap-2 py-1">
            <VIcon
              :icon="item.status === 'success' ? 'ri-user-smile-line' : 'ri-user-unfollow-line'"
              :color="statusColor(item.status)"
              size="20"
            />
            <div class="d-flex flex-column">
              <span class="font-weight-bold">{{ item.attempted_username }}</span>
              <span
                v-if="item.user"
                class="text-caption text-medium-emphasis"
              >
                {{ item.user.name }} ({{ item.user.role === 'dueno' ? 'Dueño' : 'Vendedor' }})
              </span>
            </div>
          </div>
        </template>

        <!-- Dirección IP -->
        <template #item.ip_address="{ item }">
          <code class="text-caption font-weight-medium px-2 py-1 rounded bg-surface">
            {{ item.ip_address || '127.0.0.1' }}
          </code>
        </template>

        <!-- User-Agent / Navegador -->
        <template #item.user_agent="{ item }">
          <span
            class="text-body-2 text-medium-emphasis"
            :title="item.user_agent"
          >
            {{ parseUserAgent(item.user_agent) }}
          </span>
        </template>

        <!-- Resultado -->
        <template #item.status="{ item }">
          <VChip
            size="small"
            variant="tonal"
            :color="statusColor(item.status)"
          >
            {{ statusText(item.status) }}
          </VChip>
        </template>
      </VDataTable>
    </VCard>
  </section>
</template>
