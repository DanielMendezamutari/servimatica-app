# API Contract: Dashboard Summary Endpoint

**Feature**: `specs/011-dashboard-navegacion-dark-mode`
**Date**: 2026-10-03

---

## Endpoint: Resumen Ejecutivo del Dashboard

### `GET /api/v1/dashboard/summary`

Retorna los indicadores de ventas, estado de caja, alertas de existencias y ranking de productos en tiempo real.

#### Query Parameters
| Parámetro | Tipo | Requerido | Descripción | Valores permitidos |
|---|---|---|---|---|
| `period` | string | No | Ventana temporal de consulta (default: `today`) | `today`, `this_week`, `this_month` |

#### Autenticación y Autorización
- **Header:** `Authorization: Bearer <jwt_token>`
- **Acceso:** Permitido para roles `dueno` y `vendedor`.
- **Regla de Confidencialidad (Principio VI):**
  - Si el rol es `dueno` o `administrador`: Incluye `total_profit_bs` y `profit_margin_percentage`.
  - Si el rol es `vendedor` o `cajero`: `total_profit_bs` y `profit_margin_percentage` son omitidos o devueltos como `null`. Además, las métricas de ventas se filtran exclusivamente a las generadas por dicho vendedor si la caja es personal.

---

### Respuesta 200 OK (Rol: Dueño / Administrador)

```json
{
  "success": true,
  "data": {
    "period": "today",
    "period_label": "Hoy (03 de Octubre, 2026)",
    "kpis": {
      "total_sales_bs": 1450.00,
      "sales_count": 8,
      "average_ticket_bs": 181.25,
      "previous_period_sales_bs": 1200.00,
      "sales_trend_percentage": 20.83,
      "total_profit_bs": 420.00,
      "profit_margin_percentage": 28.97
    },
    "cash_shift": {
      "has_active_shift": true,
      "shift_id": 14,
      "cashier_name": "Daniel Mendez",
      "opened_at": "08:30:15",
      "initial_cash_bs": 200.00,
      "current_cash_sales_bs": 850.00,
      "current_total_collected_bs": 1450.00
    },
    "critical_stock": [
      {
        "product_id": 5,
        "name": "Teclado Mecánico RGB Redragon",
        "sku": "KB-RED-01",
        "current_stock": 1,
        "min_stock": 3,
        "status": "low_stock"
      },
      {
        "product_id": 12,
        "name": "Pasta Térmica Arctic MX-4 4g",
        "sku": "PT-ARC-04",
        "current_stock": 0,
        "min_stock": 5,
        "status": "out_of_stock"
      }
    ],
    "top_products": [
      {
        "product_id": 1,
        "name": "Cable HDMI 2.0 4K 2 metros",
        "category_name": "Accesorios",
        "units_sold": 5,
        "total_revenue_bs": 250.00
      }
    ]
  }
}
```

---

### Respuesta 200 OK (Rol: Vendedor / Cajero)

```json
{
  "success": true,
  "data": {
    "period": "today",
    "period_label": "Hoy (03 de Octubre, 2026)",
    "kpis": {
      "total_sales_bs": 850.00,
      "sales_count": 4,
      "average_ticket_bs": 212.50,
      "previous_period_sales_bs": 600.00,
      "sales_trend_percentage": 41.67,
      "total_profit_bs": null,
      "profit_margin_percentage": null
    },
    "cash_shift": {
      "has_active_shift": true,
      "shift_id": 14,
      "cashier_name": "Vendedor Tienda",
      "opened_at": "08:30:15",
      "initial_cash_bs": 200.00,
      "current_cash_sales_bs": 850.00,
      "current_total_collected_bs": 850.00
    },
    "critical_stock": [
      {
        "product_id": 5,
        "name": "Teclado Mecánico RGB Redragon",
        "sku": "KB-RED-01",
        "current_stock": 1,
        "min_stock": 3,
        "status": "low_stock"
      }
    ],
    "top_products": [
      {
        "product_id": 1,
        "name": "Cable HDMI 2.0 4K 2 metros",
        "category_name": "Accesorios",
        "units_sold": 3,
        "total_revenue_bs": 150.00
      }
    ]
  }
}
```
