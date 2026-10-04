<script setup>
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
  supplier: { type: Object, default: null },
})

const emit = defineEmits(['update:isDialogOpen', 'saved'])

const form = ref({
  name: '',
  nit: '',
  contact_name: '',
  phone: '',
  email: '',
  city: '',
  address: '',
  is_active: true,
})

const busy = ref(false)
const errors = ref({})
const error = ref('')
const refForm = ref()

const required = value => !!String(value || '').trim() || 'La razón social o nombre es obligatorio.'

watch(() => props.isDialogOpen, open => {
  if (!open) return
  form.value = {
    name: props.supplier?.name || '',
    nit: props.supplier?.nit || '',
    contact_name: props.supplier?.contact_name || '',
    phone: props.supplier?.phone || '',
    email: props.supplier?.email || '',
    city: props.supplier?.city || '',
    address: props.supplier?.address || '',
    is_active: props.supplier?.is_active ?? true,
  }
  errors.value = {}
  error.value = ''
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
    nit: form.value.nit?.trim() || null,
    contact_name: form.value.contact_name?.trim() || null,
    phone: form.value.phone?.trim() || null,
    email: form.value.email?.trim() || null,
    city: form.value.city?.trim() || null,
    address: form.value.address?.trim() || null,
    is_active: form.value.is_active,
  }

  try {
    const url = props.supplier ? `/suppliers/${props.supplier.id}` : '/suppliers'
    const method = props.supplier ? 'PUT' : 'POST'
    const res = await $api(url, { method, body })
    emit('saved', res.data)
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
    max-width="650"
    :persistent="busy"
    @update:model-value="val => !val && close()"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span class="text-h6 font-weight-bold">
          <VIcon icon="ri-truck-line" class="me-2 text-primary" />
          {{ supplier ? 'Editar Proveedor' : 'Nuevo Proveedor Mayorista' }}
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
        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          class="mb-4"
          role="alert"
          closable
        >
          {{ error }}
        </VAlert>

        <VForm
          ref="refForm"
          @submit.prevent="save"
        >
          <VRow>
            <VCol cols="12" md="8">
              <VTextField
                v-model="form.name"
                label="Razón Social / Distribuidor *"
                :rules="[required]"
                maxlength="150"
                :error-messages="errors.name"
                :disabled="busy"
                placeholder="Ej. Deltron Bolivia SRL, Intcomex..."
                autofocus
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="form.nit"
                label="NIT / Documento Fiscal"
                maxlength="30"
                :error-messages="errors.nit"
                :disabled="busy"
                placeholder="Ej. 1029384019"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="form.contact_name"
                label="Contacto / Ejecutivo de Cuenta"
                maxlength="100"
                :error-messages="errors.contact_name"
                :disabled="busy"
                placeholder="Ej. Lic. Mario Terán"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="form.phone"
                label="Teléfono / WhatsApp"
                maxlength="30"
                :error-messages="errors.phone"
                :disabled="busy"
                placeholder="Ej. 77123456"
              />
            </VCol>

            <VCol cols="12" md="7">
              <VTextField
                v-model="form.email"
                label="Correo Electrónico"
                maxlength="100"
                :error-messages="errors.email"
                :disabled="busy"
                placeholder="ventas@proveedor.bo"
              />
            </VCol>

            <VCol cols="12" md="5">
              <VTextField
                v-model="form.city"
                label="Ciudad"
                maxlength="50"
                :error-messages="errors.city"
                :disabled="busy"
                placeholder="Ej. Santa Cruz, La Paz..."
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="form.address"
                label="Dirección de Almacén Central"
                maxlength="255"
                :error-messages="errors.address"
                :disabled="busy"
                placeholder="Av. Banzer 4to Anillo..."
              />
            </VCol>

            <VCol cols="12" v-if="supplier">
              <VSwitch
                v-model="form.is_active"
                label="Proveedor activo para recepción de compras"
                :disabled="busy"
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
          Guardar Proveedor
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
