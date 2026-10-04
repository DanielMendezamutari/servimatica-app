<script setup>
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
})

const emit = defineEmits(['update:isDialogOpen', 'clientCreated'])

const form = ref({
  name: '',
  nit_ci: '',
  phone: '',
  email: '',
  address: '',
})

const busy = ref(false)
const error = ref('')
const refForm = ref()

watch(() => props.isDialogOpen, open => {
  if (!open) return
  form.value = {
    name: '',
    nit_ci: '',
    phone: '',
    email: '',
    address: '',
  }
  error.value = ''
  nextTick(() => refForm.value?.resetValidation())
})

function close() {
  if (!busy.value) emit('update:isDialogOpen', false)
}

async function submitCreate() {
  if (busy.value) return
  const { valid } = await refForm.value.validate()
  if (!valid) return

  busy.value = true
  error.value = ''

  try {
    const res = await $api('/clients', {
      method: 'POST',
      body: {
        name: form.value.name.trim(),
        nit_ci: form.value.nit_ci?.trim() || null,
        phone: form.value.phone?.trim() || null,
        email: form.value.email?.trim() || null,
        address: form.value.address?.trim() || null,
      },
    })

    emit('clientCreated', res.data)
    emit('update:isDialogOpen', false)
  } catch (e) {
    error.value = apiError(e, 'No se pudo registrar el cliente.')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    max-width="520"
    persistent
    @update:model-value="close"
  >
    <VCard class="pa-2">
      <VCardTitle class="d-flex align-center justify-space-between pb-2">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" rounded size="38">
            <VIcon icon="ri-user-add-line" size="24" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-bold">Nuevo Cliente</div>
            <div class="text-caption text-medium-emphasis">Registro rápido para ventas y cotizaciones</div>
          </div>
        </div>
        <VBtn
          icon
          variant="text"
          size="small"
          :disabled="busy"
          @click="close"
        >
          <VIcon icon="ri-close-line" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pt-4">
        <VAlert
          v-if="error"
          type="error"
          variant="tonal"
          closable
          class="mb-4"
          @click:close="error = ''"
        >
          {{ error }}
        </VAlert>

        <VForm ref="refForm" @submit.prevent="submitCreate">
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="form.name"
                label="Nombre / Razón Social *"
                placeholder="Ej. Juan Pérez o Empresa XYZ"
                variant="outlined"
                autofocus
                :rules="[v => !!v?.trim() || 'El nombre es obligatorio']"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="form.nit_ci"
                label="NIT o C.I."
                placeholder="Ej. 1029384019"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="form.phone"
                label="Teléfono / WhatsApp"
                placeholder="Ej. 77012345"
                variant="outlined"
                hint="Para envío directo de proformas"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="form.email"
                label="Correo Electrónico (Opcional)"
                placeholder="cliente@ejemplo.com"
                type="email"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="form.address"
                label="Dirección (Opcional)"
                placeholder="Av. Principal #123"
                variant="outlined"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4 d-flex justify-end gap-2">
        <VBtn
          variant="outlined"
          color="secondary"
          :disabled="busy"
          @click="close"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="primary"
          :loading="busy"
          prepend-icon="ri-check-line"
          @click="submitCreate"
        >
          Guardar Cliente
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
