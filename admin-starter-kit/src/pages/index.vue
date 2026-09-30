<script setup>
import { currentUser } from '@/utils/session'
</script>

<template>
  <VCard>
    <VCardText class="pa-8">
      <VIcon
        icon="ri-home-smile-2-line"
        size="48"
        color="primary"
        class="mb-4"
      />
      <h1 class="text-h4 mb-3">
        Bienvenido, {{ currentUser?.name }}
      </h1>
      <p class="text-body-1">
        Su sesión de {{ currentUser?.role === 'dueno' ? 'Dueño' : 'Vendedor' }} está activa.
      </p>
      <VBtn
        v-if="$can('manage', 'User')"
        to="/users"
        prepend-icon="ri-team-line"
      >
        Gestionar personal
      </VBtn>
      <p
        v-else
        class="mb-0"
      >
        Está en su espacio de trabajo. Recuerde cerrar sesión al finalizar su turno.
      </p>
    </VCardText>
  </VCard>
</template>
