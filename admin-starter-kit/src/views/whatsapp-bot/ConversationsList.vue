<script setup>
import { ref, onMounted } from 'vue'
import { $api, apiError } from '@/utils/api'

const emit = defineEmits(['updated'])

const conversations = ref([])
const loading = ref(false)
const search = ref('')
const statusFilter = ref(null)
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const notice = ref('')
const error = ref('')

const statusOptions = [
  { title: 'Todos los estados', value: null },
  { title: 'IA Activa', value: 'active' },
  { title: 'Asesor Humano Requerido', value: 'human_agent' },
  { title: 'Pausado', value: 'paused' },
]

const fetchConversations = async () => {
  loading.value = true
  error.value = ''
  try {
    const params = new URLSearchParams()
    params.append('page', page.value)
    if (search.value.trim()) params.append('search', search.value.trim())
    if (statusFilter.value) params.append('status', statusFilter.value)

    const response = await $api(`/whatsapp-bot/conversations?${params.toString()}`)
    conversations.value = response.data || []
    page.value = response.current_page || 1
    lastPage.value = response.last_page || 1
    total.value = response.total || 0
  } catch (err) {
    error.value = apiError(err) || 'Error al cargar las conversaciones.'
  } finally {
    loading.value = false
  }
}

const resetHandoff = async conv => {
  loading.value = true
  notice.value = ''
  try {
    await $api(`/whatsapp-bot/conversations/${conv.id}/reset-handoff`, { method: 'POST' })
    notice.value = `Bot reactivado para ${conv.customer_name || conv.phone_number}`
    await fetchConversations()
    emit('updated')
  } catch (err) {
    error.value = apiError(err) || 'Error al reactivar el bot.'
  } finally {
    loading.value = false
  }
}

const formatDate = dateStr => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleString([], {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(() => {
  fetchConversations()
})

defineExpose({
  fetchConversations,
})
</script>

<template>
  <VCard class="pa-4">
    <VCardTitle class="px-0 d-flex justify-space-between align-center flex-wrap gap-2">
      <div>
        <h3 class="text-h6 font-weight-bold">
          Conversaciones de Clientes (WhatsApp)
        </h3>
        <p class="text-caption text-disabled mb-0">
          Registro de clientes que han interactuado con la tienda vía WhatsApp
        </p>
      </div>

      <VBtn
        variant="tonal"
        color="secondary"
        size="small"
        prepend-icon="ri-refresh-line"
        :loading="loading"
        @click="fetchConversations"
      >
        Actualizar
      </VBtn>
    </VCardTitle>

    <!-- Filtros de búsqueda -->
    <VRow class="my-2">
      <VCol cols="12" md="6">
        <VTextField
          v-model="search"
          placeholder="Buscar por teléfono o nombre de cliente..."
          density="compact"
          variant="outlined"
          prepend-inner-icon="ri-search-line"
          clearable
          hide-details
          @update:model-value="page = 1; fetchConversations()"
        />
      </VCol>
      <VCol cols="12" md="4">
        <VSelect
          v-model="statusFilter"
          :items="statusOptions"
          item-title="title"
          item-value="value"
          density="compact"
          variant="outlined"
          hide-details
          @update:model-value="page = 1; fetchConversations()"
        />
      </VCol>
    </VRow>

    <VAlert
      v-if="notice"
      type="success"
      variant="tonal"
      density="compact"
      closable
      class="mb-3"
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
      class="mb-3"
      @click:close="error = ''"
    >
      {{ error }}
    </VAlert>

    <!-- Tabla -->
    <VTable density="comfortable" class="border rounded text-no-wrap">
      <thead>
        <tr>
          <th>CLIENTE / WHATSAPP</th>
          <th>ESTADO DEL ASISTENTE</th>
          <th>ÚLTIMO MENSAJE</th>
          <th>FECHA</th>
          <th class="text-center">ACCIONES</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading && conversations.length === 0">
          <td colspan="5" class="text-center py-6 text-disabled">
            <VProgressCircular indeterminate size="24" color="primary" class="me-2" />
            Cargando conversaciones...
          </td>
        </tr>
        <tr v-else-if="conversations.length === 0">
          <td colspan="5" class="text-center py-6 text-disabled">
            No se han registrado conversaciones todavía.
          </td>
        </tr>
        <tr v-for="c in conversations" :key="c.id">
          <td>
            <div class="d-flex align-center gap-2">
              <VAvatar size="32" color="success" variant="tonal">
                <VIcon icon="ri-whatsapp-line" size="18" />
              </VAvatar>
              <div>
                <a
                  :href="`https://wa.me/${c.phone_number}`"
                  target="_blank"
                  rel="noopener"
                  class="text-primary font-weight-medium text-decoration-none d-flex align-center gap-1"
                >
                  +{{ c.phone_number }}
                  <VIcon icon="ri-external-link-line" size="12" />
                </a>
                <div class="text-caption text-disabled">
                  {{ c.customer_name || 'Sin nombre guardado' }}
                </div>
              </div>
            </div>
          </td>

          <td>
            <VChip
              v-if="c.is_human_handoff_active || c.status === 'human_agent'"
              color="warning"
              size="small"
              variant="tonal"
              prepend-icon="ri-user-star-line"
            >
              Asesor Humano
            </VChip>
            <VChip
              v-else-if="c.status === 'active'"
              color="success"
              size="small"
              variant="tonal"
              prepend-icon="ri-robot-2-line"
            >
              IA Activa
            </VChip>
            <VChip
              v-else
              color="secondary"
              size="small"
              variant="tonal"
            >
              {{ c.status }}
            </VChip>
          </td>

          <td>
            <div
              class="text-truncate"
              style="max-width: 280px;"
              :title="c.messages && c.messages[0] ? c.messages[0].message : ''"
            >
              <span v-if="c.messages && c.messages[0]" class="text-body-2">
                <strong v-if="c.messages[0].direction === 'outbound'">Bot:</strong>
                {{ c.messages[0].message }}
              </span>
              <span v-else class="text-disabled">-</span>
            </div>
          </td>

          <td class="text-caption text-disabled">
            {{ formatDate(c.last_message_at) }}
          </td>

          <td class="text-center">
            <VBtn
              v-if="c.is_human_handoff_active || c.status === 'human_agent'"
              size="small"
              color="primary"
              variant="tonal"
              prepend-icon="ri-arrow-go-back-line"
              @click="resetHandoff(c)"
            >
              Reactivar IA
            </VBtn>
            <span v-else class="text-caption text-disabled">
              IA respondiendo
            </span>
          </td>
        </tr>
      </tbody>
    </VTable>

    <!-- Paginación -->
    <div v-if="lastPage > 1" class="d-flex justify-end mt-3">
      <VPagination
        v-model="page"
        :length="lastPage"
        total-visible="5"
        density="compact"
        @update:model-value="fetchConversations"
      />
    </div>
  </VCard>
</template>
