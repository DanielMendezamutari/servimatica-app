# API & Interface Contracts: 016-garantias-autonomas-hardware-software

**Feature**: 016-garantias-autonomas-hardware-software  
**Date**: 2026-10-08  
**Status**: Completed  

---

## 1. Catálogo de Productos

### `POST /api/products` & `PUT /api/products/{id}`

#### Request Payload
```json
{
  "name": "Laptop Lenovo IdeaPad 3 15ITL6",
  "sku": "NB-LEN-IP3-01",
  "category_id": 1,
  "cost_price": 3200.00,
  "sale_price": 3850.00,
  "stock": 5,
  "warranty_hardware_days": 730,
  "warranty_software_days": 180
}
```

#### Response (`200 OK` / `201 Created`)
```json
{
  "success": true,
  "data": {
    "id": 14,
    "name": "Laptop Lenovo IdeaPad 3 15ITL6",
    "sku": "NB-LEN-IP3-01",
    "warranty_hardware_days": 730,
    "warranty_software_days": 180,
    "warranty_days": 730
  }
}
```

---

## 2. Registro de Venta en POS

### `POST /api/sales`

#### Request Payload
```json
{
  "cash_shift_id": 1,
  "payment_method": "efectivo",
  "payment_method_id": 1,
  "client_id": 3,
  "client_name": "Carlos Mamani",
  "items": [
    {
      "product_id": 14,
      "quantity": 1,
      "unit_price": 3850.00,
      "warranty_hardware_days": 730,
      "warranty_software_days": 180,
      "serial_number": "PF3B1A2C"
    }
  ]
}
```

#### Response (`201 Created`)
```json
{
  "success": true,
  "data": {
    "id": 42,
    "invoice_number": "VTA-000042",
    "created_at": "2026-10-08 15:30:00",
    "items": [
      {
        "id": 89,
        "product_name": "Laptop Lenovo IdeaPad 3 15ITL6",
        "quantity": 1,
        "unit_price": 3850.00,
        "warranty_hardware_days": 730,
        "warranty_hardware_expires_at": "2028-10-07",
        "warranty_software_days": 180,
        "warranty_software_expires_at": "2027-04-06",
        "serial_number": "PF3B1A2C"
      }
    ]
  }
}
```

---

## 3. Consulta de Verificación Postventa

### `GET /api/sales/{id}/warranty-check`

#### Response (`200 OK`)
```json
{
  "success": true,
  "data": {
    "sale_id": 42,
    "invoice_number": "VTA-000042",
    "sale_date": "2026-10-08",
    "client_name": "Carlos Mamani",
    "items": [
      {
        "sale_item_id": 89,
        "product_name": "Laptop Lenovo IdeaPad 3 15ITL6",
        "serial_number": "PF3B1A2C",
        "quantity": 1,
        "hardware": {
          "days": 730,
          "expires_at": "2028-10-07",
          "status": "active",
          "remaining_days": 729
        },
        "software": {
          "days": 180,
          "expires_at": "2027-04-06",
          "status": "active",
          "remaining_days": 179
        }
      }
    ]
  }
}
```
