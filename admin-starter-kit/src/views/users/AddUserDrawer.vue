<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { $api, apiError } from '@/utils/api'
import { currentUser, updateIdentity } from '@/utils/session'

// Adaptado de admin-full-version/src/views/apps/user/list/AddNewUserDrawer.vue
const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  user: { type: Object, default: null },
})

const emit = defineEmits(['update:isDrawerOpen', 'saved'])

const roles = [
  { title: 'Vendedor Mostrador', value: 'vendedor' },
  { title: 'Dueño / Administrador', value: 'dueno' },
]

const genders = [
  { title: 'Masculino', value: 'masculino' },
  { title: 'Femenino', value: 'femenino' },
  { title: 'Otro', value: 'otro' },
]

const branches = [
  'Casa Matriz',
  'Sucursal 1 - Central',
  'Sucursal 2 - Equipetrol',
]

const form = ref({
  ci: '',
  name: '',
  username: '',
  email: '',
  phone: '',
  address: '',
  gender: null,
  salesCommission: 0,
  branch: 'Casa Matriz',
  role: 'vendedor',
  password: '',
  pin: '',
})

const avatarFile = ref(null)
const avatarPreview = ref('')
const busy = ref(false)
const errors = ref({})
const error = ref('')
const refForm = ref()
const refFileInput = ref()

const required = value => !!String(value || '').trim() || 'Este campo es obligatorio.'
const isEdit = computed(() => Boolean(props.user && typeof props.user === 'object' && props.user.id))

watch(() => props.isDrawerOpen, open => {
  if (!open) return

  avatarFile.value = null
  avatarPreview.value = props.user?.avatarUrl || props.user?.avatar_url || ''

  if (isEdit.value) {
    form.value = {
      ci: props.user?.ci || '',
      name: props.user?.name || '',
      username: props.user?.username || '',
      email: props.user?.email || '',
      phone: props.user?.phone || '',
      address: props.user?.address || '',
      gender: props.user?.gender || null,
      salesCommission: props.user?.salesCommission ?? props.user?.sales_commission ?? 0,
      branch: props.user?.branch || 'Casa Matriz',
      role: props.user?.role || 'vendedor',
      password: '',
      pin: '',
    }
  } else {
    form.value = {
      ci: '',
      name: '',
      username: '',
      email: '',
      phone: '',
      address: '',
      gender: null,
      salesCommission: 0,
      branch: 'Casa Matriz',
      role: 'vendedor',
      password: '',
      pin: '',
    }
  }

  errors.value = {}
  error.value = ''
  nextTick(() => refForm.value?.resetValidation())
})

function close() {
  if (!busy.value) emit('update:isDrawerOpen', false)
}

function onFileSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return

  if (file.size > 2 * 1024 * 1024) {
    error.value = 'La fotografía no debe superar 2 MB.'
    return
  }

  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

function removeAvatar() {
  avatarFile.value = null
  avatarPreview.value = ''
  if (refFileInput.value) refFileInput.value.value = ''
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
    if (form.value.ci) formData.append('ci', form.value.ci.trim())
    formData.append('username', form.value.username.trim())
    if (form.value.email) formData.append('email', form.value.email.trim())
    if (form.value.phone) formData.append('phone', form.value.phone.trim())
    if (form.value.address) formData.append('address', form.value.address.trim())
    if (form.value.gender) formData.append('gender', form.value.gender)
    formData.append('sales_commission', String(form.value.salesCommission || 0))
    if (form.value.branch) formData.append('branch', form.value.branch)
    formData.append('role', form.value.role)

    if (form.value.password) {
      formData.append('password', form.value.password)
    }
    if (form.value.pin) {
      formData.append('pin', form.value.pin)
    }

    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }

    let url = '/users'
    let method = 'POST'

    if (isEdit.value) {
      url = `/users/${props.user.id}`
      // En Laravel, para multipart PUT en PHP nativo se usa POST con _method=PUT
      formData.append('_method', 'PUT')
    }

    await $api(url, {
      method: 'POST',
      body: formData,
    })

    if (isEdit.value && props.user?.id === currentUser.value?.id) {
      updateIdentity(await $api('/auth/me'))
    }

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
    :width="480"
    location="end"
    class="scrollable-content"
    :model-value="isDrawerOpen"
    :persistent="busy"
    @update:model-value="value => !value && close()"
  >
    <AppDrawerHeaderSection
      :title="isEdit ? 'Editar ficha de usuario' : 'Nuevo personal / usuario'"
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
              <!-- Avatar Upload & Preview -->
              <VCol
                cols="12"
                class="d-flex align-center gap-4 py-2"
              >
                <VAvatar
                  rounded
                  size="72"
                  color="primary"
                  variant="tonal"
                >
                  <VImg
                    v-if="avatarPreview"
                    :src="avatarPreview"
                    cover
                  />
                  <span
                    v-else
                    class="text-h5 font-weight-bold"
                  >
                    {{ form.name ? form.name.slice(0, 2).toUpperCase() : 'US' }}
                  </span>
                </VAvatar>

                <div class="d-flex flex-column gap-2">
                  <div class="d-flex gap-2">
                    <VBtn
                      size="small"
                      variant="tonal"
                      color="primary"
                      prepend-icon="ri-upload-2-line"
                      @click="refFileInput?.click()"
                    >
                      Subir foto
                    </VBtn>
                    <VBtn
                      v-if="avatarPreview"
                      size="small"
                      variant="text"
                      color="error"
                      icon="ri-delete-bin-line"
                      @click="removeAvatar"
                    />
                  </div>
                  <span class="text-caption text-medium-emphasis">
                    JPG, PNG o WebP. Máx. 2 MB.
                  </span>
                  <input
                    ref="refFileInput"
                    type="file"
                    accept="image/*"
                    class="d-none"
                    @change="onFileSelected"
                  >
                </div>
              </VCol>

              <VDivider class="my-2" />

              <!-- Datos Obligatorios de Identidad -->
              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model="form.ci"
                  label="CI / Cédula *"
                  :rules="[required]"
                  maxlength="30"
                  :error-messages="errors.ci"
                  :disabled="busy"
                  placeholder="Ej. 8472910 LP"
                />
              </VCol>

              <VCol
                cols="12"
                sm="6"
              >
                <VSelect
                  v-model="form.role"
                  label="Rol *"
                  :items="roles"
                  :disabled="busy || (isEdit && props.user?.id === currentUser?.id)"
                  :error-messages="errors.role"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="form.name"
                  label="Nombre completo *"
                  :rules="[required]"
                  maxlength="150"
                  :error-messages="errors.name"
                  :disabled="busy"
                  placeholder="Ej. Juan Pérez Ramos"
                />
              </VCol>

              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model="form.username"
                  label="Usuario (Alias) *"
                  :rules="[required]"
                  maxlength="50"
                  :error-messages="errors.username"
                  :disabled="busy"
                  placeholder="ej. jperez"
                  hint="Para inicio de sesión en sistema"
                  persistent-hint
                />
              </VCol>

              <VCol
                cols="12"
                sm="6"
              >
                <VSelect
                  v-model="form.branch"
                  label="Sucursal"
                  :items="branches"
                  :disabled="busy"
                />
              </VCol>

              <!-- Credenciales -->
              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model="form.password"
                  :label="isEdit ? 'Nueva Contraseña' : 'Contraseña *'"
                  type="password"
                  autocomplete="new-password"
                  :rules="isEdit ? [] : [required]"
                  :error-messages="errors.password"
                  :disabled="busy"
                  :hint="isEdit ? 'Dejar vacío para conservar actual' : 'Mínimo 6 caracteres'"
                  persistent-hint
                />
              </VCol>

              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model="form.pin"
                  label="PIN rápido (4 dígitos)"
                  type="password"
                  inputmode="numeric"
                  maxlength="4"
                  autocomplete="new-password"
                  :error-messages="errors.pin"
                  :disabled="busy"
                  hint="Opcional. Para acceso rápido con PIN"
                  persistent-hint
                />
              </VCol>

              <VDivider class="my-2" />

              <!-- Información de Contacto y Perfil (Opcionales) -->
              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model="form.phone"
                  label="Teléfono / Celular"
                  maxlength="30"
                  :error-messages="errors.phone"
                  :disabled="busy"
                  placeholder="Ej. 77012345"
                />
              </VCol>

              <VCol
                cols="12"
                sm="6"
              >
                <VSelect
                  v-model="form.gender"
                  label="Sexo"
                  :items="genders"
                  clearable
                  :disabled="busy"
                  placeholder="Seleccionar"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="form.email"
                  label="Correo electrónico"
                  type="email"
                  maxlength="150"
                  :error-messages="errors.email"
                  :disabled="busy"
                  placeholder="ejemplo@servimatica.com"
                  hint="Opcional"
                  persistent-hint
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="form.address"
                  label="Dirección de domicilio"
                  maxlength="255"
                  :error-messages="errors.address"
                  :disabled="busy"
                  placeholder="Ej. Calle Junín #452, Zona Central"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model.number="form.salesCommission"
                  label="Comisión sobre ventas (%)"
                  type="number"
                  step="0.1"
                  min="0"
                  max="100"
                  suffix="%"
                  :error-messages="errors.sales_commission"
                  :disabled="busy"
                  hint="Porcentaje de incentivo asignado por ventas concretadas"
                  persistent-hint
                />
              </VCol>

              <VCol
                cols="12"
                class="d-flex gap-3 mt-2"
              >
                <VBtn
                  type="submit"
                  :loading="busy"
                >
                  Guardar
                </VBtn>
                <VBtn
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
