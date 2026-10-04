# Tasks: Asistente de Ventas IA en WhatsApp con Google Gemini y Catálogo en Tiempo Real

**Input**: Design artifacts from `specs/015-bot-whatsapp-ia-gemini/`  
**Prerequisites**: `spec.md`, `plan.md`

---

## Phase 1: Setup & Migrations

**Purpose**: Estructura de persistencia para registrar conversaciones de WhatsApp y mensajes

- [X] T001 Crear migración `database/migrations/2026_10_04_000004_create_whatsapp_conversations_table.php` con `phone_number` (string unique), `customer_name` (nullable), `status` (active/paused/human_agent) y `last_message_at`
- [X] T002 Crear migración `database/migrations/2026_10_04_000005_create_whatsapp_messages_table.php` con `conversation_id` (foreign key), `direction` (inbound/outbound), `message` (text) y `tokens_used`
- [X] T003 [P] Crear modelos Eloquent `app/Infrastructure/Persistence/Eloquent/WhatsAppConversationModel.php` y `app/Infrastructure/Persistence/Eloquent/WhatsAppMessageModel.php`

---

## Phase 2: Core Domain & Gemini AI Service

**Purpose**: El motor de inteligencia artificial que alimenta las respuestas a partir del inventario real

- [X] T004 [P] Crear servicio `app/Application/Ai/GeminiCatalogContextBuilder.php` que consulta productos activos con stock > 0, precios en Bs., garantía y enlaces 360°, excluyendo estrictamente costos
- [X] T005 Implementar servicio `app/Application/Ai/GeminiChatService.php` para comunicarse con la API de Google Gemini (`v1beta/models/gemini-1.5-flash:generateContent`), inyectando el prompt comercial de Servimática y el catálogo de la BD
- [X] T006 [P] Crear pruebas unitarias y de integración en `tests/Unit/Ai/GeminiChatServiceTest.php` validando la estructura del prompt, la no filtración de costos y la recomendación de stock real

---

## Phase 3: Webhook & WhatsApp Gateway

**Purpose**: Recepción de mensajes del cliente y envío de respuestas vía Gateway QR

- [X] T007 Implementar interfaz `app/Domain/WhatsApp/WhatsAppGatewayInterface.php` y cliente `app/Infrastructure/Gateways/WhatsApp/QrGatewayClient.php` para envío de mensajes salientes
- [X] T008 Implementar caso de uso `app/Application/WhatsApp/ProcessIncomingWhatsAppMessageUseCase.php` para orquestar la recepción, memoria de conversación, consulta a Gemini y envío de respuesta
- [X] T009 Crear controlador `app/Infrastructure/Http/Controllers/Api/WhatsAppWebhookController.php` con la ruta `POST /api/webhooks/whatsapp`
- [X] T010 [P] Crear pruebas de integración en `tests/Feature/WhatsApp/WhatsAppWebhookTest.php` simulando mensajes entrantes y derivación a humano

---

## Phase 4: Administrative APIs & Chat Simulator

**Purpose**: Endpoints para probar el bot en vivo y configurar credenciales desde el panel

- [X] T011 Crear controlador `app/Infrastructure/Http/Controllers/Api/WhatsAppBotController.php` con endpoints `/api/whatsapp-bot/settings`, `/api/whatsapp-bot/simulate` y `/api/whatsapp-bot/conversations`
- [X] T012 Registrar rutas en `routes/api.php` bajo middleware de autenticación de dueño/administrador
- [X] T013 [P] Crear pruebas de feature en `tests/Feature/WhatsApp/WhatsAppBotManagementTest.php` validando el simulador de chat y el guardado de configuración

---

## Phase 5: Frontend Dashboard & Live Simulator

**Purpose**: Interfaz gráfica en Vue 3 y Vuetify para que el dueño controle el bot y pruebe el chat en vivo

- [X] T014 Crear la vista `admin-starter-kit/src/pages/whatsapp-bot/index.vue` con tarjeta de estado (Activo/Pausado), selector de modelo Gemini y campo para API Key
- [X] T015 Implementar el componente de simulador de chat interactivo tipo WhatsApp en `admin-starter-kit/src/views/whatsapp-bot/ChatSimulator.vue`
- [X] T016 Implementar la tabla de conversaciones recientes con indicador de estado (Activo / Requiere atención de asesor)
- [X] T017 Agregar el enlace "Bot de WhatsApp IA" en el menú de navegación vertical en `admin-starter-kit/src/navigation/vertical/index.js`

---

## Phase 6: Polish & Verification

**Purpose**: Verificación de extremo a extremo, suite de pruebas y compilación

- [X] T018 Ejecutar suite completa con `php artisan test` validando cero regresiones
- [X] T019 Compilar bundle frontend con `pnpm --prefix admin-starter-kit run build`
- [X] T020 Validar flujo completo en navegador: simulación de consulta de laptop, respuesta con link 360° y persistencia de conversación
