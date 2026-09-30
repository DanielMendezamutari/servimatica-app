<script setup>
import AddUserDrawer from '@/views/users/AddUserDrawer.vue'
import { $api, apiError } from '@/utils/api'
import { currentUser } from '@/utils/session'

// Tabla, VCard, VChip y drawer adaptados del catálogo apps/user/list.
const users = ref([])
const loading = ref(false)
const error = ref('')
const notice = ref('')
const search = ref('')
const drawer = ref(false)
const editing = ref(null)
const confirming = ref(null)
const saving = ref(false)

const headers = [
  { title: 'Nombre', key: 'name' },
  { title: 'Alias', key: 'username' },
  { title: 'Correo', key: 'email' },
  { title: 'Rol', key: 'role' },
  { title: 'Estado', key: 'status' },
  { title: 'Acciones', key: 'actions', sortable: false },
]

async function loadUsers() {
  loading.value = true
  error.value = ''
  try { users.value = (await $api('/users')).data }
  catch (failure) { error.value = apiError(failure) }
  finally { loading.value = false }
}
function edit(user = null) {
  editing.value = user
  drawer.value = true
}
async function saved() {
  notice.value = 'Usuario guardado correctamente.'
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
onMounted(loadUsers)
</script>

<template>
  <section>
    <VCard title="Personal / Usuarios">
      <VCardText>
        <p>Administre las cuentas y el acceso de su equipo.</p>
        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          class="mb-4"
          role="alert"
        >
          {{ error }} <VBtn
            variant="text"
            @click="loadUsers"
          >
            Reintentar
          </VBtn>
        </VAlert>
        <div class="d-flex flex-wrap gap-4 align-center">
          <VTextField
            v-model="search"
            label="Buscar personal"
            prepend-inner-icon="ri-search-line"
            hide-details
          />
          <VBtn
            prepend-icon="ri-add-line"
            @click="edit"
          >
            Nuevo usuario
          </VBtn>
        </div>
      </VCardText>
      <VDataTable
        :headers="headers"
        :items="users"
        :search="search"
        :loading="loading"
        :items-per-page="10"
        no-data-text="No hay usuarios para mostrar."
        loading-text="Cargando personal..."
        items-per-page-text="Usuarios por página"
      >
        <template #item.role="{ item }">
          {{ item.role === 'dueno' ? 'Dueño' : 'Vendedor' }}
        </template>
        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'active' ? 'success' : 'secondary'"
            size="small"
          >
            {{ item.status === 'active' ? 'Activo' : 'Inactivo' }}
          </VChip>
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex gap-2">
            <VBtn
              size="small"
              variant="text"
              :aria-label="'Editar a ' + item.name"
              @click="edit(item)"
            >
              Editar
            </VBtn>
            <VBtn
              size="small"
              variant="tonal"
              :color="item.status === 'active' ? 'warning' : 'success'"
              :disabled="item.id === currentUser?.id"
              @click="confirming = item"
            >
              {{ item.status === 'active' ? 'Desactivar' : 'Activar' }}
            </VBtn>
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
        <VCardText>¿Confirma cambiar el acceso de {{ confirming?.name }}?</VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            :disabled="saving"
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
      @update:model-value="notice = ''"
    >
      {{ notice }}
    </VSnackbar>
  </section>
</template>
