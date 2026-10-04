# Research & Decisiones Técnicas: Capítulo 5 — Compras y Proveedores

**Feature**: `specs/005-compras-proveedores-stock`  
**Date**: 2026-10-03  
**Status**: Completed  

---

## 1. Decisiones Arquitectónicas Clave

### D1: Estrategia de Actualización de Costos de Compra
- **Contexto**: Cuando llega un lote nuevo de mercadería, los distribuidores mayoristas de computación en Bolivia varían precios constantemente debido al tipo de cambio y fletes.
- **Alternativas Evaluadas**:
  1. *Costo Promedio Ponderado*: Requiere un balance contable exhaustivo y fórmulas históricas susceptibles a errores de arrastre si algún lote previo tuvo registro impreciso.
  2. *Último Costo de Compra (Last Purchase Cost)*: Estándar comercial de tiendas minoristas tecnológicas. Al recepcionar la factura, el campo `products.cost_price` toma el costo unitario más reciente, sirviendo de base directa para fijar el nuevo precio de venta (`products.sale_price`).
- **Decisión**: **Último Costo de Compra**. Sencillo, directo (KISS & YAGNI) y alineado con la realidad operativa de Servimática.

### D2: Manejo de la Transacción Atómica de Recepción de Stock
- **Contexto**: La orden de compra puede contener decenas de ítems (discos, memorias, fuentes, procesadores). Una falla intermedia no debe dejar el stock a medias.
- **Decisión**: Se implementa `DB::transaction()` con bloqueo pesimista `lockForUpdate()` sobre cada producto. Por cada ítem:
  1. Se bloquea el registro del producto.
  2. Se incrementa `products.stock` en la cantidad comprada.
  3. Se actualiza `products.cost_price` al costo unitario facturado.
  4. Si el Dueño indicó un nuevo precio de venta, se actualiza `products.sale_price`.
  5. Se inserta el registro inmutable en `stock_movements` con `type = 'in'`, `previous_stock`, `new_stock` y motivo `"Compra Proveedor [Razón Social] - Doc [N°]"`.
  6. Se persiste la cabecera `purchases` y el detalle `purchase_items`.

### D3: Condiciones de Pago a Distribuidores
- **Contexto**: Servimática compra tanto al contado (efectivo/transferencia bancaria inmediata) como a crédito (plazos de 15, 30 o 45 días otorgados por distribuidores mayoristas autorizados).
- **Decisión**: La tabla `purchases` incluye:
  - `payment_condition`: `ENUM('contado', 'credito')`.
  - `payment_method`: `ENUM('efectivo', 'transferencia', 'otro')` (aplicable a contado o abonos).
  - `due_date`: `DATE NULL` (obligatorio si `payment_condition === 'credito'`).
  - `payment_status`: `ENUM('pagado', 'pendiente')`.

### D4: Regla Anti-Stock Negativo en Anulación de Compra
- **Contexto**: Si una compra se anula por error de digitación o devolución al mayorista, las unidades deben restarse del inventario.
- **Decisión**: Antes de restar, el sistema verifica que `product.stock >= item.quantity`. Si algún producto ya fue vendido en el POS y el stock restante es insuficiente para cubrir la devolución completa, el sistema aborta la operación con un error explicativo para evitar saldos negativos.

### D5: Privacidad Estricta de Costos (Constitución Principio VI)
- **Contexto**: Los vendedores no deben conocer los márgenes de ganancia ni los costos de adquisición de los proveedores.
- **Decisión**: Todos los endpoints `/api/suppliers` y `/api/purchases` se protegen estrictamente bajo el middleware `jwt.auth` + `owner`. La interfaz de compras se expone únicamente si CASL valida `ability.can('manage', 'all')`.
