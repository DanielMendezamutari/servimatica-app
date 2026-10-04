# Data Model: Dashboard y Resumen Ejecutivo en Tiempo Real

**Feature**: `specs/011-dashboard-navegacion-dark-mode`
**Date**: 2026-10-03

---

## 1. Entidades Virtuales de Lectura

No se requieren migraciones de base de datos nuevas; el Dashboard agrega datos de las tablas relacionales existentes (`sales`, `sale_items`, `cash_shifts`, `products`, `users`).

### 1.1 DashboardKPIs (Value Object)
Estructura de métricas financieras y operativas del periodo:
- `period`: string (`today` | `this_week` | `this_month`)
- `total_sales_bs`: float (Total facturado en ventas completadas en `Bs.`)
- `sales_count`: int (Número de tickets / ventas realizadas)
- `average_ticket_bs`: float (Promedio facturado por transacción en `Bs.`)
- `previous_period_sales_bs`: float (Total facturado en el periodo anterior homólogo, e.g. ayer)
- `sales_trend_percentage`: float (Variación porcentual positiva o negativa frente al periodo anterior)
- `total_profit_bs`: float | null (Utilidad bruta calculada `Ventas - Costos` en `Bs.`, **solo para Dueño**)
- `profit_margin_percentage`: float | null (Margen porcentual sobre ventas, **solo para Dueño**)

### 1.2 ActiveCashShiftSummary (Value Object)
Estado del turno de caja del día:
- `has_active_shift`: bool (Indica si hay turno abierto en la jornada)
- `shift_id`: int | null
- `cashier_name`: string | null (Nombre del operador responsable)
- `opened_at`: string | null (Hora de apertura en formato local)
- `initial_cash_bs`: float (Monto con el que inició la caja chica)
- `current_cash_sales_bs`: float (Cobros acumulados en efectivo del turno)
- `current_total_collected_bs`: float (Total cobrado en todos los métodos de pago)

### 1.3 CriticalStockItem (Value Object)
Registro de artículos con existencias críticas (punto de reorden):
- `product_id`: int
- `name`: string
- `sku`: string
- `current_stock`: int (Existencias vendibles disponibles)
- `min_stock`: int (Stock mínimo configurado)
- `status`: string (`out_of_stock` si stock == 0, `low_stock` si stock <= min_stock)

### 1.4 TopSellingProductSummary (Value Object)
Ranking de los 5 productos con mayor rotación en el periodo:
- `product_id`: int
- `name`: string
- `category_name`: string
- `units_sold`: int (Unidades totales vendidas)
- `total_revenue_bs`: float (Total facturado en `Bs.`)
