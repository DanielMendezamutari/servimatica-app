# Implementation Plan: Gestión Integral de Clientes (CRM Comercial y Fidelización)

**Feature Branch**: `012-gestion-clientes-crm`  
**Spec**: `specs/012-gestion-clientes-crm/spec.md`  
**Status**: Ready for Tasks  

---

## 1. Technical Context

- **Backend**:
  - Laravel 12 con Arquitectura Hexagonal y DDD.
  - Entidad `Client` en `app/Domain/Client/`.
  - Migración `add_crm_fields_to_clients_table` (`client_type`, `city`, `notes`).
  - Casos de Uso en `app/Application/Client/`:
    - `ListClientsUseCase` (actualizado con filtros y métricas agregadas).
    - `CreateClientUseCase` (actualizado con los nuevos campos).
    - `UpdateClientUseCase` (nuevo).
    - `GetClientDetailUseCase` (nuevo, con cálculo de estadísticas 360°).
    - `GetClientWarrantiesUseCase` (nuevo, cálculo de vigencia y seriales).
    - `ExportClientsToExcelUseCase` (nuevo, generación de CSV estructurado).
  - Repositorio `EloquentClientRepository` en `app/Infrastructure/Persistence/Eloquent/`.
  - Controlador `ClientController` en `app/Infrastructure/Http/Controllers/Api/`.

- **Frontend**:
  - Vue 3 + Vuetify 3 + CASL en `admin-starter-kit/`.
  - Página principal: `admin-starter-kit/src/pages/clients/index.vue` (Directorio, búsqueda predictiva, drawer de creación/edición, enlace WhatsApp).
  - Drawer reusable: `admin-starter-kit/src/views/clients/AddEditClientDrawer.vue`.
  - Página Ficha 360°: `admin-starter-kit/src/pages/clients/[id].vue` (KPIs del cliente, pestañas de Ventas, Cotizaciones y Garantías activas).
  - Navegación lateral: `admin-starter-kit/src/navigation/vertical/index.js` (Acceso directo a Clientes en el grupo "Operaciones").

- **Seguridad & Permisos (CASL)**:
  - `vendedor` y `dueno`: pueden listar, buscar, registrar, editar y consultar ficha 360° de clientes.
  - `dueno`: tiene permiso exclusivo para exportar la base de clientes y conmutar estado activo/inactivo.

---

## 2. Constitution Alignment

- **Principio I (KISS & YAGNI)**: Sin dependencias de terceros; cálculo en vivo de estadísticas a través de consultas agregadas eficientes; reutilización de tablas existentes (`sales`, `sale_items`, `quotes`).
- **Principio II (Bolivia / BOB)**: Celular con formato +591, ciudades de Bolivia predeterminadas (Trinidad, Santa Cruz, La Paz, etc.), montos en `Bs.`.
- **Principio VI (Privacidad & Seguridad)**: La exportación masiva del directorio comercial queda restringida a administradores mediante middleware `auth:api` y verificación de rol.

---

## 3. Delivery Phases

- **Fase 1**: Migración de base de datos para campos CRM (`client_type`, `city`, `notes`).
- **Fase 2**: Actualización de dominio, repositorio y casos de uso backend + pruebas de integración.
- **Fase 3**: Interfaz del Directorio de Clientes (`/clients`) con búsqueda, drawer y enlace WhatsApp (US1 - MVP).
- **Fase 4**: Ficha 360° del Cliente (`/clients/:id`) con historial de ventas, cotizaciones y garantías vigentes (US2).
- **Fase 5**: Exportación segura a CSV/Excel y restricción por roles (US3).
- **Fase 6**: Incorporación en la barra lateral y verificación con `php artisan test` y `pnpm run build`.
