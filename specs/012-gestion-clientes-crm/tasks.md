# Tasks: Gestión Integral de Clientes (CRM Comercial y Fidelización)

**Input**: Design artifacts from `specs/012-gestion-clientes-crm/`
**Prerequisites**: `spec.md`, `plan.md`, `data-model.md`, `contracts/`, `research.md`, `quickstart.md`

---

## Phase 1: Setup & Migrations

**Purpose**: Crear la migración de base de datos para incorporar los campos comerciales al modelo de clientes

- [x] T001 Crear migración `database/migrations/2026_10_04_000001_add_crm_fields_to_clients_table.php` agregando `client_type` (enum/string indexado), `city` (string nullable default 'Trinidad') y `notes` (text nullable)
- [x] T002 Ejecutar `php artisan migrate` y actualizar el seeder si aplica para incluir tipos de cliente en datos de prueba

---

## Phase 2: Foundational (Backend Architecture & Domain)

**Purpose**: Capa de dominio, casos de uso y endpoints de API para gestión de clientes y ficha 360°

- [x] T003 Actualizar entidad de dominio `app/Domain/Client/Client.php` y el contrato `app/Domain/Client/ClientRepositoryInterface.php` con los nuevos campos y métodos (`search`, `findById`, `save`, `update`, `toggleStatus`, `getStats`, `getClientSales`, `getClientQuotes`, `getClientWarranties`)
- [x] T004 Implementar repositorio `app/Infrastructure/Persistence/Eloquent/EloquentClientRepository.php` y modelo Eloquent `app/Infrastructure/Persistence/Eloquent/ClientModel.php` con relaciones hacia `sales` y `quotes`
- [x] T005 Implementar casos de uso `app/Application/Client/UpdateClientUseCase.php`, `app/Application/Client/GetClientDetailUseCase.php` y `app/Application/Client/GetClientWarrantiesUseCase.php`
- [x] T006 Actualizar `app/Infrastructure/Http/Controllers/Api/ClientController.php` con los métodos `index`, `show`, `store`, `update`, `sales`, `quotes`, `warranties`, `toggleStatus` y `export`
- [x] T007 Registrar rutas de la API en `routes/api.php` bajo `prefix('v1/clients')` y `prefix('clients')` protegidas por `auth:api`

---

## Phase 3: User Story 1 - Directorio Central y Gestión Rápida de Clientes (Priority: P1) 🎯 MVP

**Goal**: Permitir listar, buscar, filtrar y registrar clientes en un drawer lateral con enlace directo a WhatsApp y detección de duplicados en tiempo real.

**Independent Test**: Acceder a `/clients`, listar clientes con paginación, registrar un cliente con teléfono boliviano y abrir un chat directo de WhatsApp a un clic.

### Tests for User Story 1 ⚠️
- [x] T008 [P] [US1] Crear pruebas funcionales en `tests/Feature/Client/ClientManagementTest.php` validando búsqueda, creación con campos CRM, validación de teléfono/NIT y actualización

### Implementation for User Story 1
- [x] T009 [US1] Crear componente de drawer lateral `admin-starter-kit/src/views/clients/AddEditClientDrawer.vue` con soporte para creación, edición, validación reactiva y advertencia visual de duplicidad en NIT/CI o teléfono
- [x] T010 [US1] Desarrollar la vista principal `admin-starter-kit/src/pages/clients/index.vue` con tabla interactiva, filtros por tipo de cliente, buscador predictivo, botón de WhatsApp (`wa.me`) y acciones de edición/ficha
- [x] T011 [US1] Agregar el acceso directo a **Clientes** en el bloque *Operaciones* de `admin-starter-kit/src/navigation/vertical/index.js` (con icono `ri-user-star-line` o `ri-user-smile-line`)

---

## Phase 4: User Story 2 - Ficha 360° del Cliente: Historial y Garantías (Priority: P2)

**Goal**: Visualizar el perfil completo del cliente (`/clients/:id`) con KPIs acumulados, compras históricas, cotizaciones emitidas y garantías de equipos con seriales y días restantes.

**Independent Test**: Abrir la ficha de un cliente con ventas pasadas y constatar en pestañas el listado de tickets, proformas y equipos con garantía vigente o expirada.

### Tests for User Story 2 ⚠️
- [x] T012 [P] [US2] Crear pruebas de integración en `tests/Feature/Client/ClientProfile360Test.php` validando cálculo de totales comprados (`total_spent_bs`), conteo de compras y cálculo exacto de días de garantía restantes

### Implementation for User Story 2
- [x] T013 [US2] Desarrollar la vista de Ficha 360° en `admin-starter-kit/src/pages/clients/[id].vue` con tarjetas de métricas comerciales y pestañas: *Resumen Comercial*, *Historial de Ventas*, *Cotizaciones* y *Garantías de Equipos*
- [x] T014 [US2] Integrar en la pestaña de garantías los chips visuales de vigencia (Verde: *Vigente X días*; Rojo: *Garantía Expirada*) y número de serie del producto

---

## Phase 5: User Story 3 - Exportación de Directorio y Segmentación (Priority: P3)

**Goal**: Permitir al Dueño exportar la cartera de clientes a CSV/Excel protegiendo el acceso a vendedores.

**Independent Test**: Descargar el archivo CSV como administrador y constatar que un vendedor recibe `403 Forbidden`.

### Tests for User Story 3 ⚠️
- [x] T015 [P] [US3] Agregar pruebas en `tests/Feature/Client/ClientExportTest.php` validando descarga CSV con datos agregados y restricción 403 a vendedores

### Implementation for User Story 3
- [x] T016 [US3] Implementar caso de uso `app/Application/Client/ExportClientsToCsvUseCase.php`, método `export` en `ClientController.php` y botón de exportación en la barra superior de `admin-starter-kit/src/pages/clients/index.vue` (restringido con CASL `v-if="$can('manage', 'all')"`)

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Verificación global, pruebas automatizadas y compilación limpia

- [x] T017 Ejecutar suite completa de pruebas con `php artisan test`
- [x] T018 Ejecutar compilación de producción del frontend con `pnpm --prefix admin-starter-kit run build`
- [x] T019 Validar escenarios del quickstart conforme a `specs/012-gestion-clientes-crm/quickstart.md`
