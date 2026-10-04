<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  brand: { type: Object, default: null },
})

const emit = defineEmits(['update:isDrawerOpen', 'saved'])
const form = ref({ name: '', is_active: true })
const busy = ref(false)
const errors = ref({})
const error = ref('')
const refForm = ref()

const required = value => !!String(value || '').trim() || 'El nombre de la marca es obligatorio.'

watch(() => props.isDrawerOpen, open => {
  if (!open) return
  form.value = {
    name: props.brand?.name || '',
    is_active: props.brand?.is_active ?? true,
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
    is_active: form.value.is_active,
  }

  try {
    const url = props.brand ? `/brands/${props.brand.id}` : '/brands'
    const method = props.brand ? 'PUT' : 'POST'
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
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="isDrawerOpen"
    :persistent="busy"
    @update:model-value="value => !value && close()"
  >
    <AppDrawerHeaderSection
      :title="brand ? 'Editar Marca' : 'Nueva Marca'"
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
                  label="Nombre de Marca *"
                  :rules="[required]"
                  maxlength="100"
                  :error-messages="errors.name"
                  :disabled="busy"
                  placeholder="Ej. ASUS, Kingston, Logitech..."
                  autofocus
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="form.is_active"
                  label="Marca activa para el catálogo"
                  :disabled="busy"
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
