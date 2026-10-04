# Research: Dashboard Ejecutivo, Reestructuración de Navegación UX y Modo Dark

**Feature**: `specs/011-dashboard-navegacion-dark-mode`
**Date**: 2026-10-03

---

## 1. Arquitectura del Endpoint de Dashboard

### Decision
Crear un caso de uso centralizado `App\Application\Dashboard\GetDashboardSummaryUseCase` atendido por `DashboardController@summary` en la ruta `GET /api/v1/dashboard/summary`.

### Rationale
- **Velocidad y Eficiencia:** La pantalla de inicio de la tienda debe cargar en menos de 200 ms. Un solo payload JSON consolidado reduce la sobrecarga de red y evita condiciones de carrera o múltiples spinners en la interfaz.
- **Seguridad y Confidencialidad (Principio VI):** En el backend se valida el rol del usuario (`dueno` vs `vendedor`). Si el usuario no es Dueño o Administrador, los campos de costo, utilidad bruta y margen porcentual son completamente excluidos del payload.

### Parámetros Soportados
- `period`: `today` (por defecto), `this_week`, `this_month`.

---

## 2. Reestructuración de Navegación Lateral (Sidebar UX)

### Decision
Eliminar la lista plana desordenada de 16 elementos y estructurar la barra en 4 bloques temáticos con divisores semánticos (`{ heading: '...' }`), respetando el resultado de la clarificación interactiva (Opción A):
1. **Inicio:** Dashboard principal.
2. **Ventas y Mostrador:** Punto de Venta POS (con badge destacado `badgeContent: 'POS'`, color `primary`), Proformas / Cotizaciones, Ventas en Tienda, Devoluciones y Garantías, Caja Chica y Turnos.
3. **Inventario y Abastecimiento:** Catálogo de Productos, Compras y Stock, Proveedores, Categorías y Marcas.
4. **Reportes y Finanzas:** Kardex de Inventario, Rentabilidad y Utilidades, Valoración Patrimonial (protegidos por CASL para Dueño).
5. **Administración:** Personal / Usuarios, Formas de Pago, Mi Empresa, Auditoría de Accesos.

### Rationale
- **Acceso directo a 1 clic:** Cajeros y vendedores no pierden tiempo abriendo y cerrando carpetas colapsables mientras atienden clientes en mostrador físico.
- **Separación de roles:** Vendedores solo visualizan *Inicio* y *Ventas y Mostrador* + *Productos* y *Kardex Físico*.

---

## 3. Optimización para Modo Dark (Alto Contraste y Ergonomía Visual)

### Decision
- Emplear exclusivamente tokens semánticos de Vuetify 3:
  - Fondos: `bg-surface` y `bg-background` automáticos.
  - Textos: `text-high-emphasis` para encabezados y números de KPIs, `text-medium-emphasis` para subtítulos e indicadores.
  - Bordes y separadores: `border` con opacidad sutil (`rgba(255, 255, 255, 0.08)` en tema dark) evitando líneas toscas o invisibles.
  - Badges y Chips: Variantes `tonal` con colores semánticos (`success`, `warning`, `primary`, `error`).
- Asegurar que `NavbarThemeSwitcher.vue` en la barra superior permita alternar entre Claro, Oscuro y Sistema instantáneamente y persista la elección en cookies.

### Alternatives Considered
- *Dark mode con CSS custom forzado:* Rechazado. Provoca inconsistencias y rompe las directrices del framework Vuetify 3. Se opta por el sistema nativo de temas de Vuetify.
