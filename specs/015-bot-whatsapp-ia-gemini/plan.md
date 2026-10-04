# Implementation Plan: Asistente de Ventas IA en WhatsApp con Google Gemini y Catálogo en Tiempo Real

**Branch**: `015-bot-whatsapp-ia-gemini` | **Date**: 2026-10-04 | **Status**: Planned

---

## 1. Architecture & Design Principles

El Asistente IA de WhatsApp se construye siguiendo los principios de la **Constitución de Servimática** (KISS, verdad única en tiempo real y privacidad comercial).

```mermaid
graph TD
    Cliente[Cliente en WhatsApp] -->|Mensaje entrante| Gateway[Gateway QR Local / Baileys]
    Gateway -->|Webhook POST /api/webhooks/whatsapp| Controller[WhatsAppWebhookController]
    Controller --> UseCase[ProcessIncomingWhatsAppMessageUseCase]
    UseCase --> History[Historial Conversación (DB)]
    UseCase --> CatalogQuery[Catálogo Activo en Tiempo Real (DB)]
    UseCase --> GeminiService[GeminiChatService]
    GeminiService -->|Prompt + Inventario + Historial| GoogleGemini[Google Gemini 1.5 Flash API]
    GoogleGemini -->|Respuesta comercial con enlaces 360°| GeminiService
    GeminiService --> UseCase
    UseCase -->|Guardar mensaje saliente| History
    UseCase -->|Enviar mensaje| GatewayClient[QrGatewayClient]
    GatewayClient --> Gateway
    Gateway --> Cliente

    Owner[Dueño en Panel Web] -->|Simulador de Chat /api/whatsapp-bot/simulate| ControllerSim[WhatsAppBotController]
    ControllerSim --> GeminiService
```

### Principios Arquitectónicos
1. **Verdad Única de Datos (Constitución V):** Gemini no inventa especificaciones ni precios. El catálogo activo (laptops, PCs, componentes con `stock > 0`) se inyecta dinámicamente en el contexto del prompt para que Gemini responda exclusivamente sobre lo que Servimática tiene físicamente en inventario.
2. **Privacidad de Negocio (Constitución VI):** El query de inventario entregado a Gemini excluye terminantemente `cost_price` y proveedores. Solo expone `sale_price` en Bolivianos (Bs.), garantía, stock y URL pública 360°.
3. **Simplicidad & Cero Bloat (KISS):** Integración directa con la API REST de Google Gemini mediante el cliente nativo `Http` de Laravel, sin SDKs externos pesados.

---

## 2. Data Model & Schema Migrations

### Tabla `whatsapp_conversations`
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `phone_number`: VARCHAR(30) UNIQUE NOT NULL (ej. '59178901234')
- `customer_name`: VARCHAR(150) NULLABLE
- `status`: ENUM('active', 'paused', 'human_agent') DEFAULT 'active'
- `last_message_at`: TIMESTAMP NULLABLE
- `created_at`, `updated_at`: TIMESTAMP

### Tabla `whatsapp_messages`
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `conversation_id`: BIGINT UNSIGNED FOREIGN KEY REFERENCES `whatsapp_conversations(id)` ON DELETE CASCADE
- `direction`: ENUM('inbound', 'outbound') NOT NULL
- `message`: TEXT NOT NULL
- `tokens_used`: INT UNSIGNED NULLABLE
- `created_at`, `updated_at`: TIMESTAMP

---

## 3. Endpoints & API Contracts

### A. Webhook de WhatsApp
- `POST /api/webhooks/whatsapp`: Recibe eventos y mensajes entrantes desde el Gateway QR.
  - Payload: `{ "event": "messages.upsert", "data": { "from": "59178901234", "body": "Busco laptop de 3000 Bs", "pushName": "Carlos" } }`
  - Respuesta: `200 OK` `{ "status": "processed", "replied": true }`

### B. Gestión Administrativa y Simulador
- `GET /api/whatsapp-bot/settings`: Retorna estado del bot (activo/inactivo), configuración de gateway y recuento de conversaciones.
- `POST /api/whatsapp-bot/settings`: Actualiza estado (activo/inactivo), Gemini API Key y URL del gateway.
- `POST /api/whatsapp-bot/simulate`: Permite al dueño chatear con Gemini en vivo desde el panel administrativo.
  - Request: `{ "message": "¿Tienen laptops disponibles para diseño?", "history": [...] }`
  - Response: `{ "reply": "¡Hola! Con gusto te asesoro...", "matched_products": [...] }`
- `GET /api/whatsapp-bot/conversations`: Lista de chats recientes con botón para reanudar o derivar a humano.

---

## 4. Frontend Web (`admin-starter-kit/`)

Nueva vista: `admin-starter-kit/src/pages/whatsapp-bot/index.vue`
1. **Tarjeta de Control Operativo:**
   - Interruptor de encendido/apagado general del bot.
   - Indicador visual de estado ("En línea y respondiendo" / "Pausado").
   - Clave de API de Gemini configurada de forma segura.
2. **Simulador de Chat en Vivo:**
   - Interfaz visual tipo WhatsApp (burbujas verdes/blancas, avatar de Servimática, campo de texto inferior).
   - El dueño puede escribir cualquier pregunta y presionar Enter para ver la respuesta exacta de Gemini.
   - Lista lateral de "Productos sugeridos por la BD" detectados en la respuesta.
3. **Pestaña de Conversaciones Recientes:**
   - Tabla o lista de los últimos clientes que escribieron a WhatsApp con filtro por estado (`Activo` o `Requiere Asesor Humano`).

---

## 5. Fases de Implementación

- **Fase 1: Base de Datos & Migraciones:** Creación de `whatsapp_conversations` y `whatsapp_messages`.
- **Fase 2: Cerebro IA (GeminiChatService):** Implementación del servicio de Gemini con inyección dinámica del catálogo real y reglas comerciales bolivianas.
- **Fase 3: Webhook & Casos de Uso:** Implementación de `ProcessIncomingWhatsAppMessageUseCase` y controlador del webhook.
- **Fase 4: Endpoints de Administración y Simulador:** Rutas `/api/whatsapp-bot/*` para prueba y configuración.
- **Fase 5: Frontend (Simulador & Panel en Vue/Vuetify):** Construcción de la vista `/whatsapp-bot` y enlace en el menú vertical.
- **Fase 6: Pruebas Automatizadas & Verificación:** Tests en PHPUnit simulando consultas de clientes y validando que nunca se filtren costos.
