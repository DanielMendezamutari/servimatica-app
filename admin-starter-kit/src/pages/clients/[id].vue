<script setup>
import { $api, apiError } from '@/utils/api'
import AddEditClientDrawer from '@/views/clients/AddEditClientDrawer.vue'

const route = useRoute()
const clientId = computed(() => route.params.id)

const clientData = ref(null)
const loading = ref(true)
const error = ref('')
const activeTab = ref(0)
const isDrawerOpen = ref(false)

// Pestañas
const salesList = ref([])
const salesLoading = ref(false)
const salesPage = ref(1)
const salesTotal = ref(0)

const quotesList = ref([])
const quotesLoading = ref(false)
const quotesPage = ref(1)
const quotesTotal = ref(0)

const warrantiesList = ref([])
const warrantiesLoading = ref(false)

const updatingNotes = ref(false)
const notesFeedback = ref('')

async function fetchClientDetail() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api(`/v1/clients/${clientId.value}`)
    clientData.value = res.data || res
  } catch (err) {
    error.value = apiError(err)
  } finally {
    loading.value = false
  }
}

async function fetchSales() {
  salesLoading.value = true
  try {
    const res = await $api(`/v1/clients/${clientId.value}/sales`, {
      params: { page: salesPage.value, per_page: 10 },
    })
    salesList.value = res.data || []
    salesTotal.value = res.meta?.total || salesList.value.length
  } catch {
    //
  } finally {
    salesLoading.value = false
  }
}

async function fetchQuotes() {
  quotesLoading.value = true
  try {
    const res = await $api(`/v1/clients/${clientId.value}/quotes`, {
      params: { page: quotesPage.value, per_page: 10 },
    })
    quotesList.value = res.data || []
    quotesTotal.value = res.meta?.total || quotesList.value.length
  } catch {
    //
  } finally {
    quotesLoading.value = false
  }
}

async function fetchWarranties() {
  warrantiesLoading.value = true
  try {
    const res = await $api(`/v1/clients/${clientId.value}/warranties`)
    warrantiesList.value = res.data || res || []
  } catch {
    //
  } finally {
    warrantiesLoading.value = false
  }
}

async function saveCommercialNotes() {
  if (!clientData.value) return
  updatingNotes.value = true
  notesFeedback.value = ''
  try {
    await $api(`/v1/clients/${clientId.value}`, {
      method: 'PUT',
      body: {
        name: clientData.value.name,
        notes: clientData.value.notes,
        client_type: clientData.value.client_type,
        city: clientData.value.city,
      },
    })
    notesFeedback.value = 'Notas comerciales actualizadas correctamente.'
    setTimeout(() => { notesFeedback.value = '' }, 3500)
  } catch (err) {
    notesFeedback.value = apiError(err)
  } finally {
    updatingNotes.value = false
  }
}

function onClientSaved() {
  fetchClientDetail()
}

watch(activeTab, newTab => {
  if (newTab === 0 && salesList.value.length === 0) fetchSales()
  if (newTab === 1 && quotesList.value.length === 0) fetchQuotes()
  if (newTab === 2 && warrantiesList.value.length === 0) fetchWarranties()
})

const getClientTypeChip = type => {
  switch (type) {
    case 'empresa':
      return { color: 'primary', label: 'Empresa / Corp.', icon: 'ri-building-line' }
    case 'mayorista':
      return { color: 'info', label: 'Mayorista / Técnico', icon: 'ri-tools-line' }
    case 'institucion':
      return { color: 'secondary', label: 'Institucional', icon: 'ri-government-line' }
    default:
      return { color: 'default', label: 'Consumidor Final', icon: 'ri-user-line' }
  }
}

onMounted(async () => {
  await fetchClientDetail()
  fetchSales()
})
</script>

<template>
  <div>
    <!-- Botón Volver y Título -->
    <div class="d-flex align-center gap-3 mb-6">
      <VBtn
        icon
        variant="tonal"
        size="small"
        :to="{ path: '/clients' }"
        title="Volver al Directorio"
      >
        <VIcon icon="ri-arrow-left-line" />
      </VBtn>

      <div>
        <h4 class="text-h4 font-weight-medium">
          Ficha Comercial 360° del Cliente
        </h4>
        <span class="text-body-2 text-medium-emphasis">
          Historial integral de compras, cotizaciones, garantías y condiciones comerciales
        </span>
      </div>
    </div>

    <!-- Alerta de Carga / Error -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <VProgressCircular
        indeterminate
        color="primary"
        size="48"
      />
      <div class="mt-4 text-medium-emphasis">
        Cargando perfil comercial 360°...
      </div>
    </div>

    <VAlert
      v-else-if="error"
      type="error"
      variant="tonal"
      class="mb-6"
    >
      {{ error }}
    </VAlert>

    <!-- Contenido Principal 360 -->
    <VRow v-else-if="clientData">
      <!-- Columna Izquierda: Panel Bio y Datos Rápidos (Estilo Materio) -->
      <VCol
        cols="12"
        md="4"
      >
        <VCard class="elevation-1 mb-6">
          <VCardText class="text-center pt-8 pb-4">
            <!-- Avatar -->
            <VAvatar
              size="88"
              color="primary"
              variant="tonal"
              class="mb-4"
            >
              <span class="text-h3 font-weight-bold">
                {{ clientData.name.charAt(0).toUpperCase() }}
              </span>
            </VAvatar>

            <!-- Nombre y Tipo -->
            <h5 class="text-h5 font-weight-bold mb-1">
              {{ clientData.name }}
            </h5>
            <div class="mb-3">
              <VChip
                size="small"
                :color="getClientTypeChip(clientData.client_type).color"
                variant="tonal"
              >
                <VIcon
                  start
                  size="14"
                  :icon="getClientTypeChip(clientData.client_type).icon"
                />
                {{ getClientTypeChip(clientData.client_type).label }}
              </VChip>
            </div>

            <!-- Botón WhatsApp -->
            <div
              v-if="clientData.whatsapp_url"
              class="mb-4"
            >
              <VBtn
                :href="clientData.whatsapp_url"
                target="_blank"
                rel="noopener noreferrer"
                color="success"
                variant="tonal"
                size="small"
                prepend-icon="ri-whatsapp-line"
              >
                Contactar por WhatsApp
              </VBtn>
            </div>

            <!-- KPIs Rápidos en Grid -->
            <div class="d-flex justify-space-around text-center py-4 bg-var-theme-background rounded">
              <div>
                <div class="text-caption text-medium-emphasis text-uppercase">
                  Invertido
                </div>
                <div class="text-h6 font-weight-bold text-primary">
                  Bs. {{ Number(clientData.stats?.total_spent_bs || 0).toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}
                </div>
              </div>

              <VDivider
                vertical
                inset
              />

              <div>
                <div class="text-caption text-medium-emphasis text-uppercase">
                  Ventas
                </div>
                <div class="text-h6 font-weight-bold">
                  {{ clientData.stats?.sales_count || 0 }}
                </div>
              </div>

              <VDivider
                vertical
                inset
              />

              <div>
                <div class="text-caption text-medium-emphasis text-uppercase">
                  Garantías
                </div>
                <div class="text-h6 font-weight-bold text-success">
                  {{ clientData.stats?.active_warranties_count || 0 }}
                </div>
              </div>
            </div>
          </VCardText>

          <VDivider />

          <!-- Datos de Contacto y Ubicación -->
          <VCardText>
            <div class="text-subtitle-2 font-weight-bold text-uppercase mb-3">
              Información de Contacto
            </div>

            <VList
              density="compact"
              class="card-list"
            >
              <VListItem>
                <template #prepend>
                  <VIcon
                    icon="ri-id-card-line"
                    size="18"
                    class="me-2 text-medium-emphasis"
                  />
                </template>
                <VListItemTitle class="text-body-2">
                  <span class="text-medium-emphasis">NIT / CI:</span>
                  <span class="font-weight-medium ms-1">{{ clientData.nit_ci || 'No registrado' }}</span>
                </VListItemTitle>
              </VListItem>

              <VListItem>
                <template #prepend>
                  <VIcon
                    icon="ri-phone-line"
                    size="18"
                    class="me-2 text-medium-emphasis"
                  />
                </template>
                <VListItemTitle class="text-body-2">
                  <span class="text-medium-emphasis">Celular:</span>
                  <span class="font-weight-medium ms-1">{{ clientData.phone || 'No registrado' }}</span>
                </VListItemTitle>
              </VListItem>

              <VListItem>
                <template #prepend>
                  <VIcon
                    icon="ri-mail-line"
                    size="18"
                    class="me-2 text-medium-emphasis"
                  />
                </template>
                <VListItemTitle class="text-body-2">
                  <span class="text-medium-emphasis">Email:</span>
                  <span class="font-weight-medium ms-1">{{ clientData.email || 'No registrado' }}</span>
                </VListItemTitle>
              </VListItem>

              <VListItem>
                <template #prepend>
                  <VIcon
                    icon="ri-map-pin-line"
                    size="18"
                    class="me-2 text-medium-emphasis"
                  />
                </template>
                <VListItemTitle class="text-body-2">
                  <span class="text-medium-emphasis">Ciudad:</span>
                  <span class="font-weight-medium ms-1">{{ clientData.city || 'Trinidad' }}</span>
                </VListItemTitle>
              </VListItem>

              <VListItem>
                <template #prepend>
                  <VIcon
                    icon="ri-building-line"
                    size="18"
                    class="me-2 text-medium-emphasis"
                  />
                </template>
                <VListItemTitle class="text-body-2">
                  <span class="text-medium-emphasis">Dirección:</span>
                  <span class="font-weight-medium ms-1">{{ clientData.address || 'No registrada' }}</span>
                </VListItemTitle>
              </VListItem>

              <VListItem>
                <template #prepend>
                  <VIcon
                    icon="ri-calendar-line"
                    size="18"
                    class="me-2 text-medium-emphasis"
                  />
                </template>
                <VListItemTitle class="text-body-2">
                  <span class="text-medium-emphasis">Registrado el:</span>
                  <span class="font-weight-medium ms-1">{{ new Date(clientData.created_at).toLocaleDateString('es-BO') }}</span>
                </VListItemTitle>
              </VListItem>
            </VList>

            <VBtn
              block
              variant="outlined"
              color="primary"
              class="mt-4"
              prepend-icon="ri-pencil-line"
              @click="isDrawerOpen = true"
            >
              Editar Cliente
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Columna Derecha: Pestañas de Historial, Cotizaciones y Garantías -->
      <VCol
        cols="12"
        md="8"
      >
        <VCard class="elevation-1">
          <VTabs
            v-model="activeTab"
            class="v-tabs-pill border-b"
          >
            <VTab>
              <VIcon
                start
                icon="ri-shopping-cart-line"
              />
              Historial de Ventas ({{ clientData.stats?.sales_count || 0 }})
            </VTab>
            <VTab>
              <VIcon
                start
                icon="ri-file-list-3-line"
              />
              Cotizaciones ({{ clientData.stats?.quotes_count || 0 }})
            </VTab>
            <VTab>
              <VIcon
                start
                icon="ri-shield-check-line"
              />
              Garantías de Equipos ({{ clientData.stats?.active_warranties_count || 0 }})
            </VTab>
            <VTab>
              <VIcon
                start
                icon="ri-sticky-note-line"
              />
              Notas Comerciales
            </VTab>
          </VTabs>

          <VWindow
            v-model="activeTab"
            class="pa-4"
          >
            <!-- PESTAÑA 1: VENTAS -->
            <VWindowItem>
              <div
                v-if="salesLoading"
                class="text-center py-6 text-medium-emphasis"
              >
                <VProgressCircular
                  indeterminate
                  size="28"
                  class="me-2"
                />
                Cargando historial de compras...
              </div>

              <div
                v-else-if="salesList.length === 0"
                class="text-center py-8 text-medium-emphasis"
              >
                <VIcon
                  icon="ri-shopping-bag-line"
                  size="40"
                  class="mb-2 text-disabled"
                />
                <div>El cliente aún no registra ventas completadas.</div>
              </div>

              <VTable
                v-else
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>Nº Ticket / Factura</th>
                    <th>Fecha</th>
                    <th>Método</th>
                    <th class="text-center">Artículos</th>
                    <th class="text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="s in salesList"
                    :key="s.id"
                  >
                    <td class="font-weight-medium">
                      {{ s.ticket_code }}
                    </td>
                    <td class="text-body-2">
                      {{ new Date(s.created_at).toLocaleDateString('es-BO') }}
                    </td>
                    <td>
                      <VChip
                        size="x-small"
                        variant="tonal"
                      >
                        {{ s.payment_method }}
                      </VChip>
                    </td>
                    <td class="text-center text-body-2">
                      {{ s.total_units }} uds ({{ s.items_count }} items)
                    </td>
                    <td class="text-end font-weight-bold">
                      Bs. {{ Number(s.total).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </VWindowItem>

            <!-- PESTAÑA 2: COTIZACIONES -->
            <VWindowItem>
              <div
                v-if="quotesLoading"
                class="text-center py-6 text-medium-emphasis"
              >
                <VProgressCircular
                  indeterminate
                  size="28"
                  class="me-2"
                />
                Cargando cotizaciones...
              </div>

              <div
                v-else-if="quotesList.length === 0"
                class="text-center py-8 text-medium-emphasis"
              >
                <VIcon
                  icon="ri-file-list-3-line"
                  size="40"
                  class="mb-2 text-disabled"
                />
                <div>No hay cotizaciones emitidas para este cliente.</div>
              </div>

              <VTable
                v-else
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>Nº Cotización</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th class="text-center">Artículos</th>
                    <th class="text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="q in quotesList"
                    :key="q.id"
                  >
                    <td class="font-weight-medium">
                      {{ q.quote_number }}
                    </td>
                    <td class="text-body-2">
                      {{ new Date(q.created_at).toLocaleDateString('es-BO') }}
                    </td>
                    <td>
                      <VChip
                        size="x-small"
                        :color="q.status === 'converted' ? 'success' : (q.status === 'cancelled' ? 'error' : 'warning')"
                        variant="tonal"
                      >
                        {{ q.status }}
                      </VChip>
                    </td>
                    <td class="text-center text-body-2">
                      {{ q.items_count }} items
                    </td>
                    <td class="text-end font-weight-bold">
                      Bs. {{ Number(q.total).toLocaleString('es-BO', { minimumFractionDigits: 2 }) }}
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </VWindowItem>

            <!-- PESTAÑA 3: GARANTÍAS Y EQUIPOS -->
            <VWindowItem>
              <div
                v-if="warrantiesLoading"
                class="text-center py-6 text-medium-emphasis"
              >
                <VProgressCircular
                  indeterminate
                  size="28"
                  class="me-2"
                />
                Cargando garantías registradas...
              </div>

              <div
                v-else-if="warrantiesList.length === 0"
                class="text-center py-8 text-medium-emphasis"
              >
                <VIcon
                  icon="ri-shield-line"
                  size="40"
                  class="mb-2 text-disabled"
                />
                <div>No se registran equipos con cobertura de garantía asociada a este cliente.</div>
              </div>

              <VTable
                v-else
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>Equipo / Producto</th>
                    <th>Nº Serie (S/N)</th>
                    <th>Venta</th>
                    <th>Vence</th>
                    <th class="text-end">Estado de Cobertura</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="w in warrantiesList"
                    :key="w.id"
                  >
                    <td>
                      <div class="font-weight-medium">
                        {{ w.product_name }}
                      </div>
                      <span class="text-caption text-medium-emphasis">
                        Garantía: {{ w.warranty_days }} días
                      </span>
                    </td>
                    <td>
                      <VChip
                        size="x-small"
                        variant="outlined"
                        class="font-mono"
                      >
                        {{ w.serial_number }}
                      </VChip>
                    </td>
                    <td class="text-body-2">
                      {{ w.ticket_code }}
                    </td>
                    <td class="text-body-2">
                      {{ new Date(w.warranty_until).toLocaleDateString('es-BO') }}
                    </td>
                    <td class="text-end">
                      <VChip
                        size="small"
                        :color="w.is_valid ? 'success' : 'error'"
                        variant="tonal"
                      >
                        <VIcon
                          start
                          size="14"
                          :icon="w.is_valid ? 'ri-shield-check-line' : 'ri-shield-cross-line'"
                        />
                        {{ w.status_label }}
                      </VChip>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </VWindowItem>

            <!-- PESTAÑA 4: NOTAS COMERCIALES -->
            <VWindowItem>
              <div class="d-flex flex-column gap-4">
                <div>
                  <h6 class="text-h6 font-weight-medium mb-1">
                    Condiciones Comerciales y Observaciones
                  </h6>
                  <p class="text-body-2 text-medium-emphasis">
                    Espacio para registrar acuerdos especiales, límite de crédito o preferencias de contacto de este cliente.
                  </p>
                </div>

                <VAlert
                  v-if="notesFeedback"
                  type="info"
                  variant="tonal"
                  density="compact"
                >
                  {{ notesFeedback }}
                </VAlert>

                <VTextarea
                  v-model="clientData.notes"
                  rows="5"
                  placeholder="Ej: Cliente compra habitualmente repuestos con crédito a 15 días. Facturar siempre con razón social comercial..."
                  counter
                />

                <div class="d-flex justify-end">
                  <VBtn
                    :loading="updatingNotes"
                    prepend-icon="ri-save-line"
                    @click="saveCommercialNotes"
                  >
                    Guardar Notas Comerciales
                  </VBtn>
                </div>
              </div>
            </VWindowItem>
          </VWindow>
        </VCard>
      </VCol>
    </VRow>

    <!-- Drawer Lateral para Edición -->
    <AddEditClientDrawer
      v-model:is-drawer-open="isDrawerOpen"
      :client="clientData"
      @saved="onClientSaved"
    />
  </div>
</template>

<style scoped>
.font-mono {
  font-family: monospace;
}
</style>
