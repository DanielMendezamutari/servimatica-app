# Contrato API: Marcas y Modelos

**Ruta Base**: `/api/brands` y `/api/product-models`
**Autenticación**: Bearer JWT Token

---

## 1. Listar Marcas

- **Endpoint**: `GET /api/brands`
- **Permisos**: Dueño / Vendedor
- **Query Params**:
  - `search`: string (opcional)
  - `status`: `all` | `active` | `inactive` (default: `active` para vendedores, configurable para dueño)
- **Respuesta Exitosa (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": 1,
        "name": "ASUS",
        "is_active": true,
        "models_count": 8,
        "created_at": "2026-10-03T10:00:00Z"
      }
    ]
  }
  ```

---

## 2. Crear Marca

- **Endpoint**: `POST /api/brands`
- **Permisos**: Dueño (Administrador)
- **Request Body**:
  ```json
  {
    "name": "Logitech",
    "is_active": true
  }
  ```
- **Validaciones**: `name` (required, string, max:100, unique:brands).
- **Respuesta (201 Created)**: Objeto de marca creada.

---

## 3. Listar Modelos de una Marca

- **Endpoint**: `GET /api/brands/{brandId}/models`
- **Permisos**: Dueño / Vendedor
- **Respuesta (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": 10,
        "brand_id": 1,
        "name": "TUF Gaming F15",
        "notes": "Laptops gamer serie FX506",
        "is_active": true
      }
    ]
  }
  ```

---

## 4. Creación Rápida de Modelo al Vuelo

- **Endpoint**: `POST /api/product-models/quick-create`
- **Permisos**: Dueño (Administrador)
- **Request Body**:
  ```json
  {
    "brand_id": 1,
    "name": "ROG Strix G16"
  }
  ```
- **Lógica**: Si ya existe para esa marca, retorna el existente; si no existe, lo crea y lo retorna.
- **Respuesta (200 OK o 201 Created)**:
  ```json
  {
    "data": {
      "id": 11,
      "brand_id": 1,
      "name": "ROG Strix G16",
      "is_active": true
    },
    "message": "Modelo registrado y vinculado a la marca"
  }
  ```
