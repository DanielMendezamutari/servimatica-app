# Implementation Plan: Capítulo 5 — Compras y Recepción de Mercadería de Proveedores

**Branch**: `main` | **Date**: 2026-10-03 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/005-compras-proveedores-stock/spec.md`

---

## Summary

Implementar el módulo integral de proveedores y compras mayoristas para Servimática:
1. Directorio de proveedores distribuidores de tecnología.
2. Recepción atómica de compras con incremento automático de `products.stock` e inserción en `stock_movements`.
3. Actualización de `cost_price` al último costo facturado y ajuste opcional del `sale_price` en Bolivianos (Bs.).
4. Historial, reimpresión de nota de recepción y anulación segura controlando saldos negativos.
5. Privacidad estricta de costos y márgenes exclusiva para el Dueño (`ability: manage, all`).

---

## Technical Context

- **Backend Language/Version**: PHP 8.2+ con Laravel 12.x en la raíz.
- **Arquitectura Backend**: Arquitectura Hexagonal y DDD (Dominio puro, Casos de Uso en Aplicación, Repositorios Eloquent y Controladores API en Infraestructura).
- **Frontend Framework**: Vue 3, Vite, Vuetify 3 y CASL en `admin-starter-kit/`.
- **Autenticación**: JWT con tokens Bearer y middleware `owner` para exclusividad del Dueño.
- **Base de Datos**: MySQL / MariaDB (con pruebas automatizadas en SQLite en memoria).
- **Moneda**: Bolivianos (`BOB` / `Bs.`) en todo el sistema.
- **Testing**: PHPUnit / Pest (`php artisan test`).

---

## Constitution Check

*GATE: Must pass before implementation.*

- [X] **Principle I (KISS & YAGNI)**: Solución pragmática con actualización directa al último costo de compra; cero sobre-ingeniería de costos complejos.
- [X] **Principle II (Bolivia / BOB)**: Todos los montos, facturas y reportes expresados en Bolivianos (Bs.).
- [X] **Principle III (Zero Scope Creep)**: Se abordan estrictamente las 4 historias de usuario especificadas.
- [X] **Principle IV (Verificable por no técnico)**: 5 escenarios documentados paso a paso en [quickstart.md](quickstart.md).
- [X] **Principle V (Verdad única de datos en tiempo real)**: El incremento de stock impacta de forma unificada e inmediata al catálogo del panel web y al POS.
- [X] **Principle VI (Privacidad y Seguridad)**: Proveedores, compras y costos 100% inaccesibles para el rol Vendedor.

---

## Project Structure

### Documentation (this feature)

```text
specs/005-compras-proveedores-stock/
├── plan.md              # Plan de implementación técnica
├── research.md          # Decisiones de diseño y alternativas
├── data-model.md        # Esquema relacional de proveedores y compras
├── quickstart.md        # Guía de verificación en 5 minutos
└── contracts/
    └── purchases-api.md # Contratos de endpoints REST
```

### Source Code

```text
app/
├── Domain/
│   ├── Supplier/
│   │   ├── Supplier.php
│   │   └── SupplierRepositoryInterface.php
│   └── Purchase/
│       ├── Purchase.php
│       ├── PurchaseItem.php
│       └── PurchaseRepositoryInterface.php
├── Application/
│   ├── Supplier/
│   │   ├── ListSuppliersUseCase.php
│   │   ├── CreateSupplierUseCase.php
│   │   ├── UpdateSupplierUseCase.php
│   │   └── ToggleSupplierStatusUseCase.php
│   └── Purchase/
│       ├── ProcessPurchaseUseCase.php
│       ├── ListPurchasesUseCase.php
│       ├── GetPurchaseUseCase.php
│       └── CancelPurchaseUseCase.php
└── Infrastructure/
    ├── Http/Controllers/Api/
    │   ├── SupplierController.php
    │   └── PurchaseController.php
    └── Persistence/Eloquent/
        ├── SupplierModel.php
        ├── PurchaseModel.php
        ├── PurchaseItemModel.php
        ├── EloquentSupplierRepository.php
        └── EloquentPurchaseRepository.php

database/migrations/
├── 2026_10_03_000011_create_suppliers_table.php
└── 2026_10_03_000012_create_purchases_and_items_tables.php

resources/views/purchases/
└── receipt.blade.php    # Nota formal de recepción para archivo/impresión

admin-starter-kit/src/
├── pages/
│   ├── suppliers/
│   │   └── index.vue    # Directorio de proveedores
│   └── purchases/
│       ├── index.vue    # Historial de compras
│       └── create.vue   # Formulario interactivo de recepción de mercadería
└── views/purchases/
    ├── PurchaseDetailsDialog.vue
    └── QuickProductDialog.vue

tests/Feature/
├── Supplier/
│   └── SupplierManagementTest.php
└── Purchase/
    └── PurchaseManagementTest.php
```

---

## Phase Breakdown

1. **Phase 1: Migraciones & Base de Datos**: Creación de tablas `suppliers`, `purchases`, `purchase_items` e índices.
2. **Phase 2: Dominio & Repositorios**: Entidades puras e implementaciones Eloquent con registro en `AppServiceProvider`.
3. **Phase 3: Proveedores (US1)**: CRUD y endpoints de proveedores con protección de rol `owner`.
4. **Phase 4: Recepción de Compra & Incremento Atómico de Stock (US2 & US3)**: Caso de uso transaccional, actualización de costos/precios y movimiento de inventario.
5. **Phase 5: Historial, Detalle y Anulación de Compra (US4)**: Consulta, anulación con validación anti-stock negativo e impresión.
6. **Phase 6: Frontend SPA (Vue 3 / Vuetify)**: Pantallas de proveedores, compras, alta rápida de producto y detalle.
7. **Phase 7: Verificación Integral**: Tests automatizados y validación manual con `quickstart.md`.
