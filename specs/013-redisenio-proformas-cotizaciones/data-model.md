# Data Model & Mappings: Proformas y Cotizaciones Dinámicas

## 1. Entidades Involucradas

### `CompanySetting` (Configuración de la Empresa)
- `trade_name`: Nombre comercial de la tienda (ej: `SERVIMÁTICA COMPUTACIÓN`).
- `slogan`: Lema comercial (ej: `Tecnología y Soluciones Digitales`).
- `city`: Ciudad sede (ej: `Trinidad`).
- `address`: Dirección de la tienda (ej: `Av. Gil Coimbra #120`).
- `mobile`: Número celular oficial para WhatsApp / llamadas.
- `phone`: Teléfono fijo de atención.
- `warranty_terms`: Términos de garantía institucionales.

### `PaymentMethod` (Medios de Pago Activos)
- `name`: Nombre descriptivo (ej: `Pago QR Simple`, `Transferencia Banco Nacional de Bolivia`).
- `type`: `qr` | `transferencia` | `efectivo`.
- `bank_name`: Nombre del banco (ej: `BNB`, `Banco Unión`, `BCP`).
- `account_number`: Número de cuenta corriente o caja de ahorro.
- `account_holder`: Titular de la cuenta.
- `is_active`: Booleano (solo se incluyen los activos).
- `applies_to`: `sales` | `all`.

### `Product` (Catálogo de Productos)
- `name`: Nombre comercial del equipo.
- `sku`: Código interno único.
- `warranty_days`: Entero de días de garantía técnica (ej: 365, 180, 90).

### `Quote` & `QuoteItem` (Cotización)
- `quote_number`: Correlativo oficial (ej: `PRF-000003`).
- `client_name`: Nombre del cliente destinatario.
- `client_phone`: Celular de contacto.
- `subtotal`: Importe base en Bs.
- `discount_amount`: Descuento aplicado en Bs.
- `total_amount`: Total a pagar en Bs.
- `valid_until`: Fecha límite de vigencia de la oferta.
- `seller_id`: Asesor comercial que atendió la proforma.

---

## 2. Lógica de Conversión de Garantía

```php
function formatWarrantyLabel(int $days): string
{
    if ($days <= 0) return 'Garantía según fabricante / No especificada';
    if ($days % 365 === 0) {
        $years = $days / 365;
        return $years === 1 ? '12 meses (1 año)' : ($years * 12) . " meses ({$years} años)";
    }
    if ($days % 30 === 0) {
        $months = $days / 30;
        return "{$months} meses de garantía oficial";
    }
    return "{$days} días de garantía oficial";
}
```
