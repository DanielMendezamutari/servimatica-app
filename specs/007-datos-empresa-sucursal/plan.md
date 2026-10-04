# Implementation Plan: Configuración Centralizada de Mi Empresa, Sucursal y Comprobantes Dinámicos

**Branch**: `007-datos-empresa-sucursal` | **Date**: 2026-10-03 | **Spec**: [spec.md](spec.md)

**Input**: Especificación formal de requerimientos para eliminar datos institucionales hardcodeados y crear el módulo administrativo "Mi Empresa" para parametrizar nombre comercial, sucursal, ciudad, teléfonos, logo y políticas de comprobantes.

---

## Summary

Implementar una entidad y tabla singleton `company_settings` en Laravel, protegida bajo arquitectura hexagonal, con casos de uso para consultar y actualizar la información de la empresa (incluyendo subida de logotipo). Conectar un View Composer / Provider para inyectar automáticamente `$company` en todas las plantillas Blade de comprobantes (`quotes/print`, `sales/receipt`, `purchases/receipt`, `cash_shifts/receipt`), erradicando cualquier texto fijo. En el frontend Vue/Vuetify, construir la página administrativa `pages/settings/company.vue` protegida por CASL para el rol Dueño.

---

## Technical Context

- **Backend**: PHP 8.2+, Laravel 11.x, Arquitectura Hexagonal (Domain, Application, Infrastructure).
- **Base de Datos**: MySQL/MariaDB (vía XAMPP/Laragon) y SQLite en tests de integración.
- **Frontend**: Vue 3, Vite, Vuetify 3, CASL, Remix Icons (`ri-*`).
- **Autenticación**: JWT (tymon/jwt-auth) con rol estricto `dueno` (`owner`).
- **Storage**: Disco `public` (`storage/app/public/company`) para almacenamiento de logotipos con enlace simbólico.
- **Testing**: PHPUnit / Pest para pruebas de Feature y Unitarias en backend.

---

## Constitution Check

*GATE: Validación contra `.specify/memory/constitution.md`*

| Principio | Estado | Justificación |
|---|:---:|---|
| **I. Simplicidad (KISS & YAGNI)** | ✅ PASS | Diseño singleton (1 fila activa en tabla), evitando sobreingeniería multi-tenant o tablas key-value innecesarias. |
| **II. Idioma y Moneda (BOB / Bs.)** | ✅ PASS | Español claro, Bolivianos (`Bs.`), validado para ciudades y prefijos telefónicos bolivianos. |
| **III. Cero Alcance Fantasma** | ✅ PASS | Solo cubre la parametrización de la empresa y la inyección en los comprobantes existentes. |
| **IV. Verificable por No Técnico** | ✅ PASS | Documentado en `quickstart.md` para verificación visual en 2 minutos cambiando la ciudad y abriendo una proforma. |
| **V. Verdad Única de Datos** | ✅ PASS | Una sola fuente de verdad para web, proformas, tickets e integraciones móviles. |
| **VI. Privacidad y Seguridad** | ✅ PASS | Endpoint administrativo restringido al Dueño; endpoint público expone únicamente datos de marca sin secretos. |

---

## Project Structure

### Documentation (this feature)
```text
specs/007-datos-empresa-sucursal/
├── spec.md              # Especificación funcional
├── research.md          # Decisiones de arquitectura y almacenamiento
├── data-model.md        # Esquema de persistencia y entidad de dominio
├── contracts/
│   └── company-settings.yaml # Contrato OpenAPI
├── quickstart.md        # Guía de verificación en 2 minutos
├── checklists/
│   └── requirements.md # Checklist de calidad de requisitos
└── plan.md              # Este plan de implementación
```

### Source Code

```text
app/
├── Domain/Company/
│   ├── CompanySetting.php
│   └── CompanySettingRepositoryInterface.php
├── Application/Company/
│   ├── GetCompanySettingUseCase.php
│   ├── UpdateCompanySettingUseCase.php
│   └── GetPublicCompanyInfoUseCase.php
├── Infrastructure/
│   ├── Persistence/Eloquent/
│   │   ├── CompanySettingModel.php
│   │   └── EloquentCompanySettingRepository.php
│   ├── Http/Controllers/Api/
│   │   └── CompanySettingController.php
│   ├── Http/Requests/
│   │   └── UpdateCompanySettingRequest.php
│   └── View/Composers/
│       └── CompanyReceiptComposer.php

database/
├── migrations/
│   └── 2026_10_03_000015_create_company_settings_table.php
└── seeders/
    └── CompanySettingSeeder.php

resources/views/
├── quotes/print.blade.php      # Proforma formal Carta dinámica
├── sales/receipt.blade.php       # Ticket térmico 80mm dinámico
├── purchases/receipt.blade.php   # Comprobante de compras dinámico
└── cash_shifts/receipt.blade.php # Ticket de arqueo dinámico

admin-starter-kit/
└── src/
    ├── pages/settings/
    │   └── company.vue          # Vista de formulario "Mi Empresa"
    └── navigation/vertical/
        └── index.js             # Entrada en menú Ajustes > Mi Empresa
```

---

## Fases de Implementación Planificadas

1. **Fase 1: Setup y Persistencia Backend (Hexagonal)**: Migración `company_settings`, entidad `CompanySetting`, contrato `CompanySettingRepositoryInterface`, modelo Eloquent `CompanySettingModel`, repositorio `EloquentCompanySettingRepository` y seeder `CompanySettingSeeder`.
2. **Fase 2: Casos de Uso y API REST**: Casos de uso (`GetCompanySettingUseCase`, `UpdateCompanySettingUseCase` con manejo de logo, `GetPublicCompanyInfoUseCase`), request validator, controlador `CompanySettingController` y rutas en `routes/api.php` (`/api/company-settings`, `/api/company-settings/public`).
3. **Fase 3: Inyección Dinámica en Vistas Blade**: Configuración de View Composer / Provider o inyección de `$company` en `quotes/print.blade.php`, `sales/receipt.blade.php`, `purchases/receipt.blade.php` y `cash_shifts/receipt.blade.php`, purgando todo texto quemado.
4. **Fase 4: Frontend Web "Mi Empresa" (Vuetify 3)**: Crear página [pages/settings/company.vue](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/admin-starter-kit/src/pages/settings/company.vue) con pestañas, subida/preview de logo y vinculación al menú lateral con protección CASL (`action: 'manage', subject: 'all'`).
5. **Fase 5: Pruebas y Control de Calidad**: Test de integración en `tests/Feature/Company/CompanySettingManagementTest.php` comprobando persistencia, validaciones, restricción de acceso a vendedores, build de frontend y suite completa en verde.
