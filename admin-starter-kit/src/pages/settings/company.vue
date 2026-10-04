<script setup>
import { $api, apiError } from '@/utils/api'

const activeTab = ref('identity')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const successNotice = ref('')

const form = ref({
  trade_name: '',
  legal_name: '',
  tax_id: '',
  slogan: '',
  branch_name: '',
  city: '',
  address: '',
  mobile: '',
  phone: '',
  email: '',
  logo_url: null,
  default_quote_terms: '',
  receipt_footer_message: '',
  warranty_terms: '',
})

const logoFile = ref(null)
const logoPreview = ref(null)

async function loadSettings() {
  loading.value = true
  error.value = ''
  try {
    const res = await $api('/company-settings')
    if (res) {
      form.value = {
        trade_name: res.trade_name || '',
        legal_name: res.legal_name || '',
        tax_id: res.tax_id || '',
        slogan: res.slogan || '',
        branch_name: res.branch_name || '',
        city: res.city || '',
        address: res.address || '',
        mobile: res.mobile || '',
        phone: res.phone || '',
        email: res.email || '',
        logo_url: res.logo_url || null,
        default_quote_terms: res.default_quote_terms || '',
        receipt_footer_message: res.receipt_footer_message || '',
        warranty_terms: res.warranty_terms || '',
      }
      logoPreview.value = res.logo_url || null
    }
  } catch (err) {
    error.value = apiError(err, 'Error al cargar la información de la empresa.')
  } finally {
    loading.value = false
  }
}

function onLogoSelected(event) {
  const file = event.target.files?.[0]
  if (file) {
    logoFile.value = file
    const reader = new FileReader()
    reader.onload = e => {
      logoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function removeSelectedLogo() {
  logoFile.value = null
  logoPreview.value = form.value.logo_url
}

async function saveSettings() {
  saving.value = true
  error.value = ''
  try {
    const formData = new FormData()

    formData.append('trade_name', form.value.trade_name || '')
    if (form.value.legal_name) formData.append('legal_name', form.value.legal_name)
    if (form.value.tax_id) formData.append('tax_id', form.value.tax_id)
    if (form.value.slogan) formData.append('slogan', form.value.slogan)
    formData.append('branch_name', form.value.branch_name || '')
    formData.append('city', form.value.city || '')
    formData.append('address', form.value.address || '')
    formData.append('mobile', form.value.mobile || '')
    if (form.value.phone) formData.append('phone', form.value.phone)
    if (form.value.email) formData.append('email', form.value.email)
    if (form.value.default_quote_terms) formData.append('default_quote_terms', form.value.default_quote_terms)
    if (form.value.receipt_footer_message) formData.append('receipt_footer_message', form.value.receipt_footer_message)
    if (form.value.warranty_terms) formData.append('warranty_terms', form.value.warranty_terms)

    if (logoFile.value) {
      formData.append('logo', logoFile.value)
    }

    const res = await $api('/company-settings', {
      method: 'POST',
      body: formData,
    })

    successNotice.value = res.message || 'Datos de la empresa actualizados exitosamente.'
    logoFile.value = null
    await loadSettings()
  } catch (err) {
    error.value = apiError(err, 'No se pudo guardar la configuración.')
  } finally {
    saving.value = false
  }
}

onMounted(loadSettings)
</script>

<template>
  <section>
    <div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
      <div>
        <h2 class="text-h4 font-weight-bold text-high-emphasis">
          Mi Empresa y Sucursal
        </h2>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Personaliza la identidad comercial, dirección y datos de contacto que se imprimen en proformas y tickets de venta.
        </p>
      </div>

      <VBtn
        color="primary"
        size="large"
        prepend-icon="ri-save-line"
        :loading="saving"
        @click="saveSettings"
      >
        Guardar Configuración
      </VBtn>
    </div>

    <VAlert
      v-if="error"
      type="error"
      variant="tonal"
      class="mb-6"
      closable
      @click:close="error = ''"
    >
      {{ error }}
    </VAlert>

    <VSnackbar
      v-model="successNotice"
      color="success"
      location="top end"
      :timeout="3500"
    >
      <div class="d-flex align-center gap-2">
        <VIcon icon="ri-checkbox-circle-line" size="20" />
        <span>{{ successNotice }}</span>
      </div>
    </VSnackbar>

    <VCard :loading="loading">
      <VTabs
        v-model="activeTab"
        class="border-b px-4"
      >
        <VTab value="identity">
          <VIcon icon="ri-building-line" class="me-2" />
          Identidad & Sucursal
        </VTab>
        <VTab value="contact">
          <VIcon icon="ri-image-line" class="me-2" />
          Contacto & Logotipo
        </VTab>
        <VTab value="policies">
          <VIcon icon="ri-file-text-line" class="me-2" />
          Políticas de Comprobantes
        </VTab>
      </VTabs>

      <VCardText class="pa-6 pt-8">
        <VWindow
          v-model="activeTab"
          :touch="false"
        >
          <!-- Pestaña 1: Identidad & Sucursal -->
          <VWindowItem
            value="identity"
            class="pt-3"
          >
            <VRow>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.trade_name"
                  label="Nombre Comercial *"
                  placeholder="Ej. Servimática PC"
                  prepend-inner-icon="ri-store-2-line"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.legal_name"
                  label="Razón Social / Titular Legal"
                  placeholder="Ej. Servimática Bolivia S.R.L."
                  prepend-inner-icon="ri-shield-user-line"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.tax_id"
                  label="NIT / Identificación Tributaria"
                  placeholder="Ej. 1029384756"
                  prepend-inner-icon="ri-article-line"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.slogan"
                  label="Slogan o Actividad Comercial"
                  placeholder="Ej. Venta de Equipos de Computación y Servicio Técnico"
                  prepend-inner-icon="ri-sparkling-line"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.branch_name"
                  label="Nombre de la Sucursal *"
                  placeholder="Ej. Sucursal Central / Casa Matriz"
                  prepend-inner-icon="ri-map-pin-2-line"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="form.city"
                  label="Ciudad y Departamento *"
                  placeholder="Ej. Riberalta, Beni — Bolivia"
                  prepend-inner-icon="ri-earth-line"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="form.address"
                  label="Dirección Física del Establecimiento *"
                  placeholder="Ej. Av. Plácido Méndez N° 450 entre Calle Sucre y Bolívar"
                  prepend-inner-icon="ri-road-map-line"
                />
              </VCol>
            </VRow>
          </VWindowItem>

          <!-- Pestaña 2: Contacto & Logotipo -->
          <VWindowItem
            value="contact"
            class="pt-3"
          >
            <VRow>
              <VCol cols="12" md="4">
                <VTextField
                  v-model="form.mobile"
                  label="Celular / WhatsApp Comercial *"
                  placeholder="Ej. 77012345"
                  prepend-inner-icon="ri-whatsapp-line"
                />
              </VCol>

              <VCol cols="12" md="4">
                <VTextField
                  v-model="form.phone"
                  label="Teléfono Fijo Secundario"
                  placeholder="Ej. 3-8521234"
                  prepend-inner-icon="ri-phone-line"
                />
              </VCol>

              <VCol cols="12" md="4">
                <VTextField
                  v-model="form.email"
                  label="Correo Electrónico de Contacto"
                  placeholder="contacto@servimatica.com"
                  prepend-inner-icon="ri-mail-line"
                />
              </VCol>

              <VCol cols="12">
                <VDivider class="my-4" />
                <h3 class="text-h6 font-weight-bold mb-2">
                  Logotipo Oficial de la Empresa
                </h3>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  Se imprimirá en el membrete de las proformas de cotización y encabezados formales. Formatos: PNG, JPG, WebP o SVG (máx. 2MB).
                </p>

                <div class="d-flex flex-wrap align-center gap-6">
                  <div
                    class="d-flex align-center justify-center rounded border pa-4 bg-surface"
                    style="width: 220px; height: 110px; border-style: dashed !important;"
                  >
                    <img
                      v-if="logoPreview"
                      :src="logoPreview"
                      alt="Logo Empresa"
                      style="max-width: 100%; max-height: 100%; object-fit: contain;"
                    />
                    <div v-else class="text-center text-medium-emphasis">
                      <VIcon icon="ri-image-add-line" size="32" class="mb-1" />
                      <div class="text-caption">Sin logotipo cargado</div>
                    </div>
                  </div>

                  <div class="d-flex flex-column gap-2">
                    <label class="v-btn v-btn--density-default v-btn--size-default v-btn--variant-tonal bg-primary text-white cursor-pointer px-4 py-2 rounded">
                      <VIcon icon="ri-upload-2-line" class="me-2" />
                      Subir Nuevo Logotipo
                      <input
                        type="file"
                        accept="image/*"
                        style="display: none;"
                        @change="onLogoSelected"
                      />
                    </label>

                    <VBtn
                      v-if="logoFile"
                      color="secondary"
                      variant="text"
                      size="small"
                      prepend-icon="ri-close-line"
                      @click="removeSelectedLogo"
                    >
                      Descartar imagen seleccionada
                    </VBtn>
                  </div>
                </div>
              </VCol>
            </VRow>
          </VWindowItem>

          <!-- Pestaña 3: Políticas de Comprobantes -->
          <VWindowItem
            value="policies"
            class="pt-3"
          >
            <VRow>
              <VCol cols="12">
                <VTextarea
                  v-model="form.default_quote_terms"
                  label="Términos y Condiciones Predeterminados para Cotizaciones"
                  placeholder="Escriba las condiciones de validez, disponibilidad de inventario y garantías que aparecerán al pie de cada proforma Carta."
                  rows="4"
                  prepend-inner-icon="ri-file-shield-line"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="form.receipt_footer_message"
                  label="Mensaje de Pie de Página para Tickets POS (80mm)"
                  placeholder="Ej. ¡Gracias por su preferencia! Conserve este comprobante para reclamos y validación de su garantía."
                  rows="2"
                  prepend-inner-icon="ri-receipt-line"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="form.warranty_terms"
                  label="Términos, Cláusulas y Exclusiones de Garantía Técnica"
                  placeholder="Escriba las condiciones de cobertura (ej. defectos de fabricación) y exclusiones (ej. caídas, sobrecarga eléctrica, sellos alterados) para imprimirse en los comprobantes y notas de venta..."
                  rows="4"
                  prepend-inner-icon="ri-shield-check-line"
                />
              </VCol>
            </VRow>
          </VWindowItem>
        </VWindow>
      </VCardText>
    </VCard>
  </section>
</template>
