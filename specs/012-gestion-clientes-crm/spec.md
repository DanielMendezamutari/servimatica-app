# Feature Specification: Gestión Integral de Clientes (CRM Comercial y Fidelización)

**Feature Branch**: `012-gestion-clientes-crm`  
**Created**: 2026-10-04  
**Status**: Draft  
**Input**: "Gestión Integral de Clientes (CRM): Directorio de clientes, historial de compras, créditos, garantías activas y exportación"

---

## Executive Summary
El presente capítulo implementa la **Gestión Integral de Clientes (CRM Comercial)** para Servimática. Permite a vendedores y administradores gestionar un directorio centralizado de clientes con búsqueda inteligente por Nombre, NIT/CI o WhatsApp, clasificación por tipo de cliente (Cliente Final, Técnico / Mayorista, Empresa / Corporativo), acceso a la **Ficha 360°** con historial cronológico de compras, cotizaciones emitidas y garantías vigentes con números de serie, además de exportación del directorio en formato Excel/CSV.

---

## Clarifications

### Session 2026-10-04
- **Q1 (Ubicación en Navegación UX):** ¿En qué parte de la barra lateral ubicar el acceso al módulo de Clientes?
  - **Decisión:** Acceso directo en "Operaciones" a 1 solo clic junto a *Inicio*, *Punto de Venta (POS)* y *Cotizaciones / Proformas*.
- **Q2 (Tipificación de Clientes):** ¿Cómo manejar la tipificación de clientes?
  - **Decisión:** Clasificación puramente informativa inicial (`Cliente Final`, `Técnico / Mayorista`, `Empresa / Corporativo`) para segmentación, ficha comercial y reportes, sin alterar precios de forma automática en esta fase (apego estricto a KISS).
- **Q3 (Documento de Identidad y Teléfono):** ¿Cuál es la política para NIT/CI y teléfono?
  - **Decisión:** Opcionales (permite ventas rápidas o clientes sin datos fiscales), pero con validación de advertencia visual si ya existe otro cliente con el mismo NIT/CI o teléfono para evitar duplicidad accidental.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Directorio Central y Gestión Rápida de Clientes (Priority: P1) 🎯 MVP

Como vendedor o dueño de Servimática, quiero consultar y registrar clientes desde una pantalla dedicada con búsqueda en tiempo real por razón social, documento de identidad o número telefónico, para agilizar la atención en mostrador y mantener los datos de contacto siempre actualizados.

**Why this priority**: Es la base operativa indispensable para que el personal no dependa únicamente de registrar clientes "al vuelo" en el POS, permitiendo administrar datos de facturación, direcciones y contacto directo por WhatsApp.

**Independent Test**: Acceder a `/clients`, listar clientes con paginación, buscar por nombre o NIT/CI, registrar un nuevo cliente en un drawer lateral con validación de teléfono/documento, y abrir un chat directo de WhatsApp con el número del cliente a un solo clic.

**Acceptance Scenarios**:
1. **Given** un usuario autenticado (`dueno` o `vendedor`) en `/clients`, **When** escribe en el buscador `"71234567"` o `"Servicios SRL"`, **Then** la tabla filtra instantáneamente en menos de 300ms mostrando coincidencias.
2. **Given** el formulario de creación de cliente, **When** el usuario ingresa un nombre y un teléfono boliviano válido (ej. `"77332211"`), **Then** el cliente se guarda en la base de datos y se muestra un enlace de WhatsApp con formato internacional `https://wa.me/59177332211`.
3. **Given** un cliente existente, **When** el usuario edita su tipo de cliente (`final`, `mayorista`, `empresa`) o notas comerciales, **Then** los cambios persisten de inmediato en la base de datos sin alterar las ventas históricas.
4. **Given** un cliente que ya no opera, **When** el Dueño conmuta su estado a inactivo, **Then** el cliente se oculta de las sugerencias del POS pero sus registros históricos se conservan intactos.

---

### User Story 2 - Ficha 360° del Cliente: Historial de Compras, Proformas y Garantías (Priority: P2)

Como vendedor o administrador, quiero abrir el perfil de un cliente y visualizar en pestañas ordenadas su historial de compras pasadas, cotizaciones pendientes y garantías vigentes de equipos informáticos con su número de serie, para brindar una atención personalizada y resolver reclamos de garantía con rapidez.

**Why this priority**: En el rubro informático, los clientes consultan frecuentemente si su equipo sigue en garantía, solicitan reimpresiones de comprobantes o requieren seguimiento de proformas previas.

**Independent Test**: Ingresar al detalle de un cliente (`/clients/:id`), verificar las tarjetas de resumen (Total compras acumuladas, última visita) y revisar en sus pestañas las ventas asociadas, las cotizaciones y la lista de garantías activas indicando cuántos días de cobertura restan.

**Acceptance Scenarios**:
1. **Given** un cliente con 3 ventas pasadas, **When** se consulta la pestaña *Historial de Compras*, **Then** se despliegan las ventas con fecha, código de ticket, método de pago, monto total y enlace para reimprimir o ver detalle.
2. **Given** un cliente con productos comprados que tienen garantía de 180 días, **When** se consulta la pestaña *Garantías Vigentes*, **Then** el sistema lista el producto, número de serie, fecha de expiración y un chip indicando los días restantes (o `"Vencida"` si expiró).
3. **Given** un cliente con cotizaciones vigentes, **When** se consulta la pestaña *Cotizaciones*, **Then** se muestran los presupuestos con opción de abrir el PDF o enviarlo por WhatsApp.

---

### User Story 3 - Exportación de Directorio y Segmentación Comercial (Priority: P3)

Como Dueño de Servimática, quiero exportar el directorio de clientes a Excel/CSV con sus datos de contacto y volumen total de compras acumuladas, para realizar campañas de fidelización y seguimiento comercial.

**Why this priority**: Permite al negocio realizar análisis de cartera, identificar a sus mejores clientes (clientes VIP / corporativos) y exportar teléfonos para difusión autorizada por WhatsApp.

**Independent Test**: Hacer clic en el botón *"Exportar Directorio"* en `/clients` y descargar un archivo `.xlsx` o `.csv` con todos los clientes activos y su métrica de compras acumuladas.

**Acceptance Scenarios**:
1. **Given** el Dueño autenticado en `/clients`, **When** hace clic en *"Exportar a Excel"*, **Then** el sistema descarga un archivo tabular con columnas: Nombre/Razón Social, Tipo, NIT/CI, Celular, Email, Ciudad/Dirección, Total Compras Bs., Fecha de Registro.
2. **Given** un usuario con rol `vendedor`, **When** intenta ejecutar el endpoint de exportación masiva, **Then** el sistema retorna `403 Forbidden` conforme al Principio VI de confidencialidad y control de base de datos.

---

## Functional Requirements

- **RF-01**: Extender el modelo de datos de `clients` con campos comerciales: `client_type` (`final`, `mayorista`, `empresa`), `city` (por defecto Trinidad / Beni u otra ciudad de Bolivia) y `notes` (campo de texto para observaciones comerciales o crédito acordado).
- **RF-02**: Endpoint `GET /api/v1/clients` con soporte para paginación, ordenación, filtrado por `search` (nombre, NIT/CI, teléfono), `client_type` y `is_active`.
- **RF-03**: Endpoint `GET /api/v1/clients/{id}` que retorne el perfil completo del cliente junto con estadísticas comerciales calculadas: `total_spent_bs`, `sales_count`, `last_purchase_at`.
- **RF-04**: Endpoint `GET /api/v1/clients/{id}/sales` con el listado paginado de ventas vinculadas al cliente.
- **RF-05**: Endpoint `GET /api/v1/clients/{id}/quotes` con el listado de proformas emitidas al cliente.
- **RF-06**: Endpoint `GET /api/v1/clients/{id}/warranties` que busque en `sale_items` de las ventas del cliente los productos con `warranty_days > 0`, calculando la fecha de vencimiento contra la fecha actual (`now()`).
- **RF-07**: Endpoint `PUT /api/v1/clients/{id}` para actualización de datos y `PATCH /api/v1/clients/{id}/toggle-status` para activar/desactivar.
- **RF-08**: Endpoint `GET /api/v1/clients/export` restringido a administradores para exportación en CSV/Excel.
- **RF-09**: Vista frontend SPA en `admin-starter-kit/src/pages/clients/index.vue` con tabla interactiva, drawer lateral de creación/edición, chips de estado, botón directo de WhatsApp y botón de Ficha 360°.
- **RF-10**: Vista frontend SPA en `admin-starter-kit/src/pages/clients/[id].vue` con Ficha 360° en pestañas: *Resumen*, *Ventas*, *Cotizaciones*, *Garantías*.
- **RF-11**: Incorporar el acceso a Clientes en la navegación lateral (`admin-starter-kit/src/navigation/vertical/index.js`) dentro de las operaciones clave.

---

## Non-Functional Requirements & Principles Adherence

- **Principio I (KISS & YAGNI):** Sin dependencias externas complejas ni librerías de CRM sobredimensionadas. Solo tablas nativas, relaciones Eloquent limpias y componentes Vuetify existentes.
- **Principio II (Bolivia / BOB):** Formato telefónico adaptado a Bolivia (+591 para móviles de 8 dígitos que inician con 6 o 7), moneda `Bs.`, soporte para NIT con o sin dígito verificador y Cédula de Identidad con extensión departamental opcional.
- **Principio VI (Privacidad & Seguridad):** La exportación masiva de la cartera de clientes queda restringida exclusivamente al Dueño / Administrador.
