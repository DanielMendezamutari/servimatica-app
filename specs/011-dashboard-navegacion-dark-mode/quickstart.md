# Quickstart: Validación del Dashboard, Menú UX y Modo Dark

**Feature**: `specs/011-dashboard-navegacion-dark-mode`
**Date**: 2026-10-03

---

## 1. Pruebas Automatizadas de Backend

Ejecutar las pruebas unitarias y de integración para validar el endpoint y la confidencialidad de roles:

```bash
php artisan test --filter=DashboardSummaryTest
```

### Casos Validados:
1. `owner_receives_complete_kpis_with_profit_and_shift`: Valida que el Dueño recibe `total_sales_bs`, `total_profit_bs`, `profit_margin_percentage`, `critical_stock` y `cash_shift`.
2. `seller_receives_operational_kpis_without_financial_profit_disclosure`: Valida que el rol Vendedor no recibe utilidades netas ni márgenes de ganancia (Principio VI).
3. `period_filtering_updates_sales_metrics`: Valida que al enviar `?period=this_week` o `?period=this_month` los acumulados correspondan al intervalo temporal solicitado.

---

## 2. Validación Visual y Manual en el Frontend

### Escenario A: Centro de Mando Ejecutivo (Dueño)
1. Iniciar sesión en el navegador con credenciales de Dueño (`admin@servimatica.com`).
2. Acceder a `/` (Inicio):
   - Verificar tarjetas KPI: Ventas de Hoy (`Bs.`), Ganancia de Hoy (`Bs.`), Tickets y Ticket Promedio.
   - Constatar la tarjeta de estado de Caja Chica (abierta o cerrada con botón de apertura).
   - Constatar la tabla de Alertas de Stock Bajo (productos con stock $\le$ stock mínimo).
   - Constatar el lanzador de accesos directos (*POS*, *Proformas*, *Compras*, *Kardex*).
3. Cambiar el selector de periodo a "Esta Semana" y "Este Mes" para verificar la actualización reactiva sin recarga de página.

### Escenario B: Confidencialidad para Vendedor
1. Iniciar sesión con un usuario Vendedor.
2. Acceder a `/`:
   - Verificar que **NO** aparece la tarjeta de Utilidades ni márgenes porcentuales.
   - Verificar acceso directo a Punto de Venta (POS) y Cotizaciones.

### Escenario C: Menú Lateral Izquierdo (Sidebar UX)
1. Observar la barra lateral izquierda:
   - Validar las 4 secciones temáticas: *Ventas y Mostrador*, *Inventario y Abastecimiento*, *Reportes y Finanzas*, *Administración*.
   - Comprobar que "Punto de Venta (POS)" cuenta con el badge distintivo destacado.
   - Comprobar que los vendedores no ven las secciones de Administración ni Reportes Financieros (CASL).

### Escenario D: Modo Oscuro (Dark Theme)
1. En la barra superior, hacer clic en el conmutador de tema (`NavbarThemeSwitcher`) y seleccionar **Oscuro** (Dark).
2. Constatar:
   - Contraste óptimo en textos de KPIs, cabeceras de tablas y separadores.
   - Ningún fondo blanco cegador en el POS, ventas o tablas.
   - Recargar la página (`F5`) y verificar que el modo oscuro se preserva (persistencia de cookie).
