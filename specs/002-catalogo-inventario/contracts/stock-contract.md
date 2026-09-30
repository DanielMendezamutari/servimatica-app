# Contrato de API: Ajuste e Historial de Stock (`/api/products/{id}/stock`)

**Feature**: `002-catalogo-inventario`
**Date**: 2026-09-26

*Exclusivo para el rol `Dueño/Administrador`. Cualquier petición con token de rol `vendedor` recibe `403 Forbidden`.*

---

## 1. `POST /api/products/{id}/stock`

Realiza un ajuste de stock (incremento o decremento) sobre un producto específico.

### Headers
```http
Authorization: Bearer <accessToken>
Content-Type: application/json
Accept: application/json
```

### Request Body
```json
{
  "type": "in",
  "quantity": 3,
  "reason": "Recepción de mercadería del proveedor"
}
```

| Campo | Tipo | Obligatorio | Validación |
|-------|------|-------------|------------|
| `type` | string | Sí | `in` (ingreso) o `out` (egreso) |
| `quantity` | integer | Sí | > 0 |
| `reason` | string | Sí | Máx 255 caracteres |

### Success Response (`200 OK`)
```json
{
  "message": "Stock ajustado correctamente.",
  "data": {
    "productId": 1,
    "productName": "Laptop HP 15-ef2xxx",
    "previousStock": 3,
    "newStock": 6,
    "movement": {
      "id": 1,
      "type": "in",
      "quantity": 3,
      "reason": "Recepción de mercadería del proveedor",
      "userId": 1,
      "userName": "Administrador Dueño",
      "createdAt": "2026-09-26T14:30:00Z"
    }
  }
}
```

### Error Responses

**`422 Unprocessable Entity` — Stock insuficiente**
```json
{
  "message": "No se puede reducir el stock. La cantidad solicitada (10) supera el stock actual (5)."
}
```

**`404 Not Found`**
```json
{
  "message": "Producto no encontrado."
}
```

---

## 2. `GET /api/products/{id}/stock-history`

Lista el historial de movimientos de stock de un producto con paginación y filtros.

### Headers
```http
Authorization: Bearer <accessToken>
Accept: application/json
```

### Query Parameters
| Parámetro | Tipo | Obligatorio | Descripción |
|-----------|------|-------------|-------------|
| `type` | string | No | Filtra por tipo: `in` o `out` |
| `from` | string (date) | No | Fecha inicio (formato `YYYY-MM-DD`) |
| `to` | string (date) | No | Fecha fin (formato `YYYY-MM-DD`) |
| `page` | integer | No | Número de página (default: 1) |
| `per_page` | integer | No | Elementos por página (default: 20, máx: 50) |

### Success Response (`200 OK`)
```json
{
  "data": [
    {
      "id": 3,
      "type": "out",
      "quantity": 1,
      "previousStock": 6,
      "newStock": 5,
      "reason": "Merma / defectuoso",
      "userName": "Administrador Dueño",
      "createdAt": "2026-09-26T16:00:00Z"
    },
    {
      "id": 2,
      "type": "in",
      "quantity": 3,
      "previousStock": 3,
      "newStock": 6,
      "reason": "Recepción de mercadería del proveedor",
      "userName": "Administrador Dueño",
      "createdAt": "2026-09-26T14:30:00Z"
    }
  ],
  "meta": {
    "currentPage": 1,
    "lastPage": 1,
    "perPage": 20,
    "total": 2
  },
  "product": {
    "id": 1,
    "name": "Laptop HP 15-ef2xxx",
    "sku": "LAP-HP-001",
    "currentStock": 5
  }
}
```

*Nota: Los movimientos se ordenan del más reciente al más antiguo.*

---

## Permisos

- **Dueño:** Ajustar stock (`POST`) y consultar historial (`GET`).
- **Vendedor:** Sin acceso. Respuesta `403 Forbidden`.
