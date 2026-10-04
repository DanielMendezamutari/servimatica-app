# Tasks: Dashboard Ejecutivo en Tiempo Real, Navegación UX y Soporte Dark Mode

**Input**: Design artifacts from `specs/011-dashboard-navegacion-dark-mode/`
**Prerequisites**: `spec.md`, `plan.md`, `data-model.md`, `contracts/`, `research.md`, `quickstart.md`

---

## Phase 1: Setup & Environment

**Purpose**: Verificación de configuración del frontend, tema y dependencias base

- [x] T001 Verificar configuración de tema y persistencia de cookies en `admin-starter-kit/themeConfig.js`
- [x] T002 [P] Verificar conmutador de tema `admin-starter-kit/src/layouts/components/NavbarThemeSwitcher.vue` en la barra de navegación superior

---

## Phase 2: Foundational (Backend Architecture)

**Purpose**: Componentes base de backend que alimentan los datos del Dashboard y respetan la confidencialidad

- [x] T003 Implementar caso de uso `app/Application/Dashboard/GetDashboardSummaryUseCase.php` calculando ventas, comparativa, turno de caja, alertas de stock mínimo y top 5 productos más vendidos según el rol (`dueno` vs `vendedor`)
- [x] T004 Implementar controlador `app/Infrastructure/Http/Controllers/Api/DashboardController.php` con el método `summary` protegido por `auth:api`
- [x] T005 Registrar rutas `GET /dashboard/summary` y `GET /v1/dashboard/summary` en `routes/api.php`

**Checkpoint**: Base de datos y endpoints listos para responder tanto a Dueño como a Vendedor con estricto apego al Principio VI.

---

## Phase 3: User Story 1 - Dashboard Ejecutivo en Tiempo Real (Priority: P1) 🎯 MVP

**Goal**: Proveer al Dueño y Vendedor un centro de mando en la pantalla de inicio (`/`) con indicadores en tiempo real, alertas de existencias críticas, estado de caja chica y accesos directos.

**Independent Test**: Iniciar sesión como Dueño y constatar las tarjetas de facturación, utilidad neta en `Bs.`, turno de caja y alertas de reorden; e iniciar sesión como Vendedor constatando que las utilidades y costos están completamente ocultos.

### Tests for User Story 1 ⚠️
- [x] T006 [P] [US1] Crear pruebas de integración en `tests/Feature/Dashboard/DashboardSummaryTest.php` validando acceso completo del Dueño, exclusión de costos/utilidades al Vendedor y filtro por periodos (`today`, `this_week`, `this_month`)

### Implementation for User Story 1
- [x] T007 [US1] Diseñar componentes de tarjetas de métricas (KPIs) con selector interactivo de periodos (Hoy, Esta Semana, Este Mes) en `admin-starter-kit/src/pages/index.vue`
- [x] T008 [US1] Implementar tarjeta de estado de Caja Chica en `admin-starter-kit/src/pages/index.vue` mostrando turno activo, cajero y saldo acumulado (o botón de apertura si la caja está cerrada)
- [x] T009 [US1] Implementar bloque de Alertas de Stock Mínimo / Crítico con indicadores de criticidad y botón directo de compra en `admin-starter-kit/src/pages/index.vue`
- [x] T010 [US1] Implementar ranking de Top 5 Productos más vendidos con unidades comercializadas e ingresos en `admin-starter-kit/src/pages/index.vue`
- [x] T011 [US1] Implementar lanzador de Accesos Rápidos (Quick Actions) para POS, Proformas, Compras y Kardex en `admin-starter-kit/src/pages/index.vue`

**Checkpoint**: User Story 1 100% funcional y verificable como MVP.

---

## Phase 4: User Story 2 - Reestructuración Ergonómica de la Navegación Lateral (Sidebar UX) (Priority: P2)

**Goal**: Organizar la barra lateral izquierda en 4 bloques funcionales con acceso directo a 1 clic, badges visuales en el POS y permisos CASL limpios para eliminar la sobrecarga cognitiva.

**Independent Test**: Comprobar visualmente que el menú lateral exhibe la estructura de 4 bloques con cabeceras visuales claras y badge destacado en POS, ocultando las opciones no autorizadas al vendedor.

### Implementation for User Story 2
- [x] T012 [US2] Reestructurar el menú lateral en `admin-starter-kit/src/navigation/vertical/index.js` clasificando los ítems en los 4 bloques temáticos: *Ventas y Mostrador*, *Inventario y Abastecimiento*, *Reportes y Finanzas*, *Administración*
- [x] T013 [US2] Incorporar badge de acceso prioritario para el Punto de Venta POS (`badgeContent: 'POS'`, `badgeClass: 'bg-primary'`) en `admin-starter-kit/src/navigation/vertical/index.js`
- [x] T014 [US2] Validar y afinar reglas de visibilidad CASL (`action`, `subject`) en `admin-starter-kit/src/navigation/vertical/index.js` garantizando que los vendedores solo accedan a mostrador y catálogo público

**Checkpoint**: Navegación lateral renovada y ergonómica.

---

## Phase 5: User Story 3 - Optimización Visual de Alto Contraste en Modo Dark (Priority: P3)

**Goal**: Garantizar que el Dashboard, tablas, tarjetas y navegación ofrezcan un contraste impecable y libre de fatiga visual tanto en Modo Claro como en Modo Oscuro.

**Independent Test**: Alternar el conmutador de tema en la barra superior entre Claro y Oscuro, verificando la legibilidad de todos los textos, bordes y tarjetas sin recargar la página.

### Implementation for User Story 3
- [x] T015 [US3] Auditar y ajustar estilos CSS/SCSS de bordes divisores y tarjetas en modo oscuro en `admin-starter-kit/src/pages/index.vue` y estilos globales del starter kit
- [x] T016 [US3] Verificar que los chips de estado, badges y tablas del Dashboard utilicen colores semánticos (`success`, `warning`, `primary`, `error`) con legibilidad contrastada en fondo oscuro
- [x] T017 [US3] Constatar que la cookie de tema persiste la preferencia del usuario entre sesiones y recargas

**Checkpoint**: Todas las historias de usuario (US1, US2 y US3) completamente funcionales.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Verificación global, pruebas automatizadas y compilación limpia

- [x] T018 Ejecutar suite completa de pruebas de backend con `php artisan test` garantizando 0 regresiones
- [x] T019 Ejecutar compilación limpia del frontend con `pnpm --prefix admin-starter-kit run build`
- [x] T020 Validar escenarios de extremo a extremo conforme a `specs/011-dashboard-navegacion-dark-mode/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies
- **Phase 1 (Setup)**: Sin dependencias.
- **Phase 2 (Foundational)**: Requiere Phase 1. Bloquea User Story 1.
- **Phase 3 (US1 - MVP)**: Requiere Phase 2. Puede entregarse y probarse de forma independiente.
- **Phase 4 (US2)**: Puede desarrollarse en paralelo con US1 o inmediatamente después.
- **Phase 5 (US3)**: Se ejecuta sobre los componentes del Dashboard y navegación de US1 y US2.
- **Phase 6 (Polish)**: Requiere completar US1, US2 y US3.

---

## Parallel Opportunities

- T001 y T002 pueden validarse en paralelo.
- Los componentes de la vista del Dashboard (T007, T008, T009, T010, T011) residen en `index.vue` y se integran incrementalmente.
- T012 y T013 pueden ejecutarse directamente sobre el archivo de navegación lateral.
