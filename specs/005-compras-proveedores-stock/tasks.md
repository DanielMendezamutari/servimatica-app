# Tasks: Capítulo 5 — Compras y Recepción de Mercadería de Proveedores

**Input**: Design documents from `specs/005-compras-proveedores-stock/`
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/purchases-api.md](contracts/purchases-api.md), [quickstart.md](quickstart.md)

---

## Phase 1: Setup & Migraciones de Base de Datos

**Purpose**: Crear el esquema relacional en MySQL para proveedores, órdenes de compra y detalle de ítems recepcionados.

- [X] T173 Crear migración para la tabla `suppliers` con campos: `id`, `name (VARCHAR(150))`, `nit (VARCHAR(30) NULL INDEX)`, `contact_name (VARCHAR(100) NULL)`, `phone (VARCHAR(30) NULL INDEX)`, `email (VARCHAR(100) NULL)`, `city (VARCHAR(50) NULL)`, `address (VARCHAR(255) NULL)`, `is_active (BOOLEAN DEFAULT TRUE)`, `timestamps` en `database/migrations/2026_10_03_000011_create_suppliers_table.php`
- [X] T174 [P] Crear migración para las tablas `purchases` y `purchase_items` con campos: `purchase_number (VARCHAR(30) UNIQUE)`, `invoice_number (VARCHAR(50))`, `supplier_id (FK → suppliers)`, `user_id (FK → users)`, `purchase_date (DATE)`, `payment_condition (ENUM('contado','credito'))`, `payment_method (ENUM('efectivo','transferencia','otro'))`, `payment_status (ENUM('pagado','pendiente'))`, `due_date (DATE NULL)`, `subtotal`, `total_amount`, `status (ENUM('received','cancelled'))`, `cancellation_reason (VARCHAR(255) NULL)`, `cancelled_by (FK → users NULL)`, `cancelled_at (TIMESTAMP NULL)`, `notes (TEXT NULL)` en `database/migrations/2026_10_03_000012_create_purchases_and_items_tables.php`
- [X] T175 Ejecutar migraciones con `php artisan migrate` y verificar integridad de llaves foráneas

**Checkpoint**: Esquema relacional disponible en la base de datos para compras y proveedores.

---

## Phase 2: Foundational (Entidades de Dominio, Repositorios e Infraestructura)

**Purpose**: Construir las entidades de dominio puro y los repositorios Eloquent para proveedores y compras.

- [X] T176 [P] Implementar Entidad de Dominio `Supplier` y su contrato `SupplierRepositoryInterface` en `app/Domain/Supplier/`
- [X] T177 [P] Implementar Modelo Eloquent `SupplierModel` y Repositorio `EloquentSupplierRepository` en `app/Infrastructure/Persistence/Eloquent/`
- [X] T178 [P] Implementar Entidades de Dominio `Purchase`, `PurchaseItem` y su contrato `PurchaseRepositoryInterface` en `app/Domain/Purchase/`
- [X] T179 [P] Implementar Modelos Eloquent `PurchaseModel`, `PurchaseItemModel` y Repositorio `EloquentPurchaseRepository` en `app/Infrastructure/Persistence/Eloquent/`
- [X] T180 Registrar los bindings de `SupplierRepositoryInterface` y `PurchaseRepositoryInterface` en `app/Providers/AppServiceProvider.php`

**Checkpoint**: Capa de Dominio y Persistencia conectada y disponible en el contenedor de dependencias de Laravel.

---

## Phase 3: User Story 1 — Directorio y Gestión de Proveedores (Prioridad: P1)

**Goal**: Permitir al Dueño registrar, editar, listar y alternar el estado de proveedores mayoristas con bloqueo de acceso estricto para vendedores.
**Independent Test**: Registrar proveedor con NIT y WhatsApp, comprobar listado y verificar que una petición con token de vendedor retorne 403 Forbidden.

- [X] T181 [US1] Implementar casos de uso `ListSuppliersUseCase`, `CreateSupplierUseCase`, `UpdateSupplierUseCase` y `ToggleSupplierStatusUseCase` en `app/Application/Supplier/`
- [X] T182 [US1] Implementar `SupplierController` con endpoints `GET /api/suppliers`, `GET /api/suppliers/options`, `POST /api/suppliers`, `PUT /api/suppliers/{id}` y `PATCH /api/suppliers/{id}/toggle-status` en `app/Infrastructure/Http/Controllers/Api/SupplierController.php` y registrar rutas bajo middleware `owner` en `routes/api.php`
- [X] T183 [US1] Escribir prueba de integración para CRUD de proveedores y verificación de bloqueo 403 para vendedor en `tests/Feature/Supplier/SupplierManagementTest.php`
- [X] T184 [US1] Crear diálogo modal para registro/edición de proveedor `SupplierDialog.vue` en `admin-starter-kit/src/views/suppliers/SupplierDialog.vue`
- [X] T185 [US1] Crear vista de administración de proveedores en `admin-starter-kit/src/pages/suppliers/index.vue` con buscador, filtro de estado y tabla interactiva

**Checkpoint**: Directorio mayorista operativo y protegido para el Dueño.

---

## Phase 4: User Story 2 & 3 — Recepción de Compra, Stock Atómico y Actualización de Precios (Prioridad: P2 & P3)

**Goal**: Registrar compras con N° de factura, incremento atómico de stock con bloqueo pesimista (`lockForUpdate`), registro en `stock_movements`, actualización de costo del producto y nuevo precio de venta al público en Bs.
**Independent Test**: Comprar 5 unidades de un producto con stock 2 a costo Bs. 100 y nuevo precio de venta Bs. 150; verificar que el stock aumente a 7, el costo se actualice a 100 y el precio de venta a 150.

- [X] T186 [US2] Implementar caso de uso transaccional `ProcessPurchaseUseCase` con `DB::transaction()`, `lockForUpdate()`, incremento de `products.stock`, creación inmutable en `stock_movements` (tipo `in`), actualización de `products.cost_price` y nuevo `products.sale_price` en `app/Application/Purchase/ProcessPurchaseUseCase.php`
- [X] T187 [US2] Implementar endpoint `POST /api/purchases` en `app/Infrastructure/Http/Controllers/Api/PurchaseController.php` y registrar ruta bajo middleware `owner` en `routes/api.php`
- [X] T188 [US2] Escribir prueba de integración para recepción de compra, incremento de stock y auditoría en `tests/Feature/Purchase/PurchaseReceptionTest.php`
- [X] T189 [US3] Crear diálogo modal de alta rápida de producto `QuickProductDialog.vue` en `admin-starter-kit/src/views/purchases/QuickProductDialog.vue` para registrar productos nuevos al vuelo sin salir de la compra
- [X] T190 [US2] Implementar formulario interactivo de compra en `admin-starter-kit/src/pages/purchases/create.vue` con selector de proveedor, buscador de productos, cálculo automático de subtotales/totales, ajuste de precios de venta y alta rápida

**Checkpoint**: Reabastecimiento de inventario físico operativo con actualización simultánea de costos y precios.

---

## Phase 5: User Story 4 — Historial, Detalle, Reimpresión y Anulación de Compra (Prioridad: P4)

**Goal**: Consultar compras pasadas, ver detalle de ítems con costos históricos, imprimir Nota de Recepción formal y permitir la anulación segura restituyendo el stock sin saldos negativos.
**Independent Test**: Consultar compra recepcionada, anularla justificando motivo, verificar que el stock disminuya y comprobar que si el stock físico es menor al comprado se impida la anulación.

- [X] T191 [US4] Implementar casos de uso `ListPurchasesUseCase`, `GetPurchaseUseCase` y `CancelPurchaseUseCase` (con verificación estricta de stock disponible para devolución) en `app/Application/Purchase/`
- [X] T192 [US4] Implementar endpoints `GET /api/purchases`, `GET /api/purchases/{id}`, `POST /api/purchases/{id}/cancel` y `GET /api/purchases/{id}/receipt` en `PurchaseController` y registrar rutas en `routes/api.php`
- [X] T193 [US4] Crear plantilla Blade para la Nota de Recepción de Mercadería formal para impresión en `resources/views/purchases/receipt.blade.php`
- [X] T194 [US4] Escribir prueba de integración para anulación de compra con restitución de stock y rechazo por stock insuficiente en `tests/Feature/Purchase/PurchaseCancellationTest.php`
- [X] T195 [US4] Crear diálogo de detalle de compra `PurchaseDetailsDialog.vue` en `admin-starter-kit/src/views/purchases/PurchaseDetailsDialog.vue`
- [X] T196 [US4] Crear vista de historial de compras en `admin-starter-kit/src/pages/purchases/index.vue` con filtros por proveedor, fechas, estado y diálogo modal de anulación

**Checkpoint**: Trazabilidad completa y control administrativo de adquisiciones mayoristas.

---

## Phase 6: Polish, Permisos CASL, Navegación y Verificación Final

**Purpose**: Configurar permisos CASL exclusivos de Dueño, enlaces de navegación y verificación integral de la guía quickstart.

- [X] T197 Configurar reglas CASL para `Supplier` y `Purchase` exclusivas para `Role::Owner` en `app/Domain/User/User.php` y `admin-starter-kit/src/plugins/casl/ability.js`
- [X] T198 Agregar enlaces de navegación ("Proveedores" y "Compras") en `admin-starter-kit/src/navigation/vertical/index.js` y registrar rutas en `admin-starter-kit/src/plugins/1.router/index.js`
- [X] T199 Ejecutar pruebas automatizadas completas de Laravel con `php artisan test` y verificar 100% de aserciones pasando
- [X] T200 Ejecutar validación end-to-end siguiendo los 5 escenarios de [`quickstart.md`](quickstart.md) en el entorno local

---

## Dependencies & Execution Order

### Phase Dependencies
- **Phase 1 (Setup & Migraciones)**: Inicia de inmediato.
- **Phase 2 (Foundational)**: Depende de Phase 1.
- **Phase 3 (User Story 1 - Proveedores)**: Depende de Phase 2 (prerrequisito para asociar compras).
- **Phase 4 (User Story 2 & 3 - Recepción y Stock)**: Depende de Phase 3 (proveedores existentes).
- **Phase 5 (User Story 4 - Historial y Anulación)**: Depende de Phase 4 (compras registradas).
- **Phase 6 (Polish y Permisos)**: Depende de Phase 5.
