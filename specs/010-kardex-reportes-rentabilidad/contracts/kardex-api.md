# Contract: Kardex API

**Endpoint**: `GET /api/v1/kardex/{productId}`
**Auth**: Bearer Token (JWT)
**Roles autorizados**: `dueño`, `administrador`, `cajero`, `vendedor`

## Query Parameters
- `from` (opcional, string Y-m-d): Fecha de inicio del filtro.
- `to` (opcional, string Y-m-d): Fecha de fin del filtro.
- `type` (opcional, string: `all`, `in`, `out`): Tipo de movimiento.
- `export` (opcional, string: `csv`): Si se especifica, descarga el archivo CSV.

## Response 200 OK (Rol Dueño / Administrador - Vista Valorizada Completa)

```json
{
  "success": true,
  "data": {
    "product": {
      "id": 15,
      "name": "Memoria RAM DDR4 16GB 3200MHz Kingston",
      "sku": "RAM-DDR4-16G",
      "current_stock": 24,
      "cost_price": 280.00,
      "sale_price": 380.00,
      "current_cpp": 275.50
    },
    "filter": {
      "from": "2026-09-01",
      "to": "2026-10-03"
    },
    "initial_balance": {
      "quantity": 5,
      "average_cost": 270.00,
      "total_value": 1350.00
    },
    "movements": [
      {
        "id": 101,
        "date": "2026-09-05 10:15:00",
        "type": "in",
        "reason": "Compra Factura F-9941",
        "reference_type": "purchase",
        "reference_id": 12,
        "user_name": "Daniel Méndez",
        "physical": {
          "entry": 20,
          "exit": 0,
          "balance": 25
        },
        "financial": {
          "unit_cost": 276.92,
          "debit": 5538.46,
          "credit": 0.00,
          "average_cost": 275.54,
          "balance_value": 6888.46
        }
      },
      {
        "id": 105,
        "date": "2026-09-10 14:30:00",
        "type": "out",
        "reason": "Venta POS VTA-0055",
        "reference_type": "sale",
        "reference_id": 44,
        "user_name": "Cajero Turno Tarde",
        "physical": {
          "entry": 0,
          "exit": 1,
          "balance": 24
        },
        "financial": {
          "unit_cost": 275.54,
          "debit": 0.00,
          "credit": 275.54,
          "average_cost": 275.54,
          "balance_value": 6612.92
        }
      }
    ],
    "totals": {
      "total_entries_quantity": 20,
      "total_exits_quantity": 1,
      "final_balance_quantity": 24,
      "total_debit_amount": 5538.46,
      "total_credit_amount": 275.54,
      "final_balance_value": 6612.92
    }
  }
}
```

## Response 200 OK (Rol Cajero / Vendedor - Vista Física Exclusiva)

```json
{
  "success": true,
  "data": {
    "product": {
      "id": 15,
      "name": "Memoria RAM DDR4 16GB 3200MHz Kingston",
      "sku": "RAM-DDR4-16G",
      "current_stock": 24
    },
    "filter": {
      "from": "2026-09-01",
      "to": "2026-10-03"
    },
    "initial_balance": {
      "quantity": 5
    },
    "movements": [
      {
        "id": 101,
        "date": "2026-09-05 10:15:00",
        "type": "in",
        "reason": "Compra Factura F-9941",
        "user_name": "Daniel Méndez",
        "physical": {
          "entry": 20,
          "exit": 0,
          "balance": 25
        }
      },
      {
        "id": 105,
        "date": "2026-09-10 14:30:00",
        "type": "out",
        "reason": "Venta POS VTA-0055",
        "user_name": "Cajero Turno Tarde",
        "physical": {
          "entry": 0,
          "exit": 1,
          "balance": 24
        }
      }
    ],
    "totals": {
      "total_entries_quantity": 20,
      "total_exits_quantity": 1,
      "final_balance_quantity": 24
    }
  }
}
```
*Nótese que la sección `financial` no existe en la respuesta para roles no administrativos (Principio VI).*
