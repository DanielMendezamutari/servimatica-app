# Feature Specification: Capítulo 4 — Cotizaciones / Proformas, Ventas en Tienda (POS) y Comisiones

**Feature Branch**: `004-ventas-proformas-pos`  
**Created**: 2026-10-03  
**Status**: Draft  
**Input**: Solicitud de desarrollo para el flujo comercial central de Servimática: módulo de Punto de Venta (POS) en mostrador, emisión de Proformas/Cotizaciones en PDF y WhatsApp, registro de ventas con medios de pago en Bolivianos (Efectivo y QR), descuento de inventario en tiempo real y liquidación automática de comisiones para vendedores.

---

## 1. Visión y Justificación del Negocio

En el rubro de comercialización y servicio técnico de computadoras en Bolivia, el proceso comercial cotidiano de mostrador se compone de dos flujos indispensables:
1. **La Proforma / Cotización rápida:** Los clientes cotizan computadoras ensambladas, laptops o accesorios para evaluar presupuestos antes de comprar. El vendedor debe poder armar una proforma en segundos, imprimirla o enviarla directamente por WhatsApp sin comprometer el stock físico del inventario.
2. **La Venta Directa en Mostrador (POS):** Cuando el cliente decide comprar, la cotización se convierte en venta (o se genera una venta directa). El sistema registra el medio de pago (Efectivo con cálculo de cambio, o QR / Transferencia Bancaria), descuenta de inmediato las existencias físicas en tiempo real, genera el recibo de venta y acredita la comisión comercial al vendedor responsable según el porcentaje configurado en su perfil.

## Clarifications

### Session 2026-10-03
- Q: ¿Cómo deben gestionarse los descuentos de precios al armar una proforma o concretar una venta en el Punto de Venta (POS)? → A: Descuento libre al total de la venta (monto fijo en Bs. o porcentaje %) aplicable directamente en caja.
- Q: ¿Qué formatos de comprobante o impresión deben generarse para las Proformas y para las Ventas en el mostrador? → A: Ambos formatos: Ticket térmico rápido (80mm/58mm) para ventas en mostrador y PDF formal membretado (tamaño Carta) para proformas y cotizaciones.
- Q: ¿Debe incluirse en este capítulo el control estricto de apertura y cierre de caja diaria (turnos con arqueo de saldo inicial y final en efectivo), o se registra directamente la venta por empleado? → A: Control formal de caja con apertura de turno (monto inicial en Bs.), registro de ventas vinculado a la caja activa del vendedor y arqueo de cierre diario.

---

## 2. User Scenarios & Testing *(mandatory)*

### User Story 1 - Emisión Ágil de Proformas / Cotizaciones con Envío a WhatsApp (Priority: P1) 🎯 MVP

Como vendedor de tienda o dueño de Servimática,  
quiero armar una cotización seleccionando productos del catálogo, indicar los datos del cliente y generar un comprobante en PDF con botón de envío a WhatsApp,  
para presentar presupuestos formales y transparentes a los clientes sin descontar aún el inventario físico.

**Why this priority**: Es la herramienta principal de preventa y atención en piso de venta. Muchos clientes solicitan cotización previa antes de tomar la decisión de compra.

**Independent Test**: Puede probarse creando una proforma para el cliente "Carlos Mamani" con una Laptop y un mouse, verificando que el stock de ambos productos permanezca intacto, que se descargue el documento en PDF con precios en `Bs.` y que el enlace de WhatsApp abra el chat con el mensaje preformateado.

**Acceptance Scenarios**:
1. **Dado** que el Vendedor está en la pantalla de Proformas/POS, **cuando** busca productos por nombre o SKU y los añade al carrito con sus cantidades y precios oficiales en `Bs.`, **entonces** el subtotal y total se calculan instantáneamente.
2. **Dado** que el cliente solicita el presupuesto a su nombre, **cuando** el vendedor ingresa Nombre/Razón Social, NIT/CI (opcional) y Teléfono celular, **entonces** la proforma queda vinculada al cliente con código correlativo (ej. `PRF-000001`) y fecha de vigencia (48 horas por defecto).
3. **Dado** que la proforma ha sido guardada, **cuando** el vendedor hace clic en "Enviar a WhatsApp", **entonces** se abre la API de WhatsApp Web/App (`wa.me/591...`) con un mensaje estructurado que detalla los productos cotizados, el total en Bolivianos y la validez de la oferta.
4. **Dado** que se consulta el inventario en el catálogo, **cuando** una proforma está vigente, **entonces** las existencias físicas (`stock`) de los productos no se reducen ni se bloquean.

---

### User Story 2 - Venta en Mostrador (POS) y Descuento de Stock en Tiempo Real (Priority: P2)

Como vendedor o dueño,  
quiero registrar una venta cobrada en Efectivo o QR, imprimiendo el recibo de venta,  
para entregar los productos al cliente garantizando que el inventario se descuente de inmediato y las existencias sean verídicas en todo momento.

**Why this priority**: Es el núcleo de la transacción económica y el cumplimiento del Principio V de la Constitución (Verdad única de datos de inventario en tiempo real).

**Independent Test**: Realizar una venta en efectivo de un producto con stock 5, registrar pago con billete de 200 Bs., comprobar que el vuelto se calcule correctamente, imprimir el recibo y verificar que el stock en el catálogo quede automáticamente en 4.

**Acceptance Scenarios**:
1. **Dado** que hay una proforma previa guardada, **cuando** el vendedor la abre y presiona "Convertir en Venta", **entonces** todos los ítems se cargan automáticamente en la pasarela de cobro del POS sin tener que volver a registrarlos.
2. **Dado** que el cliente paga en **Efectivo**, **cuando** el vendedor digita el importe entregado por el cliente en Bolivianos, **entonces** el sistema calcula y muestra en pantalla de forma visible el cambio/vuelto exacto en `Bs.`.
3. **Dado** que el cliente paga mediante **QR (Simple QR / Transferencia)**, **cuando** se selecciona este medio de pago, **entonces** el sistema registra el comprobante como pagado vía transferencia bancaria/QR sin solicitar cálculo de cambio.
4. **Dado** que se confirma la venta, **cuando** se procesa la transacción, **entonces** el stock de cada producto vendido disminuye en la base de datos de manera atómica, emitiendo un recibo de venta con correlativo único (ej. `VNT-000001`).
5. **Dado** que un producto no cuenta con existencias suficientes (`stock = 0` o menor a la cantidad solicitada), **cuando** se intenta concretar la venta, **entonces** el sistema bloquea la operación y notifica la falta de disponibilidad.

---

### User Story 3 - Cálculo y Liquidación de Comisiones por Vendedor (Priority: P3)

Como Dueño de Servimática,  
quiero que cada venta acumule la comisión pactada del vendedor y poder consultar los totales ganados por empleado en un periodo de tiempo,  
para liquidar los sueldos y comisiones de forma justa, transparente y sin planillas manuales propensas a errores.

**Why this priority**: Conecta directamente con la ficha de usuario creada en el Capítulo 3 (`sales_commission`) e incentiva el desempeño del equipo comercial.

**Independent Test**: Realizar una venta de `Bs. 1.000,00` por un vendedor con comisión del `3%`, verificar que la venta registre `Bs. 30,00` de comisión ganada, y revisar el reporte de comisiones comprobando el acumulado del vendedor.

**Acceptance Scenarios**:
1. **Dado** que un vendedor con comisión del `2.5%` concreta una venta de `Bs. 2.000,00`, **cuando** la venta queda en estado "Completada", **entonces** se almacena en el registro de la venta la comisión de `Bs. 50,00`.
2. **Dado** que el Dueño accede al reporte de ventas y comisiones, **cuando** filtra por rango de fechas (ej. Mes actual) y selecciona un vendedor, **entonces** visualiza el listado de ventas realizadas, el importe total vendido y el monto total de comisiones por pagar.
3. **Dado** que una venta es anulada por el Dueño (ej. por devolución justificada), **cuando** se cancela la transacción, **entonces** el stock de los productos retorna al inventario y la comisión asociada se descuenta o invalida en el reporte.

---

### User Story 4 - Directorio Rápido de Clientes y Clientes Casuales (Priority: P4)

Como vendedor o dueño,  
quiero buscar rápidamente clientes recurrentes por NIT, CI o teléfono, o vender a un "Cliente General" sin fricción de registro,  
para no demorar la atención al cliente en el mostrador.

**Why this priority**: Evita cuellos de botella en la fila de la tienda física y permite construir una base de datos de clientes para garantías y fidelización.

**Independent Test**: Buscar a un cliente existente por CI para cargarlo en la venta; luego realizar una venta rápida a "Cliente Mostrador / Sin NIT" con un solo clic.

**Acceptance Scenarios**:
1. **Dado** que un cliente no desea registrar sus datos, **cuando** el vendedor inicia una venta o cotización, **entonces** la opción predeterminada es "Cliente General / Sin NIT" permitiendo finalizar la venta en un clic.
2. **Dado** que un cliente solicita comprobante formal con sus datos, **cuando** el vendedor ingresa su NIT/CI o teléfono en el buscador, **entonces** si ya existe se autocompleta el nombre, y si no existe se permite registrarlo al vuelo sin salir de la pantalla de venta.

### User Story 5 - Apertura de Turno, Control de Efectivo y Arqueo de Caja Diaria (Priority: P5)

Como vendedor o cajero de Servimática,  
quiero abrir mi turno de caja indicando el fondo inicial en efectivo (`Bs.`) y realizar el arqueo de cierre al finalizar mi jornada,  
para asegurar que el dinero cobrado en mostrador cuadre exactamente con las ventas registradas sin pérdidas ni discrepancias.

**Why this priority**: Es el mecanismo indispensable para la responsabilidad sobre el dinero físico en mostrador y la prevención de descuadres de caja.

**Independent Test**: Abrir caja con `Bs. 200,00` de fondo inicial, realizar una venta en efectivo de `Bs. 150,00` y otra por QR de `Bs. 300,00`. Al cerrar caja, el sistema debe indicar que el efectivo esperado es `Bs. 350,00` (el QR no suma a caja física). Si el usuario cuenta `Bs. 350,00`, la diferencia debe ser `Bs. 0,00` (cuadre exacto).

**Acceptance Scenarios**:
1. **Dado** que un vendedor inicia su jornada en el POS y no tiene caja abierta, **cuando** intenta cobrar una venta, **entonces** el sistema le solicita previamente abrir su turno registrando el monto inicial en efectivo (fondo de caja para cambio).
2. **Dado** que la caja está abierta, **cuando** se realizan ventas en **Efectivo**, **entonces** el sistema acumula el dinero al balance físico esperado de la caja activa.
3. **Dado** que el vendedor finaliza su turno, **cuando** presiona "Cerrar Caja / Arqueo", **entonces** el sistema le solicita ingresar el monto físico real contado en monedas y billetes en Bolivianos.
4. **Dado** que se registra el arqueo de cierre, **cuando** el monto contado difiere del esperado, **entonces** el sistema calcula y guarda la diferencia explícita (sobrante o faltante en `Bs.`), cerrando el turno y generando el resumen de cierre de caja.

---

## 3. Edge Cases y Casos Límite

- **¿Qué ocurre si dos vendedores intentan vender la última unidad de un producto al mismo tiempo?**  
  La transacción en base de datos debe ser atómica y contar con bloqueo pesimista o verificación estricta de stock (`stock >= cantidad`). El primero que confirma descuenta la unidad; al segundo se le notifica que el producto acaba de agotarse.
- **¿Qué ocurre si el cliente paga con un importe en efectivo menor al total de la venta?**  
  El sistema valida que el importe entregado sea igual o mayor al total de la venta; el botón de confirmación permanece deshabilitado mientras falte dinero.
- **¿Qué ocurre si una proforma guardada hace 5 días intenta convertirse en venta pero los precios o el stock cambiaron?**  
  Al presionar "Convertir en Venta", el sistema valida las existencias actuales y alerta al vendedor si algún ítem ya no cuenta con stock disponible o si la proforma superó su fecha límite de validez.
- **¿Puede un vendedor ver los costos de los productos en la pantalla del POS?**  
  Bajo ninguna circunstancia (Principio VI de la Constitución). En la pantalla de ventas y proformas, el vendedor solo visualiza el precio de venta al público en Bolivianos (`Bs.`), nunca el costo de compra ni el margen del dueño.
- **¿Qué ocurre con los pagos recibidos vía QR respecto a la caja física?**  
  Los pagos por QR van directamente a la cuenta bancaria del negocio; se registran y auditan como ventas completadas de la sesión, pero no suman al efectivo físico esperado en el cajón de monedas/billetes durante el arqueo de caja.

---

## 4. Requirements *(mandatory)*

### Functional Requirements

- **RF-040**: El sistema DEBE proveer un módulo de Punto de Venta (POS) interactivo con carrito de compras, selector rápido de productos por nombre, SKU o código de barras, y ajuste de cantidades.
- **RF-041**: El sistema DEBE permitir registrar Proformas/Cotizaciones sin descontar existencias de stock del inventario, con numeración correlativa única (ej. `PRF-000001`).
- **RF-042**: El sistema DEBE generar un documento descargable/imprimible en PDF para cada proforma y un enlace directo con mensaje preformateado para WhatsApp Web / App móvil.
- **RF-043**: El sistema DEBE permitir convertir una proforma existente en una venta concretada con un solo clic, transfiriendo los productos y cantidades a la pasarela de cobro.
- **RF-044**: El sistema DEBE admitir métodos de pago en moneda nacional Boliviana (`Bs.`): Efectivo (con cálculo automático de cambio/vuelto) y Pago QR / Transferencia Bancaria.
- **RF-045**: Al confirmar una venta, el sistema DEBE descontar automáticamente y de forma atómica la cantidad vendida del campo `stock` de cada producto en la base de datos central.
- **RF-046**: El sistema DEBE registrar para cada venta concretada: vendedor responsable, cliente asociado (o Cliente General), medio de pago, fecha y hora, total en Bolivianos, y comisión calculada.
- **RF-047**: El sistema DEBE calcular la comisión del vendedor multiplicando el total de la venta por el porcentaje `sales_commission` que el usuario tiene registrado en su perfil (si es mayor a 0).
- **RF-048**: El sistema DEBE generar un comprobante/recibo de venta en formato ticket (impresora térmica 80mm/58mm) y estándar, con opción de reimpresión posterior.
- **RF-049**: El sistema DEBE mantener un directorio de Clientes (Nombre, NIT/CI, Teléfono, Correo, Dirección) con opción de creación rápida al vuelo durante el cobro.
- **RF-050**: Solo el usuario con rol de Dueño DEBE tener permisos para anular ventas concretadas, revirtiendo el stock al inventario e invalidando la comisión acumulada.
- **RF-051**: El sistema DEBE proveer al Dueño un reporte consolidado de ventas y comisiones acumuladas por vendedor en un rango de fechas determinado, con opción de exportación a Excel.
- **RF-052**: El sistema DEBE requerir que el vendedor o cajero abra un turno de caja especificando el monto inicial en efectivo (`opening_amount` en Bs.) antes de poder procesar ventas en el POS.
- **RF-053**: Cada venta en el POS DEBE asociarse al turno de caja abierto del vendedor responsable (`cash_shift_id`).
- **RF-054**: Al cerrar el turno de caja, el sistema DEBE solicitar el conteo físico de efectivo real (`closing_amount` en Bs.), compararlo contra el saldo esperado (saldo inicial + ventas en efectivo) y registrar la diferencia (sobrante o faltante) con fecha y notas de cierre.

---

### Key Entities

- **Cliente (Client)**: Representa a los compradores de la tienda. Atributos: `name` (Nombre / Razón Social), `nit_ci` (Número de documento), `phone` (Teléfono / Celular de contacto), `email` (opcional), `address` (opcional).
- **Turno de Caja (CashShift)**: Control de caja chica y efectivo en mostrador. Atributos: `user_id`, `opening_amount` (Bs.), `closing_amount` (Bs.), `expected_amount` (Bs.), `difference` (Bs.), `status` (`open`, `closed`), `opened_at`, `closed_at`, `notes`.
- **Proforma / Cotización (Quote)**: Presupuesto emitido para un cliente sin compromiso de stock. Atributos: `quote_number`, `client_id`, `seller_id`, `subtotal`, `discount`, `total`, `valid_until`, `status` (`activa`, `convertida`, `vencida`).
- **Ítem de Proforma (QuoteItem)**: Detalle de productos cotizados. Atributos: `quote_id`, `product_id`, `quantity`, `unit_price`, `subtotal`.
- **Venta (Sale)**: Transacción comercial concretada y cobrada. Atributos: `invoice_number`, `client_id`, `seller_id`, `cash_shift_id`, `payment_method` (`efectivo`, `qr`, `transferencia`), `subtotal`, `discount_amount`, `total_amount`, `cash_tendered`, `change_due`, `commission_rate`, `commission_amount`, `status` (`completada`, `anulada`), `created_at`.
- **Ítem de Venta (SaleItem)**: Detalle de productos vendidos. Atributos: `sale_id`, `product_id`, `quantity`, `unit_price`, `unit_cost` (para balance de margen en reportes del dueño), `subtotal`.

---

## 5. Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-014**: Un vendedor puede armar una proforma completa de 3 productos y generar el mensaje de WhatsApp para el cliente en menos de 45 segundos.
- **SC-015**: El cobro de una venta en mostrador (en efectivo o QR) y la emisión del comprobante toma menos de 30 segundos.
- **SC-016**: El descuento de stock en el inventario ocurre de manera atómica e inmediata: ningún cliente o vendedor puede ver stock desactualizado tras una venta confirmada.
- **SC-017**: El cálculo del vuelto en efectivo es 100% exacto y se presenta de forma destacada en la pantalla para evitar errores de caja.
- **SC-018**: El reporte mensual de comisiones calcula con precisión matemática el total correspondiente a cada empleado sin requerir ajustes manuales.
- **SC-019**: El 100% de los precios, totales, pagos y cambios se expresan y validan en Bolivianos (`Bs.`), cumpliendo estrictamente la Constitución de Servimática.
- **SC-020**: La apertura de turno de caja toma menos de 15 segundos y el arqueo de cierre calcula discrepancias de caja automáticamente con precisión al centavo.

---

## 6. Assumptions & Scope Boundaries

### Supuestos Aceptados:
- La moneda exclusiva del sistema es el Boliviano (`BOB` / `Bs.`).
- El medio de pago QR opera mediante lectura del código QR estático o dinámico del banco del negocio (Simple QR); el vendedor valida visualmente en su aplicación bancaria o comprobante del cliente la recepción del pago antes de confirmar la venta en el sistema.
- Las proformas no reservan stock físico para evitar que productos queden inmovilizados por cotizaciones no concretadas.
- La anulación de ventas requiere confirmación explícita con motivo justificado y queda registrada para auditoría.

### Fuera de Alcance en este Capítulo:
- Integración directa con pasarelas de pago con tarjeta de crédito extranjeras (Stripe / PayPal) — no aplica al mercado local boliviano de computación en mostrador.
- Facturación electrónica en línea SIAT (Servicio de Impuestos Nacionales) — el sistema emite recibos / notas de venta comerciales de tienda física; la integración con SIAT se evaluará en un capítulo posterior específico.
