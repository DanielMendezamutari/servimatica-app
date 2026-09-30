<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { $api, apiError } from '@/utils/api'

// Adaptado de los drawers del catálogo admin-full-version
const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  product: { type: Object, default: null },
})

const emit = defineEmits(['update:isDrawerOpen', 'saved'])

const form = ref({
  name: '',
  description: '',
  categoryId: null,
  sku: '',
  costPrice: '',
  salePrice: '',
  stock: 0,
  minStock: 0,
})

const categories = ref([])
const busy = ref(false)
const errors = ref({})
const error = ref('')
const refForm = ref()

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
    const res = await $api('/categories')
    categories.value = (res.data || []).map(c => ({
      title: c.name,
      value: c.id,
      status: c.status,
    }))
  } catch (e) {
    // Silently fail or handled on retry
  }
}

watch(() => props.isDrawerOpen, async open => {
  if (!open) return
  await fetchCategories()
  if (props.product) {
    form.value = {
      name: props.product.name || '',
      description: props.product.description || '',
      categoryId: props.product.categoryId,
      sku: props.product.sku || '',
      costPrice: props.product.costPrice ?? '',
      salePrice: props.product.salePrice ?? '',
      stock: props.product.stock ?? 0,
      minStock: props.product.minStock ?? 0,
    }
  } else {
    form.value = {
      name: '',
      description: '',
      categoryId: categories.value.length ? categories.value[0].value : null,
      sku: '',
      costPrice: '',
      salePrice: '',
      stock: 0,
      minStock: 0,
    }
  }
  errors.value = {}
  error.value = ''
  nextTick(() => refForm.value?.resetValidation())
})

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

  const body = {
    name: form.value.name.trim(),
    description: form.value.description ? form.value.description.trim() : null,
    categoryId: form.value.categoryId,
    costPrice: parseFloat(form.value.costPrice) || 0,
    salePrice: parseFloat(form.value.salePrice) || 0,
    minStock: parseInt(form.value.minStock, 10) || 0,
  }

  if (form.value.sku && form.value.sku.trim()) {
    body.sku = form.value.sku.trim()
  }

  if (!props.product) {
    body.stock = parseInt(form.value.stock, 10) || 0
  }

  try {
    const url = props.product ? `/products/${props.product.id}` : '/products'
    const method = props.product ? 'PUT' : 'POST'
    await $api(url, { method, body })
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

              <VCol cols="12">
                <VSelect
                  v-model="form.categoryId"
                  label="Categoría *"
                  :items="categories"
                  :rules="[required]"
                  :error-messages="errors.categoryId"
                  :disabled="busy"
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
