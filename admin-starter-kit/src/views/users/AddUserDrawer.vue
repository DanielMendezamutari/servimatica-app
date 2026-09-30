<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { $api, apiError } from '@/utils/api'
import { currentUser, updateIdentity } from '@/utils/session'

// Adaptado de admin-full-version/src/views/apps/user/list/AddNewUserDrawer.vue.
const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  user: { type: Object, default: null },
})

const emit = defineEmits(['update:isDrawerOpen', 'saved'])
const form = ref({})
const busy = ref(false)
const errors = ref({})
const error = ref('')
const refForm = ref()
const roles = [{ title: 'Vendedor', value: 'vendedor' }, { title: 'Dueño', value: 'dueno' }]
const required = value => !!String(value || '').trim() || 'Este campo es obligatorio.'

watch(() => props.isDrawerOpen, open => {
  if (!open) return
  form.value = { name: props.user?.name || '', username: props.user?.username || '', email: props.user?.email || '',
    role: props.user?.role || 'vendedor', password: '', pin: '' }
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

  const body = { ...form.value }
  if (props.user) {
    if (!body.password) delete body.password
    if (!body.pin) delete body.pin
  }
  try {
    await $api(props.user ? `/users/${props.user.id}` : '/users', { method: props.user ? 'PUT' : 'POST', body })
    if (props.user?.id === currentUser.value?.id) updateIdentity(await $api('/auth/me'))
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
      :title="user ? 'Editar usuario' : 'Nuevo usuario'"
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
          <VForm
            ref="refForm"
            @submit.prevent="save"
          >
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="form.name"
                  label="Nombre completo"
                  :rules="[required]"
                  maxlength="150"
                  :error-messages="errors.name"
                  :disabled="busy"
                />
              </VCol>
              <VCol cols="12">
                <VTextField
                  v-model="form.username"
                  label="Alias"
                  :rules="[required]"
                  maxlength="50"
                  :error-messages="errors.username"
                  :disabled="busy"
                  hint="Entre 3 y 50 letras o números, sin espacios."
                />
              </VCol>
              <VCol cols="12">
                <VTextField
                  v-model="form.email"
                  label="Correo electrónico"
                  type="email"
                  :rules="[required]"
                  maxlength="150"
                  :error-messages="errors.email"
                  :disabled="busy"
                />
              </VCol>
              <VCol cols="12">
                <VSelect
                  v-model="form.role"
                  label="Rol"
                  :items="roles"
                  :disabled="busy || user?.id === currentUser?.id"
                  :error-messages="errors.role"
                />
              </VCol>
              <VCol cols="12">
                <VTextField
                  v-model="form.password"
                  label="Contraseña"
                  type="password"
                  autocomplete="new-password"
                  :rules="user ? [] : [required]"
                  :error-messages="errors.password"
                  :disabled="busy"
                  :hint="user ? 'Deje vacío para conservar la contraseña actual.' : 'Mínimo 6 caracteres.'"
                  persistent-hint
                />
              </VCol>
              <VCol cols="12">
                <VTextField
                  v-model="form.pin"
                  label="PIN de 4 dígitos"
                  type="password"
                  inputmode="numeric"
                  maxlength="4"
                  autocomplete="new-password"
                  :rules="user ? [] : [required]"
                  :error-messages="errors.pin"
                  :disabled="busy"
                  :hint="user ? 'Deje vacío para conservar el PIN actual.' : 'Exactamente 4 números.'"
                  persistent-hint
                />
              </VCol>
              <VCol
                cols="12"
                class="d-flex gap-3"
              >
                <VBtn
                  type="submit"
                  :loading="busy"
                >
                  Guardar
                </VBtn><VBtn
                  variant="outlined"
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
