<script setup>
import AddCategoryDrawer from '@/views/categories/AddCategoryDrawer.vue'
import AddBrandDrawer from '@/views/brands/AddBrandDrawer.vue'
import BrandModelsDialog from '@/views/brands/BrandModelsDialog.vue'
import { $api, apiError } from '@/utils/api'

// Centro Unificado de Clasificación de Catálogo: Categorías + Marcas y Modelos
const route = useRoute()
const router = useRouter()

const currentTab = ref(route.query.tab === 'brands' ? 'brands' : 'categories')

watch(currentTab, tab => {
  router.replace({ query: { ...route.query, tab } })
})

// ==========================================
// 1. ESTADO Y LÓGICA DE CATEGORÍAS
// ==========================================
const categories = ref([])
const loadingCategories = ref(false)
const errorCategories = ref('')
const noticeCategories = ref('')
const searchCategory = ref('')
const categoryDrawer = ref(false)
const editingCategory = ref(null)
const confirmingCategoryToggle = ref(null)
const confirmingCategoryDelete = ref(null)
const savingCategory = ref(false)

const drawerInitialType = ref('family')
const drawerDefaultParentId = ref(null)

const categoryHeaders = [
  { title: 'Nombre', key: 'name', minWidth: '220px' },
  { title: 'Clasificación', key: 'parent_name', minWidth: '180px' },
  { title: 'Descripción', key: 'description', minWidth: '200px' },
  { title: 'Cant. Productos', key: 'productsCount', align: 'center', minWidth: '130px' },
  { title: 'Estado', key: 'status', align: 'center', minWidth: '110px' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end', minWidth: '210px' },
]

async function loadCategories() {
  loadingCategories.value = true
  errorCategories.value = ''
  try {
    const res = await $api('/categories', { params: { search: searchCategory.value } })
    categories.value = res.data || []
  } catch (failure) {
    errorCategories.value = apiError(failure)
  } finally {
    loadingCategories.value = false
  }
}

function openCreateFamily() {
  editingCategory.value = null
  drawerInitialType.value = 'family'
  drawerDefaultParentId.value = null
  categoryDrawer.value = true
}

function openCreateSubfamily(parent = null) {
  editingCategory.value = null
  drawerInitialType.value = 'subfamily'
  drawerDefaultParentId.value = parent ? parent.id : null
  categoryDrawer.value = true
}

function openEditCategory(cat) {
  editingCategory.value = cat
  drawerInitialType.value = cat.parent_id ? 'subfamily' : 'family'
  drawerDefaultParentId.value = cat.parent_id || null
  categoryDrawer.value = true
}

async function onCategorySaved() {
  noticeCategories.value = editingCategory.value ? 'Categoría actualizada exitosamente.' : 'Registro creado exitosamente.'
  await loadCategories()
}

async function toggleCategoryStatus() {
  if (savingCategory.value || !confirmingCategoryToggle.value) return
  savingCategory.value = true
  errorCategories.value = ''
  try {
    await $api(`/categories/${confirmingCategoryToggle.value.id}/toggle-status`, { method: 'PATCH' })
    noticeCategories.value = 'Estado de categoría actualizado correctamente.'
    confirmingCategoryToggle.value = null
    await loadCategories()
  } catch (failure) {
    errorCategories.value = apiError(failure)
    confirmingCategoryToggle.value = null
  } finally {
    savingCategory.value = false
  }
}

async function deleteCategory() {
  if (savingCategory.value || !confirmingCategoryDelete.value) return
  savingCategory.value = true
  errorCategories.value = ''
  try {
    await $api(`/categories/${confirmingCategoryDelete.value.id}`, { method: 'DELETE' })
    noticeCategories.value = 'Categoría eliminada exitosamente.'
    confirmingCategoryDelete.value = null
    await loadCategories()
  } catch (failure) {
    errorCategories.value = apiError(failure)
    confirmingCategoryDelete.value = null
  } finally {
    savingCategory.value = false
  }
}

// ==========================================
// 2. ESTADO Y LÓGICA DE MARCAS Y MODELOS
// ==========================================
const brands = ref([])
const loadingBrands = ref(false)
const errorBrands = ref('')
const noticeBrands = ref('')
const searchBrand = ref('')
const brandDrawer = ref(false)
const editingBrand = ref(null)
const selectedBrandForModels = ref(null)
const modelsDialog = ref(false)
const confirmingBrandToggle = ref(null)
const savingBrand = ref(false)

const brandHeaders = [
  { title: 'Marca', key: 'name' },
  { title: 'Cant. Modelos', key: 'models_count', align: 'center' },
  { title: 'Estado', key: 'is_active', align: 'center' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
]

async function loadBrands() {
  loadingBrands.value = true
  errorBrands.value = ''
  try {
    const res = await $api('/brands', { params: { search: searchBrand.value } })
    brands.value = res.data || []
  } catch (failure) {
    errorBrands.value = apiError(failure)
  } finally {
    loadingBrands.value = false
  }
}

function openAddBrand(brand = null) {
  editingBrand.value = brand
  brandDrawer.value = true
}

function openModels(brand) {
  selectedBrandForModels.value = brand
  modelsDialog.value = true
}

async function onBrandSaved() {
  noticeBrands.value = editingBrand.value ? 'Marca actualizada exitosamente.' : 'Marca creada exitosamente.'
  await loadBrands()
}

async function toggleBrandStatus() {
  if (savingBrand.value || !confirmingBrandToggle.value) return
  savingBrand.value = true
  errorBrands.value = ''
  try {
    await $api(`/brands/${confirmingBrandToggle.value.id}/toggle-status`, { method: 'PATCH' })
    noticeBrands.value = 'Estado de marca actualizado correctamente.'
    confirmingBrandToggle.value = null
    await loadBrands()
  } catch (failure) {
    errorBrands.value = apiError(failure)
    confirmingBrandToggle.value = null
  } finally {
    savingBrand.value = false
  }
}

onMounted(() => {
  loadCategories()
  loadBrands()
})
</script>

<template>
  <section>
    <!-- Card Principal con Pestañas de Clasificación -->
    <VCard>
      <VCardItem class="pb-2">
        <VCardTitle class="text-h5 font-weight-bold">
          Clasificación de Catálogo
        </VCardTitle>
        <VCardSubtitle class="text-body-1 mt-1">
          Administración centralizada de categorías jerárquicas (familias y subfamilias) y marcas con sus modelos correspondientes.
        </VCardSubtitle>
      </VCardItem>

      <!-- Tabs de Navegación -->
      <VTabs
        v-model="currentTab"
        class="v-tabs-pill px-4 border-b"
      >
        <VTab value="categories">
          <VIcon
            start
            icon="ri-folder-3-line"
          />
          Categorías y Familias
        </VTab>
        <VTab value="brands">
          <VIcon
            start
            icon="ri-price-tag-3-line"
          />
          Marcas y Modelos
        </VTab>
      </VTabs>

      <VWindow v-model="currentTab">
        <!-- ============================================== -->
        <!-- PESTAÑA 1: CATEGORÍAS Y FAMILIAS -->
        <!-- ============================================== -->
        <VWindowItem value="categories">
          <VCardText>
            <VAlert
              v-if="errorCategories"
              type="error"
              variant="tonal"
              class="mb-4"
              role="alert"
            >
              {{ errorCategories }}
              <VBtn
                variant="text"
                class="ms-2"
                @click="loadCategories"
              >
                Reintentar
              </VBtn>
            </VAlert>

            <div class="d-flex flex-wrap gap-3 align-center justify-space-between mb-4">
              <VTextField
                v-model="searchCategory"
                placeholder="Buscar por nombre o descripción..."
                prepend-inner-icon="ri-search-line"
                density="compact"
                style="min-width: 240px; max-width: 320px;"
                clearable
                hide-details
                @update:model-value="loadCategories"
              />

              <div class="d-flex flex-wrap gap-2 align-center">
                <VBtn
                  color="primary"
                  prepend-icon="ri-folder-add-line"
                  @click="openCreateFamily"
                >
                  Nueva Familia
                </VBtn>

                <VBtn
                  color="info"
                  variant="outlined"
                  prepend-icon="ri-node-tree"
                  @click="openCreateSubfamily(null)"
                >
                  Nueva Subfamilia
                </VBtn>
              </div>
            </div>

            <VDataTable
              :headers="categoryHeaders"
              :items="categories"
              :loading="loadingCategories"
              :items-per-page="10"
              no-data-text="No se encontraron categorías registradas."
              loading-text="Cargando categorías..."
            >
              <!-- Nombre con Icono de Jerarquía -->
              <template #item.name="{ item }">
                <div class="d-flex align-center gap-2 py-1">
                  <VIcon
                    v-if="item.parent_id"
                    icon="ri-corner-down-right-line"
                    size="18"
                    color="info"
                    class="ms-3"
                  />
                  <VIcon
                    v-else
                    icon="ri-folder-3-fill"
                    size="20"
                    color="primary"
                  />
                  <span :class="item.parent_id ? 'text-body-2 font-weight-medium' : 'text-body-1 font-weight-bold'">
                    {{ item.name }}
                  </span>
                </div>
              </template>

              <!-- Clasificación (Familia / Subfamilia) -->
              <template #item.parent_name="{ item }">
                <VChip
                  v-if="item.parent_id"
                  size="small"
                  color="info"
                  variant="tonal"
                >
                  Subfamilia de: <strong>{{ item.parent_name }}</strong>
                </VChip>
                <VChip
                  v-else
                  size="small"
                  color="primary"
                  variant="outlined"
                >
                  Familia Principal
                </VChip>
              </template>

              <!-- Descripción -->
              <template #item.description="{ item }">
                <span class="text-caption text-medium-emphasis">
                  {{ item.description || 'Sin descripción' }}
                </span>
              </template>

              <!-- Cantidad de productos -->
              <template #item.productsCount="{ item }">
                <VChip
                  size="small"
                  variant="tonal"
                  color="secondary"
                >
                  {{ item.productsCount }} productos
                </VChip>
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
                <div class="d-flex gap-1 justify-end align-center">
                  <!-- Botón contextual para crear subfamilia si es Familia Principal -->
                  <VBtn
                    v-if="!item.parent_id"
                    size="x-small"
                    variant="tonal"
                    color="info"
                    prepend-icon="ri-add-line"
                    class="me-1"
                    title="Crear subfamilia dependiente de esta categoría"
                    @click="openCreateSubfamily(item)"
                  >
                    Subfamilia
                  </VBtn>

                  <VBtn
                    size="small"
                    variant="text"
                    color="primary"
                    icon="ri-edit-line"
                    :title="item.parent_id ? 'Editar subfamilia' : 'Editar familia'"
                    @click="openEditCategory(item)"
                  />

                  <VBtn
                    size="small"
                    variant="text"
                    :color="item.status === 'active' ? 'warning' : 'success'"
                    :icon="item.status === 'active' ? 'ri-eye-off-line' : 'ri-eye-line'"
                    :title="item.status === 'active' ? 'Desactivar' : 'Activar'"
                    @click="confirmingCategoryToggle = item"
                  />

                  <VBtn
                    size="small"
                    variant="text"
                    color="error"
                    icon="ri-delete-bin-line"
                    title="Eliminar"
                    @click="confirmingCategoryDelete = item"
                  />
                </div>
              </template>
            </VDataTable>
          </VCardText>
        </VWindowItem>

        <!-- ============================================== -->
        <!-- PESTAÑA 2: MARCAS Y MODELOS -->
        <!-- ============================================== -->
        <VWindowItem value="brands">
          <VCardText>
            <VAlert
              v-if="errorBrands"
              type="error"
              variant="tonal"
              class="mb-4"
              role="alert"
            >
              {{ errorBrands }}
              <VBtn
                variant="text"
                class="ms-2"
                @click="loadBrands"
              >
                Reintentar
              </VBtn>
            </VAlert>

            <div class="d-flex flex-wrap gap-4 align-center justify-space-between mb-4">
              <VTextField
                v-model="searchBrand"
                placeholder="Buscar por nombre de marca..."
                prepend-inner-icon="ri-search-line"
                density="compact"
                style="min-width: 240px; max-width: 320px;"
                clearable
                @update:model-value="loadBrands"
              />

              <VBtn
                prepend-icon="ri-add-line"
                @click="openAddBrand(null)"
              >
                Nueva marca
              </VBtn>
            </div>

            <VDataTable
              :headers="brandHeaders"
              :items="brands"
              :loading="loadingBrands"
              :items-per-page="10"
              no-data-text="No se encontraron marcas registradas."
              loading-text="Cargando marcas..."
            >
              <!-- Nombre de la Marca -->
              <template #item.name="{ item }">
                <div class="d-flex align-center gap-2 py-1">
                  <VIcon
                    icon="ri-price-tag-3-line"
                    size="20"
                    color="primary"
                  />
                  <span class="text-body-1 font-weight-bold">{{ item.name }}</span>
                </div>
              </template>

              <!-- Contador de Modelos -->
              <template #item.models_count="{ item }">
                <VBtn
                  size="small"
                  variant="tonal"
                  color="info"
                  prepend-icon="ri-list-settings-line"
                  @click="openModels(item)"
                >
                  {{ item.models_count }} modelos
                </VBtn>
              </template>

              <!-- Estado -->
              <template #item.is_active="{ item }">
                <VChip
                  :color="item.is_active ? 'success' : 'secondary'"
                  size="small"
                >
                  {{ item.is_active ? 'Activo' : 'Inactivo' }}
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
                    title="Editar marca"
                    @click="openAddBrand(item)"
                  />

                  <VBtn
                    size="small"
                    variant="text"
                    :color="item.is_active ? 'warning' : 'success'"
                    :icon="item.is_active ? 'ri-eye-off-line' : 'ri-eye-line'"
                    :title="item.is_active ? 'Desactivar marca' : 'Activar marca'"
                    @click="confirmingBrandToggle = item"
                  />
                </div>
              </template>
            </VDataTable>
          </VCardText>
        </VWindowItem>
      </VWindow>
    </VCard>

    <!-- Drawers y Diálogos de Categorías -->
    <AddCategoryDrawer
      v-model:is-drawer-open="categoryDrawer"
      :category="editingCategory"
      :initial-type="drawerInitialType"
      :default-parent-id="drawerDefaultParentId"
      @saved="onCategorySaved"
    />

    <VDialog
      :model-value="!!confirmingCategoryToggle"
      max-width="450"
      :persistent="savingCategory"
      @update:model-value="val => !val && !savingCategory && (confirmingCategoryToggle = null)"
    >
      <VCard :title="confirmingCategoryToggle?.status === 'active' ? 'Desactivar categoría' : 'Activar categoría'">
        <VCardText>
          ¿Confirma cambiar el estado de la categoría <strong>{{ confirmingCategoryToggle?.name }}</strong>?
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            :disabled="savingCategory"
            variant="outlined"
            @click="confirmingCategoryToggle = null"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            :loading="savingCategory"
            @click="toggleCategoryStatus"
          >
            Confirmar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog
      :model-value="!!confirmingCategoryDelete"
      max-width="480"
      :persistent="savingCategory"
      @update:model-value="val => !val && !savingCategory && (confirmingCategoryDelete = null)"
    >
      <VCard title="Eliminar categoría">
        <VCardText>
          ¿Está seguro de que desea eliminar la categoría <strong>{{ confirmingCategoryDelete?.name }}</strong>?
          <p class="text-caption text-error mt-2 mb-0">
            Esta acción no se puede deshacer. No se permitirá eliminar si tiene productos o subfamilias asociadas.
          </p>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            :disabled="savingCategory"
            variant="outlined"
            @click="confirmingCategoryDelete = null"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="error"
            :loading="savingCategory"
            @click="deleteCategory"
          >
            Eliminar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Drawers y Diálogos de Marcas y Modelos -->
    <AddBrandDrawer
      v-model:is-drawer-open="brandDrawer"
      :brand="editingBrand"
      @saved="onBrandSaved"
    />

    <BrandModelsDialog
      v-model:is-open="modelsDialog"
      :brand="selectedBrandForModels"
      @updated="loadBrands"
    />

    <VDialog
      :model-value="!!confirmingBrandToggle"
      max-width="450"
      :persistent="savingBrand"
      @update:model-value="val => !val && !savingBrand && (confirmingBrandToggle = null)"
    >
      <VCard :title="confirmingBrandToggle?.is_active ? 'Desactivar marca' : 'Activar marca'">
        <VCardText>
          ¿Confirma cambiar el estado de la marca <strong>{{ confirmingBrandToggle?.name }}</strong>?
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            :disabled="savingBrand"
            variant="outlined"
            @click="confirmingBrandToggle = null"
          >
            Cancelar
          </VBtn>
          <VBtn
            color="primary"
            :loading="savingBrand"
            @click="toggleBrandStatus"
          >
            Confirmar
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Snackbars de Notificación -->
    <VSnackbar
      :model-value="!!noticeCategories"
      color="success"
      location="top end"
      @update:model-value="noticeCategories = ''"
    >
      {{ noticeCategories }}
    </VSnackbar>

    <VSnackbar
      :model-value="!!noticeBrands"
      color="success"
      location="top end"
      @update:model-value="noticeBrands = ''"
    >
      {{ noticeBrands }}
    </VSnackbar>
  </section>
</template>
