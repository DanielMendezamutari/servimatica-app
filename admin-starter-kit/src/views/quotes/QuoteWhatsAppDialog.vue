<script setup>
import { ref, watch } from 'vue'
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
  quote: { type: Object, default: null },
})

const emit = defineEmits(['update:isDialogOpen'])

const loading = ref(false)
const error = ref('')
const phoneNumber = ref('')
const messageText = ref('')
const whatsappLink = ref('')
const copied = ref(false)

async function fetchWhatsAppDetails(customPhone = null) {
  if (!props.quote?.id) return
  loading.value = true
  error.value = ''
  try {
    const params = {}
    if (customPhone !== null && customPhone !== undefined && customPhone.trim() !== '') {
      params.phone = customPhone.trim()
    }
    const res = await $api(`/quotes/${props.quote.id}/whatsapp-link`, { params })
    whatsappLink.value = res.whatsapp_link || ''
    messageText.value = res.message_text || res.formatted_message || ''
    if (customPhone === null) {
      phoneNumber.value = res.phone_used || props.quote.client_phone || ''
    }
  } catch (e) {
    error.value = apiError(e, 'Error al generar el mensaje de WhatsApp.')
  } finally {
    loading.value = false
  }
}

watch(() => props.isDialogOpen, open => {
  if (open && props.quote) {
    phoneNumber.value = props.quote.client_phone || ''
    fetchWhatsAppDetails()
  } else {
    messageText.value = ''
    whatsappLink.value = ''
    copied.value = false
    error.value = ''
  }
})

// Si el usuario cambia el teléfono, actualizar en caliente
watch(phoneNumber, newPhone => {
  if (props.isDialogOpen && props.quote) {
    updateLinkForPhone(newPhone)
  }
})

function updateLinkForPhone(phone) {
  const digits = (phone || '').replace(/\D+/g, '')
  const formattedPhone = digits.length === 8 ? `591${digits}` : digits
  if (messageText.value) {
    const encoded = encodeURIComponent(messageText.value)
    whatsappLink.value = formattedPhone
      ? `https://api.whatsapp.com/send?phone=${formattedPhone}&text=${encoded}`
      : `https://api.whatsapp.com/send?text=${encoded}`
  }
}

function onPhoneChange() {
  fetchWhatsAppDetails(phoneNumber.value)
}

async function copyMessage() {
  if (!messageText.value) return
  try {
    if (navigator?.clipboard?.writeText) {
      await navigator.clipboard.writeText(messageText.value)
    } else {
      const textarea = document.createElement('textarea')
      textarea.value = messageText.value
      document.body.appendChild(textarea)
      textarea.select()
      document.execCommand('copy')
      document.body.removeChild(textarea)
    }
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2500)
  } catch (err) {
    console.error('Error al copiar al portapapeles:', err)
  }
}

function openWhatsApp() {
  const digits = (phoneNumber.value || '').replace(/\D+/g, '')
  const formattedPhone = digits.length === 8 ? `591${digits}` : digits
  const textToSend = messageText.value || ''

  if (!textToSend) return

  // Usar api.whatsapp.com directo con el texto actualmente visualizado
  const encodedText = encodeURIComponent(textToSend)
  const url = formattedPhone
    ? `https://api.whatsapp.com/send?phone=${formattedPhone}&text=${encodedText}`
    : `https://api.whatsapp.com/send?text=${encodedText}`

  window.open(url, '_blank')
}

function close() {
  emit('update:isDialogOpen', false)
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogOpen"
    max-width="680"
    scrollable
    @update:model-value="close"
  >
    <VCard class="pa-2">
      <VCardTitle class="d-flex align-center justify-space-between pb-2">
        <div class="d-flex align-center gap-2">
          <VAvatar color="success" variant="tonal" rounded size="38">
            <VIcon icon="ri-whatsapp-line" size="24" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-bold">
              Enviar Cotización por WhatsApp
            </div>
            <div class="text-caption text-medium-emphasis">
              Mensaje comercial persuasivo con garantías y datos de cobro
            </div>
          </div>
        </div>
        <VBtn icon variant="text" size="small" @click="close">
          <VIcon icon="ri-close-line" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pt-4" style="max-height: 520px;">
        <VAlert v-if="error" type="error" variant="tonal" class="mb-4">
          {{ error }}
        </VAlert>

        <!-- Destinatario y Teléfono -->
        <VRow class="mb-2" align="center">
          <VCol cols="12" sm="6">
            <div class="text-caption text-medium-emphasis mb-1">Cliente destinatario</div>
            <div class="text-body-1 font-weight-bold text-high-emphasis">
              {{ props.quote?.client_name || 'Cliente' }}
            </div>
            <div class="text-caption text-primary font-weight-medium">
              Proforma: {{ props.quote?.quote_number }}
            </div>
          </VCol>
          <VCol cols="12" sm="6">
            <VTextField
              v-model="phoneNumber"
              label="Número de WhatsApp"
              placeholder="Ej: 77012345"
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-phone-line"
              append-inner-icon="ri-refresh-line"
              hide-details
              @click:append-inner="onPhoneChange"
              @keyup.enter="onPhoneChange"
            />
            <div class="text-caption text-medium-emphasis mt-1">
              Presione Enter o el icono para actualizar enlace
            </div>
          </VCol>
        </VRow>

        <VDivider class="my-3" />

        <!-- Vista Previa del Mensaje -->
        <div class="d-flex align-center justify-space-between mb-2">
          <div class="text-subtitle-2 font-weight-bold d-flex align-center gap-1">
            <VIcon icon="ri-message-3-line" size="18" color="success" />
            <span>Vista Previa del Mensaje:</span>
          </div>
          <VChip size="x-small" color="success" variant="tonal">
            Persuasivo / Alta Conversión
          </VChip>
        </div>

        <div v-if="loading" class="text-center py-8">
          <VProgressCircular indeterminate color="success" />
          <div class="text-caption mt-2">Preparando propuesta comercial...</div>
        </div>

        <div
          v-else
          class="message-preview-box rounded pa-4"
        >
          <pre class="message-preview-text">{{ messageText }}</pre>
        </div>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4 d-flex justify-space-between flex-wrap gap-2">
        <VBtn
          variant="outlined"
          color="secondary"
          @click="close"
        >
          Cerrar
        </VBtn>

        <div class="d-flex gap-2">
          <VBtn
            variant="tonal"
            color="primary"
            prepend-icon="ri-file-copy-line"
            :disabled="!messageText || loading"
            @click="copyMessage"
          >
            {{ copied ? '¡Copiado!' : 'Copiar Texto' }}
          </VBtn>

          <VBtn
            color="success"
            prepend-icon="ri-whatsapp-line"
            :disabled="!whatsappLink || loading"
            @click="openWhatsApp"
          >
            Abrir WhatsApp
          </VBtn>
        </div>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.message-preview-box {
  background-color: rgba(var(--v-theme-surface-variant), 0.35);
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-left: 4px solid #25d366;
  max-height: 320px;
  overflow-y: auto;
}

.message-preview-text {
  font-family: inherit;
  font-size: 13px;
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
  margin: 0;
  color: inherit;
}
</style>
