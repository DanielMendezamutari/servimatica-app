# Contrato de API: Gestión de Personal (`/api/users`)

**Feature**: `001-base-estructural-acceso`  
**Date**: 2026-09-25  

*Exclusivo para el rol `Dueño/Administrador`. Cualquier petición con token de rol `vendedor` recibe `403 Forbidden`.*

---

## 1. `GET /api/users`

Lista a todo el personal de la tienda.

### Headers
```http
Authorization: Bearer <accessToken>
Accept: application/json
```

### Success Response (`200 OK`)
```json
{
  "data": [
    {
      "id": 1,
      "name": "Administrador Dueño",
      "username": "admin",
      "email": "admin@servimatica.com",
      "role": "dueno",
      "status": "active",
      "createdAt": "2026-09-25T14:00:00Z"
    },
    {
      "id": 2,
      "name": "Carlos Pérez",
      "username": "carlos",
      "email": "carlos@tienda.com",
      "role": "vendedor",
      "status": "active",
      "createdAt": "2026-09-25T15:30:00Z"
    }
  ]
}
```

---

## 2. `POST /api/users`

Registra un nuevo miembro del personal de la tienda (ej. Vendedor).

### Headers
```http
Authorization: Bearer <accessToken>
Content-Type: application/json
Accept: application/json
```

### Request Body
```json
{
  "name": "Carlos Pérez",
  "username": "carlos",
  "email": "carlos@tienda.com",
  "password": "password123", // Mínimo 6 caracteres
  "pin": "2468",              // Exactamente 4 dígitos
  "role": "vendedor"          // "dueno" o "vendedor"
}
```

### Success Response (`201 Created`)
```json
{
  "message": "Usuario creado exitosamente.",
  "data": {
    "id": 2,
    "name": "Carlos Pérez",
    "username": "carlos",
    "email": "carlos@tienda.com",
    "role": "vendedor",
    "status": "active"
  }
}
```

---

## 3. `PATCH /api/users/{id}/toggle-status`

Activa o desactiva a un empleado.

### Headers
```http
Authorization: Bearer <accessToken>
Accept: application/json
```

### Success Response (`200 OK`)
```json
{
  "message": "Estado de usuario actualizado correctamente.",
  "data": {
    "id": 2,
    "status": "inactive"
  }
}
```

### Error Response (`422 Unprocessable Entity` — Auto-bloqueo)
```json
{
  "message": "No puede desactivar su propia cuenta de Dueño/Administrador."
}
```

## 4. `PUT /api/users/{id}` — Edición (RF-006 / FA-005)

Exclusivo del Dueño. Cuerpo: `name`, `username`, `email`, `role` obligatorios;
`password` y `pin` opcionales. Ausentes o vacíos conservan el hash existente.
Si se proporcionan, se validan y reemplazan por un hash nuevo.

Respuesta 200: `{ "message": "Usuario actualizado correctamente.", "data": { ...datos públicos del usuario... } }`.
No expone contraseñas ni hashes. Unicidad de alias/correo excluye al usuario editado.
422 si se repiten identificadores, hay credenciales inválidas o se intenta quitar el propio rol Dueño.
404 si el usuario no existe; 401 sin token válido; 403 para vendedor o cuenta inactiva.

## Permisos del capítulo

Dueño: `manage/all`. Vendedor: `read/Dashboard`. Los módulos de ventas y proformas
no forman parte de este capítulo; no se generan pantallas ni permisos anticipados.