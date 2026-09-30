# Contrato de API: Autenticación (`/api/auth`)

**Feature**: `001-base-estructural-acceso`  
**Date**: 2026-09-25  

---

## 1. `POST /api/auth/login`

Autentica al usuario mediante contraseña estándar o mediante PIN rápido de 4 dígitos.

### Request Headers
```http
Content-Type: application/json
Accept: application/json
```

### Request Body (Opción A: con Contraseña)
```json
{
  "login": "admin@servimatica.com", // Acepta correo o alias (ej. "admin")
  "password": "password"
}
```

### Request Body (Opción B: con PIN de 4 dígitos)
```json
{
  "login": "admin", // Acepta alias o correo
  "pin": "1234"     // Exactamente 4 dígitos
}
```

### Success Response (`200 OK`)
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "tokenType": "Bearer",
  "expiresIn": 28800, // 8 horas en segundos
  "userData": {
    "id": 1,
    "name": "Administrador Dueño",
    "username": "admin",
    "email": "admin@servimatica.com",
    "role": "dueno",
    "status": "active"
  },
  "userAbilityRules": [
    {
      "action": "manage",
      "subject": "all"
    }
  ]
}
```
*(Para rol `vendedor`, `userAbilityRules` devuelve la regla read/Dashboard para el panel del capítulo actual).*

### Error Responses

#### `401 Unauthorized` (Credenciales inválidas)
```json
{
  "message": "Credenciales no válidas. Verifique sus datos o su PIN."
}
```

#### `403 Forbidden` (Usuario inactivo)
```json
{
  "message": "Su cuenta se encuentra inactiva. Consulte con administración."
}
```

#### `422 Unprocessable Entity` (Error de validación)
```json
{
  "message": "Los datos proporcionados son inválidos.",
  "errors": {
    "login": ["El campo de identificación es obligatorio."],
    "pin": ["El PIN debe contener exactamente 4 números."]
  }
}
```

---

## 2. `POST /api/auth/logout`

Cierra la sesión invalidando el token JWT actual.

### Request Headers
```http
Authorization: Bearer <accessToken>
Accept: application/json
```

### Success Response (`200 OK`)
```json
{
  "message": "Sesión cerrada correctamente."
}
```

---

## 3. `GET /api/auth/me`

Devuelve los datos del usuario en sesión activa.

### Request Headers
```http
Authorization: Bearer <accessToken>
Accept: application/json
```

### Success Response (`200 OK`)
```json
{
  "userData": {
    "id": 1,
    "name": "Administrador Dueño",
    "username": "admin",
    "email": "admin@servimatica.com",
    "role": "dueno",
    "status": "active"
  },
  "userAbilityRules": [
    {
      "action": "manage",
      "subject": "all"
    }
  ]
}
```
