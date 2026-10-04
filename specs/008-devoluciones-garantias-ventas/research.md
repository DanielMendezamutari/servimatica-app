# Research & Technical Decisions: 008-devoluciones-garantias-ventas

**Feature**: Devoluciones de Venta y Gestión de Garantías  
**Date**: 2026-10-03  
**Status**: Completed

---

## 1. Modelo de Datos para Garantías y Números de Serie

### Decisión
- Agregar columna `warranty_days` (entero no negativo, default: 0) a la tabla `products`.
- Extender la tabla `sale_items` con:
  - `warranty_days` (entero no negativo): Días de garantía pactados en la venta (heredados del producto o personalizados en el POS).
  - `warranty_expires_at` (timestamp/date nullable): Fecha exacta de vencimiento de la garantía calculada automáticamente (`sale.created_at + warranty_days`).
  - `serial_number` (string nullable, max 100): Número de serie físico del equipo o componente entregado al cliente.

### Justificación
- Garantiza la inmutabilidad histórica: si el catálogo cambia la garantía de un producto meses después, las ventas pasadas conservan exactamente los días de garantía contratados al momento de la venta.
- Permite calcular al instante en SQL o en PHP si la garantía está vigente sin recalculaciones complejas.

### Alternativas Descartadas
- *Calcular la garantía en tiempo real uniendo siempre con `products.warranty_days`:* Descartada porque alteraría retroactivamente las garantías de ventas pasadas si el dueño cambia la política de un producto.

---

## 2. Segregación de Inventario Operativo vs Defectuoso (RMA / Merma)

### Decisión
- Añadir a `products` una columna `defective_stock` (entero no negativo, default: 0).
- Cuando un ítem devuelto tenga condición `stock_operativo` (devolución comercial de producto nuevo o sellado):
  - Se incrementa `products.stock` (inventario disponible para venta en mostrador).
- Cuando un ítem devuelto tenga condición `stock_defectuoso_rma` (producto fallado por garantía):
  - `products.stock` NO se incrementa (permanece intocado para evitar que se venda a otro cliente).
  - `products.defective_stock` se incrementa para auditoría y posterior gestión con el mayorista/proveedor.
- Cuando la resolución es `cambio_fisico` (reemplazo mano a mano):
  - Se decrementa 1 unidad de `products.stock` (la nueva unidad que se entrega al cliente).

### Justificación
- Resuelve el mayor dolor de cabeza en tiendas de hardware: que un disco o placa fallada devuelta por un cliente se confunda y se vuelva a vender a otra persona.
- Permite al Dueño auditar en cualquier momento cuántos equipos defectuosos tiene pendientes de reclamo a los proveedores.

---

## 3. Integración con Caja Chica y Turnos de Ventas

### Decisión
- Toda devolución con `resolution = 'reembolso_efectivo'` exige un turno de caja abierto (`cash_shift_id`).
- La tabla `cash_shifts` registrará un campo o acumulación `total_refunds` (o decremento de saldo de ventas en efectivo) para que el arqueo de caja físico final cuadre al centavo:
  $$\text{Efectivo Esperado} = \text{Apertura} + \text{Ventas Efectivo} - \text{Reembolsos Devolución}$$
- Si no hay turno abierto o no hay saldo en efectivo suficiente en caja, el sistema rechaza la devolución en efectivo.

### Justificación
- Cero desfasajes en el arqueo del cajero al cierre del día (Principio V de la Constitución).

---

## 4. Resoluciones Comerciales y Permisos de Usuario

### Decisión
- **Permisos:**
  - Vendedor: Puede consultar garantías, buscar comprobantes y procesar devoluciones con resolución `cambio_fisico` (1 a 1 de la misma referencia) siempre que la garantía esté vigente.
  - Dueño: Puede procesar cualquier devolución, incluyendo `reembolso_efectivo`, excepciones con garantías expiradas y notas de crédito.
- **Diferencia de precio en cambios:**
  - Si el cliente quiere un producto superior o de mayor costo, la devolución se liquida entregando saldo a favor o reembolso, y el cliente realiza una venta normal en el POS por el nuevo equipo, cobrando la diferencia por cualquiera de los métodos de pago habilitados.

### Justificación
- Simplicidad (KISS) y cero sobre-ingeniería en transacciones mixtas complejas: aprovecha el flujo maduro del POS existente.
