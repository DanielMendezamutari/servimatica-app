# Tasks: 009-gestion-garantias-pos

## Phase 1: Setup & Database Schema

**Purpose**: Preparar la base de datos para almacenar los términos institucionales de garantía de la empresa.

- [X] T001 Crear migración de base de datos `database/migrations/2026_10_03_000018_add_warranty_terms_to_company_settings_table.php` agregando la columna `warranty_terms` (`text`, nullable) a la tabla `company_settings`.
- [X] T002 Ejecutar migración de base de datos con `php artisan migrate` y actualizar `CompanySettingSeeder.php` en `database/seeders/CompanySettingSeeder.php` con términos de garantía predeterminados.

---

## Phase 2: Foundational Backend & Domain Support

**Purpose**: Soporte completo en requests, entidades y repositorios backend para persistir y exponer `warranty_days` en productos y `warranty_terms` en empresa.

- [X] T003 [P] Actualizar `ProductRequest.php` en `app/Infrastructure/Http/Requests/ProductRequest.php` para normalizar y validar `warrantyDays` / `warranty_days` con reglas `['nullable', 'integer', 'min:0']`.
- [X] T004 [P] Actualizar `EloquentProductRepository.php` en `app/Infrastructure/Persistence/Eloquent/EloquentProductRepository.php` asegurando que el método `attributes()` incluya `'warranty_days' => max(0, (int)($data['warrantyDays'] ?? $data['warranty_days'] ?? 0))`.
- [X] T005 [P] Actualizar `CompanySettingModel.php` en `app/Infrastructure/Persistence/Eloquent/CompanySettingModel.php` añadiendo `'warranty_terms'` en `$fillable`.
- [X] T006 [P] Actualizar `UpdateCompanySettingRequest.php` en `app/Infrastructure/Http/Requests/UpdateCompanySettingRequest.php` permitiendo el campo `'warranty_terms'` (`nullable|string|max:5000`).
- [X] T007 Escribir prueba unitaria y de integración en `tests/Feature/Product/ProductWarrantyManagementTest.php` verificando la creación y actualización de productos con distintos plazos de garantía técnica.

**Checkpoint**: Backend listo y verificado con pruebas automáticas. Las interfaces de usuario pueden implementarse de forma independiente.

---

## Phase 3: User Story 1 - Configuración y Administración de Garantía en Catálogo de Productos (Priority: P1) 🎯 MVP

**Goal**: Permitir al Administrador/Dueño asignar plazos de garantía (0d, 15d, 30d, 90d, 180d, 365d, 730d o días personalizados) al crear y editar productos en el catálogo web.

**Independent Test**: Abrir `AddProductDrawer.vue`, seleccionar `1 año (365 días)` para un producto, guardar, recargar y constatar que el valor persiste en el catálogo y en la ficha del producto.

- [X] T008 [US1] Añadir `warrantyDays: 0` al modelo reactivo `form` en `admin-starter-kit/src/views/products/AddProductDrawer.vue` y poblarlo en `watch(() => props.product)` con `props.product.warranty_days ?? props.product.warrantyDays ?? 0`.
- [X] T009 [US1] Implementar en la plantilla de `admin-starter-kit/src/views/products/AddProductDrawer.vue` el componente de selección de garantía con opciones rápidas (Sin garantía, 15 días, 30 días, 90 días, 180 días, 365 días, 730 días) y selector condicional para días personalizados.
- [X] T010 [US1] Incluir la columna o badge de garantía técnica en la tabla de listado de productos en `admin-starter-kit/src/pages/products/index.vue`.

**Checkpoint**: MVP alcanzado. Los productos se crean y editan con su garantía técnica oficial desde el catálogo.

---

## Phase 4: User Story 2 - Aplicación Ergonómica y Edición Rápida en el Carrito del POS (Priority: P2)

**Goal**: Rediseñar la fila del carrito del POS para eliminar selectores estorbosos, precargar la garantía del catálogo y ofrecer un micro-modal con foco automático para pistolear o escribir número(s) de serie y ajuste excepcional de garantía.

**Independent Test**: En el POS, agregar un producto con 180 días de garantía; comprobar que la fila muestra el chip limpio `🛡️ 180 días`. Abrir el micro-modal, escanear el serial `SN-123456`, guardar y procesar la venta.

- [X] T011 [US2] Reemplazar los controles toscos fijos de garantía y serie en la fila del carrito de `admin-starter-kit/src/pages/pos/index.vue` por un chip compacto interactivo (`VChip`) que muestre el plazo actual y el estado de serie.
- [X] T012 [US2] Implementar en `admin-starter-kit/src/pages/pos/index.vue` el diálogo modal emergente `warrantyModal` con foco automático (`autofocus`) en el campo de número de serie (`serial_number`).
- [X] T013 [US2] Añadir soporte en el micro-modal de `admin-starter-kit/src/pages/pos/index.vue` para registrar múltiples números de serie separados por comas o saltos de línea cuando la cantidad en la línea sea mayor a 1.
- [X] T014 [US2] Garantizar que al agregar un producto al carrito en `addToCart()` de `admin-starter-kit/src/pages/pos/index.vue`, se inicialice `warranty_days` con el valor exacto del catálogo (`Number(product.warranty_days ?? product.warrantyDays ?? 0)`).

**Checkpoint**: Experiencia de usuario en mostrador rápida, limpia y ergonómica.

---

## Phase 5: User Story 3 - Respaldo Formal en Comprobante y Términos de Cobertura (Priority: P3)

**Goal**: Emitir tickets térmicos y notas de venta que desglosen con exactitud la fecha de expiración calculada de la garantía, el número de serie de los equipos y las cláusulas institucionales configuradas por el Dueño.

**Independent Test**: Configurar términos en `settings/company.vue`, realizar una venta con serie y verificar que el ticket en `ReceiptPrintDialog.vue` calcula la fecha de vencimiento y muestra las cláusulas al final.

- [X] T015 [US3] Agregar el campo de texto multi-línea "Términos y Condiciones de Garantía Técnica" en el formulario de configuración de empresa en `admin-starter-kit/src/pages/settings/company.vue`.
- [X] T016 [US3] Actualizar el diálogo de comprobante de venta `ReceiptPrintDialog.vue` en `admin-starter-kit/src/views/pos/ReceiptPrintDialog.vue` para mostrar claramente por cada producto con garantía: `Garantía: X días (Vence: DD/MM/AAAA)` y el `S/N` correspondiente.
- [X] T017 [US3] Incluir en el pie del ticket térmico en `admin-starter-kit/src/views/pos/ReceiptPrintDialog.vue` y en la plantilla Blade `resources/views/sales/receipt.blade.php` el bloque impreso de términos y condiciones institucionales de la empresa.

---

## Phase 6: Polish & Verification

**Purpose**: Verificación cruzada, calidad de código y pruebas de regresión.

- [X] T018 Ejecutar suite completa de pruebas automatizadas con `php artisan test` garantizando 0 errores (76 tests pasando al 100%).
- [X] T019 Ejecutar compilación del frontend con `pnpm --prefix admin-starter-kit run build` verificando ausencia de errores de sintaxis o empaquetado (build exitoso en 7.1s).
- [X] T020 Validar visualmente los 3 escenarios de la guía `quickstart.md` en el entorno local.

---

## Dependencies & Execution Order

- **Phase 1 (Setup)**: Sin dependencias, arranca de inmediato.
- **Phase 2 (Foundational)**: Depende de Phase 1. Bloquea las historias de usuario.
- **Phase 3 (User Story 1 - P1 MVP)**: Depende de Phase 2. Establece la base de datos de catálogo.
- **Phase 4 (User Story 2 - P2)**: Depende de Phase 2 y Phase 3.
- **Phase 5 (User Story 3 - P3)**: Depende de Phase 2 y Phase 4.
- **Phase 6 (Polish)**: Depende de todas las fases anteriores.
