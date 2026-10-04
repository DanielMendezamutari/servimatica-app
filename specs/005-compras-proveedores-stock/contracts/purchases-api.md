# Contrato de API REST: Compras y Proveedores

**Base URL**: `/api`  
**Autenticación**: `Authorization: Bearer <jwt-token>` (Middleware `jwt.auth` + `owner`)  
**Moneda**: Bolivianos (`BOB` / `Bs.`)  

---

## 1. Módulo Proveedores (`/api/suppliers`)

### `GET /api/suppliers`
Lista de proveedores paginada con búsqueda.
- **Query Params**:
  - `page` (int, default 1)
  - `per_page` (int, default 15)
  - `search` (string, opcional: busca por nombre, NIT, contacto o teléfono)
  - `status` (string, `active` | `inactive` | `all`, default `all`)
- **Response 200 OK**:
  ```json
  {
    "data": [
      {
        "id": 1,
        "name": "Deltron Bolivia SRL",
        "nit": "1029384019",
        "contact_name": "Lic. Mario Terán",
        "phone": "77123456",
        "email": "ventas@deltron.bo",
        "city": "Santa Cruz",
        "address": "Av. Banzer 4to Anillo",
        "is_active": true,
        "created_at": "2026-10-03 14:00:00"
      }
    ],
    "total": 1,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
  ```

### `GET /api/suppliers/options`
Listado liviano de proveedores activos para selectores en formularios de compra.
- **Response 200 OK**:
  ```json
  {
    "data": [
      { "id": 1, "name": "Deltron Bolivia SRL", "nit": "1029384019" },
      { "id": 2, "name": "Intcomex Bolivia", "nit": "3049582011" }
    ]
  }
  ```

### `POST /api/suppliers`
Crear un nuevo proveedor.
- **Request Body**:
  ```json
  {
    "name": "Deltron Bolivia SRL",
    "nit": "1029384019",
    "contact_name": "Lic. Mario Terán",
    "phone": "77123456",
    "email": "ventas@deltron.bo",
    "city": "Santa Cruz",
    "address": "Av. Banzer 4to Anillo"
  }
  ```
- **Response 201 Created**:
  ```json
  {
    "message": "Proveedor registrado exitosamente.",
    "data": { "id": 1, "name": "Deltron Bolivia SRL", "is_active": true }
  }
  ```

### `PUT /api/suppliers/{id}`
Actualizar datos de un proveedor.
- **Response 200 OK**:
  ```json
  {
    "message": "Proveedor actualizado correctamente.",
    "data": { ... }
  }
  ```

### `PATCH /api/suppliers/{id}/toggle-status`
Alternar estado activo/inactivo del proveedor.
- **Response 200 OK**:
  ```json
  {
    "message": "Estado de proveedor actualizado exitosamente.",
    "data": { "id": 1, "is_active": false }
  }
  ```

---

## 2. Módulo Órdenes de Compra y Recepción (`/api/purchases`)

### `GET /api/purchases`
Historial de compras recepcionadas.
- **Query Params**:
  - `page`, `per_page`
  - `supplier_id` (int, opcional)
  - `search` (string, opcional: N° correlativo o N° de factura)
  - `status` (`received` | `cancelled` | `all`, default `all`)
  - `start_date`, `end_date` (`YYYY-MM-DD`, opcional)
- **Response 200 OK**:
  ```json
  {
    "data": [
      {
        "id": 1,
        "purchase_number": "COM-000001",
        "invoice_number": "FC-98432",
        "supplier_id": 1,
        "supplier_name": "Deltron Bolivia SRL",
        "purchase_date": "2026-10-03",
        "payment_condition": "contado",
        "payment_method": "transferencia",
        "payment_status": "pagado",
        "due_date": null,
        "subtotal": "5500.00",
        "total_amount": "5500.00",
        "status": "received",
        "items_count": 2,
        "user_name": "Administrador",
        "created_at": "2026-10-03 14:30:00"
      }
    ],
    "total": 1
  }
  ```

### `POST /api/purchases`
Recepcionar compra con incremento atómico de stock y actualización de costos.
- **Request Body**:
  ```json
  {
    "supplier_id": 1,
    "invoice_number": "FC-98432",
    "purchase_date": "2026-10-03",
    "payment_condition": "contado",
    "payment_method": "transferencia",
    "due_date": null,
    "notes": "Lote de discos y memorias para reposición",
    "items": [
      {
        "product_id": 10,
        "quantity": 5,
        "unit_cost": 250.00,
        "new_sale_price": 320.00
      },
      {
        "product_id": 12,
        "quantity": 10,
        "unit_cost": 425.00,
        "new_sale_price": null
      }
    ]
  }
  ```
- **Response 201 Created**:
  ```json
  {
    "message": "Compra recepcionada e inventario actualizado exitosamente.",
    "data": {
      "id": 1,
      "purchase_number": "COM-000001",
      "invoice_number": "FC-98432",
      "total_amount": "5500.00",
      "status": "received"
    }
  }
  ```

### `GET /api/purchases/{id}`
Ver detalle completo de una compra y sus ítems con comparación de costos anteriores.
- **Response 200 OK**:
  ```json
  {
    "data": {
      "id": 1,
      "purchase_number": "COM-000001",
      "invoice_number": "FC-98432",
      "supplier_name": "Deltron Bolivia SRL",
      "supplier_nit": "1029384019",
      "purchase_date": "2026-10-03",
      "total_amount": "5500.00",
      "items": [
        {
          "id": 1,
          "product_id": 10,
          "product_name": "Disco SSD Kingston 480GB",
          "product_sku": "SSD-KING-480",
          "quantity": 5,
          "unit_cost": "250.00",
          "subtotal": "1250.00",
          "previous_cost": "230.00",
          "previous_sale_price": "300.00",
          "new_sale_price": "320.00"
        }
      ]
    }
  }
  ```

### `POST /api/purchases/{id}/cancel`
Anular compra con restitución segura de stock (validando que haya existencias físicas para devolver).
- **Request Body**:
  ```json
  {
    "reason": "Factura anulada por proveedor por error en los números de serie"
  }
  ```
- **Response 200 OK**:
  ```json
  {
    "message": "Compra anulada y stock descontado del inventario exitosamente.",
    "data": { "id": 1, "status": "cancelled" }
  }
  ```
- **Response 422 Unprocessable Entity** (si no hay suficiente stock):
  ```json
  {
    "message": "No se puede anular la compra. El producto 'Disco SSD Kingston 480GB' solo tiene 2 unidades disponibles en inventario (se requieren 5 para la devolución)."
  }
  ```

### `GET /api/purchases/{id}/receipt`
Vista formal de la Nota de Recepción de Mercadería para archivo contable e impresión en tamaño Carta.
