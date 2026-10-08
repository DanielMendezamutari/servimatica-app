# Implementation Plan: 016-garantias-autonomas-hardware-software

**Branch**: `016-garantias-autonomas-hardware-software` | **Date**: 2026-10-08 | **Spec**: [specs/016-garantias-autonomas-hardware-software/spec.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/016-garantias-autonomas-hardware-software/spec.md)

**Input**: Feature specification from `specs/016-garantias-autonomas-hardware-software/spec.md`

---

## Summary

Implementar la gestión dual y autónoma de **Garantía de Hardware** y **Garantía de Software** en todo el ciclo operativo de Servimática App:
1. Catálogo de productos (creación y edición con selectores independientes y presets ergonómicos).
2. Punto de Venta (herencia automática al carrito, modal para ajuste independiente de Hardware/Software y pistoleo de S/N).
3. Emisión de comprobantes y tickets térmicos (cálculo y desglose exacto de ambas fechas de vencimiento).
4. Verificación técnica postventa (dictamen dual e independiente de vigencia para servicio técnico y taller).

---

## Technical Context

**Language/Version**: PHP 8.2+ (Backend Laravel 11.x) / JavaScript ES2023 (Vue.js 3.4+)  
**Primary Dependencies**: Vuetify 3.x, Remix Icons (`ri-*`), Pinia, Eloquent ORM, DB Transactions  
**Storage**: MySQL 8.x / MariaDB (`products.warranty_hardware_days`, `products.warranty_software_days`, `sale_items.warranty_hardware_days`, `sale_items.warranty_hardware_expires_at`, `sale_items.warranty_software_days`, `sale_items.warranty_software_expires_at`, `sale_items.serial_number`)  
**Testing**: PHPUnit / Pest (`php artisan test`), Vite build (`pnpm --prefix admin-starter-kit run build`)  
**Target Platform**: Servidor Web Apache (XAMPP / Herd / BanaHosting) y Navegador Web de escritorio / pantalla táctil de mostrador  
**Project Type**: Web Application (Backend API REST Hexagonal en raíz + Frontend SPA en `admin-starter-kit/`)  
**Performance Goals**: Precarga instantánea en el carrito; cálculo de vencimientos en < 5ms  
**Constraints**: Prohibido usar `php artisan serve`; apego a Vuetify y componentes de `admin-full-version/`; cero librerías externas innecesarias  
**Scale/Scope**: Operación diaria en tienda física (laptops, accesorios, servicio técnico)  

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **I. Simplicidad ante todo (KISS & YAGNI)**: Se incorporan columnas precisas sin crear modelos polimórficos ni sobre-ingeniería de contratos legales innecesarios.
- [x] **II. Idioma, Mercado y Moneda (Bolivia / BOB)**: Todos los términos en español adaptados a la realidad comercial de Santa Cruz de la Sierra / Comercial Chiriguano.
- [x] **III. Cero Alcance Fantasma (Scope Creep)**: Se ciñe estrictamente a las dos garantías autónomas en catálogo, carrito, ticket y verificación.
- [x] **IV. Verificable por una Persona No Técnica**: El Dueño puede vender una máquina y ver en el ticket impreso las dos garantías en menos de 2 minutos.
- [x] **V. Verdad Única de Datos en Tiempo Real**: Base de datos relacional única para productos, ventas y consultas de postventa.
- [x] **VI. Privacidad y Seguridad de los Datos**: Datos técnicos visibles para vendedor; márgenes y costos resguardados para el Dueño.

---

## Project Structure

### Documentation (this feature)

```text
specs/016-garantias-autonomas-hardware-software/
├── spec.md                     # Especificación funcional validada
├── plan.md                     # Este plan de implementación
├── research.md                 # Decisiones de arquitectura y UI
├── data-model.md               # Modelo relacional y reglas de cálculo
├── quickstart.md               # Guía de verificación de extremo a extremo
├── checklists/
│   └── requirements.md         # Control de calidad de la especificación
└── contracts/
    └── warranty-contracts.md   # Contratos de API para catálogo, POS y verificación
```

### Source Code (repository layout)

```text
database/
└── migrations/
    └── [timestamp]_add_autonomous_hardware_software_warranties_to_tables.php

app/
├── Domain/ProductModel/
│   ├── ProductModel.php
├── Infrastructure/Persistence/Eloquent/
│   ├── ProductModel.php
│   └── SaleItemModel.php
├── Infrastructure/Http/Controllers/Api/
│   ├── ProductModelController.php
│   ├── SaleController.php
│   └── WarrantyController.php (o endpoint en SaleController)
└── Infrastructure/Http/Requests/
    ├── CreateProductRequest.php
    └── CreateSaleRequest.php

admin-starter-kit/
├── src/
│   ├── pages/
│   │   ├── pos/index.vue                      # Carrito POS y WarrantyModal dual
│   │   ├── products/index.vue                 # Tabla de catálogo y visualización
│   │   └── sales/index.vue                    # Botón y acción de verificación
│   └── views/
│       ├── products/
│       │   ├── AddProductDrawer.vue           # Formulario de alta con 2 garantías
│       │   └── EditProductDrawer.vue          # Formulario de edición con 2 garantías
│       ├── sales/
│       │   ├── WarrantyCheckDialog.vue        # Diálogo de verificación dual
│       │   └── ReceiptPrintDialog.vue         # Render del ticket térmico con 2 garantías
```

---

## Complexity Tracking

*No violations to justify: Cumple al 100% con los principios de la Constitución.*
