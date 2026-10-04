# Feature Specification: 008-devoluciones-garantias-ventas

**Feature Branch**: `008-devoluciones-garantias-ventas`  
**Created**: 2026-10-03  
**Status**: Draft  
**Input**: User description: "lo que es las devoluciones, luego de las devoluciones kardex individual, kardex valorizado, kardex por fecha y todos los kardex que se pueda hacer. toma en cuenta que en una tienda como esta dan garantía entonces en la venta debe haber eso cuanto tiempo de garantía le dan y todo eso hay que analizar"

---

## Clarifications

### Session 2026-10-03
- Q: ¿Cómo debe definirse y aplicarse el periodo de garantía técnica para los productos vendidos? → A: Configurar el tiempo de garantía predeterminado por producto en el catálogo (ej. meses o días), y permitir al vendedor ajustarlo opcionalmente al momento de la venta en el POS.
- Q: ¿Cómo debe gestionarse el cambio físico cuando el cliente desea un producto distinto o no hay existencias del mismo modelo? → A: Cambio directo 1 a 1 por el mismo producto; si se requiere un artículo diferente o de mayor valor, se resuelve la devolución generando saldo a favor (nota de crédito o reembolso) y se efectúa una nueva venta estándar en el POS cobrando la diferencia.
- Q: ¿Cómo debe manejarse el número de serie (Serial / S/N) en las ventas y garantías de los equipos? → A: Permitir registrar el número de serie (S/N) opcionalmente por ítem al vender o al procesar la devolución/garantía para facilitar el cotejo físico de equipos como laptops, monitores o componentes.
- Q: ¿Qué nivel de permisos y control deben tener los vendedores frente a las devoluciones y reembolsos de dinero? → A: Los vendedores pueden procesar cambios físicos directos mano a mano dentro del plazo de garantía; los reembolsos de dinero en efectivo quedan restringidos al rol Dueño o requieren su autorización para proteger la caja chica.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registro y Emisión de Garantía en la Venta (Priority: P1) 🎯 MVP

Como vendedor o dueño en el Punto de Venta (POS) o al facturar una cotización, quiero especificar el tiempo de garantía otorgado a cada producto vendido (ej. sin garantía, 30 días, 3 meses, 6 meses, 1 año) para que el cliente reciba un comprobante con el respaldo formal de su garantía técnica y el sistema mantenga la trazabilidad de validez temporal.

**Why this priority**: Es la base imprescindible del negocio. En informática, una devolución o cambio por falla técnica no puede gestionarse adecuadamente si no existe un registro fehaciente del tiempo de garantía otorgado en el momento de la venta.

**Independent Test**: Realizar una venta en el POS seleccionando un producto con "6 meses de garantía"; verificar que el ticket impreso y la base de datos registran la fecha límite de cobertura de garantía.

**Acceptance Scenarios**:
1. **Given** un vendedor agregando productos en la pantalla de ventas (POS), **When** selecciona un producto del catálogo, **Then** el sistema asigna el tiempo de garantía predeterminado del producto (o permite personalizarlo antes de cobrar: ej. 0, 30, 90, 180, 365 días).
2. **Given** una venta cobrada exitosamente, **When** se emite el ticket térmico o la nota de venta, **Then** el comprobante detalla expresamente el periodo o fecha hasta la cual cubre la garantía técnica de cada artículo.

---

### User Story 2 - Consulta y Verificación de Estado de Garantía por Venta (Priority: P2)

Como vendedor o dueño en mostrador, quiero buscar una venta por su número de comprobante, nombre de cliente o serie de producto para verificar al instante si el producto presentado por el cliente está dentro del periodo de garantía vigente o si ya expiró.

**Why this priority**: Evita disputas y pérdidas económicas en mostrador al dar al personal la certeza inmediata de si el equipo cumple con las condiciones temporales para ser recibido por garantía o reclamo.

**Independent Test**: Ingresar el número de ticket `TKT-00123`; el sistema muestra el detalle de los productos vendidos, las fechas de compra y de vencimiento de garantía, con un distintivo visual claro: "Garantía Vigente (faltan 45 días)" o "Garantía Expirada".

**Acceptance Scenarios**:
1. **Given** un cliente que se presenta con un ticket de compra emitido hace 2 meses con garantía de 6 meses, **When** el vendedor ingresa el número de ticket, **Then** el sistema marca el producto como "Garantía Vigente" y habilita la opción de procesar devolución o cambio.
2. **Given** un ticket emitido hace 8 meses con garantía de 3 meses, **When** se consulta el comprobante, **Then** el sistema alerta que la garantía expiró hace 5 meses, bloqueando la devolución por garantía estándar a menos que el Dueño autorice una excepción.

---

### User Story 3 - Procesamiento de Devolución por Garantía o Comercial (Priority: P3)

Como dueño o vendedor autorizado, quiero registrar la devolución de un producto vendido (indicando el motivo: falla técnica en garantía, producto defectuoso de fábrica, o devolución por cambio/retracto comercial) y elegir el destino del inventario (reingreso a stock vendible si está nuevo/sellado, o ingreso a stock defectuoso/cuarentena si presenta falla técnica) para mantener la exactitud del inventario operativo.

**Why this priority**: Garantiza que un producto dañado devuelto por un cliente nunca vuelva a venderse accidentalmente a otro cliente, segregando los equipos defectuosos para su posterior reclamo al mayorista/proveedor.

**Independent Test**: Procesar la devolución por garantía de una memoria RAM defectuosa; verificar que el stock vendible no aumenta pero se incrementa el registro de productos defectuosos/cuarentena con su respectiva justificación técnica.

**Acceptance Scenarios**:
1. **Given** una devolución por falla técnica en garantía, **When** se confirma la recepción, **Then** el sistema envía el artículo a inventario defectuoso/RMA y genera el comprobante de recepción por garantía.
2. **Given** una devolución comercial de un accesorio nuevo sin abrir, **When** se procesa la devolución, **Then** el artículo reingresa al inventario disponible para la venta.

---

### User Story 4 - Resolución Económica: Cambio Físico, Nota de Crédito o Reembolso en Caja (Priority: P4)

Como dueño o vendedor en caja, quiero resolver la devolución mediante: a) reemplazo mano a mano por otra unidad disponible, b) entrega de un saldo a favor / nota de crédito para otra compra, o c) reembolso de efectivo desde la caja chica abierta, para cerrar el circuito financiero sin desfasar el arqueo de caja.

**Why this priority**: Cuida el dinero del negocio y la conciliación diaria de los cajeros: cualquier salida de dinero por devolución debe quedar asentada como egreso del turno de caja vigente.

**Independent Test**: Realizar una devolución con reembolso de Bs. 150 en efectivo; verificar que el arqueo de caja del turno actual registra el egreso de Bs. 150 por devolución de venta con el número de ticket asociado.

**Acceptance Scenarios**:
1. **Given** una devolución con reembolso en efectivo en un turno de caja abierto, **When** se aprueba el reembolso, **Then** el saldo en efectivo de la caja disminuye por el monto devuelto y queda registrado en el historial del turno.
2. **Given** una devolución con cambio mano a mano, **When** el cliente acepta una nueva unidad del mismo producto, **Then** se descuenta una unidad del stock vendible para el cliente y no hay movimiento de dinero en efectivo.

---

## Edge Cases

- **Intento de devolución sin turno de caja abierto cuando se solicita reembolso de dinero:** El sistema debe impedir el reembolso en efectivo si la caja está cerrada, exigiendo abrir el turno o emitir nota de crédito/cambio físico.
- **Devolución de venta con descuento global:** El cálculo del monto a devolver por cada ítem debe ponderar proporcionalmente el descuento otorgado en la venta original.
- **Devolución parcial:** El cliente compró 3 unidades de un producto pero solo falla 1 unidad; el sistema debe permitir devolver exclusivamente esa unidad, manteniendo intactas las 2 restantes.
- **Devolución repetida:** Un ítem que ya fue devuelto en su totalidad no puede volver a devolverse en futuras operaciones.
- **Venta anulada previamente:** Si una venta ya fue anulada en su totalidad, no se puede iniciar un proceso de devolución sobre ella.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El catálogo de productos DEBE permitir definir el periodo de garantía predeterminado (`warranty_days`: ej. 0 para sin garantía, 30 días, 3 meses, 6 meses, 1 año), el cual se hereda automáticamente al agregar el ítem en la pantalla de ventas (POS) con posibilidad de ajuste por el vendedor antes de cobrar.
- **FR-002**: Las notas de venta, tickets POS térmicos (80mm) y comprobantes de proforma DEBEN imprimir con claridad el periodo de garantía correspondiente a cada producto vendido y la fecha límite de validez.
- **FR-003**: El sistema DEBE proveer un buscador de ventas por folio de comprobante, cliente o número de serie (S/N) que muestre el estado de vigencia de la garantía técnica de cada artículo vendido ("Garantía Vigente" con días restantes o "Garantía Expirada").
- **FR-004**: El sistema DEBE permitir registrar devoluciones totales o parciales de los artículos de una venta, exigiendo un motivo explícito (falla técnica, defecto de fábrica, error de producto, insatisfacción del cliente) y permitiendo registrar el número de serie (S/N) devuelto.
- **FR-005**: El sistema DEBE clasificar el destino del producto devuelto:
  - *Stock vendible/operativo:* si el producto se encuentra en perfecto estado y puede venderse nuevamente.
  - *Stock defectuoso / RMA / Garantía de Proveedor:* si el producto presenta fallas y debe ser apartado para reclamo al mayorista, impidiendo que vuelva a estar disponible para la venta general.
- **FR-006**: El sistema DEBE soportar las siguientes modalidades de resolución:
  - *Cambio mano a mano:* entrega inmediata de otra unidad idéntica del inventario operativo. Si el cliente requiere un modelo superior o diferente, la devolución se liquida generando saldo a favor (o nota de crédito) y se genera una nueva venta estándar en el POS cobrando la diferencia correspondiente.
  - *Reembolso en efectivo:* egreso monetario registrado automáticamente en la caja chica abierta del turno activo.
  - *Nota de crédito / Saldo a favor:* comprobante con saldo utilizable por el cliente en compras posteriores.
- **FR-007**: Toda devolución procesada DEBE generar un "Comprobante de Devolución y Garantía" imprimible en formato térmico (80mm) y Carta formal, detallando cliente, producto devuelto, serial (S/N), motivo, resolución adoptada y firmas de conformidad.
- **FR-008**: Los vendedores DEBEN tener permisos para consultar garantías y procesar cambios mano a mano por productos idénticos en garantía vigente; las operaciones que impliquen reembolso de dinero en efectivo de la caja chica o excepciones fuera de garantía DEBEN requerir privilegios o confirmación del Dueño.

---

### Key Entities

- **Garantía y Serial de Ítem de Venta (SaleItem)**: Se añaden atributos de días de garantía otorgados (`warranty_days`), fecha límite de cobertura de garantía (`warranty_expires_at`) y número de serie opcional (`serial_number`).
- **Devolución de Venta (SaleReturn)**: Identificador único, número consecutivo de comprobante de devolución (`DEV-000001`), venta origen asociada, usuario responsable, cliente, fecha de devolución, motivo técnico/comercial, resolución (`cambio_fisico`, `reembolso_efectivo`, `nota_credito`), turno de caja asociado (para egresos) y estado.
- **Ítem de Devolución (SaleReturnItem)**: Producto devuelto, cantidad, número de serie (`serial_number`), precio unitario de venta original, condición/destino del ítem (`stock_operativo` o `stock_defectuoso_rma`).

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Los vendedores pueden comprobar la vigencia de garantía de un cliente en mostrador en menos de 10 segundos ingresando el número de ticket.
- **SC-002**: El 100% de las ventas emitidas en el POS dejan registro explícito del plazo de garantía técnica de los productos.
- **SC-003**: Un proceso completo de devolución e impresión de comprobante de cambio se completa en menos de 1 minuto.
- **SC-004**: Cero desfasajes en los arqueos de caja chica al registrar devoluciones con devolución monetaria gracias al egreso automático sincronizado.
- **SC-005**: Segregación garantizada del 100% de artículos defectuosos devueltos, evitando que productos con fallas regresen al inventario para venta al público.

---

## Assumptions

- Cada venta está asociada a un cliente (registrado con nombre y documento, o cliente general de mostrador con nombre proporcionado).
- Los productos consumibles o cables que no tienen garantía técnica se registran explícitamente con 0 días de garantía.
- El turno de caja chica activo absorbe los reembolsos en efectivo realizados durante la jornada laboral.
- Este capítulo sienta la base transaccional de movimientos de devolución que alimentará en el capítulo siguiente (009) los reportes de Kardex integral físico y valorizado.
