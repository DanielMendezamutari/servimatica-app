# Contrato de API: Gestión de Categorías (`/api/categories`)

**Feature**: `002-catalogo-inventario`
**Date**: 2026-09-26

*Acceso CRUD exclusivo para el rol `Dueño/Administrador`. El Vendedor solo puede listar categorías activas vía el endpoint de productos.*

---

## 1. `GET /api/categories`

Lista todas las categorías. El Dueño ve todas (activas e inactivas); el Vendedor ve solo activas.

### Headers
```http
Authorization: Bearer <accessToken>
Accept: application/json
```

### Query Parameters
| Parámetro | Tipo | Obligatorio | Descripción |
|-----------|------|-------------|-------------|
| `search` | string | No | Filtra por nombre (LIKE) |

### Success Response (`200 OK`)
```json
{
  "data": [
    {
      "id": 1,
      "name": "Laptops",
      "description": "Computadoras portátiles y notebooks",
      "status": "active",
      "productsCount": 5,
      "createdAt": "2026-09-26T10:00:00Z"
    },
    {
      "id": 2,
      "name": "Componentes",
      "description": "Procesadores, memorias RAM, tarjetas de video",
      "status": "inactive",
      "productsCount": 12,
      "createdAt": "2026-09-26T10:05:00Z"
    }
  ]
}
```

*Nota: `productsCount` indica la cantidad de productos asignados. Los campos `status` y categorías inactivas solo se incluyen para el rol Dueño.*

---

## 2. `POST /api/categories`

Crea una nueva categoría. Solo Dueño.

### Headers
```http
Authorization: Bearer <accessToken>
Content-Type: application/json
Accept: application/json
```

### Request Body
```json
{
  "name": "Periféricos",
  "description": "Teclados, ratones, monitores, audífonos"
}
```

| Campo | Tipo | Obligatorio | Validación |
|-------|------|-------------|------------|
| `name` | string | Sí | Máx 100 caracteres, único |
| `description` | string | No | Máx 500 caracteres |

### Success Response (`201 Created`)
```json
{
  "message": "Categoría creada exitosamente.",
  "data": {
    "id": 3,
    "name": "Periféricos",
    "description": "Teclados, ratones, monitores, audífonos",
    "status": "active",
    "productsCount": 0
  }
}
```

### Error Response (`422 Unprocessable Entity`)
```json
{
  "message": "El nombre de categoría ya está en uso.",
  "errors": { "name": ["El nombre de categoría ya está en uso."] }
}
```

---

## 3. `PUT /api/categories/{id}`

Edita una categoría existente. Solo Dueño.

### Request Body
```json
{
  "name": "Periféricos y Audio",
  "description": "Teclados, ratones, monitores, audífonos y bocinas"
}
```

### Success Response (`200 OK`)
```json
{
  "message": "Categoría actualizada correctamente.",
  "data": {
    "id": 3,
    "name": "Periféricos y Audio",
    "description": "Teclados, ratones, monitores, audífonos y bocinas",
    "status": "active",
    "productsCount": 8
  }
}
```

---

## 4. `PATCH /api/categories/{id}/toggle-status`

Activa o desactiva una categoría. Solo Dueño.

### Success Response (`200 OK`)
```json
{
  "message": "Estado de categoría actualizado correctamente.",
  "data": {
    "id": 2,
    "name": "Componentes",
    "status": "inactive"
  }
}
```

---

## 5. `DELETE /api/categories/{id}`

Elimina una categoría. Solo Dueño. Solo se permite si no tiene productos asignados.

### Success Response (`200 OK`)
```json
{
  "message": "Categoría eliminada correctamente."
}
```

### Error Response (`422 Unprocessable Entity` — Tiene productos)
```json
{
  "message": "No se puede eliminar la categoría porque tiene 5 productos asignados. Reasígnelos primero."
}
```

---

## Permisos

- **Dueño:** CRUD completo + toggle-status + delete.
- **Vendedor:** Solo lectura de categorías activas (implícito en el listado de productos).
