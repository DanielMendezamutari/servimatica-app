# Implementation Plan: Capítulo 6 — Gestión Centralizada de Formas de Pago, Cuentas Bancarias y Filtros de Transacciones

**Branch**: `006-gestion-formas-pago` | **Date**: 2026-10-03 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/006-gestion-formas-pago/spec.md`

---

## Summary

Implementar la administración centralizada de formas de pago y cuentas bancarias para Servimática:
1. Panel exclusivo para el Dueño para parametrizar cuentas bancarias, billeteras móviles (Tigo Money, Yape) y efectivo, con soporte para subir imagen de código QR de cobro oficial y exigir número de transacción/referencia.
2. Integración dinámica en el Punto de Venta (POS) en `CheckoutDialog.vue`, proyectando el QR oficial en pantalla para que el cliente lo escanee en mostrador y capturando la referencia bancaria.
3. Liquidación dinámica en el registro de compras mayoristas a proveedores (`purchases/create.vue`).
4. Conciliación transparente en el Cierre y Arqueo de Caja Chica, separando el efectivo físico esperado en gaveta de los ingresos digitales en cuentas bancarias.
5. Estandarización de componentes de rango de fechas con `AppDateTimePicker` y botones rápidos (`Hoy`, `7 días`, `Este Mes`) en Compras, Ventas y Auditorías.

---

## Technical Context

- **Backend Language/Version**: PHP 8.2+ con Laravel 12.x en la raíz del repositorio.
- **Arquitectura Backend**: Arquitectura Hexagonal y Domain-Driven Design (DDD).
  - Dominio puro: `App\Domain\PaymentMethod\PaymentMethod`.
  - Aplicación: Casos de uso desacoplados (`ListPaymentMethodsUseCase`, `CreatePaymentMethodUseCase`, `UpdatePaymentMethodUseCase`, `TogglePaymentMethodStatusUseCase`, `GetPaymentMethodOptionsUseCase`).
  - Infraestructura: `EloquentPaymentMethodRepository`, `PaymentMethodModel`, `PaymentMethodController`.
- **Frontend Framework**: Vue 3, Vite, Vuetify 3 y CASL en `admin-starter-kit/`.
- **Iconografía**: Remix Icons (`ri-*`).
- **Date Picker**: `AppDateTimePicker` (Flatpickr) integrado en Vuetify con presets reactivos.
- **Autenticación**: JWT con tokens Bearer y control de permisos con middleware `owner` y CASL (`can('manage', 'all')`).
- **Almacenamiento de Archivos**: Laravel Storage público (`storage/payment-methods/`).
- **Base de Datos**: MySQL / MariaDB (con pruebas en SQLite en memoria).
- **Moneda**: Bolivianos (`BOB` / `Bs.`) en toda la lógica contable y de presentación.
- **Testing**: PHPUnit / Pest (`php artisan test`).

---

## Constitution Check

*GATE: Must pass before implementation.*

- [x] **Principle I (KISS & YAGNI)**: Diseño relacional directo `payment_methods` con campos esenciales sin crear sistemas bancarios hipercomplejos ni pasarelas automáticas innecesarias para una tienda física boliviana.
- [x] **Principle II (Bolivia / BOB)**: Adaptado al contexto boliviano: QR Simple, transferencias bancarias locales (Banco Unión, BCP, Mercantil, BNB, FIE) y moneda Bolivianos (`Bs.`).
- [x] **Principle III (Zero Scope Creep)**: Se abordan estrictamente las 5 historias de usuario especificadas en `spec.md`.
- [x] **Principle IV (Verificable por no técnico)**: 4 pasos visuales documentados en [quickstart.md](quickstart.md) que se prueban en menos de 5 minutos.
- [x] **Principle V (Verdad única de datos en tiempo real)**: Todos los canales (web y futuras apps móviles) consumen el mismo endpoint `/api/payment-methods/options`.
- [x] **Principle VI (Privacidad y Seguridad)**: Administración de cuentas bancarias y números de cuenta sensible restringida exclusivamente al rol Dueño; el cajero solo visualiza las opciones activas para cobro.

---

## Project Structure

### Documentation (this feature)

```text
specs/006-gestion-formas-pago/
├── spec.md                  # Especificación funcional y de negocio
├── plan.md                  # Plan de arquitectura e implementación (este archivo)
├── research.md              # Decisiones de storage, retrocompatibilidad y caja
├── data-model.md            # Esquema relacional y entidades de dominio
├── quickstart.md            # Guía rápida de verificación paso a paso
└── contracts/
    └── payment-methods-api.md # Contratos de endpoints REST
```

### Source Code

```text
app/
├── Domain/
│   └── PaymentMethod/
│       ├── PaymentMethod.php
│       ├── PaymentMethodType.php
│       ├── PaymentMethodScope.php
│       └── PaymentMethodRepositoryInterface.php
├── Application/
│   └── PaymentMethod/
│       ├── ListPaymentMethodsUseCase.php
│       ├── CreatePaymentMethodUseCase.php
│       ├── UpdatePaymentMethodUseCase.php
│       ├── TogglePaymentMethodStatusUseCase.php
│       └── GetPaymentMethodOptionsUseCase.php
└── Infrastructure/
    ├── Http/Controllers/Api/
    │   └── PaymentMethodController.php
    └── Persistence/Eloquent/
        ├── PaymentMethodModel.php
        └── EloquentPaymentMethodRepository.php

database/migrations/
├── 2026_10_03_000013_create_payment_methods_table.php
└── 2026_10_03_000014_add_payment_method_id_to_sales_and_purchases.php

admin-starter-kit/src/
├── pages/
│   └── payment-methods/
│       └── index.vue        # Vista administrativa del Dueño
├── views/
│   ├── payment-methods/
│   │   └── PaymentMethodDialog.vue # Modal para crear/editar con upload de QR
│   └── pos/
│       └── CheckoutDialog.vue      # Adaptado con carga dinámica y QR oficial
└── navigation/vertical/index.js    # Enlace en menú "Ajustes > Formas de Pago"
```

---

## Planned Phases

### Phase 0: Base de Datos y Dominio (Backend)
- Crear migración para `payment_methods` con seeder inicial de métodos estándar (`Efectivo`, `QR`, `Transferencia`).
- Crear migración para agregar `payment_method_id` y `reference_number` a `sales`, `purchases` y `quotes`.
- Implementar entidad de dominio `PaymentMethod`, interfaces y repositorio Eloquent.

### Phase 1: Casos de Uso y API REST (Backend)
- Crear los casos de uso de gestión para Dueño y de consulta para POS/Compras.
- Implementar `PaymentMethodController` con validación de subida de imágenes para QR.
- Actualizar `CashShiftController` para conciliar únicamente el efectivo físico esperado en caja y calcular el desglose digital.
- Escribir pruebas unitarias y de integración (`PaymentMethodManagementTest`, `CashShiftReconciliationTest`).

### Phase 2: Interfaz Web Administrativa (Frontend)
- Construir `pages/payment-methods/index.vue` y `PaymentMethodDialog.vue` en `admin-starter-kit`.
- Configurar permisos en CASL y agregar enlace en menú vertical para Dueño.

### Phase 3: Integración Transversal (POS y Compras)
- Integrar opciones dinámicas y visualización del código QR en `CheckoutDialog.vue` del POS.
- Actualizar el formulario de recepción de compras `purchases/create.vue`.
- Probar el flujo completo de punta a punta y ejecutar verificación con `quickstart.md`.
