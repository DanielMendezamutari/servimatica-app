# Implementation Plan: Capítulo 3 — Catálogo Jerárquico Avanzado, Marcas, Condición y Auditoría

**Branch**: `003-catalogo-avanzado-auditoria` | **Date**: 2026-10-03 | **Spec**: [spec.md](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/003-catalogo-avanzado-auditoria/spec.md)
**Input**: Feature specification from `specs/003-catalogo-avanzado-auditoria/spec.md`

---

## 1. Summary

Este capítulo amplía las capacidades comerciales y de seguridad operativa de Servimática App:
1. **Taxonomía Jerárquica:** Categorías organizadas en dos niveles (Categoría Principal / Familia y Subfamilia dependiente) con protección contra orfandad.
2. **Marcas y Modelos de Hardware:** Módulo centralizado de marcas comerciales con sus respectivos modelos y soporte de creación ágil al vuelo desde el formulario de producto.
3. **Ficha Avanzada y Condición Comercial:** Atributo mandatorio de condición (`Nuevo`, `Seminuevo / Open Box`, `Usado`, `Reacondicionado`) con badges visuales distintivos.
4. **Exportación de Inventario y Nómina a Excel (.xlsx):** Generación binaria nativa de hojas de cálculo con moneda en Bolivianos (`Bs.`), aplicando estricta privacidad de costos frente al rol Vendedor.
5. **Auditoría de Inicios de Sesión:** Registro inmutable de eventos de acceso (exitosos y fallidos) y vista de consulta para el Dueño.
6. **Ficha Completa de Usuario:** Soporte integral para datos de identidad (CI), contacto, sexo, comisión por ventas y avatar.

---

## 2. Technical Context

- **Lenguaje y Versiones:** PHP 8.2+ / Node.js 18+ / Vue 3.4+.
- **Framework Backend:** Laravel 12.x con Arquitectura Hexagonal (Domain, Application, Infrastructure).
- **Autenticación:** JWT con Bearer tokens (tymon/jwt-auth 2.3+).
- **Frontend Framework:** Vue 3 (Composition API `<script setup>`), Vuetify 3.5+, Vite 5.x, Pinia 2.1+, `@casl/vue` 2.2+.
- **Exportación Excel:** `phpoffice/phpspreadsheet` 5.x generando streams `.xlsx` nativos.
- **Almacenamiento / Base de Datos:** MySQL / MariaDB (XAMPP). Moneda con precisión `DECIMAL(10,2)` en Bolivianos (`Bs.`).
- **Pruebas:** PHPUnit / Pest (`php artisan test`).
- **Plataforma Objetivo:** Web local en Apache / MySQL XAMPP (`http://servimatica-app.test` o `http://127.0.0.1/servimatica-app/public`).
- **Restricciones Mandatorias:**
  - Prohibido `php artisan serve`.
  - Reutilización estricta de componentes de `admin-full-version/` hacia `admin-starter-kit/`.
  - Cero dependencias exóticas.

---

## 3. Constitution Check

*Evaluación de cumplimiento contra la Constitución v1.0.0 de Servimática App:*

| Principio Constitucional | Estado en el Plan | Justificación |
|---|---|---|
| **I. Simplicidad ante todo (KISS & YAGNI)** | ✅ Cumple | Jerarquía resuelta con adjacency list simple (`parent_id`), modelos vinculados por foreign key y generación directa de Excel sin microservicios externos. |
| **II. Idioma y Mercado (Bolivia / BOB)** | ✅ Cumple | Moneda estandarizada en Bolivianos (`Bs.`), términos comerciales adaptados a Bolivia (CI, Open Box, Seminuevo). |
| **III. Cero Alcance Fantasma** | ✅ Cumple | Acotado rigurosamente al catálogo jerárquico, marcas/modelos, condición, auditoría y exportaciones de este capítulo. Prohibido mezclar ventas/POS. |
| **IV. Verificable por persona no técnica** | ✅ Cumple | Verificable en menos de 5 minutos mediante la guía [`quickstart.md`](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/specs/003-catalogo-avanzado-auditoria/quickstart.md) abriendo el panel web y los archivos Excel descargados. |
| **V. Verdad Única de Datos en Tiempo Real** | ✅ Cumple | Base de datos centralizada en MySQL que alimenta directamente a la API REST para web y futura app móvil. |
| **VI. Privacidad y Seguridad de Datos** | ✅ Cumple | Costos y márgenes eliminados del payload API y del archivo Excel descargado cuando el usuario autenticado tiene el rol Vendedor. Doble control: backend + frontend. |

---

## 4. Project Structure

### Documentación del Capítulo
```text
specs/003-catalogo-avanzado-auditoria/
├── spec.md                     # Especificación clarificada (4 HU, RF-026 a RF-042)
├── plan.md                     # Este plan de arquitectura e implementación
├── research.md                 # Decisiones técnicas y alternativas evaluadas (Phase 0)
├── data-model.md               # Modelo relacional y cambios de esquema (Phase 1)
├── contracts/                  # Contratos de API REST (Phase 1)
│   ├── brands-models-contract.md
│   ├── catalog-advanced-contract.md
│   ├── audit-contract.md
│   └── user-profile-contract.md
├── checklists/
│   └── requirements.md         # Checklist de calidad de requisitos
└── quickstart.md               # Guía de verificación paso a paso
```

### Estructura de Código Fuente (Árbol Concreto)

```text
/Applications/XAMPP/xamppfiles/htdocs/servimatica-app/
├── admin-starter-kit/
│   └── src/
│       ├── pages/
│       │   ├── categories/index.vue       # Listado jerárquico de categorías y subfamilias
│       │   ├── brands/index.vue           # [Nuevo] Gestión de Marcas y Modelos
│       │   ├── products/index.vue         # Catálogo avanzado con filtros de marca, condición y botón Excel
│       │   ├── users/index.vue            # Gestión de personal con botón Exportar Nómina a Excel
│       │   └── audit/logins.vue           # [Nuevo] Historial de auditoría de inicios de sesión
│       ├── views/
│       │   ├── categories/AddCategoryDrawer.vue  # Drawer con selector de categoría padre
│       │   ├── brands/AddBrandDrawer.vue         # [Nuevo] Drawer alta/edición de marca
│       │   ├── brands/BrandModelsDialog.vue      # [Nuevo] Diálogo de gestión de modelos por marca
│       │   ├── products/AddProductDrawer.vue     # Selector en cascada, creación al vuelo de modelos y condición
│       │   └── users/AddUserDrawer.vue           # Ficha completa con campos obligatorios y opcionales
│       ├── navigation/vertical/                  # Enlaces de menú actualizados (Marcas, Auditoría)
│       └── plugins/casl/                         # Reglas CASL actualizadas
│
├── app/
│   ├── Domain/
│   │   ├── Brand/                        # [Nuevo] Entidad Brand, Repositorio Interface
│   │   ├── ProductModel/                 # [Nuevo] Entidad ProductModel, Repositorio Interface
│   │   ├── Audit/                        # [Nuevo] Entidad LoginLog, Repositorio Interface
│   │   ├── Category/                     # [Actualizado] Jerarquía y relaciones de Category
│   │   ├── Product/                      # [Actualizado] Atributos de condición, marca y modelo
│   │   └── User/                         # [Actualizado] Ficha extendida de usuario
│   ├── Application/
│   │   ├── Brand/                        # Casos de uso CRUD de marcas
│   │   ├── ProductModel/                 # Casos de uso y QuickCreate de modelos
│   │   ├── Audit/                        # RecordLoginAttempt, ListLoginLogs
│   │   ├── Export/                       # [Nuevo] ExportProductsToExcel, ExportUsersToExcel
│   │   ├── Category/                     # ListHierarchicalCategories
│   │   ├── Product/                      # Actualizado con filtros avanzados
│   │   └── User/                         # Actualizado con ficha completa y subida de avatar
│   ├── Infrastructure/
│   │   ├── Persistence/Eloquent/         # Modelos y Repositorios Eloquent
│   │   ├── Services/Excel/               # PhpSpreadsheet Generator Service
│   │   └── Http/Controllers/Api/
│   │       ├── BrandController.php       # [Nuevo]
│   │       ├── ProductModelController.php# [Nuevo]
│   │       ├── AuditController.php       # [Nuevo]
│   │       ├── CategoryController.php    # [Actualizado con soporte de jerarquía]
│   │       ├── ProductController.php     # [Actualizado con filtros y exportación]
│   │       ├── UserController.php        # [Actualizado con ficha completa y exportación]
│   │       └── AuthController.php        # [Actualizado con auditoría de login]
│
├── database/
│   └── migrations/
│       ├── 2026_10_03_000001_add_parent_id_to_categories_table.php
│       ├── 2026_10_03_000002_create_brands_table.php
│       ├── 2026_10_03_000003_create_product_models_table.php
│       ├── 2026_10_03_000004_add_advanced_fields_to_products_table.php
│       ├── 2026_10_03_000005_add_extended_profile_to_users_table.php
│       └── 2026_10_03_000006_create_login_logs_table.php
│
└── tests/Feature/
    ├── Brand/BrandManagementTest.php
    ├── Product/AdvancedCatalogTest.php
    ├── User/UserProfileTest.php
    └── Audit/LoginAuditTest.php
```

---

## 5. Complexity Tracking

> **Violaciones Constitucionales:** 0 (Cero). Todo el diseño se apega estrictamente a los 6 principios de la Constitución.

| Componente | Justificación Técnica | Alternativa Descartada |
|---|---|---|
| Generador de Excel | PhpSpreadsheet genera binarios nativos `.xlsx` con tipado exacto y formato de moneda boliviana. | CSV crudo descartado por problemas de codificación de caracteres acentuados y falta de formato en navegadores hispanos. |
