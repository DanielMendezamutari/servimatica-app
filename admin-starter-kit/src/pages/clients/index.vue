<script setup>
import { $api, apiError } from '@/utils/api'
import AddEditClientDrawer from '@/views/clients/AddEditClientDrawer.vue'

const clients = ref([])
const loading = ref(false)
const error = ref('')
const notice = ref('')
const search = ref('')
const clientTypeFilter = ref('all')
const statusFilter = ref('all')
const page = ref(1)
const perPage = ref(15)
const totalItems = ref(0)

const isDrawerOpen = ref(false)
const selectedClient = ref(null)
const confirmingToggle = ref(null)
const toggling = ref(false)
const exporting = ref(false)

const clientTypeOptions = [
  { title: 'Todos los segmentos', value: 'all' },
  { title: 'Consumidor Final', value: 'final' },
  { title: 'Técnico / Mayorista', value: 'mayorista' },
  { title: 'Empresa / Corporativo', value: 'empresa' },
  { title: 'Institución Pública / Educativa', value: 'institucion' },
]

const statusOptions = [
  { title: 'Todos los estados', value: 'all' },
  { title: 'Solo Activos', value: 'true' },
  { title: 'Solo Inactivos', value: 'false' },
]

const headers = [
  { title: 'Cliente / Razón Social', key: 'name' },
  { title: 'Segmento', key: 'client_type', align: 'start' },
  { title: 'Contacto & WhatsApp', key: 'phone', align: 'start' },
  { title: 'Ubicación', key: 'city', align: 'start' },
  { title: 'Historial Compras', key: 'total_spent_bs', align: 'end' },
  { title: 'Estado', key: 'is_active', align: 'center' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

// KPIs calculados
const kpis = computed(() => {
  const total = totalItems.value
  const activeCount = clients.value.filter(c => c.is_active).length
  const wholesaleOrCorporate = clients.value.filter(c => c.client_type === 'mayorista' || c.client_type === 'empresa').length
  const withPurchases = clients.value.filter(c => (c.sales_count || 0) > 0).length

  return {
    total,
    activeCount,
    wholesaleOrCorporate,
    withPurchases,
  }
})

async function loadClients() {
  loading.value = true
  error.value = ''
  try {
    const params = {
      page: page.value,
      per_page: perPage.value,
    }
    if (search.value?.trim()) params.search = search.value.trim()
    if (clientTypeFilter.value !== 'all') params.client_type = clientTypeFilter.value
    if (statusFilter.value !== 'all') params.is_active = statusFilter.value

    const res = await $api('/v1/clients', { params })
    clients.value = res.data || []
    totalItems.value = res.meta?.total || clients.value.length
  } catch (err) {
    error.value = apiError(err)
  } finally {
    loading.value = false
  }
}

function openCreate() {
  selectedClient.value = null
  isDrawerOpen.value = true
}

function openEdit(client) {
  selectedClient.value = client
  isDrawerOpen.value = true
}

function onClientSaved() {
  notice.value = selectedClient.value ? 'Cliente actualizado exitosamente.' : 'Cliente registrado exitosamente.'
  loadClients()
}

async function confirmToggleStatus() {
  if (!confirmingToggle.value || toggling.value) return
  toggling.value = true
  try {
    await $api(`/v1/clients/${confirmingToggle.value.id}/toggle-status`, { method: 'PATCH' })
    notice.value = `Cliente "${confirmingToggle.value.name}" ${confirmingToggle.value.is_active ? 'desactivado' : 'activado'} correctamente.`
    confirmingToggle.value = null
    await loadClients()
  } catch (err) {
    error.value = apiError(err)
    confirmingToggle.value = null
  } finally {
    toggling.value = false
  }
}

async function exportCsv() {
  exporting.value = true
  error.value = ''
  try {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token')
    const baseURL = import.meta.env.VITE_API_BASE_URL || '/api'
    const res = await fetch(`${baseURL}/v1/clients/export`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    if (!res.ok) {
      if (res.status === 403) throw new Error('Acceso no autorizado para exportar el directorio comercial.')
      throw new Error('Error al descargar el archivo CSV.')
    }

    const blob = await res.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `clientes_servimatica_${new Date().toISOString().slice(0, 10)}.csv`
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
    notice.value = 'Directorio de clientes exportado exitosamente.'
  } catch (err) {
    error.value = err.message || 'No se pudo exportar el directorio de clientes.'
  } finally {
    exporting.value = false
  }
}

let searchDebounce = null
function onSearchChange() {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    page.value = 1
    loadClients()
  }, 350)
}

watch([clientTypeFilter, statusFilter], () => {
  page.value = 1
  loadClients()
})

const getClientTypeChip = type => {
  switch (type) {
    case 'empresa':
      return { color: 'primary', label: 'Empresa / Corp.', variant: 'tonal' }
    case 'mayorista':
      return { color: 'info', label: 'Mayorista / Técnico', variant: 'tonal' }
    case 'institucion':
      return { color: 'secondary', label: 'Institucional', variant: 'tonal' }
    default:
      return { color: 'default', label: 'Consumidor Final', variant: 'outlined' }
  }
}

onMounted(() => {
  loadClients()
})
</script>

<template>
  <div>
    <!-- Encabezado de la página -->
    <div class="d-flex flex-wrap justify-space-between align-center gap-y-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-medium">
          Directorio de Clientes (CRM)
        </h4>
        <div class="text-body-1 text-medium-emphasis">
          Gestión de cartera comercial, segmentación, garantías activas y contacto directo vía WhatsApp
        </div>
      </div>

      <div class="d-flex gap-3">
        <!-- Botón Exportar (Solo Dueño con permiso de administración) -->
        <VBtn
          v-if="$can('manage', 'all')"
          variant="outlined"
          color="secondary"
          prepend-icon="ri-download-2-line"
          :loading="exporting"
          @click="exportCsv"
        >
          Exportar Directorio
        </VBtn>

        <!-- Botón Nuevo Cliente -->
        <VBtn
          prepend-icon="ri-user-add-line"
          @click="openCreate"
        >
          Nuevo Cliente
        </VBtn>
      </div>
    </div>

    <!-- Alertas de Notificación y Error -->
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

    <!-- Tarjeta de Métricas Rápidas (Estilo Materio Unificado) -->
    <VCard class="mb-6 elevation-1">
      <VCardText class="py-4">
        <VRow class="text-center align-center">
          <VCol
            cols="6"
            sm="3"
            class="d-flex flex-column align-center"
          >
            <div class="d-flex align-center gap-2 mb-1">
              <VIcon
                icon="ri-user-star-line"
                size="20"
                class="text-primary"
              />
              <span class="text-caption text-uppercase text-medium-emphasis font-weight-medium">Total Cartera</span>
            </div>
            <div class="text-h5 font-weight-bold">
              {{ kpis.total }}
            </div>
          </VCol>

          <VDivider
            vertical
            inset
            class="d-none d-sm-block"
          />

          <VCol
            cols="6"
            sm="3"
            class="d-flex flex-column align-center"
          >
            <div class="d-flex align-center gap-2 mb-1">
              <VIcon
                icon="ri-building-line"
                size="20"
                class="text-info"
              />
              <span class="text-caption text-uppercase text-medium-emphasis font-weight-medium">Empresas / Mayoristas</span>
            </div>
            <div class="text-h5 font-weight-bold">
              {{ kpis.wholesaleOrCorporate }}
            </div>
          </VCol>

          <VDivider
            vertical
            inset
            class="d-none d-sm-block"
          />

          <VCol
            cols="6"
            sm="3"
            class="d-flex flex-column align-center"
          >
            <div class="d-flex align-center gap-2 mb-1">
              <VIcon
                icon="ri-checkbox-circle-line"
                size="20"
                class="text-success"
              />
              <span class="text-caption text-uppercase text-medium-emphasis font-weight-medium">Clientes Activos</span>
            </div>
            <div class="text-h5 font-weight-bold">
              {{ kpis.activeCount }}
            </div>
          </VCol>

          <VDivider
            vertical
            inset
            class="d-none d-sm-block"
          />

          <VCol
            cols="6"
            sm="3"
            class="d-flex flex-column align-center"
          >
            <div class="d-flex align-center gap-2 mb-1">
              <VIcon
                icon="ri-shopping-bag-3-line"
                size="20"
                class="text-warning"
              />
              <span class="text-caption text-uppercase text-medium-emphasis font-weight-medium">Con Compras</span>
            </div>
            <div class="text-h5 font-weight-bold">
              {{ kpis.withPurchases }}
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Tarjeta Principal con Filtros y Tabla -->
    <VCard class="elevation-1">
      <!-- Barra de Filtros -->
      <VCardText class="pb-2">
        <VRow>
          <VCol
            cols="12"
            md="5"
          >
            <VTextField
              v-model="search"
              placeholder="Buscar por nombre, NIT/CI, celular o email..."
              prepend-inner-icon="ri-search-line"
              clearable
              density="compact"
              @input="onSearchChange"
              @click:clear="search = ''; onSearchChange()"
            />
          </VCol>

          <VCol
            cols="12"
            sm="6"
            md="4"
          >
            <VSelect
              v-model="clientTypeFilter"
              :items="clientTypeOptions"
              label="Segmento"
              density="compact"
              prepend-inner-icon="ri-filter-3-line"
            />
          </VCol>

          <VCol
            cols="12"
            sm="6"
            md="3"
          >
            <VSelect
              v-model="statusFilter"
              :items="statusOptions"
              label="Estado"
              density="compact"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <!-- Tabla de Clientes -->
      <VDataTableServer
        v-model:page="page"
        v-model:items-per-page="perPage"
        :items="clients"
        :items-length="totalItems"
        :headers="headers"
        :loading="loading"
        loading-text="Cargando directorio de clientes..."
        no-data-text="No se encontraron clientes registrados con los filtros aplicados"
        class="text-no-wrap"
        @update:options="loadClients"
      >
        <!-- Nombre del Cliente y NIT/CI -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3 py-2">
            <VAvatar
              size="38"
              color="primary"
              variant="tonal"
              class="font-weight-medium"
            >
              {{ item.name.charAt(0).toUpperCase() }}
            </VAvatar>
            <div class="d-flex flex-column">
              <RouterLink
                :to="{ path: `/clients/${item.id}` }"
                class="font-weight-medium text-high-emphasis text-decoration-none text-body-1 hover-primary"
              >
                {{ item.name }}
              </RouterLink>
              <span class="text-caption text-medium-emphasis">
                {{ item.nit_ci ? `NIT/CI: ${item.nit_ci}` : 'Sin NIT/CI registrado' }}
              </span>
            </div>
          </div>
        </template>

        <!-- Segmento / Tipo de Cliente -->
        <template #item.client_type="{ item }">
          <VChip
            size="small"
            :color="getClientTypeChip(item.client_type).color"
            :variant="getClientTypeChip(item.client_type).variant"
          >
            {{ getClientTypeChip(item.client_type).label }}
          </VChip>
        </template>

        <!-- Teléfono y Enlace Directo a WhatsApp -->
        <template #item.phone="{ item }">
          <div class="d-flex flex-column gap-1">
            <div
              v-if="item.phone"
              class="d-flex align-center gap-2"
            >
              <span class="text-body-2 font-weight-medium">{{ item.phone }}</span>
              <VBtn
                v-if="item.whatsapp_url"
                :href="item.whatsapp_url"
                target="_blank"
                rel="noopener noreferrer"
                icon
                size="x-small"
                variant="text"
                color="success"
                title="Abrir chat en WhatsApp"
              >
                <VIcon
                  icon="ri-whatsapp-line"
                  size="18"
                />
              </VBtn>
            </div>
            <span
              v-else
              class="text-caption text-disabled"
            >
              Sin celular
            </span>

            <span
              v-if="item.email"
              class="text-caption text-medium-emphasis"
            >
              {{ item.email }}
            </span>
          </div>
        </template>

        <!-- Ubicación -->
        <template #item.city="{ item }">
          <div class="d-flex flex-column">
            <span class="text-body-2 font-weight-medium">{{ item.city || 'Trinidad' }}</span>
            <span
              v-if="item.address"
              class="text-caption text-medium-emphasis text-truncate"
              style="max-width: 180px;"
            >
              {{ item.address }}
            </span>
          </div>
        </template>

        <!-- Total Compras -->
        <template #item.total_spent_bs="{ item }">
          <div class="d-flex flex-column align-end">
            <span class="text-body-2 font-weight-bold text-high-emphasis">
              Bs. {{ Number(item.total_spent_bs || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </span>
            <span class="text-caption text-medium-emphasis">
              {{ item.sales_count || 0 }} {{ item.sales_count === 1 ? 'venta' : 'ventas' }}
            </span>
          </div>
        </template>

        <!-- Estado Activo / Inactivo -->
        <template #item.is_active="{ item }">
          <VChip
            size="small"
            :color="item.is_active ? 'success' : 'secondary'"
            variant="tonal"
          >
            {{ item.is_active ? 'Activo' : 'Inactivo' }}
          </VChip>
        </template>

        <!-- Acciones -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-end gap-1">
            <!-- Ver Ficha 360° -->
            <IconBtn
              :to="{ path: `/clients/${item.id}` }"
              title="Ver Ficha 360°"
            >
              <VIcon
                icon="ri-eye-line"
                size="20"
              />
            </IconBtn>

            <!-- Editar -->
            <IconBtn
              title="Editar Cliente"
              @click="openEdit(item)"
            >
              <VIcon
                icon="ri-pencil-line"
                size="20"
              />
            </IconBtn>

            <!-- Toggle Estado (Solo Dueño) -->
            <IconBtn
              v-if="$can('manage', 'all')"
              :title="item.is_active ? 'Desactivar Cliente' : 'Activar Cliente'"
              :color="item.is_active ? 'error' : 'success'"
              @click="confirmingToggle = item"
            >
              <VIcon
                :icon="item.is_active ? 'ri-toggle-line' : 'ri-toggle-fill'"
                size="20"
              />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Modal de Confirmación de Activación / Desactivación -->
    <VDialog
      v-model="confirmingToggle"
      max-width="450"
    >
      <VCard v-if="confirmingToggle">
        <VCardTitle class="pt-5 px-5">
          {{ confirmingToggle.is_active ? '¿Desactivar Cliente?' : '¿Activar Cliente?' }}
        </VCardTitle>
        <VCardText class="px-5">
          ¿Está seguro de que desea cambiar el estado del cliente
          <strong>{{ confirmingToggle.name }}</strong>?
          <span v-if="confirmingToggle.is_active">
            No aparecerá disponible para nuevas ventas o cotizaciones rápidas hasta que sea reactivado.
          </span>
        </VCardText>
        <VCardActions class="pb-5 px-5 justify-end gap-2">
          <VBtn
            variant="outlined"
            color="secondary"
            :disabled="toggling"
            @click="confirmingToggle = null"
          >
            Cancelar
          </VBtn>
          <VBtn
            :color="confirmingToggle.is_active ? 'error' : 'success'"
            :loading="toggling"
            @click="confirmToggleStatus"
          >
            {{ confirmingToggle.is_active ? 'Desactivar' : 'Activar' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Drawer Lateral para Registro / Edición -->
    <AddEditClientDrawer
      v-model:is-drawer-open="isDrawerOpen"
      :client="selectedClient"
      @saved="onClientSaved"
    />
  </div>
</template>

<style scoped>
.hover-primary:hover {
  color: rgb(var(--v-theme-primary)) !important;
}
</style>
