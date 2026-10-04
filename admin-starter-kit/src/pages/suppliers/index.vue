<script setup>
import SupplierDialog from '@/views/suppliers/SupplierDialog.vue'
import { $api, apiError } from '@/utils/api'

const suppliers = ref([])
const loading = ref(false)
const error = ref('')
const notice = ref('')
const search = ref('')
const statusFilter = ref('all')
const page = ref(1)
const perPage = ref(15)
const totalItems = ref(0)

const dialog = ref(false)
const editing = ref(null)
const confirmingToggle = ref(null)
const saving = ref(false)

const statusOptions = [
  { title: 'Todos los estados', value: 'all' },
  { title: 'Solo Activos', value: 'active' },
  { title: 'Solo Inactivos', value: 'inactive' },
]

const headers = [
  { title: 'Proveedor / Razón Social', key: 'name' },
  { title: 'Contacto & Teléfono', key: 'contact' },
  { title: 'Ciudad & Dirección', key: 'location' },
  { title: 'Estado', key: 'is_active', align: 'center' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

async function loadSuppliers() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/suppliers', {
      params: {
        page: page.value,
        per_page: perPage.value,
        search: search.value,
        status: statusFilter.value,
      },
    })
    suppliers.value = res.data || []
    totalItems.value = res.total || 0
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  dialog.value = true
}

function openEdit(supplier) {
  editing.value = supplier
  dialog.value = true
}

async function saved() {
  notice.value = editing.value ? 'Proveedor actualizado exitosamente.' : 'Proveedor registrado exitosamente.'
  await loadSuppliers()
}

async function toggleStatus() {
  if (saving.value || !confirmingToggle.value) return
  saving.value = true
  error.value = ''
  try {
    await $api(`/suppliers/${confirmingToggle.value.id}/toggle-status`, { method: 'PATCH' })
    notice.value = `Proveedor "${confirmingToggle.value.name}" ${confirmingToggle.value.is_active ? 'desactivado' : 'activado'} correctamente.`
    confirmingToggle.value = null
    await loadSuppliers()
  } catch (failure) {
    error.value = apiError(failure)
    confirmingToggle.value = null
  } finally {
    saving.value = false
  }
}

let searchTimeout = null
function onSearchChange() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    loadSuppliers()
  }, 350)
}

watch(statusFilter, () => {
  page.value = 1
  loadSuppliers()
})

onMounted(loadSuppliers)
</script>

<template>
  <section>
    <VCard title="Directorio de Proveedores Mayoristas">
      <VCardText>
        <p class="text-body-1 mb-4 text-medium-emphasis">
          Gestione la información comercial y fiscal de distribuidores e importadores para la recepción de compras y control de costos.
        </p>

        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          class="mb-4"
          closable
        >
          {{ error }}
        </VAlert>

        <VAlert
          v-if="notice"
          type="success"
          variant="tonal"
          class="mb-4"
          closable
        >
          {{ notice }}
        </VAlert>

        <div class="d-flex flex-wrap gap-4 align-center justify-space-between mb-4">
          <div class="d-flex flex-wrap gap-4 align-center flex-grow-1" style="max-width: 650px;">
            <VTextField
              v-model="search"
              label="Buscar por nombre, NIT, contacto o celular..."
              prepend-inner-icon="ri-search-line"
              density="compact"
              clearable
              style="min-width: 280px;"
              @update:model-value="onSearchChange"
            />

            <VSelect
              v-model="statusFilter"
              :items="statusOptions"
              density="compact"
              style="max-width: 180px;"
            />
          </div>

          <VBtn
            prepend-icon="ri-truck-line"
            color="primary"
            @click="openCreate"
          >
            Nuevo Proveedor
          </VBtn>
        </div>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="suppliers"
        :loading="loading"
        :items-per-page="perPage"
        no-data-text="No se encontraron proveedores registrados."
        loading-text="Cargando proveedores..."
        items-per-page-text="Filas por página"
      >
        <template #item.name="{ item }">
          <div class="d-flex flex-column py-2">
            <span class="font-weight-bold text-primary text-body-1">
              {{ item.name }}
            </span>
            <span v-if="item.nit" class="text-caption text-medium-emphasis">
              NIT: {{ item.nit }}
            </span>
          </div>
        </template>

        <template #item.contact="{ item }">
          <div class="d-flex flex-column py-1">
            <span v-if="item.contact_name" class="font-weight-medium">
              {{ item.contact_name }}
            </span>
            <span v-if="item.phone" class="text-caption text-success font-weight-bold">
              <VIcon icon="ri-whatsapp-line" size="14" class="me-1" />
              {{ item.phone }}
            </span>
            <span v-if="item.email" class="text-caption text-medium-emphasis">
              {{ item.email }}
            </span>
            <span v-if="!item.contact_name && !item.phone && !item.email" class="text-caption text-disabled">
              Sin datos de contacto
            </span>
          </div>
        </template>

        <template #item.location="{ item }">
          <div class="d-flex flex-column py-1">
            <span v-if="item.city" class="text-body-2 font-weight-medium">
              <VIcon icon="ri-map-pin-line" size="14" class="me-1 text-primary" />
              {{ item.city }}
            </span>
            <span v-if="item.address" class="text-caption text-medium-emphasis text-truncate" style="max-width: 250px;">
              {{ item.address }}
            </span>
            <span v-if="!item.city && !item.address" class="text-caption text-disabled">
              -
            </span>
          </div>
        </template>

        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'secondary'"
            size="small"
            variant="tonal"
          >
            {{ item.is_active ? 'Activo' : 'Inactivo' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-2 justify-end">
            <VBtn
              size="small"
              variant="text"
              color="primary"
              prepend-icon="ri-edit-line"
              @click="openEdit(item)"
            >
              Editar
            </VBtn>

            <VBtn
              size="small"
              variant="tonal"
              :color="item.is_active ? 'warning' : 'success'"
              @click="confirmingToggle = item"
            >
              {{ item.is_active ? 'Desactivar' : 'Activar' }}
            </VBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Diálogo Modal de Creación / Edición -->
    <SupplierDialog
      v-model:is-dialog-open="dialog"
      :supplier="editing"
      @saved="saved"
    />

    <!-- Diálogo de Confirmación para Alternar Estado -->
    <VDialog
      :model-value="!!confirmingToggle"
      max-width="450"
      @update:model-value="val => !val && (confirmingToggle = null)"
    >
      <VCard v-if="confirmingToggle">
        <VCardTitle class="text-h6 pa-4">
          ¿Cambiar estado del proveedor?
        </VCardTitle>
        <VCardText class="pa-4 pt-0">
          ¿Está seguro de que desea <strong>{{ confirmingToggle.is_active ? 'desactivar' : 'activar' }}</strong> al proveedor <strong>{{ confirmingToggle.name }}</strong>?
          <p v-if="confirmingToggle.is_active" class="text-caption text-warning mt-2 mb-0">
            Un proveedor inactivo no aparecerá en el selector para nuevas recepciones de compra.
          </p>
        </VCardText>
        <VCardActions class="pa-4 justify-end">
          <VBtn
            variant="text"
            :disabled="saving"
            @click="confirmingToggle = null"
          >
            Cancelar
          </VBtn>
          <VBtn
            :color="confirmingToggle.is_active ? 'warning' : 'success'"
            :loading="saving"
            @click="toggleStatus"
          >
            Confirmar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>
