<script setup>
import { computed } from 'vue'
import { useConfigStore } from '@core/stores/config'
import { useTheme } from 'vuetify'

const configStore = useConfigStore()
const vuetifyTheme = useTheme()

const isDark = computed(() => {
  return vuetifyTheme.global.name.value === 'dark' || configStore.theme === 'dark'
})

const toggleTheme = () => {
  const nextTheme = isDark.value ? 'light' : 'dark'
  configStore.theme = nextTheme
  vuetifyTheme.global.name.value = nextTheme
}
</script>

<template>
  <IconBtn
    id="theme-switcher-btn"
    @click="toggleTheme"
  >
    <VIcon :icon="isDark ? 'ri-sun-line' : 'ri-moon-line'" />

    <VTooltip
      activator="parent"
      open-delay="500"
      location="bottom"
    >
      <span>{{ isDark ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro' }}</span>
    </VTooltip>
  </IconBtn>
</template>
