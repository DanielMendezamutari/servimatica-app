# Feature Specification: Asistente de Ventas IA en WhatsApp con Google Gemini y Catálogo en Tiempo Real

**Feature Branch**: `015-bot-whatsapp-ia-gemini`  
**Created**: 2026-10-04  
**Status**: Specified / Ready for Clarification & Planning  
**Input**: Requerimiento del usuario: "quiero usar a gemini para la inteligencia artificial y la base de datos de este sistema (para el bot de WhatsApp)"

---

## Contexto y Visión de Negocio

Servimática busca automatizar la atención y el cierre de ventas en WhatsApp las 24 horas del día. A diferencia de los chatbots tradicionales rígidos ("marque 1 para laptops, marque 2 para partes"), los clientes en Santa Cruz de la Sierra consultan de manera natural, por ejemplo:
> *"Buenas tardes hermano, busco una laptop para diseño gráfico que no pase de 3.500 Bs, ¿qué me recomiendas?"*  
> *"Hola, ¿la laptop Dell Inspiron que mostraron en el Live sigue disponible? ¿Tiene garantía?"*

Para resolver esto con máxima inteligencia y cero respuestas inventadas (alucinaciones), se integrará **Google Gemini (Gemini 1.5 Flash)** como cerebro conversacional, **conectado directamente a la base de datos de Servimática**. Gemini actuará como un asesor comercial experto que consulta existencias, precios en Bolivianos (Bs.), garantías y enlaces 360°, cerrando ventas de forma cálida, veraz y persuasiva.

---

## Clarifications & Architecture Decisions

### Sesión de Clarificación (2026-10-04)
- **Modelo de IA Predeterminado:** **`gemini-1.5-flash`** (Google AI Studio). Ofrece latencia menor a 1.5s, alta calidad en lenguaje natural boliviano y cuota generosa sin costo.
- **Formato de Respuesta de Productos en WhatsApp:** Mensaje de texto persuasivo y ordenado con precio en Bolivianos (`Bs.`), días de garantía, especificaciones clave y el enlace directo al visor interactivo 360° (`https://servimatica.com.bo/catalogo?product={id}`).
- **Mecanismo de Derivación a Asesor Humano (Handoff):** Si el cliente solicita hablar con una persona (*"asesor"*, *"humano"*, *"vendedor"*), el bot le confirma que un asesor del local tomará el chat y **se silencia durante 24 horas** para ese número (a menos que el vendedor reactive manualmente la IA desde el panel antes).
- **Método de Conexión de WhatsApp:** Gateway local por **Código QR** (compatible con celular Android/iPhone físico de Servimática, ej. Evolution API / Baileys).
- **Simulador en Panel:** Consola de **Simulador de Chat en Vivo** dentro del panel administrativo (`/whatsapp-bot`), permitiendo probar las respuestas de Gemini contra el inventario real en tiempo real.

---

### User Story 1 - Asesoría Conversacional de Ventas con Google Gemini y Stock Real (Priority: P1) 🎯 MVP

Como **Cliente potencial de WhatsApp**, quiero escribir mis dudas y requerimientos en lenguaje natural a cualquier hora, para que el asistente de Servimática me asesore empáticamente, me recomiende equipos que realmente existan en tienda y me comparta enlaces a la vitrina interactiva 360°.

**Why this priority**: Es el núcleo de la experiencia de compra y conversión. Responde al instante cuando el cliente tiene mayor intención de compra.

**Independent Test**: Enviar un mensaje de WhatsApp simulado preguntando por laptops para diseño gráfico bajo 3.500 Bs. Verificar que Gemini consulte la BD, responda recomendando la "laptop inspiron" (Bs. 3.000,00, 1 año de garantía), envíe el enlace de visualización 360° y pregunte si desea apartarla o visitarnos en el Comercial Chiriguano.

**Acceptance Scenarios**:
1. **Given** un cliente que escribe buscando una recomendación, **When** el mensaje llega al webhook, **Then** Gemini analiza el pedido, busca en la tabla de productos activos con stock mayor a cero y formula una recomendación persuasiva adaptada al mercado de Bolivia.
2. **Given** una consulta sobre un producto específico con fotos 360°, **When** Gemini responde, **Then** incluye el link directo `https://servimatica.com.bo/catalogo?product={id}` para que el cliente pueda girarlo en 360° desde su celular.
3. **Given** un producto agotado o inexistente, **When** el cliente pregunta por él, **Then** Gemini aclara amablemente que no está en stock y propone la alternativa más cercana disponible en tienda, sin inventar productos.
4. **Given** la consulta a la base de datos, **When** Gemini procesa los datos, **Then** bajo ninguna circunstancia expone precios de costo, nombres de proveedores ni márgenes de ganancia (Principio Constitucional VI).

---

### User Story 2 - Webhook de WhatsApp y Memoria de Conversación Multiturno (Priority: P2)

Como **Vendedor o Dueño**, quiero que el webhook de WhatsApp mantenga la memoria de los últimos mensajes con cada cliente, para que la conversación sea fluida y Gemini entienda referencias contextuales (ej. *"¿Y de cuánto es su disco?"* refiriéndose a la laptop mencionada en el mensaje anterior).

**Why this priority**: Evita que el cliente tenga que repetir todo su mensaje en cada turno y garantiza una experiencia de atención humana y profesional.

**Independent Test**: Enviar *"Busco laptop Dell"*, recibir recomendación, y luego enviar *"¿Tiene garantía?"*. Constatar que Gemini responde confirmando la garantía de esa Dell específica sin perder el contexto.

**Acceptance Scenarios**:
1. **Given** un número de WhatsApp que envía un mensaje, **When** ingresa al webhook `POST /api/webhooks/whatsapp`, **Then** el sistema recupera o crea la sesión en `whatsapp_conversations` guardando el historial de mensajes recientes.
2. **Given** la verificación del webhook de Meta Cloud API, **When** Meta envía un `GET /api/webhooks/whatsapp` con `hub.verify_token`, **Then** el servidor responde con el desafío (`hub.challenge`) retornando HTTP 200.
3. **Given** una respuesta generada por Gemini, **When** se envía al cliente, **Then** se entrega mediante la API de WhatsApp en menos de 3 segundos.

---

### User Story 3 - Panel Administrativo de Control y Simulador de Chat IA (Priority: P3)

Como **Dueño de Servimática**, quiero tener una pestaña en el panel web (`admin-starter-kit/`) donde pueda encender o pausar el bot IA, configurar mi API Key de Gemini y probar el bot en un chat simulado en vivo antes de habilitarlo al público.

**Why this priority**: Da tranquilidad y control total al dueño del negocio para supervisar la calidad de las respuestas y pausar el bot cuando un vendedor humano tome el control del teléfono.

**Independent Test**: Ingresar a `/whatsapp-bot` en el panel web, escribir en la consola de simulación, verificar que Gemini responda en pantalla con datos de la BD y probar apagar el switch del bot.

**Acceptance Scenarios**:
1. **Given** el panel administrativo, **When** el dueño accede a la sección "Bot de WhatsApp IA", **Then** puede ver el estado actual del bot (Activo/Inactivo), métricas de mensajes atendidos y botón para encender/apagar.
2. **Given** la consola de simulación interactiva en el panel, **When** el dueño escribe cualquier pregunta como si fuera un cliente de WhatsApp, **Then** ve en tiempo real la respuesta exacta que Gemini daría, junto con los productos de la BD que tomó en cuenta.
3. **Given** un cliente que solicita hablar con un humano o asesor (*"quiero hablar con una persona"*), **When** Gemini detecta la solicitud, **Then** pausa temporalmente la automatización para ese número y marca la conversación como "Requiere atención humana".

---

## Functional Requirements

- **FR-001**: El backend debe integrar un servicio cliente para la API de **Google Gemini** (`gemini-1.5-flash` o `gemini-2.0-flash`) utilizando la credencial configurable `GEMINI_API_KEY`.
- **FR-002**: El sistema debe proveer al contexto de Gemini el inventario en tiempo real de productos activos (categoría, marca, modelo, precio de venta en Bs., garantía, stock y URL pública con visor 360°), aislando terminantemente los costos de compra.
- **FR-003**: Debe existir la tabla `whatsapp_conversations` y `whatsapp_messages` para registrar el número de teléfono, remitente, texto, tokens consumidos y marca de tiempo, limitando la memoria contextual enviada a Gemini a los últimos 8 mensajes por conversación.
- **FR-004**: Debe implementarse el endpoint dual `GET /api/webhooks/whatsapp` (handshake de token Meta) y `POST /api/webhooks/whatsapp` (recepción de mensajes entrantes).
- **FR-005**: El sistema debe enviar las respuestas formateadas en WhatsApp Markdown (negritas `*texto*`, enlaces claros y emojis comerciales sobrios).
- **FR-006**: El panel web (`admin-starter-kit/`) debe incluir la vista de gestión del Bot con interruptor de encendido/apagado, edición de prompt del sistema / datos de tienda (Comercial Chiriguano pasillo 2 local #333) y simulador de chat en vivo.
- **FR-007**: Se debe soportar la detección de intención de derivación humana: si el cliente solicita un vendedor o asesor, el bot responde cordialmente notificando que un asesor tomará el chat y suspende respuestas automáticas para ese número por 24 horas o hasta reactivación manual.

---

## Non-Functional Requirements & Constraints

- **NFR-001 (Latencia):** El tiempo total de respuesta desde la recepción del webhook hasta el envío a WhatsApp debe ser inferior a 3.5 segundos.
- **NFR-002 (KISS & Stack Strict):** Implementación nativa en Laravel 12 mediante cliente HTTP (`Http::post` con timeout) hacia el endpoint oficial de Gemini `v1beta/models/gemini-1.5-flash:generateContent`. Cero librerías exóticas no oficiales.
- **NFR-003 (Robustez ante Fallos):** Si la API de Gemini o la de WhatsApp presenta intermitencia, el webhook debe responder con HTTP 200 a Meta para evitar reintentos infinitos y registrar el error en `storage/logs/laravel.log`.
- **NFR-004 (Apego a la Constitución):** Todo producto recomendado debe tener precio expresado en Bolivianos (`Bs.`) y stock disponible confirmado en la base de datos central en tiempo real.

---

## Success Criteria

- **SC-001**: Un cliente que escribe a WhatsApp recibe respuesta relevante y contextual en menos de 4 segundos.
- **SC-002**: 100% de los productos recomendados por Gemini existen en la base de datos y tienen stock mayor a cero.
- **SC-003**: 0% de filtración de costos de compra o datos confidenciales de proveedores en las conversaciones.
- **SC-004**: El dueño de Servimática puede simular conversaciones y activar/desactivar el bot desde el panel web.
- **SC-005**: 100% de las pruebas automatizadas de Laravel (`php artisan test`) pasan con 0 fallos.
