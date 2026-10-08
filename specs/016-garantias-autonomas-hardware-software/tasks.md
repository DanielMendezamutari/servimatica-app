# Tasks: 016-garantias-autonomas-hardware-software

**Input**: Design documents from `specs/016-garantias-autonomas-hardware-software/`  
**Prerequisites**: [plan.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/016-garantias-autonomas-hardware-software/plan.md), [spec.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/016-garantias-autonomas-hardware-software/spec.md), [research.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/016-garantias-autonomas-hardware-software/research.md), [data-model.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/016-garantias-autonomas-hardware-software/data-model.md), [contracts/warranty-contracts.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/016-garantias-autonomas-hardware-software/contracts/warranty-contracts.md)

---

## Phase 1: Setup & Database Schema

**Purpose**: Preparar la estructura relacional de la base de datos para almacenar garantías autónomas de hardware y software tanto en productos como en el detalle inmutable de ventas.

- [x] T001 Crear migración de base de datos `database/migrations/2026_10_08_000001_add_autonomous_hardware_software_warranties_to_tables.php` agregando `warranty_hardware_days` y `warranty_software_days` en `products`, y `warranty_hardware_days`, `warranty_hardware_expires_at`, `warranty_software_days`, `warranty_software_expires_at` en `sale_items`.
- [x] T002 Actualizar seeders en `database/seeders/ClientDeliverySeeder.php` y `database/seeders/DemoStoreSeeder.php` asignando garantías duales de ejemplo (laptops con hardware y software, periféricos con solo hardware).

---

## Phase 2: Foundational Backend & Domain Support

**Purpose**: Soporte completo en entidades Eloquent, validaciones FormRequest, casos de uso y cálculo automático de vencimientos en la API REST de Laravel.

- [x] T003 [P] Actualizar modelos Eloquent `app/Infrastructure/Persistence/Eloquent/ProductModel.php` y `app/Infrastructure/Persistence/Eloquent/SaleItemModel.php` añadiendo las columnas de garantías de hardware y software a `$fillable` y `$casts`.
- [x] T004 [P] Actualizar validaciones en `app/Infrastructure/Http/Requests/ProductRequest.php` para normalizar y validar `warranty_hardware_days` y `warranty_software_days` con reglas `['nullable', 'integer', 'min:0']`.
- [x] T005 [P] Actualizar mapeo y persistencia en `app/Infrastructure/Persistence/Eloquent/EloquentProductRepository.php` asegurando que persista `warranty_hardware_days` y `warranty_software_days` (y sincronice `warranty_days` como fallback).
- [x] T006 [P] Actualizar el procesamiento de ventas en `app/Infrastructure/Http/Controllers/Api/SaleController.php` (o caso de uso `CreateSaleUseCase`) para calcular automáticamente `warranty_hardware_expires_at` y `warranty_software_expires_at` a partir de la fecha de la venta.
- [x] T007 [P] Actualizar el endpoint de verificación de garantía `/sales/{id}/warranty-check` en `app/Infrastructure/Http/Controllers/Api/SaleController.php` para retornar el estado dual de cobertura (`active`, `expired`, `none`) para hardware y software por separado.
- [x] T008 Escribir prueba automatizada en `tests/Feature/Warranty/AutonomousHardwareSoftwareWarrantyTest.php` validando la persistencia dual, cálculo de fechas de vencimiento y respuesta del endpoint de verificación.

**Checkpoint**: Backend listo, persistente y cubierto por pruebas automatizadas.

---

## Phase 3: User Story 1 - Parametrización Dual en Catálogo de Productos (Priority: P1) 🎯 MVP

**Goal**: Permitir al Administrador/Dueño configurar y editar de forma independiente la Garantía de Hardware y la Garantía de Software para cada producto desde el catálogo web.

**Independent Test**: Abrir `AddProductDrawer.vue`, seleccionar 2 años (730d) de Hardware y 3 meses (90d) de Software para una laptop, guardar y constatar que persisten ambos valores y se muestran en la tabla de productos.

- [x] T009 [US1] Actualizar el formulario reactivo y la plantilla en `admin-starter-kit/src/views/products/AddProductDrawer.vue` incorporando dos selectores independientes (Garantía de Hardware y Garantía de Software) con presets (0d, 15d, 30d, 90d, 180d, 365d, 730d y personalizado).
- [x] T010 [US1] Actualizar la columna "Garantía" en la tabla del catálogo en `admin-starter-kit/src/pages/products/index.vue` mostrando dos micro-chips compactos (`🛡️ HW: 1a` y `💻 SW: 3m`) o un solo chip neutro `Sin garantía` si ambos son 0.

**Checkpoint**: MVP alcanzado. Los productos del catálogo poseen garantías físicas y lógicas completamente autónomas.

---

## Phase 4: User Story 2 - Carrito del POS y Modal de Ajuste Autónomo (Priority: P2)

**Goal**: Precargar automáticamente ambas garantías en el carrito del POS, mostrando distintivos limpios y permitiendo al vendedor ajustar de forma independiente Hardware o Software y capturar el número de serie en un micro-modal enfocado.

**Independent Test**: En el POS, añadir una laptop al carrito, verificar los micro-chips duales, abrir el modal de garantía, ajustar excepcionalmente el software a 180 días, ingresar el número de serie y cobrar.

- [x] T011 [US2] Actualizar `addToCart()` en `admin-starter-kit/src/pages/pos/index.vue` para precargar `warranty_hardware_days` y `warranty_software_days` de cada ítem desde el catálogo.
- [x] T012 [US2] Actualizar la fila del carrito en `admin-starter-kit/src/pages/pos/index.vue` presentando distintivos compactos para hardware y software.
- [x] T013 [US2] Actualizar el diálogo modal emergente `warrantyModal` en `admin-starter-kit/src/pages/pos/index.vue` para incluir selectores independientes de días de Hardware y días de Software manteniendo el campo de número(s) de serie enfocado.
- [x] T014 [US2] Actualizar el envío de datos de venta en `admin-starter-kit/src/views/pos/CheckoutDialog.vue` para enviar `warranty_hardware_days` y `warranty_software_days` por cada producto del carrito.

**Checkpoint**: Experiencia de cobro en mostrador fluida y ergonómica con control total de ambas garantías.

---

## Phase 5: User Story 3 - Desglose en Comprobantes y Tickets Térmicos (Priority: P3)

**Goal**: Emitir tickets térmicos y comprobantes de venta impresos y digitales que desglosen de forma transparente la fecha de expiración de cada garantía y el S/N.

**Independent Test**: Imprimir o previsualizar el ticket térmico de una venta con garantías y corroborar que se desglosan las dos líneas de vencimiento independientes con las políticas de la empresa al pie.

- [x] T015 [US3] Actualizar la plantilla de vista previa de ticket en `admin-starter-kit/src/views/sales/ReceiptPrintDialog.vue` para desglosar `🛡️ G. Hardware: X días (Vence: DD/MM/AAAA)` y `💻 G. Software: Y días (Vence: DD/MM/AAAA)`.
- [x] T016 [US3] Actualizar la plantilla de impresión de ticket en el backend en `routes/web.php` (o controlador de ticket térmico) para reflejar las dos líneas de garantía calculadas.

**Checkpoint**: Comprobante de venta con total certeza jurídica y técnica entregable al cliente.

---

## Phase 6: User Story 4 - Diálogo de Verificación Postventa (Priority: P4)

**Goal**: Proveer al servicio técnico y mostrador un diálogo que dictamine visualmente el estado de vigencia independiente de hardware y software para cada ítem vendido.

**Independent Test**: Consultar una venta con hardware vigente y software vencido en `WarrantyCheckDialog.vue` y comprobar que muestra el badge verde de hardware y rojo de software.

- [x] T017 [US4] Actualizar `admin-starter-kit/src/views/sales/WarrantyCheckDialog.vue` para presentar dos tarjetas o columnas de cobertura: Hardware (Vigente / Vencida / Días restantes) y Software (Vigente / Vencida / Días restantes).

**Checkpoint**: Taller de servicio técnico capacitado para dictaminar garantías de inmediato.

---

## Phase 7: Polish & Verification

**Purpose**: Verificación de compilación frontend, pruebas unitarias de backend y validación de extremo a extremo.

- [x] T018 Verificar que la compilación de producción del frontend se ejecute sin errores con `pnpm --prefix admin-starter-kit run build`.
- [x] T019 Ejecutar la suite de pruebas automatizadas de garantías para validar que todos los tests pasen exitosamente.
