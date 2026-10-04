# API Contracts: Punto de Venta, Proformas y Caja (REST)

**Base URL**: `/api`  
**Authentication**: `Authorization: Bearer <jwt_token>`  
**Default Currency**: Bolivianos (`BOB` / `Bs.`)

---

## 1. Turnos de Caja (`/api/cash-shifts`)

### 1.1 `GET /api/cash-shifts/current`
Obtiene el turno de caja actualmente abierto del usuario autenticado.

- **Response 200**:
  ```json
  {
    "data": {
      "id": 1,
      "user_id": 2,
      "opening_amount": "200.00",
      "total_cash_sales": "450.00",
      "total_qr_sales": "600.00",
      "expected_amount": "650.00",
      "status": "open",
      "opened_at": "2026-10-03 08:30:00"
    }
  }
  ```
- **Response 200 (sin caja abierta)**:
  ```json
  {
    "data": null
  }
  ```

### 1.2 `POST /api/cash-shifts/open`
Abre un nuevo turno de caja.

- **Request**:
  ```json
  {
    "opening_amount": 200.00,
    "notes": "Fondo de cambio inicial en monedas y billetes"
  }
  ```
- **Response 201**: Turno creado con `status = 'open'`.

### 1.3 `POST /api/cash-shifts/close`
Cierra el turno de caja activo con arqueo físico.

- **Request**:
  ```json
  {
    "closing_amount": 650.00,
    "notes": "Cierre de turno normal, caja cuadrada"
  }
  ```
- **Response 200**:
  ```json
  {
    "data": {
      "id": 1,
      "opening_amount": "200.00",
      "total_cash_sales": "450.00",
      "expected_amount": "650.00",
      "closing_amount": "650.00",
      "difference": "0.00",
      "status": "closed",
      "closed_at": "2026-10-03 18:00:00"
    }
  }
  ```

---

## 2. Clientes (`/api/clients`)

### 2.1 `GET /api/clients`
Búsqueda de clientes por nombre, NIT/CI o teléfono.
- **Query Params**: `search`, `page`, `per_page`.
- **Response 200**: Lista paginada de clientes.

### 2.2 `POST /api/clients`
Registro rápido de cliente.
- **Request**:
  ```json
  {
    "name": "Carlos Mamani",
    "nit_ci": "4892019 LP",
    "phone": "72091823",
    "email": "carlos@gmail.com",
    "address": "Calle Calama #45"
  }
  ```

---

## 3. Proformas / Cotizaciones (`/api/quotes`)

### 3.1 `POST /api/quotes`
Crea una nueva proforma (sin descontar stock).
- **Request**:
  ```json
  {
    "client_id": 5,
    "client_name": "Carlos Mamani",
    "client_phone": "72091823",
    "valid_until": "2026-10-05",
    "discount_amount": 50.00,
    "notes": "Validez de 48 horas",
    "items": [
      {
        "product_id": 12,
        "quantity": 1,
        "unit_price": 3200.00
      },
      {
        "product_id": 8,
        "quantity": 1,
        "unit_price": 250.00
      }
    ]
  }
  ```
- **Response 201**: Retorna la proforma con su código `PRF-000001`, totales calculados y el enlace estructurado de WhatsApp.

### 3.2 `GET /api/quotes/{id}/pdf`
Descarga o visualiza la proforma en formato PDF formal con membrete.

---

## 4. Ventas en Mostrador (`/api/sales`)

### 4.1 `POST /api/sales`
Procesa y concreta una venta en mostrador (descuenta stock y valida caja).
- **Request**:
  ```json
  {
    "quote_id": null,
    "client_id": 5,
    "client_name": "Carlos Mamani",
    "client_nit_ci": "4892019 LP",
    "payment_method": "efectivo",
    "discount_amount": 50.00,
    "cash_tendered": 3500.00,
    "items": [
      {
        "product_id": 12,
        "quantity": 1,
        "unit_price": 3200.00
      },
      {
        "product_id": 8,
        "quantity": 1,
        "unit_price": 250.00
      }
    ]
  }
  ```
- **Response 201**:
  ```json
  {
    "data": {
      "id": 1,
      "invoice_number": "VNT-000001",
      "seller_name": "Vendedor Juan",
      "client_name": "Carlos Mamani",
      "payment_method": "efectivo",
      "subtotal": "3450.00",
      "discount_amount": "50.00",
      "total_amount": "3400.00",
      "cash_tendered": "3500.00",
      "change_due": "100.00",
      "commission_rate": "2.50",
      "commission_amount": "85.00",
      "status": "completed",
      "created_at": "2026-10-03 14:30:00"
    }
  }
  ```

### 4.2 `POST /api/sales/{id}/cancel` (Solo Dueño)
Anula la venta, reponiendo el stock y anulando la comisión.
- **Request**:
  ```json
  {
    "reason": "Devolución de equipo por incompatibilidad con el cliente"
  }
  ```

### 4.3 `GET /api/sales/commissions-report` (Solo Dueño)
Reporte consolidado de comisiones por empleado y rango de fechas.
