# Contrato API: Ficha de Usuario y Exportación de Personal

**Ruta Base**: `/api/users`
**Autenticación**: Bearer JWT Token
**Permisos**: Exclusivo Rol Administrador (Dueño)

---

## 1. Crear Usuario con Ficha Completa

- **Endpoint**: `POST /api/users`
- **Content-Type**: `multipart/form-data` o `application/json`
- **Campos Obligatorios**:
  - `ci`: string (requerido, máx:30, unique:users)
  - `name`: string (requerido, máx:100)
  - `username`: string (requerido, máx:50, unique:users)
  - `password`: string (requerido, min:6)
  - `role`: string (requerido, in:administrador,vendedor)
  - `is_active`: boolean (requerido)
- **Campos Opcionales**:
  - `email`: string (opcional, email, unique:users)
  - `phone`: string (opcional, máx:30)
  - `address`: string (opcional, máx:255)
  - `gender`: string (opcional, in:masculino,femenino,otro)
  - `sales_commission`: numeric (opcional, min:0, max:100, default: 0.00)
  - `branch`: string (opcional, default: "Casa Matriz")
  - `avatar`: file (opcional, image, max:2048 KB)

- **Respuesta Exitosa (201 Created)**:
  ```json
  {
    "data": {
      "id": 5,
      "ci": "8472910 LP",
      "name": "Marco Antonio Soliz",
      "username": "msoliz",
      "email": "msoliz@servimatica.com",
      "phone": "77218392",
      "address": "Av. 6 de Agosto #450",
      "gender": "masculino",
      "sales_commission": 2.50,
      "branch": "Casa Matriz",
      "avatar_url": "/storage/avatars/avatar_5.jpg",
      "role": "vendedor",
      "is_active": true,
      "created_at": "2026-10-03T14:00:00Z"
    },
    "message": "Usuario registrado exitosamente"
  }
  ```

---

## 2. Exportar Nómina de Usuarios a Excel (.xlsx)

- **Endpoint**: `GET /api/users/export-excel`
- **Permisos**: Exclusivo Rol Administrador (Dueño)
- **Query Params**: Admite filtros activos (`search`, `role`, `status`).
- **Respuesta**:
  - `Content-Type`: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
  - `Content-Disposition`: `attachment; filename="nomina_usuarios_servimatica_YYYY-MM-DD.xlsx"`
- **Columnas**:
  1. `N° de Documento (CI)`
  2. `Nombres y Apellidos`
  3. `Sexo`
  4. `Teléfono`
  5. `Dirección`
  6. `Correo Electrónico`
  7. `Usuario de Acceso`
  8. `Rol`
  9. `Estado`
  10. `Comisión por Ventas (%)`
  11. `Sucursal`
  12. `Fecha de Registro`
  13. `Último Acceso Registrado`
