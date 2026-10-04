# Data Model: Gestión Integral de Clientes (CRM)

## 1. Extensiones al Modelo `clients`

### Migración: `add_crm_fields_to_clients_table`
| Columna | Tipo | Nulable | Por Defecto | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `client_type` | `VARCHAR(30)` | No | `'final'` | Tipo de cliente: `'final'`, `'mayorista'`, `'empresa'`. Indexado. |
| `city` | `VARCHAR(100)` | Sí | `'Trinidad'` | Ciudad de residencia o facturación. |
| `notes` | `TEXT` | Sí | `NULL` | Observaciones comerciales, acuerdos de entrega o notas internas. |

### Relaciones del Modelo
- `Client -> hasMany(Sale)`: Ventas asociadas al cliente.
- `Client -> hasMany(Quote)`: Cotizaciones/proformas emitidas al cliente.
- `Client -> hasManyThrough(SaleItem, Sale)`: Artículos comprados con garantía y número de serie.

---

## 2. Entidad de Dominio `Client`
Ubicación: `app/Domain/Client/Client.php`
Propiedades:
- `id`: `?int`
- `name`: `string`
- `nitCi`: `?string`
- `phone`: `?string`
- `email`: `?string`
- `address`: `?string`
- `clientType`: `string` (`'final'`, `'mayorista'`, `'empresa'`)
- `city`: `?string`
- `notes`: `?string`
- `isActive`: `bool`
- `createdAt`: `?string`
- `updatedAt`: `?string`

---

## 3. Estadísticas y Agregados (Ficha 360°)
Calculados en la consulta o caso de uso `GetClientDetailUseCase`:
- `total_spent_bs`: Suma de `sales.total` con `status != 'cancelled'`.
- `sales_count`: Cantidad total de ventas completadas.
- `last_purchase_at`: Fecha y hora de la última compra.
- `quotes_count`: Cantidad de cotizaciones emitidas.
- `active_warranties_count`: Cantidad de ítems cuya fecha de vencimiento (`sale.created_at + warranty_days`) es mayor o igual a `now()`.
