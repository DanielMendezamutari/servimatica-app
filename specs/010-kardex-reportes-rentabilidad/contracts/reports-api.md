# Contract: Financial & Valuation Reports API

## 1. Reporte de Rentabilidad

**Endpoint**: `GET /api/v1/reports/profitability`
**Auth**: Bearer Token (JWT)
**Roles autorizados**: `dueño`, `administrador` (`403 Forbidden` para otros roles)

### Query Parameters
- `from` (opcional, string Y-m-d): Inicio del periodo (predeterminado: primer día del mes actual).
- `to` (opcional, string Y-m-d): Fin del periodo (predeterminado: fecha actual).
- `group_by` (opcional, string: `day`, `month`, `product`, `category`): Agrupación solicitada.
- `export` (opcional, string: `csv`): Descarga del reporte en formato CSV compatible con Excel.

### Response 200 OK

```json
{
  "success": true,
  "data": {
    "period": {
      "from": "2026-10-01",
      "to": "2026-10-03"
    },
    "kpis": {
      "total_sales": 15420.00,
      "total_cogs": 11200.00,
      "gross_profit": 4220.00,
      "profit_margin_percentage": 27.37,
      "transactions_count": 38,
      "items_sold_count": 85
    },
    "timeline": [
      {
        "date": "2026-10-01",
        "sales": 5200.00,
        "cost": 3800.00,
        "profit": 1400.00,
        "margin_percentage": 26.92
      },
      {
        "date": "2026-10-02",
        "sales": 6120.00,
        "cost": 4400.00,
        "profit": 1720.00,
        "margin_percentage": 28.10
      },
      {
        "date": "2026-10-03",
        "sales": 4100.00,
        "cost": 3000.00,
        "profit": 1100.00,
        "margin_percentage": 26.83
      }
    ],
    "top_products": [
      {
        "id": 15,
        "name": "Memoria RAM DDR4 16GB 3200MHz",
        "sku": "RAM-DDR4-16G",
        "category_name": "Memorias RAM",
        "units_sold": 12,
        "total_revenue": 4560.00,
        "total_cost": 3306.48,
        "profit": 1253.52,
        "margin_percentage": 27.49
      }
    ]
  }
}
```

---

## 2. Reporte de Valoración de Inventario y Capital Inmovilizado

**Endpoint**: `GET /api/v1/reports/inventory-valuation`
**Auth**: Bearer Token (JWT)
**Roles autorizados**: `dueño`, `administrador`

### Query Parameters
- `category_id` (opcional, int): Filtrar por categoría.
- `export` (opcional, string: `csv`): Descarga del reporte en formato CSV compatible con Excel.

### Response 200 OK

```json
{
  "success": true,
  "data": {
    "summary": {
      "total_products_count": 142,
      "total_sellable_units": 890,
      "total_defective_units": 15,
      "sellable_valuation_bs": 185400.50,
      "defective_valuation_bs": 3250.00,
      "total_inventory_valuation_bs": 188650.50
    },
    "products": [
      {
        "id": 15,
        "sku": "RAM-DDR4-16G",
        "name": "Memoria RAM DDR4 16GB 3200MHz",
        "category": "Memorias RAM",
        "stock": 24,
        "defective_stock": 1,
        "average_cost": 275.54,
        "sellable_value_bs": 6612.96,
        "defective_value_bs": 275.54,
        "total_value_bs": 6888.50
      }
    ]
  }
}
```
