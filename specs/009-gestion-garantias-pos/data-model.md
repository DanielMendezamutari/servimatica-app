# Data Model: 009-gestion-garantias-pos

## 1. Entidades y Esquemas de Base de Datos

### Entidad: Product (`products`)
Representa el artículo o equipo registrado en el catálogo maestro.

| Campo | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | Clave primaria. |
| `name` | VARCHAR(200) | No | Nombre comercial del producto. |
| `sku` | VARCHAR(50) | No | Código único de identificación. |
| `warranty_days` | INT | No (default: 0) | Periodo de garantía técnica predeterminada en días naturales (0 = Sin garantía). |
| `stock` | INT | No | Existencias disponibles para venta. |
| `sale_price` | DECIMAL(10,2) | No | Precio de venta oficial en Bolivianos (Bs.). |

### Entidad: SaleItem (`sale_items`)
Representa una línea vendida dentro de una transacción en el POS.

| Campo | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | Clave primaria. |
| `sale_id` | BIGINT UNSIGNED | No | Clave foránea hacia `sales.id`. |
| `product_id` | BIGINT UNSIGNED | No | Clave foránea hacia `products.id`. |
| `quantity` | INT | No | Unidades vendidas. |
| `unit_price` | DECIMAL(10,2) | No | Precio unitario cobrado. |
| `subtotal` | DECIMAL(10,2) | No | Monto total de la línea. |
| `warranty_days` | INT | No (default: 0) | Días de garantía pactados de forma inmutable al momento del cobro. |
| `warranty_expires_at` | DATE/DATETIME | Sí | Fecha límite calculada (`created_at + warranty_days`). |
| `serial_number` | VARCHAR(255) | Sí | Serie(s) única(s) del o de los equipos entregados al cliente. |

### Entidad: CompanySetting (`company_settings`)
Parámetros institucionales y de emisión de documentos del negocio.

| Campo | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | No | Clave primaria (singleton id: 1). |
| `trade_name` | VARCHAR(150) | Sí | Nombre comercial (ej. Servimática). |
| `warranty_terms` | TEXT | Sí | Cláusulas y condiciones generales de garantía técnica de la tienda. |
| `receipt_footer_message`| VARCHAR(255) | Sí | Mensaje de pie de ticket/recibo. |

---

## 2. Reglas de Validación y Dominio

1. **Garantía Técnica de Producto (`warranty_days`):**
   - Debe ser un número entero mayor o igual a 0 (`integer|min:0`).
   - Si no se especifica, por defecto es 0 (`Sin garantía`).
2. **Número de Serie (`serial_number`):**
   - Opcional en el cobro. Admite texto alfanumérico con longitud máxima de 255 caracteres para permitir pistoleo de múltiples series separadas por comas.
3. **Cálculo de Expiración:**
   - Si `warranty_days > 0`: `warranty_expires_at = sale_date + warranty_days`.
   - Si `warranty_days == 0`: `warranty_expires_at = null`.
