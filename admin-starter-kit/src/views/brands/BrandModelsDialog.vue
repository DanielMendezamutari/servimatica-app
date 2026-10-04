<script setup>
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDialogOpen: { type: Boolean, required: true },
  brand: { type: Object, default: null },
})

const emit = defineEmits(['update:isDialogOpen', 'updated'])

const models = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const newModelName = ref('')
const newModelNotes = ref('')
const editingModel = ref(null)

async function loadModels() {
  if (!props.brand) return
  loading.value = true
  error.value = ''
  try {
    const res = await $api(`/brands/${props.brand.id}/models`)
    models.value = res.data || []
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    loading.value = false
  }
}

watch(() => props.isDialogOpen, open => {
  if (open && props.brand) {
    newModelName.value = ''
    newModelNotes.value = ''
    editingModel.value = null
    loadModels()
  }
})

function close() {
  emit('update:isDialogOpen', false)
}

async function addModel() {
  if (!newModelName.value.trim() || saving.value) return
  saving.value = true
  error.value = ''
  try {
    await $api('/product-models', {
      method: 'POST',
      body: {
        brand_id: props.brand.id,
        name: newModelName.value.trim(),
        notes: newModelNotes.value.trim() || null,
        is_active: true,
      },
    })
    newModelName.value = ''
    newModelNotes.value = ''
    await loadModels()
    emit('updated')
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    saving.value = false
  }
}

async function toggleModel(model) {
  try {
    await $api(`/product-models/${model.id}/toggle-status`, { method: 'PATCH' })
    await loadModels()
  } catch (failure) {
    error.value = apiError(failure)
  }
}
</script>

<template>
  <VDialog
    :model-value="isDialogOpen"
    max-width="650"
    persistent
    @update:model-value="close"
  >
    <VCard v-if="brand">
      <VCardTitle class="d-flex justify-space-between align-center pa-4">
        <div>
          <span class="text-h6">Modelos de la Marca: {{ brand.name }}</span>
          <p class="text-caption text-medium-emphasis mb-0">
            Administre las series y modelos comerciales de este fabricante.
          </p>
        </div>
        <VBtn
          icon="ri-close-line"
          variant="text"
          density="compact"
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
        >
          {{ error }}
        </VAlert>

        <!-- Formulario para agregar modelo rápido -->
        <VCard
          variant="outlined"
          class="pa-3 mb-4 bg-var-theme-background"
        >
          <div class="text-subtitle-2 font-weight-medium mb-2">
            Agregar nuevo modelo:
          </div>
          <VRow density="compact">
            <VCol
              cols="12"
              sm="6"
            >
              <VTextField
                v-model="newModelName"
                label="Nombre del modelo *"
                placeholder="Ej. ROG Strix, TUF F15, G502"
                density="compact"
                maxlength="150"
                :disabled="saving"
                @keydown.enter="addModel"
              />
            </VCol>
            <VCol
              cols="12"
              sm="4"
            >
              <VTextField
                v-model="newModelNotes"
                label="Notas / Serie (opcional)"
                placeholder="Ej. DDR5, Gen 4"
                density="compact"
                :disabled="saving"
                @keydown.enter="addModel"
              />
            </VCol>
            <VCol
              cols="12"
              sm="2"
              class="d-flex align-center"
            >
              <VBtn
                block
                color="primary"
                size="small"
                :loading="saving"
                :disabled="!newModelName.trim()"
                @click="addModel"
              >
                Agregar
              </VBtn>
            </VCol>
          </VRow>
        </VCard>

        <!-- Lista de modelos -->
        <div v-if="loading" class="text-center py-4">
          <VProgressCircular indeterminate color="primary" />
        </div>

        <div v-else-if="models.length === 0" class="text-center py-6 text-medium-emphasis">
          No hay modelos registrados para esta marca. Agregue el primero arriba.
        </div>

        <VList
          v-else
          lines="one"
          density="compact"
          class="border rounded"
        >
          <VListItem
            v-for="model in models"
            :key="model.id"
          >
            <VListItemTitle class="font-weight-medium">
              {{ model.name }}
              <VChip
                size="x-small"
                :color="model.is_active ? 'success' : 'secondary'"
                variant="tonal"
                class="ms-2"
              >
                {{ model.is_active ? 'Activo' : 'Inactivo' }}
              </VChip>
            </VListItemTitle>

            <VListItemSubtitle v-if="model.notes" class="text-caption">
              {{ model.notes }}
            </VListItemSubtitle>

            <template #append>
              <VBtn
                size="x-small"
                variant="text"
                :color="model.is_active ? 'warning' : 'success'"
                @click="toggleModel(model)"
              >
                {{ model.is_active ? 'Desactivar' : 'Activar' }}
              </VBtn>
            </template>
          </VListItem>
        </VList>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-4 justify-end">
        <VBtn
          variant="tonal"
          @click="close"
        >
          Cerrar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
