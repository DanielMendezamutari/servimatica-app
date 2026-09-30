# Contrato de API: Gestión de Productos (`/api/products`)

**Feature**: `002-catalogo-inventario`
**Date**: 2026-09-26

*Ambos roles acceden a este endpoint, pero la respuesta varía según el rol:*
- **Dueño:** Ve todos los campos incluidos `costPrice`, margen y productos inactivos.
- **Vendedor:** Solo ve productos activos de categorías activas con `salePrice` y `stock`.

---

## 1. `GET /api/products`

Lista los productos con paginación del lado del servidor.

### Headers
```http
Authorization: Bearer <accessToken>
Accept: application/json
```

### Query Parameters
| Parámetro | Tipo | Obligatorio | Descripción |
|-----------|------|-------------|-------------|
| `search` | string | No | Filtra por nombre o SKU (LIKE) |
| `category_id` | integer | No | Filtra por categoría |
| `status` | string | No | Filtra por estado (`active`, `inactive`). Solo Dueño |
| `page` | integer | No | Número de página (default: 1) |
| `per_page` | integer | No | Elementos por página (default: 15, máx: 50) |

### Success Response — Dueño (`200 OK`)
```json
{
  "data": [
    {
      "id": 1,
      "name": "Laptop HP 15-ef2xxx",
      "description": "Laptop HP con Ryzen 5, 8GB RAM, 256GB SSD",
      "sku": "LAP-HP-001",
      "categoryId": 1,
      "categoryName": "Laptops",
      "costPrice": "2500.00",
      "salePrice": "3200.00",
      "stock": 3,
      "minStock": 2,
      "status": "active",
      "createdAt": "2026-09-26T10:00:00Z"
    }
  ],
  "meta": {
    "currentPage": 1,
    "lastPage": 3,
    "perPage": 15,
    "total": 42
  }
}
```

### Success Response — Vendedor (`200 OK`)
```json
{
  "data": [
    {
      "id": 1,
      "name": "Laptop HP 15-ef2xxx",
      "description": "Laptop HP con Ryzen 5, 8GB RAM, 256GB SSD",
      "sku": "LAP-HP-001",
      "categoryId": 1,
      "categoryName": "Laptops",
      "salePrice": "3200.00",
      "stock": 3
    }
  ],
  "meta": {
    "currentPage": 1,
    "lastPage": 2,
    "perPage": 15,
    "total": 28
  }
}
```

*Nota: `costPrice`, `minStock` y `status` NO se incluyen en la respuesta del Vendedor. Solo se muestran productos activos de categorías activas.*

---

## 2. `POST /api/products`

Registra un nuevo producto. Solo Dueño.

### Headers
```http
Authorization: Bearer <accessToken>
Content-Type: application/json
Accept: application/json
```

### Request Body
```json
{
  "name": "Laptop HP 15-ef2xxx",
  "description": "Laptop HP con Ryzen 5, 8GB RAM, 256GB SSD",
  "categoryId": 1,
  "sku": "LAP-HP-001",
  "costPrice": 2500.00,
  "salePrice": 3200.00,
  "stock": 3,
  "minStock": 2
}
```

| Campo | Tipo | Obligatorio | Validación |
|-------|------|-------------|------------|
| `name` | string | Sí | Máx 200 caracteres |
| `description` | string | No | Máx 2000 caracteres |
| `categoryId` | integer | Sí | Debe existir en `categories` |
| `sku` | string | No | Máx 50 caracteres, único. Auto-generado si se omite |
| `costPrice` | number | Sí | >= 0, máx 2 decimales |
| `salePrice` | number | Sí | >= 0, máx 2 decimales |
| `stock` | integer | No | >= 0, default 0 |
| `minStock` | integer | No | >= 0, default 0 |

### Success Response (`201 Created`)
```json
{
  "message": "Producto creado exitosamente.",
  "data": {
    "id": 1,
    "name": "Laptop HP 15-ef2xxx",
    "sku": "LAP-HP-001",
    "categoryId": 1,
    "categoryName": "Laptops",
    "costPrice": "2500.00",
    "salePrice": "3200.00",
    "stock": 3,
    "minStock": 2,
    "status": "active"
  }
}
```

### Error Responses

**`422 Unprocessable Entity` — Validación**
```json
{
  "message": "Los datos proporcionados no son válidos.",
  "errors": {
    "name": ["El campo nombre es obligatorio."],
    "sku": ["El código SKU ya está en uso por otro producto."],
    "categoryId": ["La categoría seleccionada no existe."]
  }
}
```

---

## 3. `PUT /api/products/{id}`

Edita un producto existente. Solo Dueño. El campo `stock` se ignora en la edición (se modifica solo vía ajuste de stock).

### Request Body
```json
{
  "name": "Laptop HP 15-ef2xxx (actualizado)",
  "description": "Laptop HP con Ryzen 5, 8GB RAM, 512GB SSD",
  "categoryId": 1,
  "sku": "LAP-HP-001",
  "costPrice": 2600.00,
  "salePrice": 3400.00,
  "minStock": 2
}
```

### Success Response (`200 OK`)
```json
{
  "message": "Producto actualizado correctamente.",
  "data": { "...datos completos del producto..." }
}
```

---

## 4. `PATCH /api/products/{id}/toggle-status`

Activa o desactiva un producto. Solo Dueño.

### Success Response (`200 OK`)
```json
{
  "message": "Estado de producto actualizado correctamente.",
  "data": {
    "id": 1,
    "name": "Laptop HP 15-ef2xxx",
    "status": "inactive"
  }
}
```

---

## Permisos

- **Dueño:** CRUD completo + toggle-status. Ve `costPrice`, `minStock`, `status` y productos inactivos.
- **Vendedor:** Solo `GET /api/products` con datos restringidos (sin `costPrice`, sin `minStock`, sin `status`). Solo ve productos activos de categorías activas.
