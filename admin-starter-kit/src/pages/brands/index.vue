<script setup>
import AddBrandDrawer from '@/views/brands/AddBrandDrawer.vue'
import BrandModelsDialog from '@/views/brands/BrandModelsDialog.vue'
import { $api, apiError } from '@/utils/api'

const brands = ref([])
const loading = ref(false)
const error = ref('')
const notice = ref('')
const search = ref('')
const drawer = ref(false)
const editing = ref(null)
const selectedBrandForModels = ref(null)
const modelsDialog = ref(false)
const confirmingToggle = ref(null)
const saving = ref(false)

const headers = [
  { title: 'Marca', key: 'name' },
  { title: 'Cant. Modelos', key: 'models_count', align: 'center' },
  { title: 'Estado', key: 'is_active', align: 'center' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

async function loadBrands() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/brands', { params: { search: search.value } })
    brands.value = res.data || []
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    loading.value = false
  }
}

function edit(brand = null) {
  editing.value = brand
  drawer.value = true
}

function openModels(brand) {
  selectedBrandForModels.value = brand
  modelsDialog.value = true
}

async function saved() {
  notice.value = editing.value ? 'Marca actualizada exitosamente.' : 'Marca creada exitosamente.'
  await loadBrands()
}

async function toggleStatus() {
  if (saving.value || !confirmingToggle.value) return
  saving.value = true
  error.value = ''
  try {
    await $api(`/brands/${confirmingToggle.value.id}/toggle-status`, { method: 'PATCH' })
    notice.value = 'Estado de marca actualizado correctamente.'
    confirmingToggle.value = null
    await loadBrands()
  } catch (failure) {
    error.value = apiError(failure)
    confirmingToggle.value = null
  } finally {
    saving.value = false
  }
}

onMounted(loadBrands)
</script>

<template>
  <section>
    <VCard title="Catálogo / Marcas Comerciales">
      <VCardText>
        <p class="text-body-1 mb-4">
          Administre las marcas de hardware y sus modelos asociados para estandarizar el inventario.
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

        <div class="d-flex flex-wrap gap-4 align-center justify-space-between">
          <VTextField
            v-model="search"
            label="Buscar marca..."
            prepend-inner-icon="ri-search-line"
            density="compact"
            style="max-width: 320px;"
            clearable
            @update:model-value="loadBrands"
          />

          <VBtn
            prepend-icon="ri-add-line"
            @click="edit(null)"
          >
            Nueva Marca
          </VBtn>
        </div>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="brands"
        :loading="loading"
        :items-per-page="10"
        no-data-text="No hay marcas registradas."
        loading-text="Cargando marcas..."
        items-per-page-text="Filas por página"
      >
        <template #item.name="{ item }">
          <span class="font-weight-bold text-primary">
            {{ item.name }}
          </span>
        </template>

        <template #item.models_count="{ item }">
          <VChip
            size="small"
            variant="tonal"
            color="info"
            class="cursor-pointer"
            @click="openModels(item)"
          >
            <VIcon start icon="ri-list-check" size="16" />
            {{ item.models_count }} {{ item.models_count === 1 ? 'modelo' : 'modelos' }}
          </VChip>
        </template>

        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'secondary'"
            size="small"
          >
            {{ item.is_active ? 'Activa' : 'Inactiva' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-2 justify-end">
            <VBtn
              size="small"
              variant="tonal"
              color="info"
              prepend-icon="ri-price-tag-3-line"
              @click="openModels(item)"
            >
              Modelos
            </VBtn>

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
              :color="item.is_active ? 'warning' : 'success'"
              @click="confirmingToggle = item"
            >
              {{ item.is_active ? 'Desactivar' : 'Activar' }}
            </VBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Drawer de Alta/Edición de Marca -->
    <AddBrandDrawer
      v-model:is-drawer-open="drawer"
      :brand="editing"
      @saved="saved"
    />

    <!-- Diálogo de Modelos de la Marca -->
    <BrandModelsDialog
      v-model:is-dialog-open="modelsDialog"
      :brand="selectedBrandForModels"
      @updated="loadBrands"
    />

    <!-- Diálogo Confirmar Cambio de Estado -->
    <VDialog
      :model-value="!!confirmingToggle"
      max-width="450"
      @update:model-value="val => !val && (confirmingToggle = null)"
    >
      <VCard v-if="confirmingToggle">
        <VCardTitle class="text-h6 pa-4">
          ¿Cambiar estado de la marca?
        </VCardTitle>
        <VCardText class="pa-4 pt-0">
          ¿Está seguro de que desea <strong>{{ confirmingToggle.is_active ? 'desactivar' : 'activar' }}</strong> la marca <strong>{{ confirmingToggle.name }}</strong>?
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
