<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
  paymentMethod: { type: Object, default: null },
})

const emit = defineEmits(['update:isDialogOpen', 'saved'])

const isEdit = computed(() => !!props.paymentMethod?.id)

const form = ref({
  name: '',
  type: 'qr',
  bank_name: '',
  account_number: '',
  account_holder: '',
  requires_reference: false,
  applies_to: 'both',
  sort_order: 0,
  is_active: true,
})

const qrFile = ref(null)
const qrPreview = ref('')
const busy = ref(false)
const errors = ref({})
const error = ref('')
const refForm = ref()

const typeOptions = [
  { title: 'Código QR (Simple / Billetera)', value: 'qr' },
  { title: 'Efectivo en Gaveta', value: 'cash' },
  { title: 'Transferencia Bancaria', value: 'bank_transfer' },
  { title: 'Tarjeta (Débito / Crédito)', value: 'card' },
  { title: 'Otro', value: 'other' },
]

const appliesToOptions = [
  { title: 'Ventas y Compras (Ambos)', value: 'both' },
  { title: 'Solo Ventas en Tienda (POS)', value: 'sales' },
  { title: 'Solo Compras a Proveedores', value: 'purchases' },
]

const showBankFields = computed(() => ['qr', 'bank_transfer', 'card'].includes(form.value.type))
const showQrUpload = computed(() => form.value.type === 'qr')

const required = value => !!String(value || '').trim() || 'Este campo es obligatorio.'

watch(() => props.isDialogOpen, open => {
  if (!open) return
  const pm = props.paymentMethod

  form.value = {
    name: pm?.name || '',
    type: pm?.type || 'qr',
    bank_name: pm?.bank_name || pm?.bankName || '',
    account_number: pm?.account_number || pm?.accountNumber || '',
    account_holder: pm?.account_holder || pm?.accountHolder || '',
    requires_reference: pm ? !!(pm.requires_reference ?? pm.requiresReference) : false,
    applies_to: pm?.applies_to || pm?.appliesTo || 'both',
    sort_order: pm?.sort_order ?? pm?.sortOrder ?? 0,
    is_active: pm ? (pm.is_active ?? pm.isActive ?? true) : true,
  }

  qrFile.value = null
  qrPreview.value = pm?.qr_image_url || pm?.qrImageUrl || ''
  errors.value = {}
  error.value = ''
  nextTick(() => refForm.value?.resetValidation())
})

function onFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    error.value = 'Por favor seleccione un archivo de imagen válido (PNG, JPG, WEBP).'
    return
  }

  qrFile.value = file
  qrPreview.value = URL.createObjectURL(file)
}

function removeQrImage() {
  qrFile.value = null
  qrPreview.value = ''
}

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

  try {
    const formData = new FormData()
    formData.append('name', form.value.name.trim())
    formData.append('type', form.value.type)
    if (form.value.bank_name) formData.append('bank_name', form.value.bank_name.trim())
    if (form.value.account_number) formData.append('account_number', form.value.account_number.trim())
    if (form.value.account_holder) formData.append('account_holder', form.value.account_holder.trim())
    formData.append('requires_reference', form.value.requires_reference ? '1' : '0')
    formData.append('applies_to', form.value.applies_to)
    formData.append('sort_order', String(form.value.sort_order || 0))
    formData.append('is_active', form.value.is_active ? '1' : '0')

    if (qrFile.value) {
      formData.append('qr_image', qrFile.value)
    }

    let url = '/payment-methods'
    if (isEdit.value) {
      url = `/payment-methods/${props.paymentMethod.id}`
      formData.append('_method', 'PUT')
    }

    const res = await $api(url, {
      method: 'POST',
      body: formData,
    })

    emit('saved', res?.data || res)
    emit('update:isDialogOpen', false)
  } catch (err) {
    const parsed = apiError(err)
    error.value = parsed.message
    errors.value = parsed.errors || {}
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    max-width="650"
    persistent
    @update:model-value="close"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 pb-2">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" rounded size="36">
            <VIcon icon="ri-bank-card-line" size="20" />
          </VAvatar>
          <span class="text-h6 font-weight-bold">
            {{ isEdit ? 'Editar Forma de Pago' : 'Nueva Forma de Pago' }}
          </span>
        </div>
        <VBtn
          icon="ri-close-line"
          variant="text"
          density="comfortable"
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
          closable
          class="mb-4"
          @click:close="error = ''"
        >
          {{ error }}
        </VAlert>

        <VForm ref="refForm" @submit.prevent="save">
          <VRow dense>
            <!-- Tipo de Método -->
            <VCol cols="12" md="6">
              <VSelect
                v-model="form.type"
                label="Tipo de Forma de Pago *"
                :items="typeOptions"
                item-title="title"
                item-value="value"
                density="compact"
                variant="outlined"
                :error-messages="errors.type"
                :disabled="busy"
              />
            </VCol>

            <!-- Nombre -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="form.name"
                label="Nombre Descriptivo *"
                placeholder="Ej. QR Simple Banco Unión"
                :rules="[required]"
                density="compact"
                variant="outlined"
                :error-messages="errors.name"
                :disabled="busy"
              />
            </VCol>

            <!-- Datos Bancarios (condicionales) -->
            <template v-if="showBankFields">
              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.bank_name"
                  label="Entidad / Banco"
                  placeholder="Ej. Banco Unión, BCP, Tigo Money"
                  density="compact"
                  variant="outlined"
                  :error-messages="errors.bank_name"
                  :disabled="busy"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.account_number"
                  label="N° de Cuenta / Celular"
                  placeholder="Ej. 10000012345678"
                  density="compact"
                  variant="outlined"
                  :error-messages="errors.account_number"
                  :disabled="busy"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="form.account_holder"
                  label="Nombre del Titular"
                  placeholder="Ej. Servimática S.R.L. / Juan Pérez"
                  density="compact"
                  variant="outlined"
                  :error-messages="errors.account_holder"
                  :disabled="busy"
                />
              </VCol>
            </template>

            <!-- Subida de Imagen QR Oficial -->
            <VCol v-if="showQrUpload" cols="12">
              <VCard variant="outlined" class="pa-3 border-dashed">
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="d-flex align-center gap-2">
                    <VIcon icon="ri-qr-code-line" color="primary" />
                    <span class="text-subtitle-2 font-weight-medium">Imagen del Código QR Oficial</span>
                  </div>
                  <VBtn
                    v-if="qrPreview"
                    size="small"
                    color="error"
                    variant="text"
                    prepend-icon="ri-delete-bin-line"
                    @click="removeQrImage"
                  >
                    Quitar
                  </VBtn>
                </div>

                <div v-if="qrPreview" class="d-flex justify-center my-2">
                  <VImg
                    :src="qrPreview"
                    max-width="160"
                    max-height="160"
                    aspect-ratio="1"
                    class="rounded border bg-surface"
                  />
                </div>

                <VFileInput
                  accept="image/*"
                  label="Seleccionar imagen de QR (PNG, JPG)"
                  density="compact"
                  variant="outlined"
                  prepend-icon="ri-upload-2-line"
                  :error-messages="errors.qr_image"
                  :disabled="busy"
                  @change="onFileChange"
                />
                <span class="text-caption text-medium-emphasis">
                  Esta imagen se proyectará en la pantalla del POS para que el cliente la escanee al pagar.
                </span>
              </VCard>
            </VCol>

            <!-- Ámbito de Aplicación -->
            <VCol cols="12" md="6">
              <VSelect
                v-model="form.applies_to"
                label="Ámbito de Aplicación"
                :items="appliesToOptions"
                item-title="title"
                item-value="value"
                density="compact"
                variant="outlined"
                :error-messages="errors.applies_to"
                :disabled="busy"
              />
            </VCol>

            <!-- Orden -->
            <VCol cols="12" md="6">
              <VTextField
                v-model.number="form.sort_order"
                type="number"
                label="Orden de Despliegue"
                placeholder="0"
                density="compact"
                variant="outlined"
                :error-messages="errors.sort_order"
                :disabled="busy"
              />
            </VCol>

            <!-- Switches -->
            <VCol cols="12" md="6">
              <VSwitch
                v-model="form.requires_reference"
                color="warning"
                density="compact"
                hide-details
                label="¿Exige Comprobante / N° de Referencia?"
                :disabled="busy"
              />
              <span class="text-caption text-medium-emphasis d-block mt-1">
                Obliga al cajero a ingresar el número de comprobante para validar la transacción.
              </span>
            </VCol>

            <VCol cols="12" md="6">
              <VSwitch
                v-model="form.is_active"
                color="success"
                density="compact"
                hide-details
                label="Método Activo"
                :disabled="busy"
              />
              <span class="text-caption text-medium-emphasis d-block mt-1">
                Los métodos inactivos no se muestran en el POS ni en recepción de compras.
              </span>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4">
        <VSpacer />
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
          variant="elevated"
          :loading="busy"
          prepend-icon="ri-save-line"
          @click="save"
        >
          Guardar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
