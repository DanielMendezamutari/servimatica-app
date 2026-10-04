# Implementation Plan: Dashboard Ejecutivo en Tiempo Real, Navegación UX y Soporte Dark Mode

**Branch**: `011-dashboard-navegacion-dark-mode` | **Date**: 2026-10-03 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/011-dashboard-navegacion-dark-mode/spec.md`

---

## Summary

Implementar un centro de mando integral en la página de inicio (`admin-starter-kit/src/pages/index.vue`) con tarjetas de KPIs en tiempo real (ventas, ticket promedio, utilidades confidenciales para Dueño, estado de caja chica, alertas de stock mínimo y ranking de productos top), reorganizar la barra lateral izquierda en 4 bloques temáticos limpios a 1 solo clic con badge en POS, y optimizar el contraste de la interfaz para Modo Claro y Modo Oscuro mediante tokens nativos de Vuetify 3.

---

## Technical Context

**Language/Version**: PHP 8.2+ (Backend Laravel), JavaScript ESNext / Vue 3 (Frontend SPA).
**Primary Dependencies**: Laravel 11.x, Vue 3, Vuetify 3, Pinia, CASL, Remix Icon.
**Storage**: MySQL / MariaDB (tablas existentes: `sales`, `sale_items`, `cash_shifts`, `products`, `categories`, `users`).
**Testing**: PHPUnit / Pest (`php artisan test`).
**Target Platform**: Web SPA (`admin-starter-kit`), responsive en monitores, laptops y tablets.
**Performance Goals**: Carga del endpoint `GET /api/v1/dashboard/summary` en < 200 ms; alternancia entre temas claro/oscuro en < 100 ms.
**Constraints**:
- **Principio VI (Confidencialidad):** Los campos de utilidad y costo están prohibidos en el payload de vendedores.
- **Principio II (Bolivia / BOB):** Formato monetario `Bs.` y separadores decimales locales.
- **Zero Serve:** PROHIBIDO ejecutar `php artisan serve`.

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- [x] **Principio I (KISS & YAGNI):** Cero sobreingeniería; no se crean tablas nuevas de BD ni dependencias externas; se reutilizan consultas agregadas optimizadas.
- [x] **Principio II (Moneda y Mercado):** Todos los valores expresados en Bolivianos (`Bs.`).
- [x] **Principio III (Cero alcance fantasma):** Acotado exclusivamente al Dashboard, navegación lateral y soporte Dark Mode.
- [x] **Principio IV (Verificable por no técnico):** Comprobable visualmente en menos de 2 minutos iniciando sesión como Dueño y Vendedor.
- [x] **Principio V (Verdad única de datos):** Métricas consolidadas directamente desde la base de datos central en tiempo real.
- [x] **Principio VI (Privacidad de datos):** Utilidades netas y márgenes filtrados a nivel de caso de uso en Laravel antes de responder.

---

## Project Structure

### Documentation (this feature)

```text
specs/011-dashboard-navegacion-dark-mode/
├── spec.md              # Feature specification
├── plan.md              # This file
├── research.md          # Technical research & UX decisions
├── data-model.md        # Virtual data structures & KPIs
├── contracts/           # API contract for GET /api/v1/dashboard/summary
│   └── dashboard-api.md
├── quickstart.md        # Step-by-step validation guide
└── checklists/
    └── requirements.md  # 16/16 quality checklist
```

### Source Code Architecture

```text
# Backend (Laravel - Raíz)
app/
├── Application/
│   └── Dashboard/
│       └── GetDashboardSummaryUseCase.php     # Agregación atómica de KPIs, caja y alertas
├── Infrastructure/
│   └── Http/
│       └── Controllers/
│           └── Api/
│               └── DashboardController.php    # Exposición API protegida por auth:api
routes/
└── api.php                                    # GET /api/v1/dashboard/summary

tests/
└── Feature/
    └── Dashboard/
        └── DashboardSummaryTest.php           # Pruebas de integración y confidencialidad

# Frontend (Vue 3 / Vuetify 3 - admin-starter-kit)
admin-starter-kit/
├── src/
│   ├── navigation/
│   │   └── vertical/
│   │       └── index.js                       # Nueva jerarquía en 4 bloques temáticos con badge POS
│   ├── pages/
│   │   └── index.vue                          # Dashboard Ejecutivo completo y responsive
│   └── styles/ o layouts/                     # Afinamiento de tokens y contrastes en Modo Oscuro
```

---

## Plan Phases

- **Phase 0 (Research):** Decisiones de payload consolidado, estructura del menú a 1 clic y tokens de Dark Mode (completado en `research.md`).
- **Phase 1 (Design & Contracts):** Definición de Value Objects, contrato OpenAPI/JSON y guía de verificación (completado en `data-model.md`, `contracts/dashboard-api.md`, `quickstart.md`).
- **Phase 2 (Tasks Breakdown):** Ejecutada por `/speckit-tasks` para generar la lista granular de tareas `tasks.md`.
