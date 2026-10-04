# Feature Specification: Capítulo 5 — Compras y Recepción de Mercadería de Proveedores

**Feature Directory**: `specs/005-compras-proveedores-stock`

**Created**: 2026-10-03

**Status**: Draft

**Input**: User description: "Capítulo 5: Compras y Recepción de Mercadería de Proveedores (Ingreso formal de stock, costos de compra y actualización de precios)"

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Directorio y Gestión de Proveedores Mayoristas (Priority: P1)

Como Dueño / Administrador de Servimática, quiero registrar y administrar los datos de los proveedores y distribuidores mayoristas de partes y equipos de computación para centralizar los contactos comerciales, teléfonos/WhatsApp y condiciones de compra.

**Why this priority**: Es la base relacional indispensable para asociar compras, garantías de fábrica y notas de entrega. Sin proveedores registrados no se pueden registrar compras formales.

**Independent Test**: Registrar un proveedor con razón social, NIT, celular/WhatsApp y ciudad; listar, editar y verificar que los vendedores tengan bloqueado el acceso a estos datos.

**Acceptance Scenarios**:
1. **Given** el Dueño autenticado en el panel web, **When** registra un nuevo proveedor con razón social "Deltron Bolivia SRL", NIT "1029384019", contacto "Lic. Mario Terán" y teléfono "77123456", **Then** el proveedor queda almacenado y disponible inmediatamente en el selector de compras.
2. **Given** un usuario con rol Vendedor, **When** intenta consultar o acceder a la sección de proveedores, **Then** el sistema bloquea el acceso con código 403 Forbidden para resguardar la privacidad de los contactos mayoristas.

---

### User Story 2 — Registro de Compra y Recepción de Mercadería con Incremento Atómico de Stock (Priority: P2)

Como Dueño de Servimática, quiero registrar las compras de mercadería ingresando el N° de factura o nota de entrega del proveedor, seleccionando los productos adquiridos con sus cantidades y costos unitarios de compra en Bolivianos (Bs.), para que el inventario físico disponible en la tienda aumente de forma automática y auditable.

**Why this priority**: Es el núcleo operativo de reabastecimiento de la tienda. Garantiza que las existencias de stock crezcan fielmente cuando llega mercadería sin requerir ajustes manuales dispersos.

**Independent Test**: Registrar una compra de 5 unidades de un producto con stock 2 a un costo unitario de Bs. 850,00; verificar que el stock en el catálogo se incremente a 7 y que se cree el movimiento de inventario tipo `in` con motivo de compra.

**Acceptance Scenarios**:
1. **Given** un producto existente con stock 3, **When** el Dueño registra una compra de 10 unidades indicando el proveedor y N° de Nota de Entrega, **Then** el stock del producto se actualiza atómicamente a 13 unidades y se registra la traza inmutable en `stock_movements`.
2. **Given** un formulario de compra con múltiples ítems, **When** el Dueño confirma la recepción, **Then** todos los ítems se procesan bajo una única transacción (`DB::transaction`) impidiendo estados inconsistentes si falla algún registro.

---

### User Story 3 — Actualización Inteligente de Costos y Precios de Venta al Público (Priority: P3)

Como Dueño, al recepcionar mercadería cuyo costo unitario de compra haya variado, quiero tener la opción de actualizar el costo registrado del producto y definir un nuevo precio de venta al público en Bolivianos (Bs.) para mantener los márgenes de rentabilidad del negocio ante variaciones de precios mayoristas.

**Why this priority**: En el mercado boliviano de tecnología los precios de importación fluctúan; permitir ajustar el precio de venta en el mismo acto de recepción evita desfases de margen en el mostrador.

**Independent Test**: Recepcionar un producto que costaba Bs. 500 y se vendía a Bs. 650, ingresando nuevo costo de Bs. 550 y nuevo precio de venta de Bs. 720; comprobar que el catálogo refleje el nuevo precio para el POS.

**Acceptance Scenarios**:
1. **Given** un ítem en la orden de compra con costo diferente al costo actual en catálogo, **When** el Dueño ingresa el nuevo precio de venta sugerido, **Then** al recepcionar la compra el catálogo actualiza tanto el costo como el precio de venta al público en Bs.
2. **Given** un ítem donde el Dueño decide no alterar el precio de venta vigente, **When** se completa la compra, **Then** el precio de venta se mantiene inalterado en el catálogo.

---

### User Story 4 — Historial de Compras, Detalle de Recepción y Anulación de Compra (Priority: P4)

Como Dueño, quiero consultar el historial consolidado de compras con filtros por proveedor y rango de fechas, ver el detalle de ítems de cada compra, y disponer de una opción de anulación exclusiva que descuente las unidades ingresadas si una nota de entrega fue rechazada o devuelta.

**Why this priority**: Control financiero, auditoría de gastos de adquisición y capacidad de corregir errores de recepción sin descuadrar el inventario físico.

**Independent Test**: Consultar una compra recepcionada, anularla especificando el motivo, y verificar que el stock se descuente en la misma cantidad que ingresó, bloqueando la anulación si el stock actual es menor al que se intenta devolver.

**Acceptance Scenarios**:
1. **Given** una compra recepcionada de 5 unidades, **When** el Dueño anula la compra por devolución de mercadería al distribuidor, **Then** el estado de la compra cambia a `anulada` y el stock del producto disminuye en 5 unidades con su correspondiente movimiento de auditoría.
2. **Given** una compra donde los productos ya fueron vendidos y el stock actual es menor a las unidades compradas, **When** el Dueño intenta anularla, **Then** el sistema rechaza la anulación alertando que no hay suficiente stock disponible para la devolución física.

---

### Edge Cases

- **Stock insuficiente al anular compra**: Si se compraron 10 unidades pero ya se vendieron 6 (quedan 4), no se puede anular la compra completa de 10 unidades. El sistema debe lanzar una excepción clara impidiendo stock negativo.
- **Costos en cero o negativos**: El costo unitario de compra debe ser estrictamente mayor a 0,00 Bs.
- **N° de Factura/Nota repetida del mismo proveedor**: El sistema debe advertir si ya existe una compra registrada con el mismo N° de documento para el mismo proveedor para evitar ingresos duplicados por error humano.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE proveer un módulo de Proveedores exclusivo para el Dueño (`Role::Owner`), con campos: Razón Social, NIT/CI, Persona de Contacto, Teléfono/WhatsApp, Email, Ciudad, Dirección física y Estado (Activo/Inactivo).
- **FR-002**: El sistema DEBE permitir registrar compras a proveedores asociando: Proveedor, N° de Factura/Nota de Entrega, Fecha de Emisión, Fecha de Recepción, Condición de Pago (`contado` con método efectivo/transferencia, o `credito` con fecha límite de vencimiento), Observaciones y lista de ítems.
- **FR-003**: Cada ítem de compra DEBE contener: Producto del catálogo, Cantidad ingresada, Costo unitario de compra en Bs., Subtotal, y opcionalmente Nuevo precio de venta al público en Bs.
- **FR-004**: Al recepcionar la compra, el sistema DEBE actualizar de forma atómica (`lockForUpdate` + `DB::transaction`) el stock disponible del producto sumando la cantidad comprada.
- **FR-005**: Al recepcionar la compra, el sistema DEBE registrar un movimiento inmutable en `stock_movements` con `type = 'in'`, cantidad adquirida, stock anterior y nuevo stock, con motivo que incluya la razón social del proveedor y el N° de factura/nota.
- **FR-006**: Al recepcionar la compra, el sistema DEBE actualizar el `cost_price` del producto en el catálogo al valor del Último Costo de compra registrado en la factura.
- **FR-007**: El sistema DEBE permitir consultar el historial de compras con paginación, filtros por fecha, proveedor y búsqueda por N° de documento.
- **FR-008**: El sistema DEBE permitir ver el detalle completo de una compra y reimprimir la Nota de Recepción de Mercadería para archivo contable.
- **FR-009**: El sistema DEBE permitir la anulación de una compra exclusivamente por parte del Dueño, revirtiendo el inventario ingresado si existe stock disponible en el almacén.
- **FR-010**: El sistema DEBE bloquear terminantemente el acceso al módulo de compras y a cualquier dato de costos a los usuarios con rol Vendedor.
- **FR-011**: El sistema DEBE permitir seleccionar productos existentes del catálogo y proveer un diálogo de "Alta Rápida de Producto" para registrar productos nuevos al vuelo sin abandonar la pantalla de compra.

---

### Key Entities

- **Supplier (Proveedor)**: Representa a los distribuidores mayoristas (Razón Social, NIT, Contacto, Teléfono/WhatsApp, Ciudad, Dirección, Estado).
- **Purchase (Orden de Compra / Recepción)**: Registro maestro de compra (N° de documento del proveedor, Proveedor ID, Registrado por Usuario ID, Fecha de compra, Total en Bs., Estado `recepcionada` / `anulada`, Motivo de anulación).
- **PurchaseItem (Ítem de Compra)**: Detalle de productos adquiridos (Compra ID, Producto ID, Cantidad, Costo Unitario en Bs., Subtotal en Bs., Precio de Venta fijado).
- **StockMovement**: Historial inmutable de entradas y salidas de inventario.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El Dueño puede registrar un nuevo proveedor en menos de 45 segundos.
- **SC-002**: El registro de una compra de 10 productos con actualización de stock y costos se completa en menos de 2 segundos de tiempo de respuesta de servidor.
- **SC-003**: 100% de las compras recepcionadas incrementan de forma exacta el stock en el catálogo y generan su respectiva traza inmutable en `stock_movements`.
- **SC-004**: Cero filtraciones de costos de compra hacia la interfaz o APIs del rol Vendedor (verificado mediante pruebas automatizadas de seguridad).
- **SC-005**: La anulación de una compra restituye el stock de manera atómica sin permitir saldos negativos en ningún producto.

---

## Assumptions

- Toda la gestión de compras se realiza en moneda nacional Bolivianos (`BOB` / `Bs.`).
- El registro de compras es responsabilidad y potestad exclusiva del Administrador / Dueño.
- Las existencias de stock aumentan inmediatamente al confirmar la recepción de la mercadería en la tienda física de Servimática.
- La base de datos existente de productos, categorías, marcas y movimientos de stock se reutiliza sin duplicar esquemas.

---

## Clarifications

### Session 2026-10-03
- **Q1 (Actualización de Costos)**: ¿Cómo debe actualizarse el costo del producto en el catálogo al recepcionar mercadería?
  - **Decisión**: Se reemplaza por el **Último Costo de Compra** registrado en la factura/nota de entrega. Actualiza directamente `products.cost_price` para mantener reflejado el valor de reposición real del mercado.
- **Q2 (Condiciones de Pago a Proveedores)**: ¿Qué condiciones de pago a proveedores debe admitir el registro de compras?
  - **Decisión**: Se admiten compras **Al Contado** (pagadas de inmediato con efectivo o transferencia) y **A Crédito** (con fecha límite de vencimiento de pago para auditar cuentas por pagar a distribuidores mayoristas).
- **Q3 (Creación de Productos Nuevos en Compra)**: ¿Cómo debe manejarse la compra de productos nuevos que aún no existen en el catálogo?
  - **Decisión**: La orden de compra permite autocompletar productos existentes y cuenta con un botón de **Alta Rápida de Producto** que abre un diálogo modal para crear el producto básico (nombre, categoría, SKU generado/manual) sin perder los ítems ya cargados en la compra.

