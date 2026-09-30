<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { $api, apiError } from '@/utils/api'

// Adaptado de admin-full-version/src/views/apps/ecommerce/EcommerceAddCategoryDrawer.vue
const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  category: { type: Object, default: null },
})

const emit = defineEmits(['update:isDrawerOpen', 'saved'])
const form = ref({ name: '', description: '' })
const busy = ref(false)
const errors = ref({})
const error = ref('')
const refForm = ref()

const required = value => !!String(value || '').trim() || 'El nombre de la categoría es obligatorio.'

watch(() => props.isDrawerOpen, open => {
  if (!open) return
  form.value = {
    name: props.category?.name || '',
    description: props.category?.description || '',
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
  }

  try {
    const url = props.category ? `/categories/${props.category.id}` : '/categories'
    const method = props.category ? 'PUT' : 'POST'
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
    :width="420"
    location="end"
    class="scrollable-content"
    :model-value="isDrawerOpen"
    :persistent="busy"
    @update:model-value="value => !value && close()"
  >
    <AppDrawerHeaderSection
      :title="category ? 'Editar categoría' : 'Nueva categoría'"
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
                  label="Nombre de categoría *"
                  :rules="[required]"
                  maxlength="100"
                  :error-messages="errors.name"
                  :disabled="busy"
                  placeholder="Ej. Laptops, Componentes..."
                  autofocus
                />
              </VCol>
              <VCol cols="12">
                <VTextarea
                  v-model="form.description"
                  label="Descripción (opcional)"
                  maxlength="500"
                  rows="3"
                  counter
                  :error-messages="errors.description"
                  :disabled="busy"
                  placeholder="Detalles sobre los productos de esta categoría"
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
