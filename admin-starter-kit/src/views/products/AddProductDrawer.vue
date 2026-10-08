<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { $api, apiError } from '@/utils/api'

// Adaptado de los drawers del catálogo admin-full-version
const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  product: { type: Object, default: null },
})

const emit = defineEmits(['update:isDrawerOpen', 'saved'])

const conditions = [
  { title: 'Nuevo', value: 'nuevo' },
  { title: 'Seminuevo / Open Box', value: 'open_box' },
  { title: 'Usado', value: 'usado' },
  { title: 'Reacondicionado', value: 'reacondicionado' },
]

const form = ref({
  name: '',
  description: '',
  categoryId: null,
  subfamilyId: null,
  brandId: null,
  productModelId: null,
  condition: 'nuevo',
  sku: '',
  costPrice: '',
  salePrice: '',
  stock: 0,
  minStock: 0,
  warrantyDays: 0,
  warrantyHardwareDays: 0,
  warrantySoftwareDays: 0,
})

const warrantyPresets = [
  { title: 'Sin garantía (0 días)', value: 0 },
  { title: '15 días', value: 15 },
  { title: '1 mes (30 días)', value: 30 },
  { title: '3 meses (90 días)', value: 90 },
  { title: '6 meses (180 días)', value: 180 },
  { title: '1 año (365 días)', value: 365 },
  { title: '2 años (730 días)', value: 730 },
  { title: 'Personalizado...', value: 'custom' },
]

const selectedHardwarePreset = ref(0)
const selectedSoftwarePreset = ref(0)

function onHardwarePresetChange(val) {
  if (val !== 'custom') {
    form.value.warrantyHardwareDays = Number(val)
    form.value.warrantyDays = Number(val)
  }
}

function onSoftwarePresetChange(val) {
  if (val !== 'custom') {
    form.value.warrantySoftwareDays = Number(val)
  }
}

const categories = ref([])
const subfamilies = ref([])
const brands = ref([])
const models = ref([])
const modelSearch = ref('')

const busy = ref(false)
const loadingSubfamilies = ref(false)
const loadingModels = ref(false)
const errors = ref({})
const error = ref('')
const refForm = ref()

// Estado para Fotos y Galería 360°
const coverFile = ref(null)
const coverPreview = ref(null)
const removeCover = ref(false)
const coverInputRef = ref(null)

const galleryFiles = ref([])
const galleryPreviews = ref([])
const removeGallery = ref(false)
const replaceGallery = ref(false)
const galleryInputRef = ref(null)

function onCoverChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  coverFile.value = file
  removeCover.value = false
  coverPreview.value = URL.createObjectURL(file)
}

function triggerCoverInput() {
  coverInputRef.value?.click()
}

function removeCoverPhoto() {
  coverFile.value = null
  coverPreview.value = null
  removeCover.value = true
  if (coverInputRef.value) coverInputRef.value.value = ''
}

function triggerGalleryInput() {
  galleryInputRef.value?.click()
}

function onGalleryChange(e) {
  const files = Array.from(e.target.files || [])
  if (!files.length) return

  const remainingSlots = 8 - galleryPreviews.value.length
  if (remainingSlots <= 0) {
    error.value = 'El límite máximo es de 8 fotografías para la secuencia 360°.'
    return
  }

  const allowedFiles = files.slice(0, remainingSlots)
  for (const file of allowedFiles) {
    galleryFiles.value.push(file)
    galleryPreviews.value.push({
      id: 'new_' + Math.random().toString(36).substring(2, 9),
      url: URL.createObjectURL(file),
      isNew: true,
      file,
    })
  }

  removeGallery.value = false
  if (galleryInputRef.value) galleryInputRef.value.value = ''
}

function removeGalleryPhoto(index) {
  const item = galleryPreviews.value[index]
  if (item?.isNew && item.file) {
    const fileIdx = galleryFiles.value.indexOf(item.file)
    if (fileIdx > -1) galleryFiles.value.splice(fileIdx, 1)
  } else {
    // Foto existente eliminada
    replaceGallery.value = true
  }
  galleryPreviews.value.splice(index, 1)
  if (galleryPreviews.value.length === 0) {
    removeGallery.value = true
  }
}

function clearGalleryPhotos() {
  galleryFiles.value = []
  galleryPreviews.value = []
  removeGallery.value = true
  replaceGallery.value = true
  if (galleryInputRef.value) galleryInputRef.value.value = ''
}

const required = value => !!String(value ?? '').trim() || 'Este campo es obligatorio.'
const positiveNumber = value => Number(value) >= 0 || 'Debe ser un número mayor o igual a 0.'

// Cálculo de margen en tiempo real
const marginBs = computed(() => {
  const cost = parseFloat(form.value.costPrice) || 0
  const sale = parseFloat(form.value.salePrice) || 0
  return (sale - cost).toFixed(2)
})

const marginPct = computed(() => {
  const cost = parseFloat(form.value.costPrice) || 0
  const sale = parseFloat(form.value.salePrice) || 0
  if (cost <= 0) return '0.0'
  return (((sale - cost) / cost) * 100).toFixed(1)
})

const isNegativeMargin = computed(() => {
  const cost = parseFloat(form.value.costPrice) || 0
  const sale = parseFloat(form.value.salePrice) || 0
  return cost > 0 && sale > 0 && sale < cost
})

async function fetchCategories() {
  try {
    const res = await $api('/categories/options')
    categories.value = (res.data || []).map(c => ({
      title: c.name,
      value: c.id,
    }))
  } catch (e) {
    // Si falla options, fallback a categories
    try {
      const fallback = await $api('/categories')
      categories.value = (fallback.data || []).map(c => ({
        title: c.name,
        value: c.id,
      }))
    } catch {}
  }
}

async function fetchBrands() {
  try {
    const res = await $api('/brands/options')
    brands.value = (res.data || []).map(b => ({
      title: b.name,
      value: b.id,
    }))
  } catch (e) {}
}

async function loadSubfamilies(catId) {
  if (!catId) {
    subfamilies.value = []
    form.value.subfamilyId = null
    return
  }
  loadingSubfamilies.value = true
  try {
    const res = await $api(`/categories/${catId}/subfamilies`)
    subfamilies.value = (res.data || []).map(s => ({
      title: s.name,
      value: s.id,
    }))
    // Si la subfamilia actual no está en la nueva lista, limpiarla
    if (form.value.subfamilyId && !subfamilies.value.some(s => s.value === form.value.subfamilyId)) {
      form.value.subfamilyId = null
    }
  } catch (e) {
    subfamilies.value = []
  } finally {
    loadingSubfamilies.value = false
  }
}

async function loadModels(brandId) {
  if (!brandId) {
    models.value = []
    form.value.productModelId = null
    return
  }
  loadingModels.value = true
  try {
    const res = await $api(`/brands/${brandId}/models`)
    models.value = (res.data || []).map(m => ({
      title: m.name,
      value: m.id,
    }))
    if (form.value.productModelId && !models.value.some(m => m.value === form.value.productModelId)) {
      form.value.productModelId = null
    }
  } catch (e) {
    models.value = []
  } finally {
    loadingModels.value = false
  }
}

watch(() => props.isDrawerOpen, async open => {
  if (!open) return
  await Promise.all([fetchCategories(), fetchBrands()])
  
  if (props.product) {
    const hwDays = Number(props.product.warranty_hardware_days ?? props.product.warrantyHardwareDays ?? props.product.warranty_days ?? props.product.warrantyDays ?? 0)
    const swDays = Number(props.product.warranty_software_days ?? props.product.warrantySoftwareDays ?? 0)

    selectedHardwarePreset.value = [0, 15, 30, 90, 180, 365, 730].includes(hwDays) ? hwDays : 'custom'
    selectedSoftwarePreset.value = [0, 15, 30, 90, 180, 365, 730].includes(swDays) ? swDays : 'custom'

    coverPreview.value = props.product.imageUrl || props.product.image_url || null
    coverFile.value = null
    removeCover.value = false

    const existingG = props.product.galleryUrls || props.product.gallery_urls || []
    galleryPreviews.value = existingG.map((url, idx) => ({ id: 'exist_' + idx, url, isNew: false }))
    galleryFiles.value = []
    removeGallery.value = false
    replaceGallery.value = false

    form.value = {
      name: props.product.name || '',
      description: props.product.description || '',
      categoryId: props.product.categoryId,
      subfamilyId: props.product.subfamilyId || null,
      brandId: props.product.brandId || null,
      productModelId: props.product.productModelId || null,
      condition: props.product.condition || 'nuevo',
      sku: props.product.sku || '',
      costPrice: props.product.costPrice ?? '',
      salePrice: props.product.salePrice ?? '',
      stock: props.product.stock ?? 0,
      minStock: props.product.minStock ?? 0,
      warrantyDays: hwDays,
      warrantyHardwareDays: hwDays,
      warrantySoftwareDays: swDays,
    }

    if (props.product.categoryId) {
      await loadSubfamilies(props.product.categoryId)
    }
    if (props.product.brandId) {
      await loadModels(props.product.brandId)
    }
  } else {
    selectedHardwarePreset.value = 0
    selectedSoftwarePreset.value = 0
    coverPreview.value = null
    coverFile.value = null
    removeCover.value = false
    galleryPreviews.value = []
    galleryFiles.value = []
    removeGallery.value = false
    replaceGallery.value = false

    form.value = {
      name: '',
      description: '',
      categoryId: categories.value.length ? categories.value[0].value : null,
      subfamilyId: null,
      brandId: null,
      productModelId: null,
      condition: 'nuevo',
      sku: '',
      costPrice: '',
      salePrice: '',
      stock: 0,
      minStock: 0,
      warrantyDays: 0,
      warrantyHardwareDays: 0,
      warrantySoftwareDays: 0,
    }
    if (form.value.categoryId) {
      await loadSubfamilies(form.value.categoryId)
    }
    models.value = []
  }
  errors.value = {}
  error.value = ''
  nextTick(() => refForm.value?.resetValidation())
})

function onCategoryChange(catId) {
  loadSubfamilies(catId)
}

function onBrandChange(brandId) {
  loadModels(brandId)
}

function close() {
  if (!busy.value) emit('update:isDrawerOpen', false)
}

async function save() {
  if (busy.value) return
  const { valid } = await refForm.value.validate()
  if (!valid) return

  busy.value = true
  error.value = ''
  errors.value = {}

  // Si el usuario ingresó un modelo nuevo al vuelo que es texto y no ID
  let resolvedModelId = form.value.productModelId
  if (typeof resolvedModelId === 'string' && resolvedModelId.trim() && form.value.brandId) {
    try {
      const quickRes = await $api('/product-models/quick-create', {
        method: 'POST',
        body: {
          brand_id: form.value.brandId,
          name: resolvedModelId.trim(),
        },
      })
      resolvedModelId = quickRes.data.id
    } catch (e) {
      error.value = 'No se pudo registrar el nuevo modelo al vuelo.'
      busy.value = false
      return
    }
  }

  const formData = new FormData()
  formData.append('name', form.value.name.trim())
  if (form.value.description) formData.append('description', form.value.description.trim())
  formData.append('categoryId', form.value.categoryId)
  if (form.value.subfamilyId) formData.append('subfamilyId', form.value.subfamilyId)
  if (form.value.brandId) formData.append('brandId', form.value.brandId)
  if (resolvedModelId) formData.append('productModelId', resolvedModelId)
  formData.append('condition', form.value.condition || 'nuevo')
  formData.append('costPrice', parseFloat(form.value.costPrice) || 0)
  formData.append('salePrice', parseFloat(form.value.salePrice) || 0)
  formData.append('minStock', parseInt(form.value.minStock, 10) || 0)
  const hwDays = parseInt(form.value.warrantyHardwareDays, 10) || parseInt(form.value.warrantyDays, 10) || 0
  const swDays = parseInt(form.value.warrantySoftwareDays, 10) || 0
  formData.append('warrantyDays', hwDays)
  formData.append('warrantyHardwareDays', hwDays)
  formData.append('warrantySoftwareDays', swDays)

  if (form.value.sku && form.value.sku.trim()) {
    formData.append('sku', form.value.sku.trim())
  }

  if (!props.product) {
    formData.append('stock', parseInt(form.value.stock, 10) || 0)
  }

  if (coverFile.value) {
    formData.append('image', coverFile.value)
  } else if (removeCover.value) {
    formData.append('remove_image', '1')
  }

  if (galleryFiles.value.length > 0) {
    galleryFiles.value.forEach(file => {
      formData.append('gallery[]', file)
    })
    if (replaceGallery.value) {
      formData.append('replace_gallery', '1')
    }
  } else if (removeGallery.value) {
    formData.append('remove_gallery', '1')
  }

  try {
    const url = props.product ? `/products/${props.product.id}` : '/products'
    if (props.product) {
      formData.append('_method', 'PUT')
    }
    await $api(url, {
      method: 'POST',
      body: formData,
    })
    emit('saved')
    emit('update:isDrawerOpen', false)
  } catch (failure) {
    errors.value = failure.data?.errors || {}
    error.value = apiError(failure)
  } finally {
    busy.value = false
  }
}

</script>

<template>
  <VNavigationDrawer
    temporary
    :width="460"
    location="end"
    class="scrollable-content"
    :model-value="isDrawerOpen"
    :persistent="busy"
    @update:model-value="val => !val && close()"
  >
    <AppDrawerHeaderSection
      :title="product ? 'Editar producto' : 'Nuevo producto'"
      @cancel="close"
    />
    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VAlert
            v-if="error"
            type="error"
            variant="tonal"
            class="mb-4"
            role="alert"
          >
            {{ error }}
          </VAlert>

          <VForm
            ref="refForm"
            @submit.prevent="save"
          >
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="form.name"
                  label="Nombre del producto *"
                  :rules="[required]"
                  maxlength="200"
                  :error-messages="errors.name"
                  :disabled="busy"
                  placeholder="Ej. Laptop HP 15-ef2xxx"
                  autofocus
                />
              </VCol>

              <!-- Categoría y Subfamilia en cascada -->
              <VCol
                cols="12"
                sm="6"
              >
                <VSelect
                  v-model="form.categoryId"
                  label="Categoría / Familia *"
                  :items="categories"
                  :rules="[required]"
                  :error-messages="errors.categoryId"
                  :disabled="busy"
                  @update:model-value="onCategoryChange"
                />
              </VCol>

              <VCol
                cols="12"
                sm="6"
              >
                <VSelect
                  v-model="form.subfamilyId"
                  label="Subfamilia"
                  :items="subfamilies"
                  :disabled="busy || loadingSubfamilies || !subfamilies.length"
                  :loading="loadingSubfamilies"
                  clearable
                  placeholder="Opcional"
                  :hint="!subfamilies.length && form.categoryId ? 'Esta categoría no tiene subfamilias' : ''"
                  persistent-hint
                />
              </VCol>

              <!-- Marca y Modelo al vuelo -->
              <VCol
                cols="12"
                sm="6"
              >
                <VSelect
                  v-model="form.brandId"
                  label="Marca"
                  :items="brands"
                  clearable
                  :disabled="busy"
                  placeholder="Seleccionar marca"
                  @update:model-value="onBrandChange"
                />
              </VCol>

              <VCol
                cols="12"
                sm="6"
              >
                <VCombobox
                  v-model="form.productModelId"
                  label="Modelo"
                  :items="models"
                  :disabled="busy || !form.brandId || loadingModels"
                  :loading="loadingModels"
                  clearable
                  placeholder="Seleccionar o escribir nuevo"
                  hint="Puedes tipear un modelo no listado para crearlo al vuelo"
                  persistent-hint
                />
              </VCol>

              <!-- Condición comercial obligatoria -->
              <VCol cols="12">
                <VSelect
                  v-model="form.condition"
                  label="Condición comercial *"
                  :items="conditions"
                  :rules="[required]"
                  :disabled="busy"
                  hint="Estado físico y comercial del producto"
                  persistent-hint
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="form.sku"
                  label="Código SKU"
                  maxlength="50"
                  :error-messages="errors.sku"
                  :disabled="busy"
                  placeholder="Dejar vacío para auto-generar (ej. LAP-0001)"
                  hint="Identificador único de inventario. Si se omite, se generará correlativo automáticamente."
                  persistent-hint
                />
              </VCol>

              <!-- Costo y Venta en Bs. -->
              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model="form.costPrice"
                  label="Costo compra (Bs.) *"
                  type="number"
                  step="0.01"
                  min="0"
                  :rules="[required, positiveNumber]"
                  :error-messages="errors.costPrice"
                  :disabled="busy"
                  prefix="Bs."
                />
              </VCol>

              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model="form.salePrice"
                  label="Precio venta (Bs.) *"
                  type="number"
                  step="0.01"
                  min="0"
                  :rules="[required, positiveNumber]"
                  :error-messages="errors.salePrice"
                  :disabled="busy"
                  prefix="Bs."
                />
              </VCol>

              <!-- Indicador de Margen de Ganancia -->
              <VCol
                v-if="form.costPrice && form.salePrice"
                cols="12"
              >
                <VAlert
                  :type="isNegativeMargin ? 'warning' : 'info'"
                  variant="tonal"
                  class="py-2"
                >
                  <div class="d-flex justify-space-between align-center">
                    <span>Margen estimado:</span>
                    <strong>Bs. {{ marginBs }} ({{ marginPct }}%)</strong>
                  </div>
                  <div
                    v-if="isNegativeMargin"
                    class="text-caption mt-1"
                  >
                    ⚠️ El precio de venta es inferior al costo de adquisición.
                  </div>
                </VAlert>
              </VCol>

              <!-- Stock inicial (solo creación) y Stock mínimo -->
              <VCol
                v-if="!product"
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model.number="form.stock"
                  label="Stock inicial"
                  type="number"
                  min="0"
                  :rules="[positiveNumber]"
                  :error-messages="errors.stock"
                  :disabled="busy"
                  hint="Solo configurable al crear"
                  persistent-hint
                />
              </VCol>

              <VCol
                cols="12"
                :sm="product ? 12 : 6"
              >
                <VTextField
                  v-model.number="form.minStock"
                  label="Stock mínimo alerta"
                  type="number"
                  min="0"
                  :rules="[positiveNumber]"
                  :error-messages="errors.minStock"
                  :disabled="busy"
                  hint="Umbral para alerta de stock bajo"
                  persistent-hint
                />
              </VCol>

              <!-- 1. Garantía de Hardware (Física) -->
              <VCol
                cols="12"
                :sm="selectedHardwarePreset === 'custom' ? 6 : 12"
              >
                <VSelect
                  v-model="selectedHardwarePreset"
                  :items="warrantyPresets"
                  label="Garantía de Hardware (Física) *"
                  prepend-inner-icon="ri-shield-keyhole-line"
                  :disabled="busy"
                  hint="Cubre fallas de fábrica y componentes físicos"
                  persistent-hint
                  @update:model-value="onHardwarePresetChange"
                />
              </VCol>

              <VCol
                v-if="selectedHardwarePreset === 'custom'"
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model.number="form.warrantyHardwareDays"
                  label="Días Hardware personalizados *"
                  type="number"
                  min="0"
                  suffix="días"
                  :rules="[positiveNumber]"
                  :disabled="busy"
                  @update:model-value="val => form.warrantyDays = Number(val)"
                />
              </VCol>

              <!-- 2. Garantía de Software (Soporte Lógico) -->
              <VCol
                cols="12"
                :sm="selectedSoftwarePreset === 'custom' ? 6 : 12"
              >
                <VSelect
                  v-model="selectedSoftwarePreset"
                  :items="warrantyPresets"
                  label="Garantía de Software (Soporte Lógico) *"
                  prepend-inner-icon="ri-computer-line"
                  :disabled="busy"
                  hint="Cubre sistema operativo, drivers y configuración"
                  persistent-hint
                  @update:model-value="onSoftwarePresetChange"
                />
              </VCol>

              <VCol
                v-if="selectedSoftwarePreset === 'custom'"
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model.number="form.warrantySoftwareDays"
                  label="Días Software personalizados *"
                  type="number"
                  min="0"
                  suffix="días"
                  :rules="[positiveNumber]"
                  :disabled="busy"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="form.description"
                  label="Descripción y especificaciones"
                  maxlength="2000"
                  rows="3"
                  counter
                  :error-messages="errors.description"
                  :disabled="busy"
                  placeholder="Detalles técnicos, garantía, componentes..."
                />
              </VCol>

              <!-- Fotografía de Portada -->
              <VCol cols="12">
                <div class="text-subtitle-2 font-weight-medium mb-1">
                  Fotografía de Portada
                </div>
                <div class="text-caption text-medium-emphasis mb-2">
                  Imagen principal del producto para el catálogo y la vitrina pública.
                </div>

                <input
                  ref="coverInputRef"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  class="d-none"
                  @change="onCoverChange"
                >

                <div
                  v-if="coverPreview"
                  class="d-flex align-center gap-3 pa-3 rounded border"
                >
                  <VImg
                    :src="coverPreview"
                    width="80"
                    height="80"
                    cover
                    class="rounded border bg-light"
                  />
                  <div class="flex-grow-1">
                    <div class="text-body-2 font-weight-medium">
                      {{ coverFile ? coverFile.name : 'Foto de portada actual' }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ coverFile ? (coverFile.size / 1024).toFixed(0) + ' KB' : 'Cargada previamente' }}
                    </div>
                    <div class="d-flex gap-2 mt-2">
                      <VBtn
                        size="small"
                        variant="tonal"
                        color="primary"
                        prepend-icon="ri-upload-2-line"
                        @click="triggerCoverInput"
                      >
                        Cambiar
                      </VBtn>
                      <VBtn
                        size="small"
                        variant="tonal"
                        color="error"
                        prepend-icon="ri-delete-bin-line"
                        @click="removeCoverPhoto"
                      >
                        Quitar
                      </VBtn>
                    </div>
                  </div>
                </div>

                <div
                  v-else
                  class="border-dashed pa-4 rounded text-center cursor-pointer hover-bg"
                  @click="triggerCoverInput"
                >
                  <VIcon
                    icon="ri-image-add-line"
                    size="32"
                    color="primary"
                    class="mb-1"
                  />
                  <div class="text-body-2 font-weight-medium text-primary">
                    Haz clic para subir la foto de portada
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Formatos JPG, PNG o WebP (hasta 5 MB)
                  </div>
                </div>
              </VCol>

              <!-- Galería Multi-Ángulo 360° (TikTok Live / E-Commerce) -->
              <VCol cols="12">
                <VCard
                  variant="outlined"
                  class="pa-3 border"
                >
                  <div class="d-flex justify-space-between align-center mb-1">
                    <div class="d-flex align-center gap-2">
                      <VIcon
                        icon="ri-rotate-lock-line"
                        color="secondary"
                      />
                      <span class="text-subtitle-2 font-weight-bold">
                        Vitrina 360° Interactiva
                      </span>
                    </div>
                    <VChip
                      size="x-small"
                      color="primary"
                      variant="elevated"
                    >
                      TikTok Live
                    </VChip>
                  </div>

                  <div class="text-caption text-medium-emphasis mb-3">
                    Sube entre 2 y 8 fotos rotando el equipo (frente, teclado, laterales, tapa) para activar el giro táctil 360° en la vitrina pública.
                  </div>

                  <input
                    ref="galleryInputRef"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    class="d-none"
                    @change="onGalleryChange"
                  >

                  <!-- Preview Grid de las fotos 360 -->
                  <div
                    v-if="galleryPreviews.length"
                    class="d-flex flex-wrap gap-2 mb-3"
                  >
                    <div
                      v-for="(item, idx) in galleryPreviews"
                      :key="item.id || idx"
                      class="position-relative rounded border overflow-hidden"
                      style="width: 72px; height: 72px;"
                    >
                      <VImg
                        :src="item.url"
                        width="72"
                        height="72"
                        cover
                      />
                      <div
                        class="position-absolute bg-black bg-opacity-75 text-white text-caption px-1"
                        style="bottom: 0; left: 0; right: 0; font-size: 10px; text-align: center;"
                      >
                        Ángulo {{ idx + 1 }}
                      </div>
                      <VBtn
                        icon="ri-close-line"
                        size="18"
                        color="error"
                        variant="flat"
                        class="position-absolute"
                        style="top: 2px; right: 2px; border-radius: 50%; min-width: 18px; width: 18px; height: 18px; padding: 0;"
                        @click.stop="removeGalleryPhoto(idx)"
                      />
                    </div>
                  </div>

                  <div class="d-flex gap-2">
                    <VBtn
                      v-if="galleryPreviews.length < 8"
                      size="small"
                      variant="tonal"
                      color="secondary"
                      prepend-icon="ri-camera-lens-line"
                      @click="triggerGalleryInput"
                    >
                      {{ galleryPreviews.length ? 'Agregar más fotos (' + galleryPreviews.length + '/8)' : 'Cargar secuencia 360°' }}
                    </VBtn>
                    <VBtn
                      v-if="galleryPreviews.length"
                      size="small"
                      variant="text"
                      color="error"
                      @click="clearGalleryPhotos"
                    >
                      Limpiar galería
                    </VBtn>
                  </div>
                </VCard>
              </VCol>


              <VCol
                cols="12"
                class="d-flex gap-3"
              >
                <VBtn
                  type="submit"
                  :loading="busy"
                >
                  Guardar
                </VBtn>
                <VBtn
                  variant="outlined"
                  :disabled="busy"
                  @click="close"
                >
                  Cancelar
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
