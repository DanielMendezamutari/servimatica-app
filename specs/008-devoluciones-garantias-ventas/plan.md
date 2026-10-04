# Implementation Plan: 008-devoluciones-garantias-ventas

**Branch**: `008-devoluciones-garantias-ventas` | **Date**: 2026-10-03 | **Spec**: [spec.md](spec.md)

---

## Summary

Implementación vertical completa de la gestión de **garantías técnicas y devoluciones de ventas** para Servimática App. Permite configurar periodos de garantía por defecto en el catálogo (`warranty_days`) y personalizarlos al vender en el POS junto al número de serie (S/N). Provee consulta inmediata de vigencia temporal en mostrador, registro de devoluciones totales o parciales, segregación física de inventario (`stock_operativo` vs `stock_defectuoso_rma`), resolución flexible (cambio físico 1 a 1, reembolso de efectivo con egreso en caja chica o nota de crédito) y emisión de comprobantes impresos con membrete dinámico.

---

## Technical Context

- **Language/Version**: PHP 8.2 (Backend), JavaScript ES2022 (Frontend Web)
- **Primary Dependencies**: Laravel 11.x, Vue 3, Vuetify 3, Pinia, Vue Router, CASL
- **Storage**: MySQL 8.x con migraciones y transacciones atómicas (`DB::transaction`)
- **Testing**: Pest / PHPUnit para Feature y Unit tests (`php artisan test`)
- **Target Platform**: Servidor Web Apache/Nginx (Local: `http://servimatica-app.test`), Browser SPA
- **Project Type**: Web Application (Monolito modular con API REST compartida y SPA en `admin-starter-kit/`)
- **Performance Goals**: Verificación de garantía en < 200ms; registro de devolución atómica en < 500ms
- **Constraints**: 
  - Estricto apego a la Constitución de Servimática (KISS, moneda `Bs.`, cero bibliotecas exóticas).
  - Prohibido `php artisan serve`.
  - Reembolso en efectivo restringido al rol Dueño o con turno de caja chica abierto.
- **Scale/Scope**: Trazabilidad completa por venta e ítem, segregación de productos defectuosos y preparación directa para el Kardex del Capítulo 009.

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **Principio I (KISS & YAGNI):** Cero sobre-ingeniería. Cambio físico 1 a 1 directo; diferencias de precio resueltas liquidando saldo a favor y emitiendo una venta estándar en el POS existente.
- [x] **Principio II (Bolivia / BOB):** Todos los montos, comprobantes y mensajes expresados en Bolivianos (`Bs.`) y español estándar de Bolivia.
- [x] **Principio III (Cero Alcance Fantasma):** Construir exclusivamente lo especificado en `spec.md` (garantías en venta, verificación en mostrador, devoluciones y comprobantes).
- [x] **Principio IV (Verificable por Persona No Técnica):** Escenarios de validación guiados paso a paso en [quickstart.md](quickstart.md) en menos de 2 minutos.
- [x] **Principio V (Verdad Única en Tiempo Real):** Stock vendible y caja chica actualizados en la misma transacción atómica.
- [x] **Principio VI (Privacidad y Seguridad):** Reembolso de dinero restringido al Dueño, con registro de auditoría del usuario que procesa la devolución.

---

## Project Structure

### Documentation (this feature)

```text
specs/008-devoluciones-garantias-ventas/
├── spec.md              # Especificación funcional refinada con 4 clarificaciones
├── plan.md              # Este plan de arquitectura e implementación
├── research.md          # Decisiones técnicas y justificaciones de diseño
├── data-model.md        # Esquema de base de datos, relaciones y reglas de integridad
├── contracts/           # Especificación de endpoints OpenAPI (sale-returns.yaml)
├── quickstart.md        # Guía de verificación visual en 2 minutos para el Dueño
└── checklists/          # Checklists de calidad de requerimientos
```

### Source Code Layout

#### Backend (Laravel)
```text
database/migrations/
├── 2026_10_03_000016_add_warranty_to_products_and_sale_items.php
└── 2026_10_03_000017_create_sale_returns_and_items_tables.php

app/Domain/Sale/
├── SaleItem.php                      # Extendido con warranty_days, warranty_expires_at, serial_number
├── SaleReturn.php                    # Entidad de dominio de devolución
├── SaleReturnItem.php                # Entidad de ítem devuelto
└── SaleReturnRepositoryInterface.php # Contrato de persistencia

app/Application/Sale/
├── CheckSaleWarrantyUseCase.php      # Verifica vigencia de garantía y saldo de devolución
├── ProcessSaleReturnUseCase.php      # Ejecuta la devolución, ajuste de stock y caja
└── ListSaleReturnsUseCase.php        # Consulta histórica paginada con filtros

app/Infrastructure/Persistence/Eloquent/
├── SaleReturnModel.php
├── SaleReturnItemModel.php
└── EloquentSaleReturnRepository.php

app/Infrastructure/Http/Controllers/Api/
└── SaleReturnController.php         # Endpoints de garantía, devolución y comprobante

resources/views/sales/
└── return_receipt.blade.php          # Plantilla Blade para Comprobante de Devolución (80mm y Carta)
```

#### Frontend Web (SPA en `admin-starter-kit/`)
```text
admin-starter-kit/src/
├── pages/sales/
│   ├── index.vue                     # Columna de garantía y botón "Verificar Garantía / Devolución"
│   └── returns.vue                   # Historial de Devoluciones y filtro por resolución
├── views/pos/
│   └── PosCart.vue                   # Campo de tiempo de garantía y S/N opcional en el carrito
└── views/sales/
    └── SaleReturnDialog.vue          # Modal interactivo para verificar garantía y procesar devolución
```

---

## Plan de Ejecución por Fases

1. **Fase 1: Setup & Migraciones de Base de Datos**
   - Crear migraciones para `products` (`warranty_days`, `defective_stock`), `sale_items` (`warranty_days`, `warranty_expires_at`, `serial_number`), `cash_shifts` (`total_cash_refunds`) y tablas `sale_returns` + `sale_return_items`.
   - Ejecutar `php artisan migrate`.

2. **Fase 2: Dominio & Repositorios (Hexagonal)**
   - Actualizar entidad `SaleItem` e implementar entidades `SaleReturn` y `SaleReturnItem`.
   - Crear interfaz `SaleReturnRepositoryInterface` y su implementación Eloquent.
   - Registrar enlace en `AppServiceProvider`.

3. **Fase 3: User Story 1 & 2 - Garantía en Ventas y Verificación en Mostrador (MVP)**
   - Pruebas TDD de Feature (`SaleWarrantyTrackingTest`).
   - Casos de uso `CheckSaleWarrantyUseCase` y adaptación en `CreateSaleUseCase` para calcular `warranty_expires_at`.
   - Inyección en tickets térmicos de venta `sales/receipt.blade.php`.
   - Componentes UI en POS y listado de ventas.

4. **Fase 4: User Story 3 & 4 - Procesamiento de Devolución, Inventario y Caja Chica**
   - Pruebas TDD de Feature (`SaleReturnProcessingTest`).
   - Caso de uso `ProcessSaleReturnUseCase` con soporte de `stock_operativo` vs `stock_defectuoso_rma`, `cambio_fisico` y `reembolso_efectivo`.
   - Plantilla Blade `sales/return_receipt.blade.php` con membrete dinámico de `$company`.
   - Modal `SaleReturnDialog.vue` y pantalla `returns.vue`.

5. **Fase 5: Polish & Verificación Final**
   - Ejecución de suite de pruebas backend (`php artisan test`).
   - Compilación limpia de producción del frontend (`pnpm run build`).
   - Validación paso a paso con [quickstart.md](quickstart.md).
