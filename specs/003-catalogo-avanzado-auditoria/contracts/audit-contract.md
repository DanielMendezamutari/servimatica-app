# Contrato API: Auditoría de Inicios de Sesión

**Ruta Base**: `/api/audit`
**Autenticación**: Bearer JWT Token
**Permisos**: Exclusivo Rol Administrador (Dueño)

---

## 1. Listar Registros de Inicios de Sesión

- **Endpoint**: `GET /api/audit/logins`
- **Query Params**:
  - `page`: int (default 1)
  - `per_page`: int (default 15)
  - `search`: string (búsqueda por usuario, IP)
  - `status`: `all` | `success` | `failed_credentials` | `failed_inactive_user`
  - `date_from`: `YYYY-MM-DD` (opcional)
  - `date_to`: `YYYY-MM-DD` (opcional)
- **Respuesta (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": 154,
        "user_id": 2,
        "attempted_username": "vendedor1",
        "ip_address": "192.168.1.45",
        "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)...",
        "status": "success",
        "status_label": "Exitoso",
        "user": {
          "id": 2,
          "name": "Carlos Mendoza",
          "username": "vendedor1",
          "role": "vendedor"
        },
        "created_at": "2026-10-03 14:15:30"
      },
      {
        "id": 153,
        "user_id": null,
        "attempted_username": "admin_hack",
        "ip_address": "181.115.12.8",
        "user_agent": "Python-urllib/3.9",
        "status": "failed_credentials",
        "status_label": "Credenciales inválidas",
        "user": null,
        "created_at": "2026-10-03 13:40:12"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 68
    }
  }
  ```
