<script setup>
import { ref, onMounted } from 'vue'
import { $api, apiError } from '@/utils/api'
import ChatSimulator from '@/views/whatsapp-bot/ChatSimulator.vue'
import ConversationsList from '@/views/whatsapp-bot/ConversationsList.vue'

const currentTab = ref('simulator')
const loading = ref(false)
const savingSettings = ref(false)
const notice = ref('')
const error = ref('')

const settings = ref({
  is_active: true,
  gemini_model: 'gemini-1.5-flash',
  has_gemini_key: false,
  gemini_api_key_masked: '',
  gateway_url: 'http://127.0.0.1:8080',
  human_handoff_hours: 24,
})

const newApiKey = ref('')
const showApiKey = ref(false)

const stats = ref({
  total_conversations: 0,
  active_conversations: 0,
  human_agent_conversations: 0,
  total_messages: 0,
})

const conversationsListRef = ref(null)

const webhookUrl = computed(() => {
  if (typeof window !== 'undefined') {
    return `${window.location.origin}/api/webhooks/whatsapp`
  }
  return '/api/webhooks/whatsapp'
})

const copiedWebhook = ref(false)

const copyWebhookUrl = () => {
  if (navigator?.clipboard) {
    navigator.clipboard.writeText(webhookUrl.value)
    copiedWebhook.value = true
    setTimeout(() => {
      copiedWebhook.value = false
    }, 2500)
  }
}

const fetchSettings = async () => {
  loading.value = true
  error.value = ''
  try {
    const data = await $api('/whatsapp-bot/settings')
    settings.value = data.settings
    stats.value = data.stats
  } catch (err) {
    error.value = apiError(err) || 'Error al cargar la configuración del bot.'
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  savingSettings.value = true
  notice.value = ''
  error.value = ''
  try {
    const payload = {
      is_active: settings.value.is_active,
      gemini_model: settings.value.gemini_model,
      gateway_url: settings.value.gateway_url,
      human_handoff_hours: settings.value.human_handoff_hours,
    }

    if (newApiKey.value.trim()) {
      payload.gemini_api_key = newApiKey.value.trim()
    }

    await $api('/whatsapp-bot/settings', {
      method: 'POST',
      body: payload,
    })

    newApiKey.value = ''
    notice.value = '¡Configuración guardada exitosamente!'
    await fetchSettings()
  } catch (err) {
    error.value = apiError(err) || 'Error al guardar la configuración.'
  } finally {
    savingSettings.value = false
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<template>
  <div>
    <!-- Encabezado -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold d-flex align-center gap-2">
          <VIcon icon="ri-whatsapp-line" color="success" size="36" />
          Bot de WhatsApp IA (Google Gemini)
        </h2>
        <p class="text-body-1 text-disabled mb-0">
          Asistente de ventas inteligente conectado a la base de datos real y fotos 360° de Servimática
        </p>
      </div>

      <!-- Switch Rápido de Estado -->
      <VCard variant="outlined" class="px-4 py-2 d-flex align-center gap-3">
        <div>
          <div class="text-caption text-disabled">ESTADO DEL BOT</div>
          <div class="text-subtitle-2 font-weight-bold">
            {{ settings.is_active ? 'Activo (Respondiendo)' : 'Pausado' }}
          </div>
        </div>
        <VSwitch
          v-model="settings.is_active"
          color="success"
          hide-details
          inset
          :loading="savingSettings"
          @update:model-value="saveSettings"
        />
      </VCard>
    </div>

    <!-- Mensajes de feedback -->
    <VAlert
      v-if="notice"
      type="success"
      variant="tonal"
      density="compact"
      closable
      class="mb-4"
      @click:close="notice = ''"
    >
      {{ notice }}
    </VAlert>

    <VAlert
      v-if="error"
      type="error"
      variant="tonal"
      density="compact"
      closable
      class="mb-4"
      @click:close="error = ''"
    >
      {{ error }}
    </VAlert>

    <!-- Fila de Tarjetas de Métricas -->
    <VRow class="mb-6">
      <VCol cols="12" sm="6" md="3">
        <VCard class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled text-uppercase font-weight-medium">
                Total Conversaciones
              </div>
              <div class="text-h4 font-weight-bold mt-1">
                {{ stats.total_conversations }}
              </div>
            </div>
            <VAvatar size="48" color="primary" variant="tonal">
              <VIcon icon="ri-chat-voice-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled text-uppercase font-weight-medium">
                Atención por IA
              </div>
              <div class="text-h4 font-weight-bold text-success mt-1">
                {{ stats.active_conversations }}
              </div>
            </div>
            <VAvatar size="48" color="success" variant="tonal">
              <VIcon icon="ri-robot-2-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled text-uppercase font-weight-medium">
                Asesor Humano
              </div>
              <div class="text-h4 font-weight-bold text-warning mt-1">
                {{ stats.human_agent_conversations }}
              </div>
            </div>
            <VAvatar size="48" color="warning" variant="tonal">
              <VIcon icon="ri-user-star-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-disabled text-uppercase font-weight-medium">
                Mensajes Totales
              </div>
              <div class="text-h4 font-weight-bold text-info mt-1">
                {{ stats.total_messages }}
              </div>
            </div>
            <VAvatar size="48" color="info" variant="tonal">
              <VIcon icon="ri-message-3-line" size="26" />
            </VAvatar>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Navegación por Pestañas -->
    <VTabs v-model="currentTab" class="mb-4">
      <VTab value="simulator" prepend-icon="ri-message-2-line">
        Simulador en Vivo
      </VTab>
      <VTab value="conversations" prepend-icon="ri-contacts-line">
        Conversaciones de Clientes ({{ stats.total_conversations }})
      </VTab>
      <VTab value="settings" prepend-icon="ri-settings-4-line">
        Configuración y Gateway
      </VTab>
    </VTabs>

    <!-- Contenido de Pestañas -->
    <VWindow v-model="currentTab">
      <!-- Pestaña 1: Simulador en Vivo -->
      <VWindowItem value="simulator">
        <VRow>
          <VCol cols="12" md="8">
            <ChatSimulator
              :is-active="settings.is_active"
              :model-name="settings.gemini_model"
            />
          </VCol>

          <VCol cols="12" md="4">
            <VCard class="pa-4 mb-4">
              <VCardTitle class="px-0 text-base font-weight-bold d-flex align-center gap-2">
                <VIcon icon="ri-shield-check-line" color="success" size="20" />
                Reglas de Negocio Protegidas
              </VCardTitle>
              <VDivider class="my-2" />

              <VList density="compact" class="px-0">
                <VListItem class="px-0">
                  <template #prepend>
                    <VIcon icon="ri-checkbox-circle-fill" color="success" size="18" class="me-2" />
                  </template>
                  <VListItemTitle class="text-body-2 font-weight-medium">
                    Precios en Bolivianos (Bs.)
                  </VListItemTitle>
                  <VListItemSubtitle class="text-caption">
                    Solo cotiza en Bs. y prohíbe inventar precios
                  </VListItemSubtitle>
                </VListItem>

                <VListItem class="px-0">
                  <template #prepend>
                    <VIcon icon="ri-lock-2-line" color="error" size="18" class="me-2" />
                  </template>
                  <VListItemTitle class="text-body-2 font-weight-medium">
                    Costos de Compra Protegidos
                  </VListItemTitle>
                  <VListItemSubtitle class="text-caption">
                    Nunca revela costos de adquisición ni proveedores
                  </VListItemSubtitle>
                </VListItem>

                <VListItem class="px-0">
                  <template #prepend>
                    <VIcon icon="ri-archive-line" color="primary" size="18" class="me-2" />
                  </template>
                  <VListItemTitle class="text-body-2 font-weight-medium">
                    Stock Real en Tiempo Real
                  </VListItemTitle>
                  <VListItemSubtitle class="text-caption">
                    Solo recomienda productos con existencias > 0
                  </VListItemSubtitle>
                </VListItem>

                <VListItem class="px-0">
                  <template #prepend>
                    <VIcon icon="ri-eye-line" color="secondary" size="18" class="me-2" />
                  </template>
                  <VListItemTitle class="text-body-2 font-weight-medium">
                    Enlaces al Catálogo 360°
                  </VListItemTitle>
                  <VListItemSubtitle class="text-caption">
                    Genera links directos para inspeccionar equipos en 3D
                  </VListItemSubtitle>
                </VListItem>
              </VList>
            </VCard>

            <VCard class="pa-4 bg-var-theme-background">
              <div class="text-subtitle-2 font-weight-bold mb-1">
                📍 Dirección de la Tienda
              </div>
              <p class="text-caption text-disabled mb-2">
                Comercial Chiriguano pasillo 2 local # 333 (Santa Cruz de la Sierra, Bolivia).
              </p>
              <div class="text-subtitle-2 font-weight-bold mb-1">
                🛡️ Garantía Oficial
              </div>
              <p class="text-caption text-disabled mb-0">
                1 año en laptops nuevas y 6 meses con servicio técnico certificado en semi-nuevas.
              </p>
            </VCard>
          </VCol>
        </VRow>
      </VWindowItem>

      <!-- Pestaña 2: Conversaciones Registradas -->
      <VWindowItem value="conversations">
        <ConversationsList
          ref="conversationsListRef"
          @updated="fetchSettings"
        />
      </VWindowItem>

      <!-- Pestaña 3: Configuración y Gateway -->
      <VWindowItem value="settings">
        <VRow>
          <VCol cols="12" md="7">
            <VCard class="pa-4">
              <VCardTitle class="px-0 text-h6 font-weight-bold">
                Configuración del Modelo de Inteligencia Artificial
              </VCardTitle>
              <VCardSubtitle class="px-0 text-caption text-disabled mb-4">
                Google Gemini API conectada a la base de datos de Servimática
              </VCardSubtitle>

              <VForm @submit.prevent="saveSettings">
                <VRow>
                  <VCol cols="12">
                    <label class="text-body-2 font-weight-medium mb-1 d-block">
                      Modelo de Gemini
                    </label>
                    <VSelect
                      v-model="settings.gemini_model"
                      :items="[
                        { title: 'gemini-3.8-flash (Recomendado: Última generación ultrarrápida)', value: 'gemini-3.8-flash' },
                        { title: 'gemini-3.5-flash (Generación 3.5 estable)', value: 'gemini-3.5-flash' },
                        { title: 'gemini-3.7-flash (Generación 3.7)', value: 'gemini-3.7-flash' },
                        { title: 'gemini-1.5-flash (Generación clásica)', value: 'gemini-1.5-flash' }
                      ]"
                      item-title="title"
                      item-value="value"
                      variant="outlined"
                      density="comfortable"
                    />
                  </VCol>

                  <VCol cols="12">
                    <label class="text-body-2 font-weight-medium mb-1 d-block">
                      Clave de API de Google Gemini (Google AI Studio)
                    </label>
                    <div v-if="settings.has_gemini_key" class="text-caption text-success mb-2 d-flex align-center gap-1">
                      <VIcon icon="ri-checkbox-circle-line" size="16" />
                      Clave configurada en el servidor ({{ settings.gemini_api_key_masked }})
                    </div>
                    <VTextField
                      v-model="newApiKey"
                      :type="showApiKey ? 'text' : 'password'"
                      placeholder="Pega aquí tu nueva GEMINI_API_KEY si deseas cambiarla..."
                      variant="outlined"
                      density="comfortable"
                      :append-inner-icon="showApiKey ? 'ri-eye-off-line' : 'ri-eye-line'"
                      @click:append-inner="showApiKey = !showApiKey"
                    />
                    <span class="text-caption text-disabled">
                      Obtén tu clave gratuita en <a href="https://aistudio.google.com/" target="_blank" class="text-primary font-weight-bold">Google AI Studio</a>.
                    </span>
                  </VCol>

                  <VCol cols="12">
                    <label class="text-body-2 font-weight-medium mb-1 d-block">
                      URL del Gateway WhatsApp (QR / Baileys / Evolution API)
                    </label>
                    <VTextField
                      v-model="settings.gateway_url"
                      placeholder="http://127.0.0.1:8080"
                      variant="outlined"
                      density="comfortable"
                      prepend-inner-icon="ri-router-line"
                    />
                    <span class="text-caption text-disabled">
                      Dirección donde se ejecuta el servicio de WhatsApp con código QR para conectar el teléfono físico de la tienda.
                    </span>
                  </VCol>

                  <VCol cols="12">
                    <label class="text-body-2 font-weight-medium mb-1 d-block">
                      Tiempo de silencio del bot tras pedir asesor humano (Horas)
                    </label>
                    <VTextField
                      v-model="settings.human_handoff_hours"
                      type="number"
                      min="1"
                      max="72"
                      variant="outlined"
                      density="comfortable"
                      suffix="horas"
                    />
                    <span class="text-caption text-disabled">
                      Durante este lapso, el bot no responderá a ese cliente para permitir la atención personal del vendedor de tienda.
                    </span>
                  </VCol>

                  <VCol cols="12" class="d-flex justify-end gap-2 mt-2">
                    <VBtn
                      type="submit"
                      color="primary"
                      prepend-icon="ri-save-line"
                      :loading="savingSettings"
                    >
                      Guardar Configuración
                    </VBtn>
                  </VCol>
                </VRow>
              </VForm>
            </VCard>
          </VCol>

          <VCol cols="12" md="5">
            <VCard class="pa-4 mb-4">
              <VCardTitle class="px-0 text-base font-weight-bold d-flex align-center gap-2">
                <VIcon icon="ri-links-line" color="info" size="20" />
                URL del Webhook para el Gateway
              </VCardTitle>
              <VCardSubtitle class="px-0 text-caption text-disabled mb-3">
                Copia y pega esta URL en tu Gateway de WhatsApp para recibir los mensajes entrantes
              </VCardSubtitle>

              <VTextField
                :model-value="webhookUrl"
                readonly
                variant="outlined"
                density="comfortable"
                class="mb-2"
              >
                <template #append-inner>
                  <VBtn
                    size="small"
                    variant="tonal"
                    :color="copiedWebhook ? 'success' : 'primary'"
                    @click="copyWebhookUrl"
                  >
                    <VIcon :icon="copiedWebhook ? 'ri-check-line' : 'ri-file-copy-line'" class="me-1" />
                    {{ copiedWebhook ? 'Copiado' : 'Copiar' }}
                  </VBtn>
                </template>
              </VTextField>
            </VCard>

            <VCard class="pa-4 bg-var-theme-background">
              <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center gap-1">
                <VIcon icon="ri-qr-code-line" size="18" />
                Pasos para Conectar WhatsApp Físico
              </div>
              <ol class="text-caption text-disabled ps-4 mb-0" style="line-height: 1.6;">
                <li>Inicia tu gateway local o remoto (compatible con Baileys, Evolution API o Z-API).</li>
                <li>Escanea el código QR desde el WhatsApp de la tienda física (Comercial Chiriguano).</li>
                <li>Configura el Webhook en tu gateway apuntando a la URL copiada arriba.</li>
                <li>¡Listo! Los clientes que escriban al WhatsApp recibirán asesoramiento instantáneo con IA y productos de tu inventario.</li>
              </ol>
            </VCard>
          </VCol>
        </VRow>
      </VWindowItem>
    </VWindow>
  </div>
</template>
