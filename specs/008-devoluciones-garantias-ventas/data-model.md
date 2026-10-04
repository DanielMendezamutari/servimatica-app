# Data Model: 008-devoluciones-garantias-ventas

**Feature**: Devoluciones de Venta y Gestión de Garantías  
**Date**: 2026-10-03

---

## 1. Extensiones a Tablas Existentes

### 1.1 Modificación en `products`
```sql
ALTER TABLE products 
ADD COLUMN warranty_days INT UNSIGNED NOT NULL DEFAULT 0 AFTER sale_price,
ADD COLUMN defective_stock INT NOT NULL DEFAULT 0 AFTER stock;
```
- `warranty_days`: Periodo predeterminado de garantía técnica en días (ej. 0 = Sin Garantía, 30 = 1 mes, 90 = 3 meses, 180 = 6 meses, 365 = 1 año).
- `defective_stock`: Existencias apartadas por falla/RMA en espera de trámite con proveedor o baja.

### 1.2 Modificación en `sale_items`
```sql
ALTER TABLE sale_items 
ADD COLUMN warranty_days INT UNSIGNED NOT NULL DEFAULT 0 AFTER unit_price,
ADD COLUMN warranty_expires_at DATE NULL AFTER warranty_days,
ADD COLUMN serial_number VARCHAR(100) NULL AFTER warranty_expires_at;
```
- `warranty_days`: Días de cobertura acordados al momento de la venta.
- `warranty_expires_at`: Fecha límite de vigencia de la garantía (`sale.created_at + warranty_days`).
- `serial_number`: Número de serie del artículo vendido (opcional).

### 1.3 Modificación en `cash_shifts`
```sql
ALTER TABLE cash_shifts
ADD COLUMN total_cash_refunds DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER total_cash_sales;
```
- `total_cash_refunds`: Monto acumulado de reembolsos en efectivo efectuados durante el turno de caja.

---

## 2. Nuevas Tablas de Devoluciones

### 2.1 Tabla `sale_returns`
Cabecera de la devolución o trámite de garantía.

| Columna | Tipo | Nulable | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | No | Clave primaria |
| `return_number` | VARCHAR(30) UNIQUE | No | Folio consecutivo (ej. `DEV-000001`) |
| `sale_id` | BIGINT UNSIGNED | No | FK a `sales.id` (venta de origen) |
| `client_id` | BIGINT UNSIGNED | Sí | FK a `clients.id` |
| `client_name` | VARCHAR(150) | No | Nombre del cliente al momento de devolver |
| `user_id` | BIGINT UNSIGNED | No | FK a `users.id` (responsable que atendió) |
| `cash_shift_id` | BIGINT UNSIGNED | Sí | FK a `cash_shifts.id` (si hubo reembolso en efectivo) |
| `resolution` | ENUM | No | `'cambio_fisico'`, `'reembolso_efectivo'`, `'nota_credito'` |
| `total_refund_amount` | DECIMAL(12,2) | No | Monto total devuelto / liquidado en Bs. |
| `reason` | TEXT | No | Justificación / reporte técnico de la falla o motivo comercial |
| `status` | ENUM | No | `'completed'`, `'cancelled'` (Default: `'completed'`) |
| `created_at` | TIMESTAMP | Sí | Fecha y hora del registro |
| `updated_at` | TIMESTAMP | Sí | Última actualización |

### 2.2 Tabla `sale_return_items`
Detalle de artículos comprendidos en la devolución.

| Columna | Tipo | Nulable | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | No | Clave primaria |
| `sale_return_id` | BIGINT UNSIGNED | No | FK a `sale_returns.id` ON DELETE CASCADE |
| `sale_item_id` | BIGINT UNSIGNED | No | FK a `sale_items.id` |
| `product_id` | BIGINT UNSIGNED | No | FK a `products.id` |
| `product_name` | VARCHAR(200) | No | Nombre histórico del producto devuelto |
| `quantity` | INT UNSIGNED | No | Cantidad de unidades devueltas |
| `unit_price` | DECIMAL(12,2) | No | Precio unitario al que se vendió en la venta original |
| `subtotal` | DECIMAL(12,2) | No | `quantity * unit_price` |
| `condition` | ENUM | No | `'stock_operativo'`, `'stock_defectuoso_rma'` |
| `serial_number` | VARCHAR(100) | Sí | Número de serie del ítem devuelto (si aplica) |
| `created_at` | TIMESTAMP | Sí | Fecha de registro |
| `updated_at` | TIMESTAMP | Sí | Última actualización |

---

## 3. Relaciones del Modelo

```text
Sale (1) ──< SaleItem (N)
   │
   └──< SaleReturn (N) ──< SaleReturnItem (N) >── Product (1)
           │
           └── CashShift (1, opcional si hubo reembolso efectivo)
```

---

## 4. Reglas de Negocio e Integridad

1. **Límite de cantidad por ítem:** La suma de cantidades devueltas en `sale_return_items` para un `sale_item_id` nunca puede exceder la cantidad originalmente vendida (`sale_items.quantity`).
2. **Validación de Caja Chica:** Si `resolution === 'reembolso_efectivo'`, es obligatorio que exista un turno de caja abierto (`cash_shift_id`) para el usuario actual y que el monto no supere el efectivo disponible en caja.
3. **Manejo de Stock Atómico:** Toda operación de devolución se ejecuta dentro de una transacción `DB::transaction(...)`:
   - Si `condition === 'stock_operativo'`: `products.stock += quantity`.
   - Si `condition === 'stock_defectuoso_rma'`: `products.defective_stock += quantity`.
   - Si `resolution === 'cambio_fisico'`: se valida que `products.stock >= quantity` y se descuenta `products.stock -= quantity`.
