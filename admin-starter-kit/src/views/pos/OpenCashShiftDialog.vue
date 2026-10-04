<script setup>
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
})

const emit = defineEmits(['update:isDialogOpen', 'shiftOpened'])

const form = ref({
  opening_amount: '',
  notes: '',
})

const busy = ref(false)
const error = ref('')
const refForm = ref()

watch(() => props.isDialogOpen, open => {
  if (!open) return
  form.value = {
    opening_amount: '100.00',
    notes: '',
  }
  error.value = ''
  nextTick(() => refForm.value?.resetValidation())
})

function close() {
  if (!busy.value) emit('update:isDialogOpen', false)
}

async function submitOpen() {
  if (busy.value) return
  const { valid } = await refForm.value.validate()
  if (!valid) return

  busy.value = true
  error.value = ''

  try {
    const res = await $api('/cash-shifts/open', {
      method: 'POST',
      body: {
        opening_amount: parseFloat(form.value.opening_amount) || 0,
        notes: form.value.notes?.trim() || null,
      },
    })

    emit('shiftOpened', res.data)
    emit('update:isDialogOpen', false)
  } catch (e) {
    error.value = apiError(e, 'No se pudo abrir el turno de caja.')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    max-width="500"
    persistent
    @update:model-value="close"
  >
    <VCard class="pa-2">
      <VCardTitle class="d-flex align-center justify-space-between pb-2">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" rounded size="38">
            <VIcon icon="ri-money-dollar-box-line" size="24" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-bold">Apertura de Caja</div>
            <div class="text-caption text-medium-emphasis">Inicio de turno de ventas en mostrador</div>
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

        <p class="text-body-2 text-medium-emphasis mb-4">
          Ingrese el monto físico inicial en Bolivianos (Bs.) asignado como fondo para cambio o sencillo en el cajón de dinero.
        </p>

        <VForm ref="refForm" @submit.prevent="submitOpen">
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="form.opening_amount"
                label="Fondo Inicial (Bs.)"
                placeholder="100.00"
                type="number"
                step="0.50"
                min="0"
                prefix="Bs."
                variant="outlined"
                autofocus
                :rules="[
                  v => !!v || 'El monto inicial es obligatorio',
                  v => parseFloat(v) >= 0 || 'El monto no puede ser negativo',
                ]"
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="form.notes"
                label="Observaciones (Opcional)"
                placeholder="Ej. Billetes de corte menor y monedas para cambio..."
                rows="2"
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
          prepend-icon="ri-lock-unlock-line"
          @click="submitOpen"
        >
          Abrir Turno de Caja
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
