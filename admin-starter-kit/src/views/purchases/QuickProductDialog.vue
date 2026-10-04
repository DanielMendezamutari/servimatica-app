<script setup>
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
})

const emit = defineEmits(['update:isDialogOpen', 'created'])

const form = ref({
  name: '',
  sku: '',
  category_id: null,
  brand_id: null,
  cost_price: '',
  sale_price: '',
})

const categories = ref([])
const brands = ref([])
const busy = ref(false)
const errors = ref({})
const error = ref('')
const refForm = ref()

const required = value => !!String(value || '').trim() || 'Este campo es obligatorio.'

async function loadOptions() {
  try {
    const [catsRes, brandsRes] = await Promise.all([
      $api('/categories/options'),
      $api('/brands/options'),
    ])
    categories.value = catsRes.data || []
    brands.value = brandsRes.data || []
  } catch (e) {
    console.error('Error loading options for quick product dialog', e)
  }
}

watch(() => props.isDialogOpen, open => {
  if (!open) return
  form.value = {
    name: '',
    sku: '',
    category_id: null,
    brand_id: null,
    cost_price: '',
    sale_price: '',
  }
  errors.value = {}
  error.value = ''
  loadOptions()
  nextTick(() => refForm.value?.resetValidation())
})

function close() {
  if (!busy.value) emit('update:isDialogOpen', false)
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
    sku: form.value.sku?.trim() || null,
    category_id: form.value.category_id,
    brand_id: form.value.brand_id || null,
    cost_price: parseFloat(form.value.cost_price) || 0,
    sale_price: parseFloat(form.value.sale_price) || 0,
    stock: 0,
    min_stock: 1,
    condition: 'nuevo',
  }

  try {
    const res = await $api('/products', { method: 'POST', body })
    emit('created', res.data)
    emit('update:isDialogOpen', false)
  } catch (failure) {
    errors.value = failure.data?.errors || {}
    error.value = apiError(failure)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <VDialog
    :model-value="isDialogOpen"
    max-width="580"
    :persistent="busy"
    @update:model-value="val => !val && close()"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span class="text-h6 font-weight-bold">
          <VIcon icon="ri-add-box-line" class="me-2 text-primary" />
          Alta Rápida de Producto
        </span>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          :disabled="busy"
          @click="close"
        />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-4">
        <p class="text-caption text-medium-emphasis mb-3">
          Registre un nuevo artículo en el catálogo para incluirlo inmediatamente en la factura de compra.
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

        <VForm
          ref="refForm"
          @submit.prevent="save"
        >
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="form.name"
                label="Nombre del Producto *"
                :rules="[required]"
                maxlength="200"
                :error-messages="errors.name"
                :disabled="busy"
                placeholder="Ej. Disco SSD Kingston 960GB"
                autofocus
              />
            </VCol>

            <VCol cols="12" md="6">
              <VSelect
                v-model="form.category_id"
                label="Categoría *"
                :items="categories"
                item-title="name"
                item-value="id"
                :rules="[required]"
                :error-messages="errors.categoryId"
                :disabled="busy"
                placeholder="Seleccione categoría"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VSelect
                v-model="form.brand_id"
                label="Marca"
                :items="brands"
                item-title="name"
                item-value="id"
                :error-messages="errors.brandId"
                :disabled="busy"
                clearable
                placeholder="Opcional"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="form.sku"
                label="SKU / Cód. Barras"
                maxlength="50"
                :error-messages="errors.sku"
                :disabled="busy"
                placeholder="Ej. SSD-960G"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="form.cost_price"
                label="Costo Compra (Bs.) *"
                type="number"
                step="0.01"
                min="0"
                :rules="[required]"
                :error-messages="errors.costPrice"
                :disabled="busy"
                prefix="Bs."
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="form.sale_price"
                label="Precio Venta (Bs.) *"
                type="number"
                step="0.01"
                min="0"
                :rules="[required]"
                :error-messages="errors.salePrice"
                :disabled="busy"
                prefix="Bs."
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4 justify-end">
        <VBtn
          variant="outlined"
          :disabled="busy"
          @click="close"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="primary"
          :loading="busy"
          @click="save"
        >
          Registrar e Incluir
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
