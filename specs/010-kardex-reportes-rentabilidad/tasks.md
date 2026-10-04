# Tasks: Kardex Completo (Físico y Valorizado) y Reportes Financieros / Rentabilidad

**Feature**: `010-kardex-reportes-rentabilidad`
**Spec**: [spec.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/010-kardex-reportes-rentabilidad/spec.md)
**Plan**: [plan.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/010-kardex-reportes-rentabilidad/plan.md)

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Estructura de base de datos aditiva y campos de trazabilidad de costos

- [x] T001 Crear migración aditiva `database/migrations/2026_10_03_000019_add_costs_and_references_to_stock_movements_table.php` con campos `unit_cost` decimal(12,4) nullable, `total_cost` decimal(12,2) nullable, `reference_type` varchar(50) nullable, `reference_id` unsignedBigInteger nullable e índices compuestos
- [x] T002 Actualizar modelo Eloquent en `app/Infrastructure/Persistence/Eloquent/StockMovementModel.php` incorporando los nuevos campos en `$fillable` y relaciones con producto y usuario

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Motor de cálculo CPP e inyección de costos en todos los flujos de inventario

**⚠️ CRITICAL**: Ninguna historia de usuario puede completarse sin la persistencia de costos en movimientos

- [x] T003 [P] Implementar objetos de valor y motor de cálculo de Costo Promedio Ponderado en `app/Domain/Kardex/KardexMovement.php` y `app/Domain/Kardex/KardexCalculator.php`
- [x] T004 [P] Definir interfaz de repositorio `app/Domain/Kardex/KardexRepositoryInterface.php` e implementar persistencia/consulta cronológica en `app/Infrastructure/Persistence/Eloquent/EloquentKardexRepository.php`
- [x] T005 Actualizar casos de uso de compras, ventas, devoluciones y ajustes para registrar `unit_cost`, `total_cost`, `reference_type` y `reference_id` en `app/Application/Purchase/ProcessPurchaseUseCase.php`, `app/Application/Sale/ProcessSaleUseCase.php`, `app/Application/Sale/CancelSaleUseCase.php`, `app/Application/Sale/ProcessSaleReturnUseCase.php` y `app/Infrastructure/Persistence/Eloquent/EloquentStockMovementRepository.php`

**Checkpoint**: Base de cálculo y trazabilidad lista. Se puede proceder con las historias de usuario de forma independiente.

---

## Phase 3: User Story 1 - Consulta de Kardex Físico y Valorizado por Producto (Priority: P1) 🎯 MVP

**Goal**: Permitir la consulta auditada de entradas, salidas y saldos físicos y valorizados (CPP) por producto con filtros por fecha y descarga CSV/Excel, ocultando costos a roles no administrativos.

**Independent Test**: Seleccionar cualquier producto con historial y comprobar que el saldo físico y valorizado en `Bs.` coincide con la sumatoria matemática CPP, y que un usuario cajero solo ve cantidades físicas.

### Tests for User Story 1 ⚠️
- [x] T006 [P] [US1] Crear prueba de integración `tests/Feature/Report/ProductKardexTest.php` validando cálculo matemático CPP, filtros por fecha, exportación CSV y exclusión de datos financieros para roles no administrativos (Principio VI)

### Implementation for User Story 1
- [x] T007 [US1] Implementar caso de uso `app/Application/Kardex/GetProductKardexUseCase.php` con cálculo de saldos iniciales, movimientos acumulados en rango y totales del periodo
- [x] T008 [US1] Implementar `app/Infrastructure/Http/Controllers/KardexController.php` con sanitización de datos por rol (Principio VI) y generación de stream CSV con BOM UTF-8
- [x] T009 [US1] Registrar ruta protegida `GET /api/v1/kardex/{productId}` en `routes/api.php`
- [x] T010 [US1] Crear vista frontend en `admin-starter-kit/src/pages/reports/kardex.vue` con buscador de productos con autocompletado, selector de rango de fechas, tabla auditada (Entradas, Salidas, Saldos) con visibilidad condicional de columnas valorizadas según rol y botón de exportación a Excel/CSV

**Checkpoint**: User Story 1 (MVP) 100% operativa y verificable de forma independiente.

---

## Phase 4: User Story 2 - Reporte Ejecutivo de Rentabilidad y Utilidad por Periodo (Priority: P2)

**Goal**: Proporcionar al Dueño un panel financiero con KPIs de Ventas, Costo de Ventas (COGS), Utilidad Bruta y Margen %, con desglose cronológico y por producto.

**Independent Test**: Filtrar ventas del mes actual y verificar que `Utilidad Bruta = Ventas Totales - Costo de Ventas`, con ranking de artículos más rentables.

### Tests for User Story 2 ⚠️
- [x] T011 [P] [US2] Crear prueba de integración `tests/Feature/Report/ProfitabilityReportTest.php` validando cálculo de COGS, utilidad bruta, margen %, timeline de fechas y restricción 403 Forbidden para usuarios sin rol de administración

### Implementation for User Story 2
- [x] T012 [US2] Implementar caso de uso `app/Application/Report/GetProfitabilityReportUseCase.php` con agregación de ventas netas, costos de adquisición de ítems vendidos, utilidad bruta en `Bs.`, margen porcentual, desglose diario y top de productos
- [x] T013 [US2] Crear controlador `app/Infrastructure/Http/Controllers/ReportController.php` con acción `profitability` y exportación a CSV
- [x] T014 [US2] Registrar ruta protegida `GET /api/v1/reports/profitability` en `routes/api.php`
- [x] T015 [US2] Crear vista frontend en `admin-starter-kit/src/pages/reports/profitability.vue` con tarjetas ejecutivas KPI, selector de rango temporal, pestañas conmutables (Evolución diaria/mensual y Desglose por producto) y exportación a Excel

**Checkpoint**: User Stories 1 y 2 operativas e integradas sin dependencias mutuas bloqueantes.

---

## Phase 5: User Story 3 - Reporte de Valoración Global de Inventario y Capital Inmovilizado (Priority: P3)

**Goal**: Proveer al Dueño un reporte patrimonial del capital invertido en inventario, diferenciando entre stock vendible en vitrina y mercadería en garantía o defectuosa, con exportación a Excel.

**Independent Test**: Generar el reporte y constatar que el capital total en `Bs.` coincide con la sumatoria de existencias físicas multiplicadas por su costo promedio ponderado vigente.

### Tests for User Story 3 ⚠️
- [x] T016 [P] [US3] Crear prueba de integración `tests/Feature/Report/InventoryValuationTest.php` validando totalización patrimonial de stock vendible vs. defectuoso y exportación CSV

### Implementation for User Story 3
- [x] T017 [US3] Implementar caso de uso `app/Application/Report/GetInventoryValuationUseCase.php` totalizando stock comercial y defectuoso por producto y categoría
- [x] T018 [US3] Agregar método `inventoryValuation` en `app/Infrastructure/Http/Controllers/ReportController.php` y registrar ruta `GET /api/v1/reports/inventory-valuation` en `routes/api.php`
- [x] T019 [US3] Crear vista frontend en `admin-starter-kit/src/pages/reports/inventory-valuation.vue` con tarjetas de resumen patrimonial en `Bs.`, tabla con stock vendible vs. en garantía y exportación a Excel/CSV

**Checkpoint**: Todas las historias de usuario (P1, P2 y P3) 100% funcionales.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Integración de menús, seguridad CASL, verificación general y compilación

- [x] T020 Actualizar menú lateral en `admin-starter-kit/src/navigation/vertical/index.js` agregando enlaces a Kardex, Rentabilidad y Valoración con sus respectivos permisos CASL
- [x] T021 Ejecutar suite completa de pruebas backend con `php artisan test` garantizando 0 errores y regresiones
- [x] T022 Ejecutar compilación limpia del frontend con `pnpm --prefix admin-starter-kit run build`
- [x] T023 Validar escenarios de extremo a extremo conforme a `specs/010-kardex-reportes-rentabilidad/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies
- **Phase 1 (Setup)**: Inmediata. Sin dependencias.
- **Phase 2 (Foundational)**: Requiere Phase 1. Bloquea las historias de usuario.
- **Phase 3 (US1 - MVP)**: Requiere Phase 2. Puede entregarse y validarse de forma independiente como MVP.
- **Phase 4 (US2)**: Requiere Phase 2. Independiente de US1.
- **Phase 5 (US3)**: Requiere Phase 2. Independiente de US1 y US2.
- **Phase 6 (Polish)**: Requiere completar US1, US2 y US3.

---

## Parallel Opportunities

- T003 y T004 se pueden desarrollar en paralelo (Domain Value Objects vs Repositorios).
- Los tests T006, T011 y T016 pueden ejecutarse de manera independiente.
- Las vistas de frontend T010, T015 y T019 son archivos completamente independientes dentro de `admin-starter-kit/src/pages/reports/`.
