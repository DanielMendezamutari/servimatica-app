# Research & Decisiones Técnicas: Capítulo 6 — Gestión Centralizada de Formas de Pago

**Feature**: `006-gestion-formas-pago` | **Fecha**: 2026-10-03

---

## 1. Almacenamiento y Carga de Imágenes de Códigos QR

### Contexto y Alternativas
Para que los clientes de Servimática puedan pagar por QR en mostrador o mediante proformas enviadas por WhatsApp, el Dueño debe poder asociar una imagen de código QR (ej. QR Simple del Banco Unión, BCP o Yape).

- **Alternativa A (Base64 embebido en DB)**: Almacena la imagen directamente como string Base64 en una columna `TEXT`/`MEDIUMTEXT`.
  - *Pros*: Cero gestión de disco físico o enlaces simbólicos.
  - *Contras*: Incrementa el tamaño de la base de datos y satura los payloads JSON.
- **Alternativa B (Laravel Storage en `public/storage/payment-methods/`) [ELEGIDA]**:
  - Se utiliza el disco `public` estándar de Laravel (`Storage::disk('public')->putFile(...)`).
  - La URL se expone de forma directa vía `asset('storage/' . $path)`.
  - Es el estándar recomendado de Laravel, compatible con XAMPP/Laragon y producción.

---

## 2. Retrocompatibilidad en Ventas (`sales`), Compras (`purchases`) y Cotizaciones (`quotes`)

### Contexto y Decisión
Las tablas existentes `sales`, `purchases` y `quotes` cuentan con una columna `payment_method` (string) que contiene valores anteriores como `'efectivo'`, `'qr'`, `'transferencia'`.

- **Decisión**:
  1. Mantener las columnas históricas `payment_method` para evitar migraciones destructivas.
  2. Agregar `payment_method_id` (foreign key nullable con `nullOnDelete()`) y `reference_number` (string nullable de hasta 100 caracteres).
  3. Durante la creación de ventas y compras, se registrará tanto el `payment_method_id` (ID del registro en `payment_methods`) como el nombre en texto para trazabilidad histórica en caso de que la forma de pago cambie de nombre en el futuro.
  4. La migración sembrará (*seeder*) automáticamente los métodos base (`Efectivo en Mostrador`, `Pago QR Bancario`, `Transferencia Bancaria`) para que el sistema continúe funcionando de inmediato.

---

## 3. Lógica de Arqueo y Cierre de Caja Chica (Efectivo vs. Digital)

### Contexto y Decisión
El cierre de caja (`CashShiftController::close`) calculaba anteriormente:
`$expectedBalance = $shift->starting_cash + $cashSales;`

- **Decisión**:
  - El efectivo esperado en la gaveta física **solo debe sumar las ventas cuya forma de pago sea de tipo `cash`**.
  - Las ventas con métodos de tipo `qr`, `bank_transfer`, `card` u `other` no alteran el arqueo de billetes del cajero.
  - La API de cierre devolverá adicionalmente un desglose agrupado (`digital_totals_by_method`) para que el ticket de cierre y la pantalla muestren cuánto se recaudó en cada cuenta bancaria o QR sin confundirlo con el efectivo físico.

---

## 4. Estandarización de UX en Filtros de Rango de Fecha

### Contexto y Decisión
En lugar de componentes de texto nativos (`<input type="date">`), se estandariza el uso del componente oficial de la plantilla:
- **Componente**: `AppDateTimePicker` (basado en Flatpickr).
- **Formato**: `YYYY-MM-DD` (estándar ISO para consultas de backend).
- **Presets**: Grupo de botones rápidos (`Hoy`, `7 días`, `Este Mes`) para acelerar la operativa diaria en Ventas, Compras y Auditorías.
