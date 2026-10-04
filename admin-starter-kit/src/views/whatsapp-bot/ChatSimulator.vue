<script setup>
import { ref, nextTick } from 'vue'
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isActive: {
    type: Boolean,
    default: true,
  },
  modelName: {
    type: String,
    default: 'gemini-1.5-flash',
  },
})

const messages = ref([
  {
    role: 'model',
    text: '¡Hola! 👋 Soy el asistente virtual inteligente de *Servimática*. Consulto el inventario en tiempo real de nuestra tienda en el Comercial Chiriguano (Santa Cruz). ¿En qué equipo o laptop te puedo asesorar hoy?',
    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
  },
])

const inputMessage = ref('')
const loading = ref(false)
const chatContainer = ref(null)
const errorNotice = ref('')

const quickPrompts = [
  '💻 ¿Qué laptops tienen para entrega inmediata?',
  '🎮 Busco una laptop gamer de gama alta',
  '💼 ¿Tienen laptops económicas para oficina o estudio?',
  '📍 ¿Dónde queda su tienda y atienden sábados?',
  '👨‍💻 Deseo hablar con un asesor humano en tienda',
]

const scrollToBottom = async () => {
  await nextTick()
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  }
}

const sendMessage = async (textToSend = null) => {
  const text = (textToSend || inputMessage.value).trim()
  if (!text || loading.value) return

  errorNotice.value = ''
  const timeNow = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })

  // Agregar mensaje del usuario
  messages.value.push({
    role: 'user',
    text,
    time: timeNow,
  })

  inputMessage.value = ''
  loading.value = true
  await scrollToBottom()

  try {
    // Preparar historial para Gemini (excluir el primer saludo estático si se desea)
    const historyPayload = messages.value
      .slice(1, -1)
      .map(m => ({
        role: m.role,
        text: m.text,
      }))

    const response = await $api('/whatsapp-bot/simulate', {
      method: 'POST',
      body: {
        message: text,
        history: historyPayload,
      },
    })

    messages.value.push({
      role: 'model',
      text: response.reply,
      tokensUsed: response.tokens_used,
      isHumanRequested: response.is_human_requested,
      time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    })
  } catch (err) {
    errorNotice.value = apiError(err) || 'Error al comunicarse con el servicio de IA.'
    messages.value.push({
      role: 'model',
      text: '⚠️ Ocurrió un error al procesar tu consulta con la IA. Por favor verifica tu clave de Gemini API.',
      time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      isError: true,
    })
  } finally {
    loading.value = false
    await scrollToBottom()
  }
}

const clearChat = () => {
  messages.value = [
    {
      role: 'model',
      text: '¡Conversación reiniciada! 🔄 ¿En qué laptop o producto te puedo asesorar ahora?',
      time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    },
  ]
}

const formatMessageText = text => {
  if (!text) return ''
  // Convertir *negrita* en <strong>
  let formatted = text.replace(/\*(.*?)\*/g, '<strong>$1</strong>')
  
  // Convertir enlaces http:// o https:// en <a> clicables
  formatted = formatted.replace(
    /(https?:\/\/[^\s]+)/g,
    '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-primary font-weight-bold text-decoration-underline">$1</a>'
  )

  // Reemplazar saltos de línea
  return formatted.replace(/\n/g, '<br>')
}
</script>

<template>
  <VCard class="chat-simulator-card d-flex flex-column" elevation="3">
    <!-- Header del Simulador WhatsApp -->
    <VCardItem class="bg-primary text-white py-3 px-4">
      <template #prepend>
        <VAvatar size="40" color="white" class="me-3">
          <VIcon icon="ri-whatsapp-fill" color="success" size="28" />
        </VAvatar>
      </template>

      <VCardTitle class="text-white text-base font-weight-bold mb-0">
        Simulador WhatsApp IA - Servimática
      </VCardTitle>
      <VCardSubtitle class="text-white text-xs opacity-90">
        <span class="d-inline-flex align-center gap-1">
          <VBadge dot inline :color="isActive ? 'success' : 'warning'" />
          {{ isActive ? 'Bot Activo' : 'Bot Pausado' }} • Motor: {{ modelName }}
        </span>
      </VCardSubtitle>

      <template #append>
        <VBtn
          icon="ri-refresh-line"
          variant="text"
          color="white"
          size="small"
          title="Reiniciar chat"
          @click="clearChat"
        />
      </template>
    </VCardItem>

    <VDivider />

    <!-- Mensaje de error si ocurre -->
    <VAlert
      v-if="errorNotice"
      type="error"
      variant="tonal"
      density="compact"
      closable
      class="ma-2"
      @click:close="errorNotice = ''"
    >
      {{ errorNotice }}
    </VAlert>

    <!-- Caja de Conversación -->
    <div
      ref="chatContainer"
      class="chat-messages-area flex-grow-1 pa-4 overflow-y-auto"
      style="max-height: 480px; min-height: 380px; background-color: rgba(var(--v-theme-surface), 0.7);"
    >
      <div
        v-for="(msg, idx) in messages"
        :key="idx"
        class="d-flex mb-3"
        :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
      >
        <!-- Avatar Bot -->
        <VAvatar
          v-if="msg.role !== 'user'"
          size="30"
          color="primary"
          variant="tonal"
          class="me-2 mt-1 flex-shrink-0"
        >
          <VIcon icon="ri-robot-2-line" size="18" />
        </VAvatar>

        <!-- Burbuja del mensaje -->
        <div
          class="message-bubble pa-3 rounded-lg elevation-1"
          :class="[
            msg.role === 'user'
              ? 'bg-primary text-white bubble-user'
              : 'bg-surface text-high-emphasis border bubble-bot'
          ]"
          style="max-width: 82%; font-size: 0.9rem; line-height: 1.45;"
        >
          <div
            v-if="msg.isHumanRequested"
            class="d-flex align-center gap-1 mb-2 text-xs font-weight-bold text-warning"
          >
            <VIcon icon="ri-user-follow-line" size="16" />
            <span>SOLICITUD DE ASESOR HUMANO DETECTADA</span>
          </div>

          <div v-html="formatMessageText(msg.text)" />

          <div class="d-flex align-center justify-end gap-2 mt-1 text-xs opacity-75">
            <span v-if="msg.tokensUsed" class="text-caption font-weight-light">
              {{ msg.tokensUsed }} tokens
            </span>
            <span>{{ msg.time }}</span>
            <VIcon
              v-if="msg.role === 'user'"
              icon="ri-check-double-line"
              size="14"
              color="white"
            />
          </div>
        </div>

        <!-- Avatar Usuario -->
        <VAvatar
          v-if="msg.role === 'user'"
          size="30"
          color="secondary"
          variant="tonal"
          class="ms-2 mt-1 flex-shrink-0"
        >
          <VIcon icon="ri-user-3-line" size="18" />
        </VAvatar>
      </div>

      <!-- Indicador escribiendo -->
      <div v-if="loading" class="d-flex align-center gap-2 mb-3 text-disabled text-caption">
        <VProgressCircular indeterminate size="16" width="2" color="primary" />
        <span>Gemini AI analizando inventario de la tienda...</span>
      </div>
    </div>

    <VDivider />

    <!-- Sugerencias de preguntas rápidas -->
    <div class="px-3 py-2 bg-var-theme-background border-b overflow-x-auto text-no-wrap">
      <span class="text-caption text-disabled me-2 font-weight-medium">Preguntas sugeridas:</span>
      <VChip
        v-for="(prompt, pIdx) in quickPrompts"
        :key="pIdx"
        size="small"
        variant="tonal"
        color="primary"
        class="me-2 cursor-pointer"
        :disabled="loading"
        @click="sendMessage(prompt)"
      >
        {{ prompt }}
      </VChip>
    </div>

    <!-- Barra de Entrada de Texto -->
    <div class="pa-3 bg-surface d-flex align-center gap-2">
      <VTextField
        v-model="inputMessage"
        placeholder="Escribe como un cliente de WhatsApp (ej: ¿Qué laptops tienen en stock?)..."
        variant="outlined"
        density="compact"
        hide-details
        :disabled="loading"
        @keydown.enter.prevent="sendMessage()"
      />
      <VBtn
        color="primary"
        icon="ri-send-plane-fill"
        :loading="loading"
        :disabled="!inputMessage.trim() || loading"
        @click="sendMessage()"
      />
    </div>
  </VCard>
</template>

<style scoped>
.chat-simulator-card {
  border-radius: 12px;
  overflow: hidden;
}

.bubble-user {
  border-bottom-right-radius: 2px !important;
}

.bubble-bot {
  border-bottom-left-radius: 2px !important;
}

.chat-messages-area {
  scrollbar-width: thin;
}
</style>
