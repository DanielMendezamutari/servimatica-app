# Data Model: 016-garantias-autonomas-hardware-software

**Feature**: 016-garantias-autonomas-hardware-software  
**Date**: 2026-10-08  
**Status**: Completed  

---

## 1. Esquema de Base de Datos (MySQL / MariaDB)

### Tabla `products` (Modificación)

| Columna | Tipo | Nulo | Por defecto | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `warranty_hardware_days` | `UNSIGNED INT` | NO | `0` | Días predeterminados de garantía de hardware (física). |
| `warranty_software_days` | `UNSIGNED INT` | NO | `0` | Días predeterminados de garantía de software (lógica). |
| `warranty_days` | `UNSIGNED INT` | NO | `0` | *(Legacy/Compatibilidad)* Refleja el valor de hardware. |

### Tabla `sale_items` (Modificación)

| Columna | Tipo | Nulo | Por defecto | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `warranty_hardware_days` | `UNSIGNED INT` | NO | `0` | Días pactados de garantía de hardware en la venta. |
| `warranty_hardware_expires_at` | `DATE` | SÍ | `NULL` | Fecha límite calculada para hardware (`sale_date + warranty_hardware_days`). |
| `warranty_software_days` | `UNSIGNED INT` | NO | `0` | Días pactados de garantía de software en la venta. |
| `warranty_software_expires_at` | `DATE` | SÍ | `NULL` | Fecha límite calculada para software (`sale_date + warranty_software_days`). |
| `serial_number` | `VARCHAR(100)` | SÍ | `NULL` | Código o números de serie del equipo físico. |
| `warranty_days` | `UNSIGNED INT` | NO | `0` | *(Legacy/Compatibilidad)* Refleja `warranty_hardware_days`. |
| `warranty_expires_at` | `DATE` | SÍ | `NULL` | *(Legacy/Compatibilidad)* Refleja `warranty_hardware_expires_at`. |

---

## 2. Reglas de Validación en Backend (Laravel FormRequests)

### `CreateProductRequest` y `UpdateProductRequest`
```php
'warranty_hardware_days' => 'nullable|integer|min:0',
'warranty_software_days' => 'nullable|integer|min:0',
'warranty_days'          => 'nullable|integer|min:0', // fallback
```

### `CreateSaleRequest` (Ítems en venta)
```php
'items.*.warranty_hardware_days' => 'nullable|integer|min:0',
'items.*.warranty_software_days' => 'nullable|integer|min:0',
'items.*.serial_number'          => 'nullable|string|max:100',
```

---

## 3. Lógica de Dominio y Cálculo de Fechas

Al persistir la venta en el caso de uso `CreateSaleUseCase` / `SaleService`:

```php
$saleDate = Carbon::parse($sale->created_at ?? now());

// Cálculo Hardware
$hwDays = (int) ($itemData['warranty_hardware_days'] ?? $itemData['warranty_days'] ?? 0);
$hwExpiresAt = $hwDays > 0 ? $saleDate->copy()->addDays($hwDays)->toDateString() : null;

// Cálculo Software
$swDays = (int) ($itemData['warranty_software_days'] ?? 0);
$swExpiresAt = $swDays > 0 ? $saleDate->copy()->addDays($swDays)->toDateString() : null;
```

---

## 4. Estado de Cobertura en Consulta (`WarrantyCheck`)

Para cada ítem consultado respecto a la fecha actual (`today()`):
- Si `days == 0`: `status = 'none'`.
- Si `today <= expires_at`: `status = 'active'`, `remaining_days = today->diffInDays(expires_at)`.
- Si `today > expires_at`: `status = 'expired'`, `overdue_days = expires_at->diffInDays(today)`.
