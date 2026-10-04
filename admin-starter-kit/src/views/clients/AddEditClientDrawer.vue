<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { $api, apiError } from '@/utils/api'
import { requiredValidator, emailValidator } from '@core/utils/validators'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  client: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'saved',
])

const isFormValid = ref(false)
const refForm = ref()
const loading = ref(false)
const errorMessage = ref('')
const duplicateWarning = ref('')

const clientTypeOptions = [
  { title: 'Consumidor Final', value: 'final' },
  { title: 'Técnico / Mayorista', value: 'mayorista' },
  { title: 'Empresa / Corporativo', value: 'empresa' },
  { title: 'Institución Pública / Educativa', value: 'institucion' },
]

const bolivianCities = [
  'Trinidad',
  'Santa Cruz',
  'La Paz',
  'Cochabamba',
  'Riberalta',
  'Guayaramerín',
  'San Borja',
  'Rurrenabaque',
  'Cobija',
  'Sucre',
  'Tarija',
  'Potosí',
  'Oruro',
]

const formData = ref({
  name: '',
  nit_ci: '',
  phone: '',
  email: '',
  address: '',
  city: 'Trinidad',
  client_type: 'final',
  notes: '',
})

watch(() => props.client, client => {
  duplicateWarning.value = ''
  errorMessage.value = ''
  if (client) {
    formData.value = {
      name: client.name || '',
      nit_ci: client.nit_ci || '',
      phone: client.phone || '',
      email: client.email || '',
      address: client.address || '',
      city: client.city || 'Trinidad',
      client_type: client.client_type || 'final',
      notes: client.notes || '',
    }
  } else {
    formData.value = {
      name: '',
      nit_ci: '',
      phone: '',
      email: '',
      address: '',
      city: 'Trinidad',
      client_type: 'final',
      notes: '',
    }
  }
}, { immediate: true })

let duplicateDebounce = null
const checkDuplicate = () => {
  clearTimeout(duplicateDebounce)
  duplicateWarning.value = ''
  const term = (formData.value.nit_ci || formData.value.phone || '').trim()
  if (term.length < 5) return

  duplicateDebounce = setTimeout(async () => {
    try {
      const res = await $api('/clients/search', { params: { q: term } })
      const found = (res.data || res || []).find(c => 
        (!props.client || c.id !== props.client.id) &&
        ((formData.value.nit_ci && c.nit_ci === formData.value.nit_ci.trim()) ||
         (formData.value.phone && c.phone === formData.value.phone.trim()))
      )
      if (found) {
        duplicateWarning.value = `Posible duplicado: "${found.name}" ya está registrado con NIT/CI: ${found.nit_ci || 'N/A'} o Tel: ${found.phone || 'N/A'}`
      }
    } catch {
      // Ignore background duplicate check failure
    }
  }, 400)
}

const closeDrawer = () => {
  emit('update:isDrawerOpen', false)
  errorMessage.value = ''
  duplicateWarning.value = ''
  nextTick(() => {
    refForm.value?.resetValidation()
  })
}

const onSubmit = async () => {
  const { valid } = await refForm.value?.validate()
  if (!valid) return

  loading.value = true
  errorMessage.value = ''

  try {
    const payload = {
      name: formData.value.name.trim(),
      nit_ci: formData.value.nit_ci?.trim() || null,
      phone: formData.value.phone?.trim() || null,
      email: formData.value.email?.trim() || null,
      address: formData.value.address?.trim() || null,
      city: formData.value.city?.trim() || 'Trinidad',
      client_type: formData.value.client_type,
      notes: formData.value.notes?.trim() || null,
    }

    if (props.client?.id) {
      await $api(`/clients/${props.client.id}`, {
        method: 'PUT',
        body: payload,
      })
    } else {
      await $api('/clients', {
        method: 'POST',
        body: payload,
      })
    }

    emit('saved')
    closeDrawer()
  } catch (err) {
    errorMessage.value = apiError(err)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="440"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="val => emit('update:isDrawerOpen', val)"
  >
    <!-- 👉 Drawer Header -->
    <AppDrawerHeaderSection
      :title="props.client ? 'Editar Cliente' : 'Nuevo Cliente'"
      @cancel="closeDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <!-- Alerta de Error -->
          <VAlert
            v-if="errorMessage"
            type="error"
            variant="tonal"
            closable
            class="mb-4"
            @click:close="errorMessage = ''"
          >
            {{ errorMessage }}
          </VAlert>

          <!-- Advertencia de Duplicado -->
          <VAlert
            v-if="duplicateWarning"
            type="warning"
            variant="tonal"
            density="compact"
            icon="ri-alert-line"
            class="mb-4 text-caption"
          >
            {{ duplicateWarning }}
          </VAlert>

          <!-- Formulario -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- Nombre / Razón Social -->
              <VCol cols="12">
                <VTextField
                  v-model="formData.name"
                  :rules="[requiredValidator]"
                  label="Nombre / Razón Social *"
                  placeholder="Ej: Comercial Mamoré o Carlos Perez"
                  prepend-inner-icon="ri-user-line"
                />
              </VCol>

              <!-- Tipo de Cliente -->
              <VCol cols="12">
                <VSelect
                  v-model="formData.client_type"
                  :items="clientTypeOptions"
                  label="Segmento de Cliente *"
                  placeholder="Selecciona tipo de cliente"
                  prepend-inner-icon="ri-price-tag-3-line"
                />
              </VCol>

              <!-- NIT / CI -->
              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model="formData.nit_ci"
                  label="NIT / CI"
                  placeholder="Ej: 1029384756"
                  prepend-inner-icon="ri-id-card-line"
                  @input="checkDuplicate"
                />
              </VCol>

              <!-- Teléfono / Celular -->
              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model="formData.phone"
                  label="Celular / WhatsApp"
                  placeholder="Ej: 71234567"
                  prepend-inner-icon="ri-whatsapp-line"
                  @input="checkDuplicate"
                />
              </VCol>

              <!-- Email -->
              <VCol cols="12">
                <VTextField
                  v-model="formData.email"
                  :rules="formData.email ? [emailValidator] : []"
                  label="Correo Electrónico"
                  placeholder="contacto@ejemplo.bo"
                  prepend-inner-icon="ri-mail-line"
                />
              </VCol>

              <!-- Ciudad -->
              <VCol
                cols="12"
                sm="6"
              >
                <VCombobox
                  v-model="formData.city"
                  :items="bolivianCities"
                  label="Ciudad"
                  placeholder="Trinidad"
                  prepend-inner-icon="ri-map-pin-line"
                />
              </VCol>

              <!-- Dirección -->
              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model="formData.address"
                  label="Dirección"
                  placeholder="Calle / Barrio"
                  prepend-inner-icon="ri-building-line"
                />
              </VCol>

              <!-- Notas Comerciales -->
              <VCol cols="12">
                <VTextarea
                  v-model="formData.notes"
                  label="Notas Comerciales / Preferencias"
                  placeholder="Condiciones de crédito, persona de contacto para cobranza, horarios de entrega..."
                  rows="3"
                  prepend-inner-icon="ri-file-text-line"
                />
              </VCol>

              <!-- Botones de Acción -->
              <VCol
                cols="12"
                class="d-flex gap-4 pt-2"
              >
                <VBtn
                  type="submit"
                  :loading="loading"
                  prepend-icon="ri-save-line"
                >
                  {{ props.client ? 'Guardar Cambios' : 'Registrar Cliente' }}
                </VBtn>
                <VBtn
                  variant="outlined"
                  color="secondary"
                  :disabled="loading"
                  @click="closeDrawer"
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
