# Tasks: Rediseño Integral de Proformas y Cotizaciones (WhatsApp Persuasivo + PDF Institucional)

**Input**: Design artifacts from `specs/013-redisenio-proformas-cotizaciones/`
**Prerequisites**: `spec.md`, `plan.md`, `data-model.md`, `contracts/`, `research.md`, `quickstart.md`

---

## Phase 1: Setup & Prerequisites

**Purpose**: Verificación de fuentes de datos y modelos del dominio comercial

- [x] T001 Verificar disponibilidad de campos y datos en `app/Infrastructure/Persistence/Eloquent/CompanySettingModel.php`, `PaymentMethodModel.php` y `ProductModel.php`

---

## Phase 2: Foundational (Backend Service & API)

**Purpose**: Servicio de aplicación y endpoints para composición dinámica y persuasiva de la cotización

- [x] T002 Refactorizar `app/Application/Quote/WhatsAppQuoteService.php` para consultar dinámicamente `CompanySettingModel` (nombre, slogan, ciudad, dirección, teléfonos), `PaymentMethodModel` (cuentas bancarias y QR activos) y el tiempo de garantía de cada producto
- [x] T003 Actualizar el controlador `app/Infrastructure/Http/Controllers/Api/QuoteController.php` (método `whatsappLink`) para retornar tanto `whatsapp_link` como `formatted_message` y el teléfono utilizado
- [x] T004 [P] Crear pruebas funcionales en `tests/Feature/Quote/QuoteDynamicWhatsAppTest.php` validando la presencia de datos dinámicos de empresa, métodos de pago y ausencia de emojis corruptos

---

## Phase 3: User Story 1 - Mensaje de WhatsApp Comercial Persuasivo (Priority: P1) 🎯 MVP

**Goal**: Generar un mensaje de WhatsApp que resalte el valor del producto, garantías, beneficios de cortesía (configuración inicial y soporte local en Trinidad) y llamado a la acción claro para motivar la compra.

**Independent Test**: Generar el enlace de WhatsApp para una proforma existente y verificar que el texto abra en WhatsApp con formato profesional, facilidades de pago bancarias/QR y sin caracteres de interrogación .

- [x] T005 [US1] Implementar en `app/Application/Quote/WhatsAppQuoteService.php` el formato comercial persuasivo con saludo personalizado, detalle de productos con garantía formateada (`formatWarrantyLabel`), beneficios de valor agregado de Servimática, facilidades de pago de la BD y llamado a la acción directo
- [x] T006 [US1] Aplicar codificación de URL segura con `rawurlencode` asegurando que todos los emojis y acentos se transmitan intactos a la API de WhatsApp (`wa.me`)

---

## Phase 4: User Story 2 - Rediseño de Proforma Imprimible / PDF (Priority: P2)

**Goal**: Disponer de un documento de proforma membretado de alta gama visual en tamaño Carta/A4 con logotipo oficial, columna de garantía, cuentas bancarias y QR de cobro.

**Independent Test**: Acceder a `/api/quotes/:id/print` en el navegador y constatar el renderizado del membrete corporativo, tabla de productos con garantía, cuentas bancarias para depósito y optimización de impresión en 1 página.

- [x] T007 [US2] Actualizar el encabezado y membrete de `resources/views/quotes/print.blade.php` con el logotipo oficial nítido (`/images/logo_servimatica.png`), NIT, ciudad, dirección y teléfonos dinámicos de la empresa
- [x] T008 [US2] Incorporar la columna de Garantía Técnica Oficial en la tabla de productos de `resources/views/quotes/print.blade.php`
- [x] T009 [US2] Agregar el bloque institucional de Medios de Pago y Cuentas Bancarias activas al pie de `resources/views/quotes/print.blade.php`
- [x] T010 [US2] Optimizar reglas CSS `@media print` para asegurar una impresión limpia a una sola hoja sin elementos de navegación
- [x] T011 [P] [US2] Actualizar las aserciones de `tests/Feature/Quote/QuoteDynamicCompanyPrintTest.php` para validar los nuevos bloques institucionales

---

## Phase 5: User Story 3 - Modal de Envío y Previsualización Web (Priority: P3)

**Goal**: Permitir al asesor comercial en la plataforma web previsualizar el mensaje de WhatsApp, editar el número de destino si es necesario y copiar el texto con un solo clic.

**Independent Test**: En la pantalla `/quotes`, hacer clic en el botón de WhatsApp de una fila y verificar que se abra la modal con el texto persuasivo listo para copiar o enviar.

- [x] T012 [US3] Crear el componente modal `admin-starter-kit/src/views/quotes/QuoteWhatsAppDialog.vue` con previsualización del texto comercial, campo para personalizar número de teléfono y botones "Copiar Texto" y "Abrir WhatsApp"
- [x] T013 [US3] Integrar `QuoteWhatsAppDialog.vue` en `admin-starter-kit/src/pages/quotes/index.vue` sustituyendo la apertura directa en blanco

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Verificación integral, pruebas automatizadas y compilación limpia

- [x] T014 Ejecutar pruebas específicas de proformas y WhatsApp con `php artisan test --filter=Quote`
- [x] T015 Ejecutar compilación de producción del frontend con `pnpm --prefix admin-starter-kit run build`
- [x] T016 Ejecutar suite completa con `php artisan test` garantizando 0 fallos
