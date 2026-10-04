# Implementation Plan: Galería de Imágenes, Vitrina 360°/3D para TikTok Live y Enlace Seguro de Proformas

**Branch**: `014-galeria-imagenes-vitrina-360-proformas-seguras` | **Date**: 2026-10-04 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/014-galeria-imagenes-vitrina-360-proformas-seguras/spec.md`

## Summary

Dotar al sistema de:
1. Soporte de **carga de imágenes (foto de portada y galería multi-ángulo para giro 360°)** en la gestión de productos desde el panel administrativo (`AddProductDrawer.vue`).
2. **Protección y tokenización de proformas PDF** mediante `public_token` (UUID v4) eliminando la vulnerabilidad de enumeración por ID secuencial.
3. **Vitrina pública e-commerce para TikTok Live** (`/catalogo` o `/live`) optimizada para smartphones con efectos 3D Tilt y visor 360° táctil (swipe turntable) sin requerir autenticación, canalizando el cierre de venta directo hacia WhatsApp.

---

## Technical Context

**Language/Version**: PHP 8.2+ (Laravel 12) en backend, JavaScript / Vue 3 (Vite, Vuetify 3) en frontend.  
**Primary Dependencies**: Vuetify 3, Vue Router, Pinia, CASL. Cero dependencias 3D pesadas (visor 360° implementado nativo con eventos pointer/touch en Vue).  
**Storage**: MySQL / MariaDB (columnas `image_path`, `gallery_images` en `products`, y `public_token` en `quotes`). Archivos en `storage/app/public/products/`.  
**Testing**: PHPUnit / Pest (`php artisan test`).  
**Target Platform**: Servidor local / producción web; clientes en navegadores móviles (iOS Safari, Android Chrome).  
**Project Type**: Monorepo Laravel (Hexagonal Backend) + SPA Vue 3 (`admin-starter-kit/`).  
**Performance Goals**: Carga de vitrina pública < 1.2s en conexiones móviles 4G. Giro 360° a 60 FPS.  
**Constraints**: PROHIBIDO `php artisan serve` (usar host local `http://servimatica-app.test`). Prohibido exponer costos o proveedores en endpoints públicos.  

---

## Constitution Check

*GATE: Evaluado contra `AGENTS.md` y `.specify/memory/constitution.md`*

- [x] **Arquitectura Hexagonal:** Dominio independiente (`Product`, `Quote`), aplicación con UseCases, e infraestructura separada.
- [x] **No reinventar componentes:** Componente de subida de imágenes y cards adaptados del catálogo `admin-full-version/`.
- [x] **Capítulo por capítulo (Vertical Slice):** Se implementa el ciclo completo (Migraciones -> Servicios -> Endpoints -> Vistas Web -> Pruebas automatizadas) dejando la funcionalidad verificada antes de cerrar.
- [x] **KISS & YAGNI:** El visor 360° se implementa por secuencia de fotos táctil ligera sin introducir librerías 3D de 500KB innecesarias.

---

## Project Structure

### Documentation (this feature)

```text
specs/014-galeria-imagenes-vitrina-360-proformas-seguras/
├── spec.md              # Especificación funcional y criterios de éxito
├── plan.md              # Este plan de arquitectura
├── research.md          # Decisiones de diseño y evaluación técnica
├── data-model.md        # Esquema de datos y DTOs
├── quickstart.md        # Guía de verificación paso a paso
├── contracts/           # Contratos OpenAPI de endpoints públicos
└── tasks.md             # Tareas ordenadas (generadas con /speckit-tasks)
```

### Source Code

```text
# Backend (Laravel Hexagonal)
app/
├── Domain/
│   ├── Product/
│   │   ├── Product.php (actualizado con imagePath y galleryImages)
│   │   └── ProductRepositoryInterface.php
│   └── Quote/
│       ├── Quote.php (actualizado con publicToken)
│       └── QuoteRepositoryInterface.php
├── Application/
│   ├── Product/
│   │   └── UploadProductImageUseCase.php
│   ├── Quote/
│   │   ├── GetQuoteByPublicTokenUseCase.php
│   │   └── WhatsAppQuoteService.php (actualizado con enlaces con token)
│   └── PublicCatalog/
│       └── ListPublicCatalogUseCase.php
└── Infrastructure/
    ├── Http/
    │   └── Controllers/Api/
    │       ├── ProductController.php (actualizado con multipart image)
    │       ├── PublicCatalogController.php (nuevo endpoint público)
    │       └── PublicQuoteController.php (nuevo visor seguro)
    └── Persistence/
        └── Eloquent/
            ├── ProductModel.php
            └── QuoteModel.php

# Frontend (Vue 3 / Vuetify 3 en admin-starter-kit/)
admin-starter-kit/
└── src/
    ├── pages/
    │   ├── catalog/index.vue       # Vitrina pública e-commerce TikTok Live
    │   └── quotes/index.vue        # Actualizado con tokens seguros
    └── views/
        ├── catalog/
        │   ├── ProductCard3D.vue   # Tarjeta interactiva con efecto Tilt
        │   └── Product360Modal.vue # Modal con visor 360 táctil
        └── products/
            └── AddProductDrawer.vue # Subida de foto principal y galería 360
```

---

## Phases & Execution Order

### Phase 0: Research & Decisions
- Resueltas en `research.md` (Almacenamiento de fotos en disco público, rotación 360° nativa por swipe, y tokens UUID v4 no secuenciales).

### Phase 1: Design & Contracts
- Esquemas de datos en `data-model.md`.
- Contratos de API en `contracts/`.
- Escenarios de prueba en `quickstart.md`.

### Phase 2: Implementation (Vía `/speckit-tasks` y `/speckit-implement`)
- **Paso 1:** Migraciones de base de datos (`image_path`, `gallery_images` en `products`, y `public_token` en `quotes`).
- **Paso 2:** Dominio y Casos de Uso para gestión de imágenes y token de proformas.
- **Paso 3:** Subida y previsualización de fotos en `AddProductDrawer.vue`.
- **Paso 4:** Endpoint seguro de proforma pública y actualización del enlace en WhatsApp.
- **Paso 5:** Endpoint de catálogo público `GET /api/public/catalog` y página pública `/catalogo` con visor 360°.
- **Paso 6:** Pruebas automatizadas y verificación end-to-end.
