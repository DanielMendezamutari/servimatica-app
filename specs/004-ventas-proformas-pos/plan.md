# Implementation Plan: Capítulo 4 — Cotizaciones / Proformas, Ventas en Tienda (POS), Control de Caja y Comisiones

**Branch**: `004-ventas-proformas-pos` | **Date**: 2026-10-03 | **Spec**: [`specs/004-ventas-proformas-pos/spec.md`](spec.md)

**Input**: Feature specification de [`specs/004-ventas-proformas-pos/spec.md`](spec.md), contratos [`contracts/pos-api.md`](contracts/pos-api.md), modelo de datos [`data-model.md`](data-model.md) y [`research.md`](research.md).

---

## 1. Summary

Implementar el módulo central de operaciones comerciales para Servimática:
1. **Punto de Venta (POS) en Mostrador:** Interfaz rápida con carrito, lector/búsqueda de productos, cobro en Efectivo (cálculo de vuelto) y QR (Simple QR).
2. **Proformas / Cotizaciones:** Emisión formal sin reserva de stock, generación de PDF con membrete y enlace directo para enviar por WhatsApp.
3. **Control de Caja y Turnos:** Apertura de caja con fondo inicial en Bs., acumulación de ventas en efectivo y arqueo de cierre diario.
4. **Descuento de Stock y Comisiones:** Actualización atómica del inventario en tiempo real y acreditación automática de comisión comercial al vendedor.

---

## 2. Technical Context

- **Language/Version**: PHP 8.2+ (Backend Laravel 11 en la raíz), JavaScript ES2023+ (Frontend Vue 3 en `admin-starter-kit/`).
- **Primary Dependencies**: Laravel Framework, JWT Auth (`tymon/jwt-auth`), Vuetify 3, `@casl/vue`, `phpoffice/phpspreadsheet`.
- **Storage**: MySQL 8.x / MariaDB (vía migraciones Eloquent), almacenamiento local en `storage/app/public/` para comprobantes.
- **Testing**: `php artisan test` (Pest / PHPUnit para Feature y Unit tests), Vitest / comprobación `pnpm build`.
- **Target Platform**: Servidor local / Web responsive (optimizado para pantalla táctil y monitor de mostrador).
- **Project Type**: SPA (Vue 3 / Vuetify) consumiendo API REST compartida bajo Arquitectura Hexagonal y DDD.
- **Performance Goals**: Registro de venta y descuento de stock en < 500ms; carga de catálogo en POS en < 300ms.
- **Constraints**: Formato monetario obligatorio en Bolivianos (`Bs.` / `BOB`); costos ocultos para rol vendedor.

---

## 3. Constitution Check

*GATE: Evaluación contra la Constitución de Servimática App (`.specify/memory/constitution.md`).*

| Principio | Estado | Justificación |
|---|:---:|---|
| **I. Simplicidad ante todo (KISS & YAGNI)** | **PASSED** | Se evitan pasarelas de pago externas o drivers propietarios ESC/POS. La impresión térmica usa estilos CSS `@media print` nativos del navegador y el QR opera con Simple QR bancario boliviano. |
| **II. Idioma, Mercado y Moneda (BOB / Bs.)** | **PASSED** | Todos los cálculos, precios, subtotales, totales, vueltos y comisiones están expresados y validados en Bolivianos (`Bs.`). |
| **III. Cero Alcance Fantasma** | **PASSED** | Cada endpoint y vista responde estrictamente a los requerimientos aprobados en `spec.md` (RF-040 a RF-054). |
| **IV. Verificable por Persona No Técnica** | **PASSED** | La guía [`quickstart.md`](quickstart.md) describe 5 pruebas visuales completas realizables en menos de 5 minutos desde el navegador. |
| **V. Verdad Única de Datos en Tiempo Real** | **PASSED** | Las ventas descuentan el inventario central mediante transacciones atómicas con bloqueo de fila (`lockForUpdate()`), evitando desincronizaciones de stock. |
| **VI. Privacidad y Seguridad de Costos** | **PASSED** | La pantalla del POS y las respuestas de API para vendedores omiten estrictamente los costos de compra y márgenes del negocio. |

---

## 4. Project Structure & Source Code Layout

### Backend (Laravel - Arquitectura Hexagonal / DDD)
```text
app/
├── Domain/
│   ├── Client/
│   │   ├── Client.php
│   │   └── ClientRepositoryInterface.php
│   ├── CashShift/
│   │   ├── CashShift.php
│   │   └── CashShiftRepositoryInterface.php
│   ├── Quote/
│   │   ├── Quote.php
│   │   ├── QuoteItem.php
│   │   └── QuoteRepositoryInterface.php
│   └── Sale/
│       ├── Sale.php
│       ├── SaleItem.php
│       └── SaleRepositoryInterface.php
├── Application/
│   ├── CashShift/
│   │   ├── OpenCashShiftUseCase.php
│   │   ├── GetCurrentCashShiftUseCase.php
│   │   └── CloseCashShiftUseCase.php
│   ├── Client/
│   │   ├── ListClientsUseCase.php
│   │   └── CreateClientUseCase.php
│   ├── Quote/
│   │   ├── CreateQuoteUseCase.php
│   │   └── GetQuoteUseCase.php
│   └── Sale/
│       ├── ProcessSaleUseCase.php
│       ├── ListSalesUseCase.php
│       ├── CancelSaleUseCase.php
│       └── GenerateCommissionsReportUseCase.php
├── Infrastructure/
│   ├── Http/
│   │   └── Controllers/Api/
│   │       ├── CashShiftController.php
│   │       ├── ClientController.php
│   │       ├── QuoteController.php
│   │       └── SaleController.php
│   └── Persistence/Eloquent/
│       ├── ClientModel.php
│       ├── CashShiftModel.php
│       ├── QuoteModel.php
│       ├── QuoteItemModel.php
│       ├── SaleModel.php
│       ├── SaleItemModel.php
│       ├── EloquentClientRepository.php
│       ├── EloquentCashShiftRepository.php
│       ├── EloquentQuoteRepository.php
│       └── EloquentSaleRepository.php
```

### Frontend (`admin-starter-kit/`)
```text
admin-starter-kit/src/
├── pages/
│   ├── pos/
│   │   └── index.vue              # Pantalla principal del Punto de Venta (Catálogo + Carrito)
│   ├── quotes/
│   │   └── index.vue              # Listado y gestión de Proformas / Cotizaciones
│   ├── sales/
│   │   └── index.vue              # Historial de ventas y reportes
│   └── cash-shifts/
│       └── index.vue              # Control y arqueos de caja
├── views/
│   ├── pos/
│   │   ├── OpenCashShiftDialog.vue  # Diálogo de apertura de turno de caja
│   │   ├── CloseCashShiftDialog.vue # Diálogo de arqueo y cierre
│   │   ├── CheckoutDialog.vue       # Pasarela de cobro (Efectivo / QR, cálculo de vuelto)
│   │   ├── ReceiptPrintDialog.vue   # Modal de ticket térmico 80mm
│   │   └── QuickClientDialog.vue    # Alta rápida de cliente al vuelo
│   └── quotes/
│       └── QuoteDetailsDialog.vue   # Vista de proforma, PDF y WhatsApp
```

---

## 5. Phases of Implementation

### Phase 1: Setup & Migraciones de Base de Datos
- Crear migración `create_clients_table`.
- Crear migración `create_cash_shifts_table`.
- Crear migración `create_quotes_and_items_tables`.
- Crear migración `create_sales_and_items_tables`.
- Ejecutar `php artisan migrate`.

### Phase 2: Entidades de Dominio, Repositorios e Infraestructura
- Implementar entidades y contratos de repositorio en `app/Domain/`.
- Implementar modelos Eloquent y repositorios en `app/Infrastructure/Persistence/Eloquent/`.
- Registrar bindings en `AppServiceProvider`.

### Phase 3: Control de Caja Chica y Turnos (`CashShift`)
- Implementar casos de uso `OpenCashShiftUseCase`, `GetCurrentCashShiftUseCase`, `CloseCashShiftUseCase`.
- Implementar `CashShiftController` y registrar rutas en `routes/api.php`.
- Escribir pruebas automatizadas para apertura, bloqueo y arqueo de caja.

### Phase 4: Directorio de Clientes y Proformas / Cotizaciones (`Quote`)
- Implementar casos de uso `CreateClientUseCase`, `CreateQuoteUseCase`.
- Generación de enlace de WhatsApp estructurado y plantilla de proforma formal.
- Pruebas automatizadas de cotización sin descuento de stock.

### Phase 5: Punto de Venta (POS) y Venta Atómica con Descuento de Stock (`Sale`)
- Implementar `ProcessSaleUseCase` con `DB::transaction()` y `lockForUpdate()`.
- Cálculo de vuelto exacto en Efectivo y registro de cobro QR.
- Generación de ticket térmico 80mm en frontend.
- Pruebas automatizadas de cobro, vuelto y stock decrementado.

### Phase 6: Comisiones, Anulación de Venta y Reportes para el Dueño
- Implementar cálculo automático de comisión (`sales_commission`).
- Implementar `CancelSaleUseCase` (solo rol Dueño) con reposición de inventario.
- Reporte de ventas y liquidación de comisiones acumuladas.

### Phase 7: Polish, Permisos CASL y Verificación End-to-End
- Configurar reglas en `ability.js` para vendedores y dueño.
- Agregar accesos en el menú de navegación lateral (`POS / Ventas`, `Proformas`, `Caja`).
- Validación exhaustiva con los 5 escenarios de [`quickstart.md`](quickstart.md).
