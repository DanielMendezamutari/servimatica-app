# Feature Specification: Capítulo 13 — Rediseño Integral de Proformas y Cotizaciones (WhatsApp Comercial Persuasivo + PDF Institucional)

**Feature Branch**: `013-redisenio-proformas-cotizaciones`  
**Created**: 2026-10-04  
**Status**: Ready for Planning  
**Input**: Solicitud de rediseño y elevación comercial de las Proformas y Cotizaciones emitidas por Servimática Computación. Se busca erradicar mensajes toscos y con emojis rotos (símbolos ), reemplazándolos por un formato de WhatsApp persuasivo y de alto impacto comercial que motive la compra inmediata, extrayendo dinámicamente datos de la empresa (`company_settings`), métodos de pago bancarios/QR (`payment_methods`) y garantías de productos (`products`), junto con un rediseño de alta calidad del documento membretado imprimible/PDF.

---

## 1. Visión y Justificación del Negocio

En el rubro de venta de computadoras, laptops y partes en Trinidad (Beni), la proforma es la principal herramienta de cierre de ventas. Cuando un cliente solicita un presupuesto por WhatsApp o en mostrador, el impacto visual y comercial del mensaje define si el cliente decide comprar en Servimática o seguir buscando en la competencia.

El mensaje actual presenta deficiencias graves:
1. **Emojis Corruptos:** Caracteres como `📄`, `📅` y `👤` se transforman en símbolos de interrogación  debido a problemas de codificación UTF-8 / URL encoding.
2. **Mensaje Frío y Poco Comercial:** Parece un reporte plano de base de datos sin llamado a la acción (CTA) y sin destacar los beneficios incluidos (garantía local, configuración inicial de cortesía, entrega inmediata).
3. **Falta de Información Dinámica del Negocio:** No incluye la dirección física de la tienda, teléfonos oficiales ni opciones de pago bancario (Simple QR, cuentas BNB/BCP/Unión).
4. **Ausencia de Enlace Directo a Documento Formal:** El cliente no recibe un link para ver o descargar su proforma membretada en PDF.

---

## 2. User Scenarios & Testing *(mandatory)*

### User Story 1 - Generación de Mensaje de WhatsApp Comercial y Persuasivo (Priority: P1) 🎯 MVP

Como vendedor o dueño de Servimática,  
quiero que el sistema genere automáticamente un mensaje de WhatsApp enriquecido, comercialmente persuasivo y con datos dinámicos de la empresa y garantías,  
para enviar presupuestos atractivos a los clientes que despierten el deseo de comprar y faciliten el pago inmediato.

**Why this priority**: Es el punto de contacto directo más frecuente con clientes en Bolivia para concretar ventas de tecnología.

**Independent Test**: Emitir una cotización con una Laptop y un Accesorio, presionar "Enviar por WhatsApp" y verificar que el mensaje se abra con saludo personalizado, detalle de productos con garantía, beneficios de cortesía, cuentas bancarias de la BD, enlace público a la proforma y sin ningún emoji corrupto.

**Acceptance Scenarios**:
1. **Dado** que se genera una cotización para un cliente, **cuando** el vendedor solicita el enlace de WhatsApp, **entonces** el texto se genera con saludo cordial, nombre del cliente y resumen del equipo cotizado.
2. **Dado** que los productos cotizados tienen garantía registrada (`warranty_days`), **cuando** se formatea el mensaje, **entonces** cada ítem incluye su cobertura (ej. `🛡️ Garantía: 12 meses`).
3. **Dado** que la empresa tiene configurada su información en `company_settings`, **cuando** se genera el pie del mensaje, **entonces** incluye el nombre comercial, slogan, dirección física en Trinidad y teléfono oficial.
4. **Dado** que existen métodos de pago activos en `payment_methods` (tipo `qr` o `transferencia`), **cuando** se genera la sección de pagos, **entonces** se listan las facilidades (QR Simple y bancos disponibles) extraídas dinámicamente de la base de datos.
5. **Dado** que el mensaje se codifica para la URL de WhatsApp (`wa.me`), **cuando** se abre en el navegador o app móvil, **entonces** los caracteres especiales y acentos se visualizan 100% legibles sin ningún símbolo .
6. **Dado** el enlace a la proforma en el mensaje, **cuando** el cliente hace clic en el enlace, **entonces** accede directamente a la vista membretada e imprimible en PDF de su proforma.

---

### User Story 2 - Rediseño de Documento de Proforma Imprimible / PDF (Priority: P2)

Como cliente o asesor de Servimática,  
quiero un documento de cotización imprimible formal, membretado y de alta gama visual (tamaño Carta/A4),  
para tener un respaldo físico o digital con validez comercial, cuentas de depósito y términos de garantía transparentes.

**Why this priority**: Las empresas, instituciones y profesionales requieren un documento formal para autorizar compras o desembolsos.

**Independent Test**: Abrir la ruta `/api/quotes/:id/print`, constatar que cargue el logotipo oficial nítido de Servimática, tabla con columna de garantía por producto, cuadro de cuentas bancarias y QR de cobro, y notas comerciales.

**Acceptance Scenarios**:
1. **Dado** que se abre la vista de impresión, **cuando** se renderiza la proforma, **entonces** muestra el logotipo corporativo nítido de Servimática (`public/images/logo_servimatica.png`), datos fiscales/NIT, dirección y fecha.
2. **Dado** que la proforma contiene productos, **cuando** se visualiza la tabla de ítems, **entonces** incluye columna de Garantía Técnica individualizada.
3. **Dado** que la empresa cuenta con métodos de pago configurados, **cuando** se visualiza la sección de pagos en el documento, **entonces** se muestra el bloque de transferencias bancarias y código QR de cobro.
4. **Dado** que se presiona "Imprimir / Guardar PDF", **cuando** se abre el diálogo del navegador, **entonces** los estilos CSS de impresión (`@media print`) ocultan botones de navegación y optimizan la hoja a una sola página limpia.

---

### User Story 3 - Modal de Envío y Previsualización en el Frontend Web (Priority: P3)

Como vendedor en la plataforma web,  
quiero una ventana modal al hacer clic en "WhatsApp" en la lista de cotizaciones que me permita previsualizar el texto, editar el teléfono de destino o copiarlo con un clic,  
para tener control total antes de despachar el presupuesto al cliente.

**Why this priority**: Agiliza la atención y evita errores de envío si el cliente solicita que se lo envíen a otro número de teléfono alternativo.

**Independent Test**: En `/quotes`, hacer clic en el botón de WhatsApp de una proforma, verificar que se abra la modal con el texto renderizado, botón "Copiar Mensaje" y botón "Abrir WhatsApp".

---

## 3. Requerimientos Funcionales

- **FR-001**: El servicio `WhatsAppQuoteService` debe inyectar o consultar los modelos `CompanySettingModel` y `PaymentMethodModel` para componer el mensaje de forma 100% dinámica.
- **FR-002**: Las opciones de pago mostradas en el mensaje deben provenir exclusivamente de métodos activos en la BD (`is_active = true`).
- **FR-003**: Cada ítem en la cotización debe reflejar su tiempo de garantía en formato legible (ej: 365 días → "12 meses de garantía oficial").
- **FR-004**: La codificación del mensaje debe utilizar `rawurlencode` sobre UTF-8 normalizado garantizando inmunidad a caracteres rotos en cualquier sistema operativo.
- **FR-005**: La vista `quotes/print.blade.php` debe actualizarse con el membrete oficial, logotipo PNG corporativo, columnas de garantía y bloque de cuentas bancarias.
- **FR-006**: En el frontend Vue (`pages/quotes/index.vue`), incorporar el diálogo de previsualización y copia rápida de cotización.

---

## 4. Criterios de Éxito Medibles

- **SC-001**: El 100% de los mensajes de WhatsApp generados deben mostrarse sin caracteres rotos ni símbolos  en navegadores de escritorio y dispositivos móviles.
- **SC-002**: El 100% de los datos de contacto, dirección y cuentas de pago deben actualizarse en tiempo real al modificarse en la base de datos sin requerir cambios de código.
- **SC-003**: El tiempo para copiar o abrir el mensaje de WhatsApp desde el frontend web no debe superar 1 clic desde la lista de proformas.
- **SC-004**: La suite de pruebas de cotizaciones debe mantener 100% de aprobación en `php artisan test`.

---

## 5. Entidades Clave y Relaciones

- `CompanySetting`: Datos institucionales de Servimática (Nombre, Slogan, Ciudad, Dirección, Teléfonos, Términos).
- `PaymentMethod`: Cuentas bancarias y QR de cobro para compras y transferencias.
- `Product`: Catálogo de hardware con días de garantía (`warranty_days`).
- `Quote`: Cabecera de cotización con cliente, validez, totales y vendedor responsable.
- `QuoteItem`: Detalle de equipos, cantidades, precios unitarios y subtotales.
