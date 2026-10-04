# Implementation Plan: Kardex Completo (Físico y Valorizado) y Reportes Financieros / Rentabilidad

**Branch**: `010-kardex-reportes-rentabilidad` | **Date**: 2026-10-03 | **Spec**: [spec.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/010-kardex-reportes-rentabilidad/spec.md)

**Input**: Feature specification from `/specs/010-kardex-reportes-rentabilidad/spec.md`

## Summary

Implementar el módulo integral de auditoría de inventario y rentabilidad financiera para Servimática:
1. **Kardex por Producto**: Algoritmo de Costo Promedio Ponderado (CPP) móvil con doble nivel de presentación (Kardex Físico público para cajeros y Kardex Valorizado en `Bs.` exclusivo para Dueño).
2. **Reporte Financiero de Rentabilidad**: Tablero con KPIs ejecutivos (Ventas, Costo de Mercancía Vendida COGS, Utilidad Bruta y Margen %), filtros temporales flexibles y doble visualización (cronológica y por producto/categoría).
3. **Reporte de Valoración de Inventario**: Cálculo patrimonial de capital inmovilizado en Bolivianos discriminando entre stock comercial vendible y stock defectuoso/en garantía.
4. **Exportación Universal**: Descarga inmediata a Excel/CSV formateado en Bolivianos (`Bs.`).

## Technical Context

**Language/Version**: PHP 8.2+ (Backend Laravel 11) & JavaScript ES2023 (Frontend Vue 3 + Vite)

**Primary Dependencies**: Vuetify 3, Pinia, Vue Router, CASL (`@casl/vue`), Remix Icons (`ri-*`), Carbon Immutable (fechas en timezone `America/La_Paz`).

**Storage**: MySQL / MariaDB (XAMPP). Ampliación de la tabla `stock_movements` con `unit_cost`, `total_cost`, `reference_type` y `reference_id`.

**Testing**: Pest / PHPUnit (`php artisan test`). Pruebas de integración de cálculo matemático CPP, control de autorización de roles y generación de reportes.

**Target Platform**: Servidor Web Linux/macOS + Navegador Web de escritorio para administración.

**Project Type**: Aplicación web SPA (Frontend en `admin-starter-kit/`) desacoplada con API RESTful en Laravel.

**Performance Goals**:
- Consulta y cálculo de Kardex de cualquier producto en < 500ms.
- Generación de reportes financieros consolidados de más de 5,000 ventas en < 1 segundo.
- Descarga de archivos CSV/Excel en < 1.5 segundos sin bloqueo del hilo principal.

**Constraints**:
- Principio I (KISS & YAGNI): Cero dependencias pesadas de exportación; uso de streaming CSV con BOM UTF-8 y punto y coma.
- Principio II (Moneda y Mercado): Todo cálculo e indicador expresado en Bolivianos (`Bs.`).
- Principio VI (Privacidad): Costos, márgenes y reportes financieros estrictamente inaccesibles para roles no administrativos tanto en UI como en la API REST.

**Scale/Scope**:
- 3 vistas nuevas en frontend (`reports/kardex.vue`, `reports/profitability.vue`, `reports/inventory-valuation.vue`).
- 3 casos de uso en backend con endpoints dedicados y control de permisos CASL.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento | Justificación de Arquitectura |
|:----------|:------------:|:------------------------------|
| **I. KISS & YAGNI** | **PASA** | Algoritmo determinístico directo de CPP sin módulos contables sobredimensionados; exportación sin librerías pesadas. |
| **II. Idioma & Moneda (Bolivia / BOB)** | **PASA** | Todo indicador, columna y desglose en español claro y moneda `Bs.`. |
| **III. Cero Alcance Fantasma** | **PASA** | Se limita exactamente a las 3 historias de usuario aprobadas en la especificación. |
| **IV. Verificable por No Técnico** | **PASA** | Reportes visuales con tarjetas KPI claras y tablas con filtros rápidos de 1 clic. |
| **V. Verdad Única en Tiempo Real** | **PASA** | El Kardex y los reportes se calculan directamente desde las tablas operativas maestras (`sales`, `purchases`, `stock_movements`). |
| **VI. Privacidad de Costos y Márgenes** | **PASA** | Filtrado a nivel de API REST y CASL; usuarios operativos no reciben datos de costos ni en JSON ni en pantalla. |

## Project Structure

### Documentation (this feature)

```text
specs/010-kardex-reportes-rentabilidad/
├── spec.md              # Especificación funcional aprobada
├── plan.md              # Este plan de implementación
├── research.md          # Decisiones de arquitectura y fórmulas de valuación
├── data-model.md        # Estructura de base de datos y DTOs de reporte
├── quickstart.md        # Guía paso a paso de verificación y comandos
└── contracts/           # Contratos de API
    ├── kardex-api.md
    └── reports-api.md
```

### Source Code Layout

```text
app/
├── Domain/
│   └── Kardex/
│       ├── KardexMovement.php
│       ├── KardexCalculator.php
│       └── KardexRepositoryInterface.php
│   └── Report/
│       ├── ProfitabilityReport.php
│       └── InventoryValuationReport.php
├── Application/
│   ├── Kardex/
│   │   └── GetProductKardexUseCase.php
│   └── Report/
│       ├── GetProfitabilityReportUseCase.php
│       └── GetInventoryValuationUseCase.php
├── Infrastructure/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── KardexController.php
│   │   │   └── ReportController.php
│   │   └── Requests/
│   │       ├── KardexFilterRequest.php
│   │       └── ProfitabilityFilterRequest.php
│   └── Persistence/
│       └── Eloquent/
│           ├── EloquentKardexRepository.php
│           └── StockMovementModel.php (actualizado con nuevos campos)

database/
└── migrations/
    └── 2026_10_03_000019_add_costs_and_references_to_stock_movements_table.php

admin-starter-kit/
└── src/
    ├── pages/
    │   └── reports/
    │       ├── kardex.vue
    │       ├── profitability.vue
    │       └── inventory-valuation.vue
    ├── navigation/
    │   └── vertical/index.js (actualizado con accesos a reportes)
    └── plugins/
        └── casl/ability.js (permisos para reportes y kardex valorizado)

tests/
└── Feature/
    └── Report/
        ├── ProductKardexTest.php
        ├── ProfitabilityReportTest.php
        └── InventoryValuationTest.php
```

## Complexity Tracking

*No se detectan violaciones a la Constitución ni patrones de sobre-ingeniería.*
