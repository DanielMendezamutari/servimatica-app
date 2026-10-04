# Data Model: Capítulo 6 — Formas de Pago y Cuentas Bancarias

**Feature**: `006-gestion-formas-pago` | **Fecha**: 2026-10-03

---

## 1. Esquema Relacional de Base de Datos

### Tabla: `payment_methods`

| Columna | Tipo | Nulable | Predeterminado | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | No | N/A | Clave primaria |
| `name` | VARCHAR(120) | No | N/A | Nombre identificador (ej: *QR Simple Banco Unión*) |
| `type` | ENUM('cash','qr','bank_transfer','card','other') | No | `'cash'` | Tipo operativo de medio de pago |
| `bank_name` | VARCHAR(100) | Sí | NULL | Entidad bancaria o plataforma (ej: *Banco Unión*, *BCP*, *Tigo Money*) |
| `account_number` | VARCHAR(80) | Sí | NULL | Número de cuenta bancaria o número de teléfono |
| `account_holder` | VARCHAR(150) | Sí | NULL | Titular o razón social registrada en la cuenta |
| `qr_image_path` | VARCHAR(255) | Sí | NULL | Ruta relativa al archivo de imagen del código QR en storage |
| `requires_reference` | BOOLEAN | No | `false` | Indica si el cajero debe ingresar el comprobante bancario obligatoriamente |
| `applies_to` | ENUM('sales','purchases','both') | No | `'both'` | Ámbito operativo del método de pago |
| `sort_order` | INT UNSIGNED | No | `0` | Orden de visualización en la interfaz |
| `is_active` | BOOLEAN | No | `true` | Estado operativo (permite dar de baja sin borrar histórico) |
| `created_at` | TIMESTAMP | Sí | NULL | Auditoría de creación |
| `updated_at` | TIMESTAMP | Sí | NULL | Auditoría de actualización |

---

### Modificaciones en Tablas Existentes

#### Tabla: `sales`
- Agregar columna `payment_method_id` (`BIGINT UNSIGNED NULLABLE`, foreign key apuntando a `payment_methods.id` con `onDelete('set null')`).
- Agregar columna `reference_number` (`VARCHAR(100) NULLABLE`).

#### Tabla: `purchases`
- Agregar columna `payment_method_id` (`BIGINT UNSIGNED NULLABLE`, foreign key apuntando a `payment_methods.id` con `onDelete('set null')`).
- Agregar columna `reference_number` (`VARCHAR(100) NULLABLE`).

#### Tabla: `quotes`
- Agregar columna `payment_method_id` (`BIGINT UNSIGNED NULLABLE`, foreign key apuntando a `payment_methods.id` con `onDelete('set null')`).

---

## 2. Entidades de Dominio y Objetos de Valor (Hexagonal/DDD)

### `App\Domain\PaymentMethod\PaymentMethod`
- **Atributos**:
  - `id`: `?int`
  - `name`: `string`
  - `type`: `PaymentMethodType` (Value Object enum: `cash`, `qr`, `bank_transfer`, `card`, `other`)
  - `bankName`: `?string`
  - `accountNumber`: `?string`
  - `accountHolder`: `?string`
  - `qrImagePath`: `?string`
  - `requiresReference`: `bool`
  - `appliesTo`: `PaymentMethodScope` (Value Object enum: `sales`, `purchases`, `both`)
  - `sortOrder`: `int`
  - `isActive`: `bool`
- **Métodos de Negocio**:
  - `isCash(): bool`: Comprueba si impacta en caja física.
  - `isDigital(): bool`: Comprueba si es QR o transferencia.
  - `toggleStatus(): void`: Habilita o deshabilita la disponibilidad.
  - `toArray(): array`: Serialización normalizada camelCase y snake_case.

---

### `App\Domain\PaymentMethod\PaymentMethodRepositoryInterface`
- `findAll(): array`
- `findActiveByScope(string $scope): array`
- `findById(int $id): ?PaymentMethod`
- `save(PaymentMethod $method): PaymentMethod`
- `delete(int $id): bool`
