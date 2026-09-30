<script setup>
import AddCategoryDrawer from '@/views/categories/AddCategoryDrawer.vue'
import { $api, apiError } from '@/utils/api'

// Adaptado del catálogo admin-full-version/src/pages/apps/ecommerce/product/category-list.vue
const categories = ref([])
const loading = ref(false)
const error = ref('')
const notice = ref('')
const search = ref('')
const drawer = ref(false)
const editing = ref(null)
const confirmingToggle = ref(null)
const confirmingDelete = ref(null)
const saving = ref(false)

const headers = [
  { title: 'Nombre', key: 'name' },
  { title: 'Descripción', key: 'description' },
  { title: 'Cant. Productos', key: 'productsCount', align: 'center' },
  { title: 'Estado', key: 'status', align: 'center' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

async function loadCategories() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/categories', { params: { search: search.value } })
    categories.value = res.data || []
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    loading.value = false
  }
}

function edit(cat = null) {
  editing.value = cat
  drawer.value = true
}

async function saved() {
  notice.value = editing.value ? 'Categoría actualizada exitosamente.' : 'Categoría creada exitosamente.'
  await loadCategories()
}

async function toggleStatus() {
  if (saving.value || !confirmingToggle.value) return
  saving.value = true
  error.value = ''
  try {
    await $api(`/categories/${confirmingToggle.value.id}/toggle-status`, { method: 'PATCH' })
    notice.value = 'Estado de categoría actualizado correctamente.'
    confirmingToggle.value = null
    await loadCategories()
  } catch (failure) {
    error.value = apiError(failure)
    confirmingToggle.value = null
  } finally {
    saving.value = false
  }
}

async function deleteCategory() {
  if (saving.value || !confirmingDelete.value) return
  saving.value = true
  error.value = ''
  try {
    await $api(`/categories/${confirmingDelete.value.id}`, { method: 'DELETE' })
    notice.value = 'Categoría eliminada correctamente.'
    confirmingDelete.value = null
    await loadCategories()
  } catch (failure) {
    error.value = apiError(failure)
    confirmingDelete.value = null
  } finally {
    saving.value = false
  }
}

onMounted(loadCategories)
</script>

<template>
  <section>
    <VCard title="Catálogo / Categorías de Productos">
      <VCardText>
        <p class="text-body-1 mb-4">
          Organice las familias y clasificaciones de productos de la tienda.
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
            @click="loadCategories"
          >
            Reintentar
          </VBtn>
        </VAlert>

        <div class="d-flex flex-wrap gap-4 align-center justify-space-between">
          <VTextField
            v-model="search"
            label="Buscar por nombre..."
            prepend-inner-icon="ri-search-line"
            density="compact"
            style="max-width: 320px;"
            clearable
            @update:model-value="loadCategories"
          />

          <VBtn
            prepend-icon="ri-add-line"
            @click="edit(null)"
          >
            Nueva categoría
          </VBtn>
        </div>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="categories"
        :loading="loading"
        :items-per-page="10"
        no-data-text="No hay categorías registradas."
        loading-text="Cargando categorías..."
        items-per-page-text="Filas por página"
      >
        <template #item.name="{ item }">
          <div class="font-weight-medium">
            {{ item.name }}
          </div>
        </template>

        <template #item.description="{ item }">
          <span class="text-medium-emphasis text-body-2">
            {{ item.description || '—' }}
          </span>
        </template>

        <template #item.productsCount="{ item }">
          <VChip
            size="small"
            variant="tonal"
            :color="item.productsCount > 0 ? 'primary' : 'default'"
          >
            {{ item.productsCount }} {{ item.productsCount === 1 ? 'producto' : 'productos' }}
          </VChip>
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
          <div class="d-flex gap-2 justify-end">
            <VBtn
              size="small"
              variant="text"
              color="primary"
              prepend-icon="ri-edit-line"
              @click="edit(item)"
            >
              Editar
            </VBtn>

            <VBtn
              size="small"
              variant="tonal"
              :color="item.status === 'active' ? 'warning' : 'success'"
              @click="confirmingToggle = item"
            >
              {{ item.status === 'active' ? 'Desactivar' : 'Activar' }}
            </VBtn>

            <VBtn
              size="small"
              variant="text"
              color="error"
              icon="ri-delete-bin-line"
              title="Eliminar categoría"
              @click="confirmingDelete = item"
            />
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Drawer de creación / edición -->
    <AddCategoryDrawer
      v-model:is-drawer-open="drawer"
      :category="editing"
      @saved="saved"
    />

    <!-- Diálogo confirmar cambio de estado -->
    <VDialog
      :model-value="!!confirmingToggle"
      max-width="450"
      :persistent="saving"
      @update:model-value="val => !val && !saving && (confirmingToggle = null)"
    >
      <VCard :title="confirmingToggle?.status === 'active' ? 'Desactivar categoría' : 'Activar categoría'">
        <VCardText>
          ¿Desea cambiar el estado de la categoría <strong>{{ confirmingToggle?.name }}</strong> a
          <em>{{ confirmingToggle?.status === 'active' ? 'inactivo' : 'activo' }}</em>?
          <div
            v-if="confirmingToggle?.status === 'active' && confirmingToggle?.productsCount > 0"
            class="text-caption text-warning mt-2"
          >
            ⚠️ Nota: Los productos asociados a esta categoría quedarán ocultos para los vendedores mientras la categoría esté inactiva.
          </div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            :disabled="saving"
            variant="plain"
            @click="confirmingToggle = null"
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

    <!-- Diálogo confirmar eliminación -->
    <VDialog
      :model-value="!!confirmingDelete"
      max-width="480"
      :persistent="saving"
      @update:model-value="val => !val && !saving && (confirmingDelete = null)"
    >
      <VCard title="Eliminar categoría">
        <VCardText>
          <div v-if="confirmingDelete?.productsCount > 0">
            <VAlert
              type="warning"
              variant="tonal"
              class="mb-2"
            >
              No se puede eliminar la categoría <strong>{{ confirmingDelete?.name }}</strong> porque tiene
              <strong>{{ confirmingDelete?.productsCount }}</strong> {{ confirmingDelete?.productsCount === 1 ? 'producto asignado' : 'productos asignados' }}.
            </VAlert>
            <p class="text-body-2 mb-0">
              Para eliminarla, primero debe reasignar o eliminar los productos vinculados.
            </p>
          </div>
          <div v-else>
            ¿Está seguro de que desea eliminar permanentemente la categoría <strong>{{ confirmingDelete?.name }}</strong>? Esta acción no se puede deshacer.
          </div>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            :disabled="saving"
            variant="plain"
            @click="confirmingDelete = null"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="error"
            :loading="saving"
            :disabled="confirmingDelete?.productsCount > 0"
            @click="deleteCategory"
          >
            Eliminar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Feedback snackbar -->
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
