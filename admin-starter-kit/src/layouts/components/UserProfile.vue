<script setup>
import { $api, apiError } from '@/utils/api'
import { clearSession, currentUser } from '@/utils/session'

const router = useRouter()
const busy = ref(false)
const error = ref('')
async function logout() {
  if (busy.value) return
  busy.value = true
  error.value = ''
  try {
    await $api('/auth/logout', { method: 'POST' })
    clearSession()
    await router.replace('/login')
  } catch (failure) {
    error.value = apiError(failure)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="d-flex align-center gap-3">
    <div class="text-end">
      <div class="text-body-2 font-weight-medium">
        {{ currentUser?.name }}
      </div>
      <div class="text-caption">
        {{ currentUser?.role === 'dueno' ? 'Dueño' : 'Vendedor' }}
      </div>
    </div>
    <VBtn
      variant="tonal"
      color="error"
      prepend-icon="ri-logout-box-r-line"
      :loading="busy"
      @click="logout"
    >
      Cerrar sesión
    </VBtn>
    <VSnackbar
      :model-value="!!error"
      color="error"
      @update:model-value="error = ''"
    >
      {{ error }}
    </VSnackbar>
  </div>
</template>
