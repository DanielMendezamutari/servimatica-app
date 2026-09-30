<script setup>
import { $api, apiError } from '@/utils/api'
import { saveSession } from '@/utils/session'
import { useGenerateImageVariant } from '@/@core/composable/useGenerateImageVariant'
import authLight from '@images/pages/auth-v2-login-illustration-light.png'
import authDark from '@images/pages/auth-v2-login-illustration-dark.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

// Layout, ilustración y formulario reutilizados de admin-full-version/src/pages/login.vue.
const router = useRouter()
const form = ref({ login: '', password: '', pin: '' })
const mode = ref('password')
const busy = ref(false)
const error = ref('')
const errors = ref({})
const visible = ref(false)
const illustration = useGenerateImageVariant(authLight, authDark)

watch(mode, () => {
  form.value.password = ''
  form.value.pin = ''
  error.value = ''
  errors.value = {}
})
function updatePin(value) {
  form.value.pin = String(value || '').replace(/[^0-9]/g, '').slice(0, 4)
}
watch(() => form.value.pin, value => {
  if (mode.value === 'pin' && value.length === 4) login()
})
async function login() {
  if (busy.value) return
  errors.value = {}
  error.value = ''
  if (!form.value.login.trim()) errors.value.login = ['Ingrese su correo o alias.']
  if (mode.value === 'password' && !form.value.password) errors.value.password = ['Ingrese su contraseña.']
  if (mode.value === 'pin' && !/^[0-9]{4}$/.test(form.value.pin)) errors.value.pin = ['Ingrese los 4 números del PIN.']
  if (Object.keys(errors.value).length) {
    if (mode.value === 'pin') form.value.pin = ''
    
    return
  }
  busy.value = true
  let authenticated = false
  try {
    const data = await $api('/auth/login', { method: 'POST', body: {
      login: form.value.login.trim(), [mode.value]: form.value[mode.value],
    } })

    saveSession(data)
    authenticated = true
    await router.replace('/')
  } catch (failure) {
    error.value = authenticated
      ? 'Su acceso fue validado, pero no se pudo cargar el panel. Recargue la página.'
      : apiError(failure)
    errors.value = failure.data?.errors || {}
    form.value.pin = ''
    form.value.password = ''
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <VRow
    no-gutters
    class="auth-wrapper"
  >
    <VCol
      md="7"
      class="d-none d-md-flex align-center justify-center pa-10"
    >
      <img
        :src="illustration"
        class="auth-illustration w-100"
        alt="Bienvenido a Servimática"
      >
    </VCol>
    <VCol
      cols="12"
      md="5"
      class="auth-card-v2 d-flex align-center justify-center bg-surface pa-6"
    >
      <VCard
        flat
        :max-width="460"
        class="w-100"
      >
        <VCardText>
          <div class="app-logo mb-8">
            <VNodeRenderer :nodes="themeConfig.app.logo" />
            <h1 class="text-h4">
              Servimática
            </h1>
          </div>
          <h2 class="text-h5 mb-2">
            Bienvenido
          </h2>
          <p>Ingrese con su correo o alias para comenzar su jornada.</p>
          <VAlert
            v-if="error"
            type="error"
            variant="tonal"
            class="mb-4"
            role="alert"
          >
            {{ error }}
          </VAlert>
          <VForm @submit.prevent="login">
            <VTextField
              v-model="form.login"
              label="Correo o alias"
              autocomplete="username"
              :disabled="busy"
              :error-messages="errors.login"
              class="mb-4"
              autofocus
            />
            <VTabs
              v-model="mode"
              grow
              :disabled="busy"
              class="mb-6"
            >
              <VTab
                value="password"
                :disabled="busy"
              >
                Contraseña
              </VTab>
              <VTab
                value="pin"
                :disabled="busy"
              >
                PIN de 4 dígitos
              </VTab>
            </VTabs>
            <template v-if="mode === 'password'">
              <VTextField
                v-model="form.password"
                label="Contraseña"
                autocomplete="current-password"
                :type="visible ? 'text' : 'password'"
                :append-inner-icon="visible ? 'ri-eye-off-line' : 'ri-eye-line'"
                :disabled="busy"
                :error-messages="errors.password"
                class="mb-4"
                @click:append-inner="visible = !visible"
              />
              <VBtn
                block
                type="submit"
                :loading="busy"
              >
                Ingresar
              </VBtn>
            </template>
            <template v-else>
              <VTextField
                :model-value="form.pin"
                label="PIN de 4 dígitos"
                type="password"
                inputmode="numeric"
                maxlength="4"
                autocomplete="off"
                :disabled="busy"
                :error-messages="errors.pin"
                hint="Ingresará automáticamente al completar el cuarto número."
                persistent-hint
                @update:model-value="updatePin"
              />
              <VRow class="mt-3">
                <VCol
                  v-for="digit in [1,2,3,4,5,6,7,8,9]"
                  :key="digit"
                  cols="4"
                >
                  <VBtn
                    block
                    size="large"
                    variant="tonal"
                    :disabled="busy"
                    :aria-label="'Número ' + digit"
                    @click="updatePin(form.pin + digit)"
                  >
                    {{ digit }}
                  </VBtn>
                </VCol>
                <VCol cols="4">
                  <VBtn
                    block
                    variant="text"
                    :disabled="busy"
                    @click="updatePin('')"
                  >
                    Limpiar
                  </VBtn>
                </VCol>
                <VCol cols="4">
                  <VBtn
                    block
                    size="large"
                    variant="tonal"
                    :disabled="busy"
                    aria-label="Número 0"
                    @click="updatePin(form.pin + '0')"
                  >
                    0
                  </VBtn>
                </VCol>
                <VCol cols="4">
                  <VBtn
                    block
                    variant="text"
                    :disabled="busy"
                    @click="updatePin(form.pin.slice(0,-1))"
                  >
                    Borrar
                  </VBtn>
                </VCol>
              </VRow>
              <VProgressLinear
                v-if="busy"
                indeterminate
                class="mt-4"
                aria-label="Verificando acceso"
              />
            </template>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth.scss";
</style>
