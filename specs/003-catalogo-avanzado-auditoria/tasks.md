# Tasks: Capítulo 3 — Catálogo Jerárquico Avanzado, Marcas, Condición y Auditoría

**Input**: Design documents from `specs/003-catalogo-avanzado-auditoria/`
**Prerequisites**: [plan.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/003-catalogo-avanzado-auditoria/plan.md), [spec.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/003-catalogo-avanzado-auditoria/spec.md), [research.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/003-catalogo-avanzado-auditoria/research.md), [data-model.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/003-catalogo-avanzado-auditoria/data-model.md), [contracts/](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/003-catalogo-avanzado-auditoria/contracts/)

---

## Phase 1: Setup & Migraciones de Base de Datos

**Purpose**: Instalar dependencias del generador de hojas de cálculo y crear las migraciones relacionales para todas las entidades del capítulo.

- [X] T077 Instalar dependencia de generación de hojas de cálculo `phpoffice/phpspreadsheet` vía composer en `/Applications/XAMPP/xamppfiles/htdocs/servimatica-app/composer.json`
- [X] T078 [P] Crear migración para agregar `parent_id` (`BIGINT UNSIGNED NULL FK → categories.id ON DELETE RESTRICT`) a la tabla `categories` en `database/migrations/2026_10_03_000001_add_parent_id_to_categories_table.php`
- [X] T079 [P] Crear migración para la tabla `brands` con campos: `id (BIGINT UNSIGNED PK)`, `name (VARCHAR(100) UNIQUE NOT NULL)`, `is_active (BOOLEAN DEFAULT TRUE)`, `timestamps` en `database/migrations/2026_10_03_000002_create_brands_table.php`
- [X] T080 [P] Crear migración para la tabla `product_models` con campos: `id (BIGINT UNSIGNED PK)`, `brand_id (BIGINT UNSIGNED FK → brands.id ON DELETE RESTRICT)`, `name (VARCHAR(150) NOT NULL INDEX)`, `notes (TEXT NULLABLE)`, `is_active (BOOLEAN DEFAULT TRUE)`, `timestamps`, e índice único `(brand_id, name)` en `database/migrations/2026_10_03_000003_create_product_models_table.php`
- [X] T081 [P] Crear migración para agregar campos avanzados a `products`: `subfamily_id (BIGINT UNSIGNED NULL FK → categories.id ON DELETE SET NULL)`, `brand_id (BIGINT UNSIGNED NULL FK → brands.id ON DELETE RESTRICT)`, `product_model_id (BIGINT UNSIGNED NULL FK → product_models.id ON DELETE RESTRICT)`, y `condition (ENUM('nuevo','open_box','usado','reacondicionado') DEFAULT 'nuevo' NOT NULL)` en `database/migrations/2026_10_03_000004_add_advanced_fields_to_products_table.php`
- [X] T082 [P] Crear migración para agregar perfil extendido a `users`: `ci (VARCHAR(30) NULL INDEX)`, `phone (VARCHAR(30) NULL)`, `address (VARCHAR(255) NULL)`, `gender (ENUM('masculino','femenino','otro') NULL)`, `sales_commission (DECIMAL(5,2) DEFAULT 0.00)`, `branch (VARCHAR(100) DEFAULT 'Casa Matriz')`, y `avatar (VARCHAR(255) NULL)` en `database/migrations/2026_10_03_000005_add_extended_profile_to_users_table.php`
- [X] T083 [P] Crear migración para la tabla `login_logs` con campos: `id (BIGINT UNSIGNED PK)`, `user_id (BIGINT UNSIGNED NULL FK → users.id ON DELETE SET NULL)`, `attempted_username (VARCHAR(100) NOT NULL INDEX)`, `ip_address (VARCHAR(45) NULL INDEX)`, `user_agent (TEXT NULL)`, `status (ENUM('success','failed_credentials','failed_inactive_user') NOT NULL INDEX)`, `created_at (TIMESTAMP INDEX)` en `database/migrations/2026_10_03_000006_create_login_logs_table.php`
- [X] T084 Ejecutar migraciones con `php artisan migrate` y verificar consistencia de tablas en MySQL

**Checkpoint**: Esquema de base de datos ampliado y disponible para todas las entidades.

---

## Phase 2: Foundational (Servicios e Infraestructura Compartida)

**Purpose**: Crear el servicio común de exportación Excel y las entidades/repositorios de dominio base requeridos por los módulos.

- [X] T085 Implementar servicio genérico de generación y streaming de planillas Excel `ExcelExportService` con estilos, anchos automáticos y formato en Bolivianos (`[$Bs.-80A] #,##0.00`) en `app/Infrastructure/Services/Excel/ExcelExportService.php`
- [X] T086 [P] Implementar Entidad de Dominio `Brand` y su contrato `BrandRepositoryInterface` en `app/Domain/Brand/`
- [X] T087 [P] Implementar Repositorio Eloquent `EloquentBrandRepository` en `app/Infrastructure/Persistence/Eloquent/EloquentBrandRepository.php`
- [X] T088 [P] Implementar Entidad de Dominio `ProductModel` y su contrato `ProductModelRepositoryInterface` en `app/Domain/ProductModel/`
- [X] T089 [P] Implementar Repositorio Eloquent `EloquentProductModelRepository` en `app/Infrastructure/Persistence/Eloquent/EloquentProductModelRepository.php`
- [X] T090 [P] Implementar Entidad de Dominio `LoginLog` y su contrato `LoginLogRepositoryInterface` en `app/Domain/Audit/`
- [X] T091 [P] Implementar Repositorio Eloquent `EloquentLoginLogRepository` en `app/Infrastructure/Persistence/Eloquent/EloquentLoginLogRepository.php`
- [X] T092 Registrar los bindings de `BrandRepositoryInterface`, `ProductModelRepositoryInterface` y `LoginLogRepositoryInterface` en `app/Providers/AppServiceProvider.php`

**Checkpoint**: Infraestructura base de repositorios y servicio Excel lista.

---

## Phase 3: User Story 1 — Categorías Jerárquicas y Subfamilias (Prioridad: P1) 🎯 MVP

**Goal**: Permitir al Dueño estructurar categorías principales (Familias) y subcategorías (Subfamilias) dependientes con prevención de orfandad y visualización en cascada.
**Independent Test**: Crear la categoría principal "Componentes", crearle la subfamilia "Tarjetas de Video", y verificar que la tabla y el selector muestran la jerarquía correctamente.

- [X] T093 [US1] Actualizar modelo Eloquent `Category` con relaciones `parent()` y `children()` en `app/Infrastructure/Persistence/Eloquent/CategoryModel.php`
- [X] T094 [US1] Actualizar casos de uso `CreateCategoryUseCase`, `UpdateCategoryUseCase`, `ListCategoriesUseCase` y `DeleteCategoryUseCase` para soportar `parent_id` y validar que no se pueda eliminar una categoría con subfamilias o productos asignados en `app/Application/Category/`
- [X] T095 [US1] Actualizar `CategoryController` para admitir el parámetro `?tree=true` y el campo `parent_id` en `app/Infrastructure/Http/Controllers/Api/CategoryController.php`
- [X] T096 [US1] Actualizar drawer `AddCategoryDrawer.vue` con selector de Categoría Padre (opcional para raíz, obligatorio elegir padre para subfamilia) en `admin-starter-kit/src/views/categories/AddCategoryDrawer.vue`
- [X] T097 [US1] Actualizar vista de categorías `admin-starter-kit/src/pages/categories/index.vue` para mostrar columnas de nivel/padre o estructura indentada con badges de "Familia" / "Subfamilia"

**Checkpoint**: MVP de taxonomía jerárquica de categorías completamente operativo.

---

## Phase 4: User Story 2 — Gestión de Marcas y Modelos con Creación al Vuelo (Prioridad: P2)

**Goal**: Permitir al Dueño gestionar marcas comerciales, consultar sus modelos asociados y crear modelos ágilmente al vuelo.
**Independent Test**: Registrar la marca "Logitech", asociarle un modelo "G502", y probar el endpoint de creación rápida al vuelo para un modelo no existente.

- [X] T098 [P] [US2] Implementar casos de uso `ListBrandsUseCase`, `CreateBrandUseCase`, `UpdateBrandUseCase` y `ToggleBrandStatusUseCase` en `app/Application/Brand/`
- [X] T099 [P] [US2] Implementar casos de uso `ListBrandModelsUseCase` y `QuickCreateProductModelUseCase` (busca por `brand_id` y `name`, si no existe lo crea de inmediato) en `app/Application/ProductModel/`
- [X] T100 [US2] Implementar `BrandController` con endpoints CRUD `GET /api/brands`, `POST /api/brands`, `PUT /api/brands/{id}`, `PATCH /api/brands/{id}/toggle-status`, y `GET /api/brands/{id}/models` en `app/Infrastructure/Http/Controllers/Api/BrandController.php`
- [X] T101 [US2] Implementar `ProductModelController` con endpoint `POST /api/product-models/quick-create` en `app/Infrastructure/Http/Controllers/Api/ProductModelController.php`
- [X] T102 [US2] Registrar rutas de `/api/brands` y `/api/product-models` en `routes/api.php`
- [X] T103 [US2] Crear vista de administración de marcas `admin-starter-kit/src/pages/brands/index.vue` con tabla, filtro de búsqueda, toggle de estado activo y botón para abrir modelos
- [X] T104 [US2] Crear drawer `AddBrandDrawer.vue` para alta y edición de marca en `admin-starter-kit/src/views/brands/AddBrandDrawer.vue`
- [X] T105 [US2] Crear diálogo `BrandModelsDialog.vue` para visualizar, agregar y editar los modelos de una marca seleccionada en `admin-starter-kit/src/views/brands/BrandModelsDialog.vue`
- [X] T106 [US2] Agregar entrada "Marcas" bajo el menú Catálogo en `admin-starter-kit/src/navigation/vertical/index.js` y registrar ruta en `admin-starter-kit/src/plugins/1.router/index.js`

**Checkpoint**: Módulo de Marcas y Modelos operativo y listo para alimentar la ficha de producto.

---

## Phase 5: User Story 3 — Ficha de Producto Avanzada, Condición y Exportación de Inventario (Prioridad: P3)

**Goal**: Permitir registrar productos con Marca, Modelo (con creación al vuelo), Subfamilia, Condición comercial (`Nuevo`, `Seminuevo/Open Box`, `Usado`, `Reacondicionado`), badges visuales y exportación completa a Excel (.xlsx) protegiendo costos frente al vendedor.
**Independent Test**: Crear un producto "Seminuevo / Open Box" seleccionando Marca y creando un Modelo al vuelo, y exportar el catálogo a Excel comprobando que el archivo contiene los datos correctos y oculta costos para vendedores.

- [X] T107 [US3] Actualizar entidad y modelo Eloquent `Product` con relaciones `brand()`, `productModel()`, `subfamily()` y accessor de condición en `app/Infrastructure/Persistence/Eloquent/Product.php`
- [X] T108 [US3] Actualizar casos de uso `CreateProductUseCase` y `UpdateProductUseCase` para validar y guardar `subfamily_id`, `brand_id`, `product_model_id` y `condition` en `app/Application/Product/`
- [X] T109 [US3] Actualizar caso de uso `ListProductsUseCase` para admitir filtros por `brand_id`, `condition` y `subfamily_id` en `app/Application/Product/ListProductsUseCase.php`
- [X] T110 [US3] Implementar caso de uso `ExportProductsToExcelUseCase` que utiliza `ExcelExportService` para generar la planilla de inventario omitiendo costos y márgenes si el usuario autenticado es `vendedor` en `app/Application/Product/ExportProductsToExcelUseCase.php`
- [X] T111 [US3] Actualizar `ProductController` con el endpoint `GET /api/products/export-excel` y los nuevos parámetros de filtro en `app/Infrastructure/Http/Controllers/Api/ProductController.php`
- [X] T112 [US3] Registrar ruta `GET /api/products/export-excel` en `routes/api.php` protegida con `jwt.auth`
- [X] T113 [US3] Actualizar drawer `AddProductDrawer.vue` incorporando: selector en cascada Categoría -> Subfamilia, selector de Marca, combobox de Modelo con opción de creación rápida al vuelo si no existe, y selector de Condición (`Nuevo`, `Seminuevo / Open Box`, `Usado`, `Reacondicionado`) en `admin-starter-kit/src/views/products/AddProductDrawer.vue`
- [X] T114 [US3] Actualizar vista de productos `admin-starter-kit/src/pages/products/index.vue` con: columna de condición con chips de colores semánticos, selectores de filtro por Marca y Condición en el encabezado, y botón de acción "Exportar a Excel" que descarga el archivo `.xlsx`
- [X] T115 [US3] Escribir prueba de integración para catálogo avanzado y exportación segura a Excel en `tests/Feature/Product/AdvancedCatalogTest.php`

**Checkpoint**: Catálogo con clasificación profunda, marcas, condición física y exportación a Excel 100% funcional.

---

## Phase 6: User Story 4 — Ficha de Usuario, Nómina en Excel y Auditoría de Inicios de Sesión (Prioridad: P4)

**Goal**: Permitir registrar usuarios con su ficha completa (distinguiendo obligatorios y opcionales con fotografía), exportar la nómina a Excel y registrar cronológicamente la auditoría de accesos.
**Independent Test**: Registrar un usuario con CI y teléfono, exportar la nómina a Excel, provocar un intento de login fallido y verificar que en el panel de auditoría aparece registrado con IP y fecha.

- [X] T116 [US4] Actualizar modelo Eloquent `User` con campos de perfil (`ci`, `phone`, `address`, `gender`, `sales_commission`, `branch`, `avatar`) en `app/Infrastructure/Persistence/Eloquent/User.php`
- [X] T117 [US4] Actualizar casos de uso `CreateUserUseCase` y `UpdateUserUseCase` con validación estricta: obligatorios (`ci`, `name`, `username`, `password`, `role`, `is_active`) y opcionales (`phone`, `address`, `gender`, `sales_commission`, `branch`, `avatar`), gestionando la carga de fotografía en `storage/app/public/avatars` en `app/Application/User/`
- [X] T118 [US4] Implementar caso de uso `ExportUsersToExcelUseCase` que genera la planilla de nómina con todas las columnas de perfil en `app/Application/User/ExportUsersToExcelUseCase.php`
- [X] T119 [US4] Actualizar `UserController` con endpoint `GET /api/users/export-excel` y soporte para `multipart/form-data` con subida de imagen en `app/Infrastructure/Http/Controllers/Api/UserController.php`
- [X] T120 [US4] Implementar caso de uso `RecordLoginAttemptUseCase` y `ListLoginLogsUseCase` en `app/Application/Audit/`
- [X] T121 [US4] Actualizar `AuthController@login` para invocar `RecordLoginAttemptUseCase` registrando de forma inmutable cada intento (éxito o fallo con credenciales/inactivo, IP y User-Agent) en `app/Infrastructure/Http/Controllers/Api/AuthController.php`
- [X] T122 [US4] Implementar `AuditController` con endpoint `GET /api/audit/logins` (paginado con filtros por fechas, usuario y resultado) exclusivo para Administrador en `app/Infrastructure/Http/Controllers/Api/AuditController.php`
- [X] T123 [US4] Registrar rutas de auditoría y exportación de usuarios en `routes/api.php`
- [X] T124 [US4] Actualizar drawer `AddUserDrawer.vue` con la disposición completa de campos de la imagen de referencia (asteriscos en obligatorios, inputs de contacto, selector de sexo, comisión %, y cargador de foto de perfil con preview) en `admin-starter-kit/src/views/users/AddUserDrawer.vue`
- [X] T125 [US4] Actualizar vista de usuarios `admin-starter-kit/src/pages/users/index.vue` con botón "Exportar Nómina a Excel" y visualización de avatar/iniciales y CI en la tabla
- [X] T126 [US4] Crear vista de auditoría `admin-starter-kit/src/pages/audit/logins.vue` con tabla paginada de eventos de acceso, chips de estado (verde = Exitoso, rojo = Fallido), filtros por fecha y usuario
- [X] T127 [US4] Agregar enlace "Auditoría de Accesos" en el menú de navegación en `admin-starter-kit/src/navigation/vertical/index.js` y registrar su ruta en `admin-starter-kit/src/plugins/1.router/index.js`
- [X] T128 [US4] Escribir pruebas de integración para ficha de usuario y registro de auditoría en `tests/Feature/Audit/LoginAuditTest.php` y `tests/Feature/User/UserProfileTest.php`

**Checkpoint**: Gestión integral del personal, exportación de nómina y auditoría de accesos completada.

---

## Phase 7: Polish, Permisos y Verificación Final

**Purpose**: Asegurar permisos CASL, enlaces de navegación y validación integral de la guía quickstart.

- [X] T129 Configurar reglas CASL para permisos de Marcas, Auditoría y Exportación Excel en `admin-starter-kit/src/plugins/casl/ability.js`
- [X] T130 Ejecutar pruebas automatizadas completas de Laravel con `php artisan test` y verificar 100% de aserciones pasando
- [X] T131 Ejecutar validación end-to-end siguiendo los 5 escenarios de [`quickstart.md`](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/003-catalogo-avanzado-auditoria/quickstart.md) en el entorno local

---

## Dependencies & Execution Order

### Phase Dependencies
- **Phase 1 (Setup)**: Sin dependencias, inicia de inmediato.
- **Phase 2 (Foundational)**: Depende de Phase 1 (migraciones ejecutadas).
- **Phase 3 (User Story 1)**: Depende de Phase 2.
- **Phase 4 (User Story 2)**: Depende de Phase 2.
- **Phase 5 (User Story 3)**: Depende de Phase 3 y Phase 4 (requiere jerarquías y marcas listas para asociar al producto).
- **Phase 6 (User Story 4)**: Depende de Phase 2.
- **Phase 7 (Polish)**: Depende de que todas las historias de usuario estén completadas.

### Parallel Opportunities
- Las migraciones `T078`, `T079`, `T080`, `T081`, `T082`, `T083` pueden crearse en paralelo.
- Las entidades de dominio y repositorios `T086`, `T088`, `T090` pueden implementarse en paralelo.
- La User Story 4 (Personal y Auditoría) puede desarrollarse en paralelo con las User Stories 1 y 2 (Catálogo y Marcas).
