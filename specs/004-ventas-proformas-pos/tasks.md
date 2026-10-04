# Tasks: Capítulo 4 — Cotizaciones / Proformas, Ventas en Tienda (POS), Control de Caja y Comisiones

**Input**: Design documents from `specs/004-ventas-proformas-pos/`
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/pos-api.md](contracts/pos-api.md), [quickstart.md](quickstart.md)

---

## Phase 1: Setup & Migraciones de Base de Datos

**Purpose**: Crear el esquema relacional para clientes, turnos de caja, proformas y ventas en mostrador.

- [X] T132 Crear migración para la tabla `clients` con campos: `id`, `name (VARCHAR(150))`, `nit_ci (VARCHAR(30) NULL INDEX)`, `phone (VARCHAR(30) NULL INDEX)`, `email (VARCHAR(100) NULL)`, `address (VARCHAR(255) NULL)`, `is_active (BOOLEAN DEFAULT TRUE)`, `timestamps` en `database/migrations/2026_10_03_000007_create_clients_table.php`
- [X] T133 [P] Crear migración para la tabla `cash_shifts` con campos: `id`, `user_id (BIGINT UNSIGNED FK → users.id)`, `opening_amount (DECIMAL(10,2))`, `closing_amount (DECIMAL(10,2) NULL)`, `expected_amount (DECIMAL(10,2) NULL)`, `difference (DECIMAL(10,2) NULL)`, `total_cash_sales (DECIMAL(10,2) DEFAULT 0.00)`, `total_qr_sales (DECIMAL(10,2) DEFAULT 0.00)`, `status (ENUM('open','closed') DEFAULT 'open')`, `opened_at (TIMESTAMP)`, `closed_at (TIMESTAMP NULL)`, `notes (TEXT NULL)`, `timestamps` en `database/migrations/2026_10_03_000008_create_cash_shifts_table.php`
- [X] T134 [P] Crear migración para las tablas `quotes` y `quote_items` con campos: `quote_number (VARCHAR(30) UNIQUE)`, `seller_id (FK → users)`, `client_id (FK → clients NULL)`, `client_name (VARCHAR(150))`, `client_phone (VARCHAR(30) NULL)`, `subtotal`, `discount_amount`, `total_amount`, `valid_until (DATE)`, `status (ENUM('active','converted','expired','cancelled'))`, `notes (TEXT NULL)` en `database/migrations/2026_10_03_000009_create_quotes_and_items_tables.php`
- [X] T135 [P] Crear migración para las tablas `sales` y `sale_items` con campos: `invoice_number (VARCHAR(30) UNIQUE)`, `quote_id (FK → quotes NULL)`, `seller_id (FK → users)`, `client_id (FK → clients NULL)`, `client_name`, `client_nit_ci`, `cash_shift_id (FK → cash_shifts)`, `payment_method (ENUM('efectivo','qr','transferencia'))`, `subtotal`, `discount_amount`, `total_amount`, `cash_tendered`, `change_due`, `commission_rate`, `commission_amount`, `status (ENUM('completed','cancelled'))`, `cancellation_reason (VARCHAR(255) NULL)`, `cancelled_by (FK → users NULL)`, `cancelled_at (TIMESTAMP NULL)` en `database/migrations/2026_10_03_000010_create_sales_and_items_tables.php`
- [X] T136 Ejecutar migraciones con `php artisan migrate` y verificar integridad de llaves foráneas en MySQL

**Checkpoint**: Esquema relacional disponible para todas las operaciones comerciales del capítulo.

---

## Phase 2: Foundational (Entidades de Dominio, Repositorios e Infraestructura)

**Purpose**: Construir las entidades de dominio puro y los repositorios Eloquent requeridos por los casos de uso.

- [X] T137 [P] Implementar Entidad de Dominio `Client` y su contrato `ClientRepositoryInterface` en `app/Domain/Client/`
- [X] T138 [P] Implementar Modelo Eloquent `ClientModel` y Repositorio `EloquentClientRepository` en `app/Infrastructure/Persistence/Eloquent/`
- [X] T139 [P] Implementar Entidad de Dominio `CashShift` y su contrato `CashShiftRepositoryInterface` en `app/Domain/CashShift/`
- [X] T140 [P] Implementar Modelo Eloquent `CashShiftModel` y Repositorio `EloquentCashShiftRepository` en `app/Infrastructure/Persistence/Eloquent/`
- [X] T141 [P] Implementar Entidades de Dominio `Quote`, `QuoteItem` y su contrato `QuoteRepositoryInterface` en `app/Domain/Quote/`
- [X] T142 [P] Implementar Modelos Eloquent `QuoteModel`, `QuoteItemModel` y Repositorio `EloquentQuoteRepository` en `app/Infrastructure/Persistence/Eloquent/`
- [X] T143 [P] Implementar Entidades de Dominio `Sale`, `SaleItem` y su contrato `SaleRepositoryInterface` en `app/Domain/Sale/`
- [X] T144 [P] Implementar Modelos Eloquent `SaleModel`, `SaleItemModel` y Repositorio `EloquentSaleRepository` en `app/Infrastructure/Persistence/Eloquent/`
- [X] T145 Registrar los bindings de `ClientRepositoryInterface`, `CashShiftRepositoryInterface`, `QuoteRepositoryInterface` y `SaleRepositoryInterface` en `app/Providers/AppServiceProvider.php`

**Checkpoint**: Capa de Dominio y Persistencia conectada y disponible en el contenedor de Laravel.

---

## Phase 3: User Story 5 — Control de Caja Chica, Turnos y Arqueo (Prioridad: P5) 🎯 Prerrequisito POS

**Goal**: Permitir al cajero/vendedor abrir turno de caja con fondo inicial en Bs. para cambio, auditar ingresos físicos vs QR, y realizar el arqueo de cierre diario.
**Independent Test**: Abrir caja con Bs. 100,00, consultar el estado de caja abierta, y cerrarla con arqueo físico verificando cálculo de diferencia en Bs.

- [X] T146 [US5] Implementar casos de uso `OpenCashShiftUseCase`, `GetCurrentCashShiftUseCase` y `CloseCashShiftUseCase` en `app/Application/CashShift/`
- [X] T147 [US5] Implementar `CashShiftController` con endpoints `GET /api/cash-shifts/current`, `POST /api/cash-shifts/open` y `POST /api/cash-shifts/close` en `app/Infrastructure/Http/Controllers/Api/CashShiftController.php` y registrar rutas en `routes/api.php`
- [X] T148 [US5] Escribir prueba de integración para ciclo de apertura, bloqueo de doble turno y arqueo de cierre en `tests/Feature/CashShift/CashShiftTest.php`
- [X] T149 [US5] Crear componentes de diálogo `OpenCashShiftDialog.vue` y `CloseCashShiftDialog.vue` en `admin-starter-kit/src/views/pos/`
- [X] T150 [US5] Crear vista de historial y arqueos de caja en `admin-starter-kit/src/pages/cash-shifts/index.vue`

**Checkpoint**: Módulo de caja operativa para respaldar las ventas en mostrador.

---

## Phase 4: User Story 4 & 1 — Clientes y Proformas / Cotizaciones con WhatsApp (Prioridad: P1) 🎯 MVP

**Goal**: Permitir cotizar productos rápidamente, asociar o crear clientes al vuelo, emitir PDF formal y enviar proforma preformateada a WhatsApp sin descontar stock.
**Independent Test**: Armar proforma con 2 productos para un cliente, verificar que el stock en el catálogo no se descuente, generar el mensaje de WhatsApp con formato boliviano y descargar el PDF.

- [X] T151 [US4] Implementar casos de uso `ListClientsUseCase` y `CreateClientUseCase`, controlador `ClientController` y registrar rutas `/api/clients` en `routes/api.php`
- [X] T152 [US4] Crear diálogo de alta rápida de cliente `QuickClientDialog.vue` en `admin-starter-kit/src/views/pos/QuickClientDialog.vue`
- [X] T153 [US1] Implementar casos de uso `CreateQuoteUseCase`, `ListQuotesUseCase` y `GetQuoteUseCase` en `app/Application/Quote/`
- [X] T154 [US1] Implementar servicio de generación de proforma en PDF formal con membrete y generador de enlace `wa.me/591` para WhatsApp
- [X] T155 [US1] Implementar `QuoteController` con endpoints `POST /api/quotes`, `GET /api/quotes` y `GET /api/quotes/{id}/pdf` en `app/Infrastructure/Http/Controllers/Api/QuoteController.php` y registrar rutas en `routes/api.php`
- [X] T156 [US1] Escribir prueba de integración para emisión de proformas sin descuento de stock en `tests/Feature/Quote/QuoteManagementTest.php`
- [X] T157 [US1] Crear vista de proformas `admin-starter-kit/src/pages/quotes/index.vue` con botón directo de WhatsApp y diálogo de detalle `QuoteDetailsDialog.vue`

**Checkpoint**: Sistema de cotizaciones formal y preventa por WhatsApp completamente operativo.

---

## Phase 5: User Story 2 — Punto de Venta (POS) y Venta Atómica con Descuento de Stock (Prioridad: P2)

**Goal**: Registrar ventas en mostrador con validación de caja abierta, cobro en Efectivo (cálculo de vuelto) o QR, descuento inmediato y atómico de stock (`lockForUpdate`), y ticket térmico de 80mm.
**Independent Test**: Vender 1 unidad de un producto con stock 5 cobrando en efectivo con billete de 200 Bs., comprobar vuelto exacto, emitir ticket de 80mm y verificar que el stock disminuya a 4.

- [X] T158 [US2] Implementar caso de uso `ProcessSaleUseCase` con `DB::transaction()` y bloqueo pesimista `lockForUpdate()`, descontando stock y actualizando acumuladores de caja en `app/Application/Sale/ProcessSaleUseCase.php`
- [X] T159 [US2] Implementar caso de uso `ConvertQuoteToSaleUseCase` para transferir ítems de proforma directamente al cobro en `app/Application/Sale/ConvertQuoteToSaleUseCase.php`
- [X] T160 [US2] Implementar `SaleController@store` con validación estricta de turno de caja abierto y registrar ruta `POST /api/sales` en `routes/api.php`
- [X] T161 [US2] Escribir prueba de integración para venta en mostrador, cálculo de vuelto, pago QR y decremento de stock en `tests/Feature/Sale/PosSaleTest.php`
- [X] T162 [US2] Crear diálogo de cobro interactivo `CheckoutDialog.vue` en `admin-starter-kit/src/views/pos/CheckoutDialog.vue` con selección de Efectivo / QR, cálculo de vuelto en Bs. y descuento total
- [X] T163 [US2] Crear diálogo de impresión de ticket térmico `ReceiptPrintDialog.vue` en `admin-starter-kit/src/views/pos/ReceiptPrintDialog.vue` con estilos `@media print` para impresoras térmicas de 80mm
- [X] T164 [US2] Implementar pantalla principal del Punto de Venta (POS) `admin-starter-kit/src/pages/pos/index.vue` con catálogo visual de productos, buscador rápido por SKU/nombre, carrito interactivo y control de caja

**Checkpoint**: Punto de Venta ágil de mostrador operativo con descuento de inventario en tiempo real.

---

## Phase 6: User Story 3 — Comisiones, Anulación de Venta y Reportes para el Dueño (Prioridad: P3)

**Goal**: Calcular automáticamente la comisión del vendedor por venta, proveer anulación con reposición de inventario exclusiva para Dueño y reporte consolidado de comisiones por periodo.
**Independent Test**: Realizar una venta por un vendedor con 3% de comisión, verificar acreditación de comisión, probar reporte mensual del Dueño y anular la venta comprobando reposición de existencias.

- [X] T165 [US3] Implementar casos de uso `CancelSaleUseCase` (con reposición atómica de stock e invalidación de comisión) y `GenerateCommissionsReportUseCase` en `app/Application/Sale/`
- [X] T166 [US3] Implementar endpoints `POST /api/sales/{id}/cancel` y `GET /api/sales/commissions-report` en `SaleController` y registrar rutas en `routes/api.php`
- [X] T167 [US3] Escribir pruebas de integración para liquidación de comisiones y anulación con reposición de stock en `tests/Feature/Sale/SaleCommissionAndCancellationTest.php`
- [X] T168 [US3] Crear vista de historial de ventas y reporte de comisiones en `admin-starter-kit/src/pages/sales/index.vue` con filtros por fecha, vendedor y exportación a Excel

**Checkpoint**: Control administrativo y financiero de ventas y comisiones completado.

---

## Phase 7: Polish, Permisos y Verificación Final

**Purpose**: Asegurar permisos CASL para Dueño y Vendedor, accesos de navegación y verificación integral de la guía quickstart.

- [X] T169 Configurar reglas CASL para permisos de POS, Proformas, Caja y Reportes en `admin-starter-kit/src/plugins/casl/ability.js`
- [X] T170 Agregar enlaces de navegación ("Punto de Venta", "Proformas / Cotizaciones", "Ventas", "Caja Chica") en `admin-starter-kit/src/navigation/vertical/index.js` y registrar rutas en `admin-starter-kit/src/plugins/1.router/index.js`
- [X] T171 Ejecutar pruebas automatizadas completas de Laravel con `php artisan test` y verificar 100% de aserciones pasando
- [X] T172 Ejecutar validación end-to-end siguiendo los 5 escenarios de [`quickstart.md`](quickstart.md) en el entorno local

---

## Dependencies & Execution Order

### Phase Dependencies
- **Phase 1 (Setup & Migraciones)**: Inicia de inmediato.
- **Phase 2 (Foundational)**: Depende de Phase 1 (tablas creadas).
- **Phase 3 (User Story 5 - Caja)**: Depende de Phase 2 (prerrequisito obligatorio antes de abrir el POS).
- **Phase 4 (User Story 4 & 1 - Clientes y Proformas)**: Depende de Phase 2.
- **Phase 5 (User Story 2 - POS y Ventas)**: Depende de Phase 3 (requiere caja abierta) y Phase 4 (clientes/proformas).
- **Phase 6 (User Story 3 - Comisiones y Anulaciones)**: Depende de Phase 5.
- **Phase 7 (Polish)**: Depende de que todas las historias de usuario estén completadas.

### Parallel Opportunities
- Migraciones `T133`, `T134`, `T135` pueden desarrollarse en paralelo.
- Entidades y repositorios `T137`, `T139`, `T141`, `T143` pueden implementarse en paralelo.
- La User Story 1 (Proformas) puede desarrollarse en paralelo con la User Story 5 (Caja).
