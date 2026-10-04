<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { $api, apiError } from '@/utils/api'

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  category: { type: Object, default: null },
  initialType: { type: String, default: 'family' }, // 'family' | 'subfamily'
  defaultParentId: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:isDrawerOpen', 'saved'])
const creationType = ref('family') // 'family' | 'subfamily'
const form = ref({ name: '', description: '', parent_id: null })
const parentOptions = ref([])
const busy = ref(false)
const errors = ref({})
const error = ref('')
const refForm = ref()

const requiredName = value => !!String(value || '').trim() || (creationType.value === 'family' ? 'El nombre de la familia es obligatorio.' : 'El nombre de la subfamilia es obligatorio.')
const requiredParent = value => {
  if (creationType.value === 'subfamily') {
    return !!value || 'Debes seleccionar una familia principal para vincular esta subfamilia.'
  }
  return true
}

async function fetchParentOptions() {
  try {
    const res = await $api('/categories?tree=1')
    // All root categories excluding the currently edited category
    parentOptions.value = (res.data || [])
      .filter(c => !props.category || c.id !== props.category.id)
      .map(c => ({ title: c.name, value: c.id }))
  } catch (e) {
    parentOptions.value = []
  }
}

watch(() => props.isDrawerOpen, async open => {
  if (!open) return
  await fetchParentOptions()

  if (props.category) {
    creationType.value = props.category.parent_id ? 'subfamily' : 'family'
    form.value = {
      name: props.category.name || '',
      description: props.category.description || '',
      parent_id: props.category.parent_id || null,
    }
  } else {
    creationType.value = props.initialType || 'family'
    form.value = {
      name: '',
      description: '',
      parent_id: props.defaultParentId || (parentOptions.value[0]?.value ?? null),
    }
  }

  errors.value = {}
  error.value = ''
  nextTick(() => refForm.value?.resetValidation())
})

function close() {
  if (!busy.value) emit('update:isDrawerOpen', false)
}

async function save() {
  if (busy.value) return
  const { valid } = await refForm.value.validate()
  if (!valid) return

  busy.value = true
  error.value = ''
  errors.value = {}

  const isSub = creationType.value === 'subfamily'
  const body = {
    name: form.value.name.trim(),
    description: form.value.description ? form.value.description.trim() : null,
    parent_id: isSub ? (form.value.parent_id || null) : null,
  }

  try {
    const url = props.category ? `/categories/${props.category.id}` : '/categories'
    const method = props.category ? 'PUT' : 'POST'
    await $api(url, { method, body })
    emit('saved')
    emit('update:isDrawerOpen', false)
  } catch (failure) {
    errors.value = failure.data?.errors || {}
    error.value = apiError(failure)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="440"
    location="end"
    class="scrollable-content"
    :model-value="isDrawerOpen"
    :persistent="busy"
    @update:model-value="value => !value && close()"
  >
    <AppDrawerHeaderSection
      :title="category ? (category.parent_id ? 'Editar Subfamilia' : 'Editar Familia') : (creationType === 'family' ? 'Nueva Familia Principal' : 'Nueva Subfamilia')"
      @cancel="close"
    />
    <VDivider />
    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VAlert
            v-if="error"
            type="error"
            variant="tonal"
            class="mb-4"
            role="alert"
          >
            {{ error }}
          </VAlert>

          <!-- Selector de Modo: Familia Principal vs Subfamilia (solo en creación) -->
          <div
            v-if="!category"
            class="mb-4"
          >
            <div class="text-caption font-weight-medium mb-1 text-medium-emphasis">
              TIPO DE REGISTRO
            </div>
            <VBtnToggle
              v-model="creationType"
              mandatory
              color="primary"
              variant="outlined"
              density="compact"
              class="w-100 d-flex"
            >
              <VBtn
                value="family"
                class="flex-grow-1"
                prepend-icon="ri-folder-line"
              >
                Familia Principal
              </VBtn>
              <VBtn
                value="subfamily"
                class="flex-grow-1"
                prepend-icon="ri-node-tree"
              >
                Subfamilia
              </VBtn>
            </VBtnToggle>
          </div>

          <!-- Chip de tipo en modo edición -->
          <div
            v-else
            class="mb-4 d-flex align-center gap-2"
          >
            <span class="text-caption text-medium-emphasis">Tipo de elemento:</span>
            <VChip
              size="small"
              :color="category.parent_id ? 'info' : 'primary'"
              variant="tonal"
            >
              <VIcon
                start
                :icon="category.parent_id ? 'ri-node-tree' : 'ri-folder-line'"
                size="16"
              />
              {{ category.parent_id ? 'Subfamilia' : 'Familia Principal' }}
            </VChip>
          </div>

          <!-- Banner Explicativo -->
          <VAlert
            density="compact"
            variant="tonal"
            :color="creationType === 'family' ? 'primary' : 'info'"
            class="mb-4 text-caption"
          >
            <div v-if="creationType === 'family'">
              <strong>Familia Principal (Raíz):</strong> Agrupa un grupo general de productos en el catálogo (ej. <em>Laptops, Componentes, Periféricos</em>).
            </div>
            <div v-else>
              <strong>Subfamilia:</strong> Pertenece a una familia superior para clasificar productos específicos (ej. <em>Procesadores</em> dentro de <em>Componentes</em>).
            </div>
          </VAlert>

          <VForm
            ref="refForm"
            @submit.prevent="save"
          >
            <VRow>
              <!-- Selector de Familia Padre (Solo para Subfamilias) -->
              <VCol
                v-if="creationType === 'subfamily'"
                cols="12"
              >
                <VSelect
                  v-model="form.parent_id"
                  label="Familia Superior / Categoría Padre *"
                  :items="parentOptions"
                  :rules="[requiredParent]"
                  placeholder="Selecciona a qué familia pertenece..."
                  prepend-inner-icon="ri-folder-line"
                  :error-messages="errors.parent_id"
                  :disabled="busy"
                  no-data-text="No hay familias principales registradas. Crea una primero."
                />
                <div
                  v-if="!category"
                  class="mt-1 text-caption text-end"
                >
                  <a
                    href="javascript:void(0)"
                    class="text-primary font-weight-medium"
                    @click="creationType = 'family'"
                  >
                    + ¿No existe la familia? Crear Familia Principal
                  </a>
                </div>
              </VCol>

              <!-- Nombre -->
              <VCol cols="12">
                <VTextField
                  v-model="form.name"
                  :label="creationType === 'family' ? 'Nombre de la Familia Principal *' : 'Nombre de la Subfamilia *'"
                  :placeholder="creationType === 'family' ? 'Ej. Laptops, Monitores, Impresoras...' : 'Ej. Procesadores, Laptops Gamer, Memorias RAM...'"
                  :rules="[requiredName]"
                  maxlength="100"
                  :error-messages="errors.name"
                  :disabled="busy"
                  autofocus
                />
              </VCol>

              <!-- Descripción -->
              <VCol cols="12">
                <VTextarea
                  v-model="form.description"
                  label="Descripción (opcional)"
                  maxlength="500"
                  rows="3"
                  counter
                  :error-messages="errors.description"
                  :disabled="busy"
                  placeholder="Detalles breves sobre esta clasificación"
                />
              </VCol>

              <VCol
                cols="12"
                class="d-flex gap-3 pt-2"
              >
                <VBtn
                  type="submit"
                  :loading="busy"
                >
                  {{ category ? 'Guardar Cambios' : (creationType === 'family' ? 'Crear Familia' : 'Crear Subfamilia') }}
                </VBtn>

                <VBtn
                  variant="outlined"
                  color="secondary"
                  :disabled="busy"
                  @click="close"
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
