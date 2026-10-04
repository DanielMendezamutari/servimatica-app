<script setup>
import AddUserDrawer from '@/views/users/AddUserDrawer.vue'
import { $api, apiError } from '@/utils/api'
import { currentUser } from '@/utils/session'

// Adaptado de admin-full-version/src/pages/apps/user/list/index.vue
const users = ref([])
const loading = ref(false)
const exporting = ref(false)
const error = ref('')
const notice = ref('')
const search = ref('')
const drawer = ref(false)
const editing = ref(null)
const confirming = ref(null)
const saving = ref(false)

const headers = [
  { title: 'Personal / Usuario', key: 'name' },
  { title: 'CI / Cédula', key: 'ci' },
  { title: 'Alias', key: 'username' },
  { title: 'Sucursal', key: 'branch' },
  { title: 'Rol', key: 'role' },
  { title: 'Comisión', key: 'salesCommission', align: 'center' },
  { title: 'Estado', key: 'status', align: 'center' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

async function loadUsers() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/users')
    users.value = res.data || []
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    loading.value = false
  }
}

function edit(user = null) {
  editing.value = (user && typeof user === 'object' && 'id' in user) ? user : null
  drawer.value = true
}

async function saved() {
  notice.value = 'Ficha de usuario guardada correctamente.'
  await loadUsers()
}

async function toggleStatus() {
  if (saving.value || !confirming.value) return
  saving.value = true
  error.value = ''
  try {
    await $api(`/users/${confirming.value.id}/toggle-status`, { method: 'PATCH' })
    confirming.value = null
    notice.value = 'Estado actualizado correctamente.'
    await loadUsers()
  } catch (failure) {
    error.value = apiError(failure)
    confirming.value = null
  } finally {
    saving.value = false
  }
}

async function exportExcel() {
  exporting.value = true
  error.value = ''
  try {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token')
    const baseURL = import.meta.env.VITE_API_BASE_URL || '/api'
    const res = await fetch(`${baseURL}/users/export-excel`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    if (!res.ok) throw new Error('Error al descargar nómina')

    const blob = await res.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `nomina_personal_servimatica_${new Date().toISOString().slice(0, 10)}.xlsx`
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
    notice.value = 'Nómina de personal exportada exitosamente a Excel.'
  } catch (e) {
    error.value = 'No se pudo exportar la nómina a Excel.'
  } finally {
    exporting.value = false
  }
}

onMounted(loadUsers)
</script>

<template>
  <section>
    <VCard title="Personal / Equipo de Trabajo">
      <VCardText>
        <p class="text-body-1 mb-4">
          Administre las fichas completas, accesos, comisiones y sucursales del personal de Servimática.
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
            @click="loadUsers"
          >
            Reintentar
          </VBtn>
        </VAlert>

        <div class="d-flex flex-wrap gap-4 align-center justify-space-between">
          <VTextField
            v-model="search"
            placeholder="Buscar por nombre, CI o alias..."
            prepend-inner-icon="ri-search-line"
            density="compact"
            style="min-width: 240px; max-width: 320px;"
            clearable
          />

          <div class="d-flex gap-2">
            <VBtn
              color="success"
              variant="outlined"
              prepend-icon="ri-file-excel-2-line"
              :loading="exporting"
              @click="exportExcel"
            >
              Exportar Nómina a Excel
            </VBtn>

            <VBtn
              prepend-icon="ri-user-add-line"
              @click="edit(null)"
            >
              Nuevo personal
            </VBtn>
          </div>
        </div>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="users"
        :search="search"
        :loading="loading"
        :items-per-page="10"
        no-data-text="No hay usuarios registrados."
        loading-text="Cargando personal..."
      >
        <!-- Nombre, Avatar y Contacto -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3 py-2">
            <VAvatar
              size="40"
              color="primary"
              variant="tonal"
            >
              <VImg
                v-if="item.avatarUrl || item.avatar_url"
                :src="item.avatarUrl || item.avatar_url"
                cover
              />
              <span
                v-else
                class="font-weight-medium text-sm"
              >
                {{ item.name.slice(0, 2).toUpperCase() }}
              </span>
            </VAvatar>

            <div class="d-flex flex-column">
              <span class="font-weight-medium text-high-emphasis">{{ item.name }}</span>
              <span
                v-if="item.phone"
                class="text-caption text-medium-emphasis"
              >
                📞 {{ item.phone }}
              </span>
              <span
                v-else-if="item.email"
                class="text-caption text-medium-emphasis"
              >
                {{ item.email }}
              </span>
            </div>
          </div>
        </template>

        <!-- CI -->
        <template #item.ci="{ item }">
          <span class="font-weight-medium">
            {{ item.ci || '—' }}
          </span>
        </template>

        <!-- Sucursal -->
        <template #item.branch="{ item }">
          <span class="text-body-2">
            {{ item.branch || 'Casa Matriz' }}
          </span>
        </template>

        <!-- Rol -->
        <template #item.role="{ item }">
          <VChip
            size="small"
            variant="tonal"
            :color="item.role === 'dueno' ? 'primary' : 'info'"
          >
            {{ item.role === 'dueno' ? 'Dueño / Administrador' : 'Vendedor' }}
          </VChip>
        </template>

        <!-- Comisión -->
        <template #item.salesCommission="{ item }">
          <span class="font-weight-medium text-success">
            {{ item.salesCommission ?? item.sales_commission ?? 0 }}%
          </span>
        </template>

        <!-- Estado -->
        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'active' ? 'success' : 'secondary'"
            size="small"
          >
            {{ item.status === 'active' ? 'Activo' : 'Inactivo' }}
          </VChip>
        </template>

        <!-- Acciones -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-end">
            <VBtn
              size="small"
              variant="text"
              color="primary"
              icon="ri-edit-line"
              title="Editar usuario"
              @click="edit(item)"
            />

            <VBtn
              size="small"
              variant="text"
              :color="item.status === 'active' ? 'warning' : 'success'"
              :icon="item.status === 'active' ? 'ri-user-unfollow-line' : 'ri-user-follow-line'"
              :title="item.status === 'active' ? 'Desactivar usuario' : 'Activar usuario'"
              :disabled="item.id === currentUser?.id"
              @click="confirming = item"
            />
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddUserDrawer
      v-model:is-drawer-open="drawer"
      :user="editing"
      @saved="saved"
    />

    <VDialog
      :model-value="!!confirming"
      max-width="450"
      :persistent="saving"
      @update:model-value="value => !value && !saving && (confirming = null)"
    >
      <VCard :title="confirming?.status === 'active' ? 'Desactivar usuario' : 'Activar usuario'">
        <VCardText>
          ¿Confirma cambiar el estado de acceso de <strong>{{ confirming?.name }}</strong>?
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            :disabled="saving"
            variant="outlined"
            @click="confirming = null"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            :loading="saving"
            @click="toggleStatus"
          >
            Confirmar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VSnackbar
      :model-value="!!notice"
      color="success"
      location="top end"
      @update:model-value="notice = ''"
    >
      {{ notice }}
    </VSnackbar>
  </section>
</template>
