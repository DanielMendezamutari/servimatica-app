# Contratos de API REST: Formas de Pago

**Feature**: `006-gestion-formas-pago` | **Fecha**: 2026-10-03

---

## 1. `GET /api/payment-methods`
- **Acceso**: Exclusivo Dueño (`role: owner`).
- **Respuesta (200 OK)**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Efectivo en Mostrador",
      "type": "cash",
      "bank_name": null,
      "account_number": null,
      "account_holder": null,
      "qr_image_url": null,
      "requires_reference": false,
      "applies_to": "both",
      "sort_order": 0,
      "is_active": true
    },
    {
      "id": 2,
      "name": "QR Simple Banco Unión",
      "type": "qr",
      "bank_name": "Banco Unión",
      "account_number": "10000012345678",
      "account_holder": "Servimática Bolivia S.R.L.",
      "qr_image_url": "http://servimatica-app.test/storage/payment-methods/qr-union.png",
      "requires_reference": true,
      "applies_to": "sales",
      "sort_order": 1,
      "is_active": true
    }
  ]
}
```

---

## 2. `POST /api/payment-methods`
- **Acceso**: Exclusivo Dueño (`role: owner`).
- **Content-Type**: `multipart/form-data`
- **Parámetros**:
  - `name`: string, requerido, max: 120.
  - `type`: string, requerido in (`cash`, `qr`, `bank_transfer`, `card`, `other`).
  - `bank_name`: string, opcional, max: 100.
  - `account_number`: string, opcional, max: 80.
  - `account_holder`: string, opcional, max: 150.
  - `qr_image`: archivo de imagen opcional (png, jpg, jpeg, webp, max: 3MB).
  - `requires_reference`: boolean opcional (default false).
  - `applies_to`: string in (`sales`, `purchases`, `both`), default `both`.
- **Respuesta (201 Created)**: Objeto `PaymentMethod` recién creado.

---

## 3. `PUT /api/payment-methods/{id}`
- **Acceso**: Exclusivo Dueño.
- **Content-Type**: `application/json` o `multipart/form-data`.
- **Respuesta (200 OK)**: Objeto `PaymentMethod` actualizado.

---

## 4. `PATCH /api/payment-methods/{id}/toggle-status`
- **Acceso**: Exclusivo Dueño.
- **Respuesta (200 OK)**:
```json
{
  "message": "Estado de la forma de pago actualizado.",
  "data": {
    "id": 2,
    "is_active": false
  }
}
```

---

## 5. `GET /api/payment-methods/options`
- **Acceso**: Autenticado (Vendedor y Dueño).
- **Query Params**:
  - `context`: `sales` | `purchases` (opcional).
- **Respuesta (200 OK)**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Efectivo",
      "type": "cash",
      "requires_reference": false,
      "qr_image_url": null,
      "bank_name": null,
      "account_number": null,
      "account_holder": null
    },
    {
      "id": 2,
      "name": "QR Simple Banco Unión",
      "type": "qr",
      "requires_reference": true,
      "qr_image_url": "http://servimatica-app.test/storage/payment-methods/qr-union.png",
      "bank_name": "Banco Unión",
      "account_number": "10000012345678",
      "account_holder": "Servimática Bolivia S.R.L."
    }
  ]
}
```
