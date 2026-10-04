# Data Model: Configuración de Mi Empresa y Sucursal

**Feature**: `007-datos-empresa-sucursal` | **Fecha**: 2026-10-03

---

## 1. Tabla de Base de Datos: `company_settings`

Representa la configuración única institucional de la empresa y la sucursal activa.

| Columna | Tipo | Nulo | Por Defecto | Descripción |
|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | PK Auto-inc | Identificador único (registro singleton = 1) |
| `trade_name` | `VARCHAR(120)` | No | `'Servimática Computación'` | Nombre comercial visible al público |
| `legal_name` | `VARCHAR(150)` | Sí | `NULL` | Razón social o titular de registro |
| `tax_id` | `VARCHAR(30)` | Sí | `NULL` | NIT o identificación tributaria |
| `slogan` | `VARCHAR(200)` | Sí | `NULL` | Slogan comercial o actividad técnica |
| `branch_name` | `VARCHAR(100)` | No | `'Sucursal Central'` | Nombre de la sucursal activa |
| `city` | `VARCHAR(100)` | No | `'Bolivia'` | Ciudad y departamento (ej. Riberalta, Beni) |
| `address` | `VARCHAR(250)` | No | `'Av. Principal'` | Dirección física del establecimiento |
| `mobile` | `VARCHAR(30)` | No | `'77000000'` | Celular / WhatsApp de atención |
| `phone` | `VARCHAR(30)` | Sí | `NULL` | Teléfono fijo secundario |
| `email` | `VARCHAR(100)` | Sí | `NULL` | Correo electrónico de contacto |
| `logo_path` | `VARCHAR(255)` | Sí | `NULL` | Ruta relativa del archivo de logo subido |
| `default_quote_terms` | `TEXT` | Sí | `NULL` | Términos predeterminados para proformas |
| `receipt_footer_message` | `TEXT` | Sí | `NULL` | Mensaje de pie de página para tickets de venta |
| `created_at` | `TIMESTAMP` | Sí | `CURRENT_TIMESTAMP` | Fecha de creación |
| `updated_at` | `TIMESTAMP` | Sí | `CURRENT_TIMESTAMP` | Fecha de última modificación |

---

## 2. Entidad de Dominio: `CompanySetting`

Ubicación: `app/Domain/Company/CompanySetting.php`

### Propiedades
```php
namespace App\Domain\Company;

final class CompanySetting
{
    public function __construct(
        public readonly int $id,
        public readonly string $tradeName,
        public readonly ?string $legalName,
        public readonly ?string $taxId,
        public readonly ?string $slogan,
        public readonly string $branchName,
        public readonly string $city,
        public readonly string $address,
        public readonly string $mobile,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $logoPath,
        public readonly ?string $defaultQuoteTerms,
        public readonly ?string $receiptFooterMessage,
        public readonly ?string $logoUrl = null,
    ) {}
}
```

---

## 3. Contrato de Repositorio: `CompanySettingRepositoryInterface`

Ubicación: `app/Domain/Company/CompanySettingRepositoryInterface.php`

```php
namespace App\Domain\Company;

interface CompanySettingRepositoryInterface
{
    /**
     * Obtiene la configuración activa de la empresa.
     * Si no existe en BD, debe retornar una instancia por defecto con valores de fallback.
     */
    public function get(): CompanySetting;

    /**
     * Actualiza o crea la configuración institucional activa.
     */
    public function save(array $data): CompanySetting;
}
```

---

## 4. Seeder de Inicialización: `CompanySettingSeeder`

Ubicación: `database/seeders/CompanySettingSeeder.php`

Garantiza que la base de datos comience siempre con:
- `trade_name`: `'Servimática PC'`
- `slogan`: `'Venta de Equipos de Computación, Insumos y Servicio Técnico Especializado'`
- `branch_name`: `'Sucursal Central'`
- `city`: `'Beni — Bolivia'`
- `address`: `'Calle Principal'`
- `mobile`: `'77000000'`
- `default_quote_terms`: `'Precios en Bolivianos (Bs.). Cotización sujeta a disponibilidad de stock. Todos los equipos cuentan con garantía técnica oficial según políticas de Servimática.'`
- `receipt_footer_message`: `'¡Gracias por su compra! Conserve este comprobante para reclamos y validación de su garantía.'`
