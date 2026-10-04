# Data Model: Kardex y Reportes Financieros

**Feature**: `010-kardex-reportes-rentabilidad`
**Date**: 2026-10-03

## 1. Modificaciones a Tablas Existentes

### `stock_movements` (Ampliación Aditiva)

| Campo | Tipo | Nulo | Descripción |
|:------|:-----|:-----|:------------|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | No | Clave primaria |
| `product_id` | BIGINT UNSIGNED FK | No | Referencia a `products.id` |
| `user_id` | BIGINT UNSIGNED FK | No | Referencia al usuario ejecutor |
| `type` | ENUM('in', 'out') | No | Tipo físico: Entrada o Salida |
| `quantity` | INT UNSIGNED | No | Cantidad de unidades operadas |
| `previous_stock` | INT UNSIGNED | No | Existencia antes del movimiento |
| `new_stock` | INT UNSIGNED | No | Existencia resultante tras el movimiento |
| `reason` | VARCHAR(255) | No | Motivo legible de la transacción |
| `unit_cost` | DECIMAL(12, 4) | Sí | **[Nuevo]** Costo unitario asociado a la entrada o al CPP de la salida |
| `total_cost` | DECIMAL(12, 2) | Sí | **[Nuevo]** Monto total de la partida (Cantidad * Costo Unitario) |
| `reference_type` | VARCHAR(50) | Sí | **[Nuevo]** Tipo de documento origen (`purchase`, `sale`, `sale_return`, `adjustment`) |
| `reference_id` | BIGINT UNSIGNED | Sí | **[Nuevo]** ID del registro origen |
| `created_at` | TIMESTAMP | No | Fecha y hora exacta de registro |

---

## 2. Entidades de Dominio y Objetos de Valor (Domain Layer)

### `KardexEntry` (Value Object / Entidad de Reporte)
Representa un renglón procesado y auditado dentro del Kardex:
- `id`: int
- `date`: string (ISO 8601)
- `type`: string (`in` | `out`)
- `reason`: string
- `reference`: string (ej: "Compra CMP-2026-0001", "Venta VTA-0012")
- `user_name`: string
- **Campos Físicos (Públicos para cualquier rol autorizado a ver inventario)**:
  - `entry_quantity`: int
  - `exit_quantity`: int
  - `balance_quantity`: int
- **Campos Valorizados (Exclusivos para rol Dueño / Administrador)**:
  - `unit_cost`: float
  - `debit_amount`: float (Monto entrada en Bs.)
  - `credit_amount`: float (Monto salida en Bs.)
  - `average_unit_cost`: float (CPP móvil en Bs.)
  - `balance_value`: float (Saldo total en Bs.)

### `ProfitabilityReport` (DTO / Objeto de Análisis Financiero)
- `period`: object (`from`, `to`)
- `summary`:
  - `total_sales_amount`: float (Ingresos brutos por ventas en Bs.)
  - `total_cogs_amount`: float (Costo de mercancía vendida en Bs.)
  - `gross_profit`: float (Utilidad Bruta en Bs.)
  - `gross_margin_percentage`: float (Margen sobre ventas %)
  - `total_transactions`: int
  - `total_units_sold`: int
- `timeline`: array de registros diarios/mensuales con fecha, ventas, costo y ganancia
- `by_product`: array de productos ordenados por rentabilidad (id, name, sku, units_sold, revenue, cost, profit, margin_percentage)
- `by_category`: array de categorías con total vendido y utilidad aportada

### `InventoryValuationReport` (DTO de Auditoría Patrimonial)
- `total_items`: int (Variedad de productos)
- `total_units_sellable`: int (Unidades disponibles para la venta)
- `total_units_defective`: int (Unidades inmovilizadas en garantía o mermadas)
- `total_valuation_sellable`: float (Capital vendible en Bs.)
- `total_valuation_defective`: float (Capital inmovilizado en Bs.)
- `grand_total_valuation`: float (Capital total en Bs.)
- `products`: array con desglose detallado por producto.

---

## 3. Reglas de Validación y Estados

1. **Rango de Fechas**:
   - `from`: Fecha válida (Y-m-d).
   - `to`: Fecha válida (Y-m-d), mayor o igual a `from`.
   - Por defecto si no se especifican: mes en curso (del 1 al día actual).
2. **Autorización y Visibilidad (Principio VI)**:
   - Los roles `cajero` y `vendedor` tienen restringido el acceso a endpoints de rentabilidad y valoración global.
   - En el endpoint de Kardex por producto, los datos de costo se eliminan del payload si el rol no es administrativo.
