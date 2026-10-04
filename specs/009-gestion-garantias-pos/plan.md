# Implementation Plan: 009-gestion-garantias-pos

**Branch**: `009-gestion-garantias-pos` | **Date**: 2026-10-03 | **Spec**: [009-gestion-garantias-pos/spec.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/009-gestion-garantias-pos/spec.md)

**Input**: Feature specification from `/specs/009-gestion-garantias-pos/spec.md`

## Summary

Implementar la gestión centralizada de garantías técnicas en el catálogo de productos (creación y edición con presets intuitivos y días personalizados), optimizar la ergonomía del carrito del Punto de Venta (POS) sustituyendo selectores invasivos por insignias compactas (`VChip`) y un micro-modal emergente con foco para pistoleo de series (S/N), y añadir la configuración de términos institucionales de garantía en los datos de la empresa para su emisión en tickets térmicos y comprobantes de venta.

---

## Technical Context

**Language/Version**: PHP 8.2+ (Backend Laravel 11.x) / JavaScript ES2023 (Vue.js 3.4+)  
**Primary Dependencies**: Vuetify 3.x, Remix Icons (`ri-*`), Pinia, Eloquent ORM, DB Transactions  
**Storage**: MySQL 8.x / MariaDB (`products.warranty_days`, `sale_items.warranty_days`, `sale_items.serial_number`, `company_settings.warranty_terms`)  
**Testing**: PHPUnit / Pest (`php artisan test`), Vitest / Vite build (`pnpm --prefix admin-starter-kit run build`)  
**Target Platform**: Servidor Web Apache (XAMPP / Herd / Valet) y Navegador Web de escritorio / pantalla táctil de mostrador  
**Project Type**: Web Application (Backend API REST Hexagonal en raíz + Frontend SPA en `admin-starter-kit/`)  
**Performance Goals**: Precarga instantánea (<10ms) de la garantía al añadir al carrito; escaneo de número de serie en < 3 segundos  
**Constraints**: Prohibido usar `php artisan serve`; moneda obligatoria en Bolivianos (`Bs.`); apego estricto a componentes existentes en `admin-full-version/`  
**Scale/Scope**: Catálogo con cientos de productos, ventas diarias en mostrador, emisión de tickets con trazabilidad técnica  

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **I. Simplicidad ante todo (KISS & YAGNI)**: Se aprovechan las columnas y modelos existentes (`warranty_days` en `products` y `sale_items`). Cero sobre-ingeniería ni tablas complejas de pólizas.
- [x] **II. Idioma, Mercado y Moneda (Bolivia / BOB)**: Todos los textos y comprobantes en español boliviano; importes en `Bs.`. Presets de garantía adaptados a la realidad comercial de tiendas de informática en Bolivia.
- [x] **III. Cero Alcance Fantasma (Scope Creep)**: Se restringe exclusivamente al catálogo, carrito del POS y términos en comprobante, sin agregar módulos fuera de spec.
- [x] **IV. Verificable por una Persona No Técnica**: El Dueño o un cajero puede crear un producto con garantía, venderlo en el POS y ver la serie y fecha de vencimiento impresa en menos de dos minutos.
- [x] **V. Verdad Única de Datos en Tiempo Real**: La garantía del producto se almacena en la tabla `products` y el POS la obtiene en tiempo real directamente de la base de datos central.
- [x] **VI. Privacidad y Seguridad de los Datos**: Los costos de compra e información reservada permanecen inaccesibles para el vendedor en mostrador.

---

## Project Structure

### Documentation (this feature)

```text
specs/009-gestion-garantias-pos/
├── spec.md              # Especificación funcional y requerimientos clarificados
├── plan.md              # Este plan de implementación
├── research.md          # Investigación y decisiones arquitectónicas
├── data-model.md        # Esquema de entidades, columnas y validaciones
├── quickstart.md        # Guía de validación funcional y escenarios
├── contracts/
│   └── api-contracts.md # Contratos de endpoints REST
└── tasks.md             # Tareas granulares de implementación (/speckit-tasks)
```

### Source Code (repository layout)

```text
app/
├── Application/Sale/
│   └── ProcessSaleUseCase.php                  # Verificación y cálculo inmutable de garantía y serie
├── Domain/Product/
│   └── Product.php                             # Entidad con warrantyDays y toArray()
├── Infrastructure/Http/Requests/
│   ├── ProductRequest.php                      # Validación de warrantyDays / warranty_days
│   └── UpdateCompanySettingRequest.php         # Validación de warranty_terms
└── Infrastructure/Persistence/Eloquent/
    ├── EloquentProductRepository.php           # Mapeo y persistencia de warranty_days en BD
    └── CompanySettingModel.php                 # Fillable de warranty_terms

database/
└── migrations/
    └── 2026_10_03_000016_add_warranty_terms_to_company_settings_table.php

admin-starter-kit/
├── src/
│   ├── views/products/
│   │   └── AddProductDrawer.vue                # Selector con presets y personalizado para garantía
│   ├── pages/
│   │   ├── pos/
│   │   │   └── index.vue                       # Fila ergonómica de carrito + micro-modal multi-serie
│   │   └── settings/
│   │       └── company.vue                     # Edición de términos de garantía institucional
│   └── views/pos/
│       └── ReceiptDialog.vue                   # Desglose de serie, fecha expiración y cláusulas
```

---

## Implementation Breakdown (Phased Roadmap)

### Phase 1: Backend Persistence & Domain Support
- Crear migración para agregar `warranty_terms` nullable en `company_settings`.
- En `ProductRequest.php`, admitir y validar `warrantyDays` / `warranty_days` (`nullable|integer|min:0`).
- En `EloquentProductRepository.php` (`attributes()`), persistir `warranty_days` al crear o actualizar producto.
- En `UpdateCompanySettingRequest.php` y `CompanySettingModel.php`, permitir guardar `warranty_terms`.

### Phase 2: Frontend Product Catalog Warranty Management
- En `AddProductDrawer.vue`, integrar `warrantyDays` en `form` y precargar `props.product.warranty_days`.
- Diseñar el selector con presets populares (0d, 15d, 30d, 90d, 180d, 365d, 730d) y campo personalizado.
- Mostrar la garantía en la tabla del catálogo (`admin-starter-kit/src/pages/products/index.vue`).

### Phase 3: Ergonomía de Carrito en POS y Captura de Series
- En `admin-starter-kit/src/pages/pos/index.vue`, rediseñar la fila del carrito:
  - Eliminar los selectores toscos por fila.
  - Implementar chip interactivo `🛡️ [X días]` con estado visual claro.
  - Crear micro-diálogo modal (`ItemWarrantyDialog`) con foco automático para pistolear o escribir número(s) de serie y ajuste de plazo.
- En `ReceiptDialog.vue`, formatear la fecha exacta de expiración y listar los términos de garantía institucional.

### Phase 4: Institutional Warranty Settings UI
- En `admin-starter-kit/src/pages/settings/company.vue`, agregar el campo multi-línea "Términos y Condiciones de Garantía Técnica".
- Conectar con la API de ajustes de empresa.

### Phase 5: Verification & Quality Assurance
- Ejecutar suite de pruebas backend: `php artisan test`.
- Compilar frontend sin advertencias: `pnpm --prefix admin-starter-kit run build`.
- Validar escenarios visuales de `quickstart.md`.
