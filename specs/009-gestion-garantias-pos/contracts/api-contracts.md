# API Contracts: 009-gestion-garantias-pos

## 1. Endpoints de Catálogo de Productos

### POST `/api/products` (Creación de Producto)
- **Autorización:** Bearer Token (Rol: Dueño / Administrador)
- **Headers:** `Accept: application/json`, `Content-Type: application/json`

#### Request Body
```json
{
  "name": "Placa Madre ASUS TUF B550-PLUS",
  "categoryId": 2,
  "subfamilyId": 5,
  "brandId": 1,
  "productModelId": 3,
  "condition": "nuevo",
  "costPrice": 850.00,
  "salePrice": 1150.00,
  "stock": 10,
  "minStock": 2,
  "warrantyDays": 365
}
```

#### Response `201 Created`
```json
{
  "message": "Producto creado con éxito.",
  "data": {
    "id": 45,
    "name": "Placa Madre ASUS TUF B550-PLUS",
    "sku": "PLA-0045",
    "categoryId": 2,
    "salePrice": 1150.00,
    "stock": 10,
    "warrantyDays": 365,
    "warranty_days": 365,
    "condition": "nuevo"
  }
}
```

---

### PUT `/api/products/{id}` (Actualización de Producto)
- **Request Body:** Idéntica estructura; permite modificar `warrantyDays` o `warranty_days`.
- **Response `200 OK`:** Retorna el producto con los nuevos días de garantía.

---

## 2. Endpoints de Punto de Venta (POS)

### POST `/api/sales` (Registro y Cobro de Venta)
- **Autorización:** Bearer Token (Cajero / Vendedor / Dueño)

#### Request Body
```json
{
  "cash_shift_id": 1,
  "payment_method_id": 1,
  "client_id": 12,
  "client_name": "Juan Perez",
  "items": [
    {
      "product_id": 45,
      "quantity": 2,
      "unit_price": 1150.00,
      "warranty_days": 365,
      "serial_number": "SN-TUF-88491, SN-TUF-88492"
    },
    {
      "product_id": 10,
      "quantity": 1,
      "unit_price": 25.00,
      "warranty_days": 0,
      "serial_number": null
    }
  ]
}
```

#### Response `200 OK`
```json
{
  "message": "Venta registrada con éxito.",
  "data": {
    "id": 89,
    "invoice_number": "VTA-000089",
    "total_amount": 2325.00,
    "status": "completed",
    "items": [
      {
        "id": 140,
        "product_id": 45,
        "product_name": "Placa Madre ASUS TUF B550-PLUS",
        "quantity": 2,
        "unit_price": 1150.00,
        "subtotal": 2300.00,
        "warranty_days": 365,
        "warranty_expires_at": "2027-10-03 22:45:00",
        "serial_number": "SN-TUF-88491, SN-TUF-88492"
      }
    ]
  }
}
```

---

## 3. Endpoints de Configuración Institucional

### GET `/api/company/settings`
- **Response `200 OK`**
```json
{
  "data": {
    "trade_name": "Servimática",
    "warranty_terms": "La garantía técnica cubre defectos de fabricación por el plazo indicado en este documento. Quedan excluidos daños por caídas, variaciones de voltaje, humedad o sellos violados.",
    "receipt_footer_message": "¡Gracias por su compra! Servicio Técnico Garantizado."
  }
}
```

### PUT `/api/company/settings`
- **Request Body**
```json
{
  "trade_name": "Servimática",
  "warranty_terms": "La garantía técnica cubre defectos de fabricación por el plazo indicado...",
  "receipt_footer_message": "¡Gracias por su compra!"
}
```
- **Response `200 OK`:** Configuración actualizada exitosamente.
