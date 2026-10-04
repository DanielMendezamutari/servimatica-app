# API Contract: Gestión de Clientes (CRM)

## Base URL: `/api/v1/clients` (y `/api/clients`)

### 1. Listar Clientes
- **Method**: `GET /api/v1/clients`
- **Auth**: `Bearer <token>` (`dueno` o `vendedor`)
- **Query Params**:
  - `search`: Texto para buscar en `name`, `nit_ci`, `phone`.
  - `client_type`: `'all'` | `'final'` | `'mayorista'` | `'empresa'`.
  - `is_active`: `1` | `0` | `null` (todos).
  - `page`: `int` (default `1`).
  - `per_page`: `int` (default `15`).
- **Response `200 OK`**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Juan Perez",
      "nit_ci": "1234567-1B",
      "phone": "77332211",
      "whatsapp_url": "https://wa.me/59177332211",
      "email": "juan@example.com",
      "address": "Calle Cochabamba #45",
      "city": "Trinidad",
      "client_type": "final",
      "client_type_label": "Cliente Final",
      "notes": "Cliente recurrente de periféricos",
      "is_active": true,
      "total_spent_bs": 1450.00,
      "sales_count": 3,
      "last_purchase_at": "2026-10-02 18:30:00",
      "created_at": "2026-09-28 10:00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 65
  }
}
```

---

### 2. Registrar Cliente
- **Method**: `POST /api/v1/clients`
- **Auth**: `Bearer <token>` (`dueno` o `vendedor`)
- **Request Body**:
```json
{
  "name": "Comercializadora Beni SRL",
  "nit_ci": "1028475021",
  "phone": "78091122",
  "email": "contacto@comercializadorabeni.com",
  "address": "Av. Bolívar esq. 18 de Noviembre",
  "city": "Trinidad",
  "client_type": "empresa",
  "notes": "Facturación mensual"
}
```
- **Response `201 Created`**:
```json
{
  "message": "Cliente registrado correctamente.",
  "data": { ... }
}
```

---

### 3. Detalle Ficha 360° del Cliente
- **Method**: `GET /api/v1/clients/{id}`
- **Auth**: `Bearer <token>` (`dueno` o `vendedor`)
- **Response `200 OK`**:
```json
{
  "data": {
    "id": 1,
    "name": "Juan Perez",
    "nit_ci": "1234567-1B",
    "phone": "77332211",
    "whatsapp_url": "https://wa.me/59177332211",
    "email": "juan@example.com",
    "address": "Calle Cochabamba #45",
    "city": "Trinidad",
    "client_type": "final",
    "notes": "Cliente recurrente",
    "is_active": true,
    "stats": {
      "total_spent_bs": 1450.00,
      "sales_count": 3,
      "quotes_count": 2,
      "active_warranties_count": 1,
      "last_purchase_at": "2026-10-02 18:30:00"
    }
  }
}
```

---

### 4. Ventas del Cliente
- **Method**: `GET /api/v1/clients/{id}/sales`
- **Response `200 OK`**:
```json
{
  "data": [
    {
      "id": 12,
      "ticket_code": "V-2026-00012",
      "total": 850.00,
      "payment_method": "Efectivo",
      "status": "completed",
      "created_at": "2026-10-02 18:30:00",
      "items_count": 2
    }
  ]
}
```

---

### 5. Cotizaciones del Cliente
- **Method**: `GET /api/v1/clients/{id}/quotes`
- **Response `200 OK`**:
```json
{
  "data": [
    {
      "id": 5,
      "quote_number": "COT-2026-00005",
      "total": 3200.00,
      "status": "pending",
      "created_at": "2026-09-30 11:15:00",
      "items_count": 4
    }
  ]
}
```

---

### 6. Garantías del Cliente
- **Method**: `GET /api/v1/clients/{id}/warranties`
- **Response `200 OK`**:
```json
{
  "data": [
    {
      "product_name": "Laptop Lenovo ThinkPad E14 Gen 4",
      "serial_number": "PF-4910294",
      "sale_id": 12,
      "ticket_code": "V-2026-00012",
      "sale_date": "2026-09-01 10:00:00",
      "warranty_days": 365,
      "warranty_until": "2027-09-01 10:00:00",
      "days_remaining": 332,
      "is_valid": true,
      "status_label": "Vigente (332 días restantes)"
    }
  ]
}
```

---

### 7. Exportación a Excel/CSV
- **Method**: `GET /api/v1/clients/export`
- **Auth**: `Bearer <token>` (Sólo `dueno` / administrador)
- **Response `200 OK`**: Archivo `.csv` con cabeceras `Content-Type: text/csv` y `Content-Disposition: attachment; filename="clientes_servimatica_YYYY-MM-DD.csv"`.
- **Response `403 Forbidden`** para vendedores.
