# Implementation Plan: Capítulo 2 — Catálogo de Productos e Inventario

**Branch**: `main` | **Date**: 2026-09-26 | **Spec**: [spec.md](file:///c:/xampp/htdocs/servimatica-app/specs/002-catalogo-inventario/spec.md)
**Input**: Feature specification from `specs/002-catalogo-inventario/spec.md`

---

## 1. Summary

Este capítulo añade el catálogo de productos e inventario a la tienda Servimática. Se implementan dos nuevas entidades de dominio (**Category** y **Product**) con una tercera de soporte (**StockMovement**), todas integradas a la arquitectura hexagonal existente del Capítulo 1.

**Backend (Laravel API REST):** Nuevos endpoints CRUD para categorías y productos, ajuste de stock con historial trazable, y filtrado por rol — el Dueño ve costos/márgenes, el Vendedor solo ve precios de venta y stock.

**Frontend Web (Vue 3 + Vuetify + CASL):** Módulo de categorías, módulo de productos con drawer para alta/edición, indicadores de stock bajo y "Agotado", vista de historial de movimientos, y permisos CASL diferenciados para ocultar datos financieros al Vendedor.

---

## 2. Technical Context

- **Lenguaje y Versiones:** PHP 8.2+ / Node.js 18+ / Vue 3.4+ (heredado del Capítulo 1).
- **Framework Backend:** Laravel 12.x con Arquitectura Hexagonal (DDD y principios SOLID).
- **Autenticación:** JWT stateless con Bearer tokens y TTL de 480 minutos (existente del Capítulo 1).
- **Frontend Framework:** Vue 3 (Composition API `<script setup>`), Vuetify 3.5+, Vite 5.x, Pinia 2.1+, `@casl/vue` 2.2+.
- **Almacenamiento / Base de Datos:** MySQL / MariaDB (vía XAMPP). Precios con precisión `DECIMAL(10,2)` para Bolivianos.
- **Pruebas:** PHPUnit / Pest (`php artisan test`).
- **Plataforma Objetivo:** Web local en `http://servimatica-app.test`.
- **Restricciones Mandatorias:**
  - Prohibido `php artisan serve`.
  - Reutilización estricta de componentes de `admin-full-version/` hacia `admin-starter-kit/`.
  - Cero dependencias exóticas.
  - Paginación del lado del servidor para listados extensos.

---

## 3. Constitution Check

*Evaluación de cumplimiento contra la Constitución v1.0.0 de Servimática App:*

| Principio Constitucional | Estado en el Plan | Justificación |
|---|---|---|
| **I. Simplicidad ante todo (KISS & YAGNI)** | ✅ Cumple | CRUD directo de categorías, productos y ajuste de stock. Sin abstracciones anticipadas ni patrones sobredimensionados. |
| **II. Idioma y Mercado (Bolivia / BOB)** | ✅ Cumple | Todos los precios en Bolivianos (Bs.), mensajes en español, formato numérico con 2 decimales. |
| **III. Cero Alcance Fantasma** | ✅ Cumple | Estrictamente limitado a categorías, productos, stock y historial. Sin módulo de proveedores, imágenes, proformas ni app móvil. |
| **IV. Verificable por persona no técnica** | ✅ Cumple | Verificable en pantalla en 2 minutos: crear categoría, registrar producto con precios, verificar como vendedor que no se ve el costo. |
| **V. Verdad Única de Datos en Tiempo Real** | ✅ Cumple | La misma tabla `products` y `stock_movements` alimenta las vistas de Dueño y Vendedor con filtros por rol. |
| **VI. Privacidad y Seguridad de Datos** | ✅ Cumple | Costos de compra y márgenes excluidos de la respuesta API para el rol `vendedor` y ocultos en el frontend con CASL. Doble barrera: backend + frontend. |

---

## 4. Project Structure

### Documentación del Capítulo
```text
specs/002-catalogo-inventario/
├── spec.md                  # Especificación clarificada (16 RF, 4 HU)
├── plan.md                  # Este plan de implementación
├── research.md              # Decisiones técnicas (Phase 0)
├── data-model.md            # Esquema de categories, products, stock_movements
├── contracts/               # Contratos de API REST
│   ├── categories-contract.md   # /api/categories CRUD
│   ├── products-contract.md     # /api/products CRUD + toggle-status
│   └── stock-contract.md        # /api/products/{id}/stock (ajuste + historial)
├── checklists/
│   └── requirements.md      # Lista de verificación de requisitos
└── quickstart.md            # Guía de prueba y ejecución paso a paso
```

### Estructura del Código Fuente
```text
c:/xampp/htdocs/servimatica-app/
├── admin-starter-kit/
│   └── src/
│       ├── pages/
│       │   ├── categories/index.vue       # Listado y gestión de categorías (Dueño)
│       │   ├── products/index.vue         # Listado de productos (Dueño: con costos; Vendedor: solo venta)
│       │   └── products/history/[id].vue  # Historial de movimientos de stock (Dueño)
│       ├── views/
│       │   ├── categories/AddCategoryDrawer.vue  # Drawer alta/edición categoría
│       │   ├── products/AddProductDrawer.vue      # Drawer alta/edición producto
│       │   └── products/StockAdjustDialog.vue     # Dialog ajuste de stock
│       ├── plugins/casl/                  # Reglas actualizadas con permisos de catálogo
│       └── navigation/                    # Menús actualizados: "Catálogo > Categorías | Productos"
│
├── app/                            # Backend Laravel (Arquitectura Hexagonal)
│   ├── Domain/
│   │   ├── User/                   # [Existente] Entidades y repositorio de User
│   │   ├── Category/               # [Nuevo] Entidad Category, CategoryRepositoryInterface
│   │   ├── Product/                # [Nuevo] Entidad Product, ProductRepositoryInterface
│   │   └── StockMovement/          # [Nuevo] Entidad StockMovement, StockMovementRepositoryInterface
│   ├── Application/
│   │   ├── User/                   # [Existente] Casos de uso de usuario
│   │   ├── Category/               # [Nuevo] CreateCategory, UpdateCategory, ListCategories, ToggleCategoryStatus, DeleteCategory
│   │   ├── Product/                # [Nuevo] CreateProduct, UpdateProduct, ListProducts, ToggleProductStatus
│   │   └── StockMovement/          # [Nuevo] AdjustStock, ListStockMovements
│   ├── Infrastructure/
│   │   ├── Persistence/Eloquent/   # [Nuevo] Modelos y Repositorios Eloquent para Category, Product, StockMovement
│   │   └── Http/Controllers/Api/
│   │       ├── AuthController.php  # [Existente]
│   │       ├── UserController.php  # [Existente]
│   │       ├── CategoryController.php  # [Nuevo]
│   │       └── ProductController.php   # [Nuevo] (incluye stock endpoints)
│   └── Providers/
│
├── routes/
│   └── api.php                     # Endpoints nuevos: /api/categories/*, /api/products/*
├── database/
│   ├── migrations/
│   │   ├── ...create_users_table   # [Existente]
│   │   ├── ...create_categories_table     # [Nuevo]
│   │   ├── ...create_products_table       # [Nuevo]
│   │   └── ...create_stock_movements_table # [Nuevo]
│   └── seeders/                    # Seeders opcionales de categorías de demostración
└── tests/Feature/
    ├── Auth/                       # [Existente]
    ├── User/                       # [Existente]
    ├── Category/                   # [Nuevo] CategoryManagementTest
    ├── Product/                    # [Nuevo] ProductManagementTest
    └── Stock/                      # [Nuevo] StockAdjustmentTest
```

**Structure Decision**: Se extiende la arquitectura hexagonal existente del Capítulo 1 con tres nuevos aggregates de dominio (`Category`, `Product`, `StockMovement`), manteniendo la misma convención de directorios y separación de capas.

---

## 5. Fases de Ejecución

1. **Fase Migraciones y Dominio:** Crear migraciones para `categories`, `products` y `stock_movements`. Implementar entidades de dominio, interfaces de repositorio y repositorios Eloquent.
2. **Fase Backend Categorías (HU5):** Casos de uso y controlador de categorías. Endpoints CRUD + toggle-status. Pruebas automatizadas.
3. **Fase Backend Productos (HU6):** Casos de uso y controlador de productos. CRUD con validación de SKU, cálculo de margen, filtrado por rol. Pruebas automatizadas.
4. **Fase Backend Stock (HU8):** Caso de uso de ajuste de stock con historial. Endpoint de ajuste y listado de movimientos. Pruebas automatizadas.
5. **Fase Frontend Categorías:** Vista de listado y drawer reutilizado de `admin-full-version/` para alta/edición de categorías.
6. **Fase Frontend Productos:** Vista de listado con columnas diferenciadas por rol (CASL), drawer para alta/edición, indicadores de stock bajo y agotado.
7. **Fase Frontend Catálogo Vendedor (HU7):** Vista de solo lectura para el Vendedor con búsqueda, filtros por categoría y ocultamiento de costos/márgenes.
8. **Fase Frontend Stock:** Diálogo de ajuste de stock y vista de historial con filtros por fecha y tipo.
9. **Fase Verificación:** Ejecutar la suite completa de pruebas y verificar los escenarios del [quickstart.md](file:///c:/xampp/htdocs/servimatica-app/specs/002-catalogo-inventario/quickstart.md).

---

## 6. Complexity Tracking

No se detectaron violaciones a la Constitución. La tabla no aplica.
