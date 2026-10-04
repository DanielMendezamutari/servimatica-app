# Feature Specification: Dashboard Ejecutivo en Tiempo Real, Optimización de Navegación UX y Soporte Dark Mode

**Feature Branch**: `011-dashboard-navegacion-dark-mode`

**Created**: 2026-10-03

**Status**: Draft

**Input**: User description: "la opcion 1 pero quiero que tambien analises eso de la izquierda del panel no lo veo optimo asi analiza cmo podemos dar una mejor experiencia de usuario. ah y tambien la plantilla tiene soporte para modo dark mejores la interface grafica. siempre pensando en la experiencia de usuario"

---

## Clarifications

### Session 2026-10-03

- Q: ¿Cómo deben estructurarse los submódulos de Inventario, Reportes y Administración en el menú lateral izquierdo? → A: Secciones temáticas limpias y visibles con acceso directo a 1 solo clic (*Ventas y Mostrador*, *Inventario y Compras*, *Reportes y Finanzas*, *Administración*) y badge de acceso prioritario para el POS.
- Q: ¿Qué periodo temporal deben abarcar las métricas principales al entrar al Dashboard de inicio? → A: Cargar por defecto las métricas del día en curso ("Hoy") con selector rápido para alternar a "Esta Semana" y "Este Mes" en tiempo real.

### Session 2026-10-04

- Q: ¿Cómo deben distribuirse los accesos directos y los grupos colapsables en la barra lateral izquierda? → A: Híbrido ergonómico: *Inicio*, *Punto de Venta (POS)* y *Cotizaciones / Proformas* como enlaces directos superiores sin truncamiento de texto ni badges redundantes; y submenús colapsables con flechas (`children`) para *Operaciones de Caja*, *Inventario y Abastecimiento*, *Reportes y Finanzas* y *Administración*.
- Q: ¿Cómo debe funcionar el conmutador de Modo Oscuro en la barra superior? → A: Botón de alternancia directa a 1 solo clic ubicado en la esquina superior derecha (junto al perfil de usuario), mostrando icono de Luna 🌙 en modo claro y Sol ☀️ en modo oscuro con transición instantánea.
- Q: ¿Cómo debe resolverse el selector de periodos en el Dashboard para evitar solapamiento de texto? → A: Reemplazar el contenedor rígido por botones independientes tipo pill con espaciado flex (`gap-2`), garantizando ancho natural para cada etiqueta (*Hoy*, *Esta Semana*, *Este Mes*).

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Centro de Mando / Dashboard Ejecutivo en Tiempo Real (Priority: P1)

Como Dueño o Vendedor de Servimática, al iniciar sesión en el sistema quiero visualizar inmediatamente un centro de mando con las métricas clave de mi negocio (ventas del día, turno de caja activo, alertas de existencias críticas y accesos rápidos a operaciones frecuentes) para tomar decisiones operativas inmediatas sin tener que navegar por múltiples pantallas ni generar reportes manuales.

**Why this priority**: Es la pantalla de entrada principal del sistema (`/`). Actualmente solo muestra un saludo genérico. Convertirla en un dashboard ejecutivo eleva drásticamente el valor de la plataforma, la agilidad diaria del negocio y la visibilidad de los ingresos y el inventario.

**Independent Test**: Se puede verificar accediendo a la raíz `/` con el rol de Dueño para constatar la visualización de ventas del día, utilidad neta en `Bs.`, caja chica activa y productos con stock bajo; e iniciando sesión como Vendedor para constatar que solo ve sus ventas y turnos sin revelación de utilidades o costos confidenciales (Principio VI).

**Acceptance Scenarios**:

1. **Given** un usuario autenticado con rol `dueno`, **When** accede a la página de inicio `/`, **Then** visualiza las tarjetas de resumen del día con Ventas Totales en `Bs.`, Utilidad Estimada en `Bs.`, Comprobantes Emitidos, Ticket Promedio y el Estado del Turno de Caja Chica en tiempo real.
2. **Given** un usuario autenticado con rol `vendedor`, **When** accede a la página de inicio `/`, **Then** visualiza las ventas de su propio turno y accesos rápidos a Punto de Venta y Cotizaciones, pero las tarjetas y columnas de Utilidad Neta y Costos permanecen completamente ocultas (Principio VI).
3. **Given** que existen artículos en el catálogo con existencias físicas iguales o inferiores al stock mínimo (o en 0), **When** el usuario visualiza el bloque de Alertas de Inventario en el Dashboard, **Then** ve una lista clara de productos críticos con advertencia visual (rojo/amarillo) y botón de acceso rápido para abastecimiento.
4. **Given** el panel de Accesos Rápidos (Launchpad), **When** el usuario hace clic en [Punto de Venta POS], [Nueva Proforma], [Registrar Compra] o [Kardex de Inventario], **Then** el sistema lo redirige fluidamente al módulo correspondiente.

---

### User Story 2 - Reestructuración Ergonómica de la Navegación Lateral (Sidebar UX) (Priority: P2)

Como usuario del sistema (Dueño, Administrador o Vendedor), quiero una barra lateral izquierda organizada por áreas operativas claras y armoniosas, en lugar de una lista plana kilométrica desordenada, para encontrar rápidamente cualquier módulo de trabajo con mínima fatiga visual y menor cantidad de clics.

**Why this priority**: La barra lateral es la herramienta de navegación más utilizada en la jornada laboral diaria. La distribución actual de 16 elementos desparramados sin agrupación funcional genera desorden visual y lentitud operativa.

**Independent Test**: Comprobar visualmente que el menú lateral clasifica los módulos en bloques temáticos coherentes (*Mostrador y Ventas*, *Inventario y Abastecimiento*, *Reportes y Finanzas*, *Administración*), con badges destacados para módulos clave como el POS, y respetando las reglas de visibilidad CASL según el rol.

**Acceptance Scenarios**:

1. **Given** cualquier usuario en el panel administrativo, **When** observa la barra lateral izquierda, **Then** visualiza los módulos organizados en bloques temáticos bien diferenciados:
   - **Inicio** (Dashboard principal)
   - **Ventas y Mostrador** (Punto de Venta POS con badge de acceso prioritario, Cotizaciones/Proformas, Ventas en Tienda, Devoluciones y Garantías, Caja Chica y Turnos).
   - **Inventario y Abastecimiento** (Catálogo de Productos, Compras a Proveedores, Proveedores, Categorías y Marcas).
   - **Reportes y Finanzas** (Kardex de Inventario, Rentabilidad y Ganancias, Valoración Patrimonial).
   - **Administración** (Personal/Usuarios, Formas de Pago, Mi Empresa, Auditoría de Accesos).
2. **Given** la estructura de navegación en secciones temáticas limpias a 1 solo clic (*Ventas y Mostrador*, *Inventario y Compras*, *Reportes y Finanzas*, *Administración*), **When** interactúa con el menú lateral, **Then** la navegación permite acceder directamente a cualquier módulo sin pasos intermedios, resaltando el POS con un badge visual y preservando el estado activo de la ruta actual.
3. **Given** un usuario con rol `vendedor`, **When** examina la barra lateral, **Then** los bloques o ítems de acceso restringido (Administración, Rentabilidad, Valoración Patrimonial, Compras) no se muestran en su interfaz (reglas CASL).

---

### User Story 3 - Optimización Visual de Alto Contraste en Modo Dark (Priority: P3)

Como usuario de Servimática que trabaja en ambientes con iluminación variada o turnos prolongados de atención en tienda, quiero que la interfaz gráfica ofrezca una experiencia premium, nítida y agradable tanto en **Modo Claro** como en **Modo Oscuro**, garantizando contraste adecuado en textos, tarjetas, tablas y gráficos.

**Why this priority**: El modo oscuro reduce la fatiga visual de cajeros y administradores. Un mal soporte de modo oscuro provoca textos ilegibles, tablas deslavadas o fondos con contraste deficiente, degradando la percepción de calidad del software.

**Independent Test**: Cambiar el tema entre Claro y Oscuro mediante el conmutador de la barra superior (`NavbarThemeSwitcher`) y verificar que los textos de tarjetas de KPI, cabeceras de tablas, bordes divisores y componentes interactivos mantengan legibilidad absoluta sin elementos desalineados ni fondos opacos incompatibles.

**Acceptance Scenarios**:

1. **Given** el conmutador de tema en la barra superior de navegación, **When** el usuario selecciona "Modo Oscuro" (Dark) o su sistema operativo está en modo noche, **Then** la aplicación adapta instantáneamente su esquema de colores a una paleta oscura equilibrada (`#1e1e2d` / `#2b2c40` o la paleta nativa del starter-kit de Vuetify 3) sin recargar la página.
2. **Given** el Dashboard y las vistas de datos en modo oscuro, **When** se renderizan tablas con estilo `hover`, tarjetas de métricas, alertas y chips de estado, **Then** todos los textos usan tipografía de alto contraste (`text-high-emphasis` o `text-medium-emphasis`), con bordes suaves que delimitan las áreas de trabajo.
3. **Given** la persistencia de sesión del usuario, **When** el usuario define su preferencia de tema (Claro u Oscuro) y recarga la página o inicia sesión posteriormente, **Then** el sistema recuerda su elección sin resetearse a la configuración por defecto.

---

### Edge Cases

- **Caja Chica no abierta en el día:** Si no existe ningún turno de caja abierto hoy, el Dashboard debe mostrar una tarjeta informativa clara en color ámbar/secundario con el mensaje *"Caja actualmente cerrada"* y un botón directo para *"Abrir Turno de Caja"*.
- **Cero ventas en el día:** Si el negocio apenas abre o no ha registrado ventas hoy, las tarjetas deben mostrar `Bs. 0.00` de forma limpia y elegante, sin errores `NaN` ni divisiones por cero en el cálculo del ticket promedio.
- **Catálogo sin stock crítico:** Si todos los productos tienen existencias saludables por encima del stock mínimo, el bloque de alertas de inventario debe mostrar un estado positivo con icono de verificación (*"Todo el inventario se encuentra en niveles óptimos"*).
- **Pantallas pequeñas y tablets:** El Dashboard debe reorganizarse de manera responsive en 1, 2 o 4 columnas según el ancho de pantalla (móvil, tablet, escritorio) garantizando legibilidad sin desbordamientos horizontales.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE proveer un endpoint backend seguro (`GET /api/v1/dashboard/summary`) que retorne los datos consolidados en tiempo real para el Dashboard: ventas de hoy, comparativa con el día previo, turno de caja activo, alertas de stock mínimo y top de productos más vendidos.
- **FR-002**: El endpoint del Dashboard DEBE respetar de forma inviolable la regla de confidencialidad (Principio VI): los campos de utilidad neta estimada en `Bs.` y márgenes porcentuales SOLO deben calcularse y transmitirse en la respuesta si el usuario autenticado tiene rol de Dueño o Administrador.
- **FR-003**: El sistema DEBE cargar por defecto en el Dashboard las métricas del día en curso ("Hoy"), proveyendo un selector ágil e interactivo para alternar a "Esta Semana" o "Este Mes" en tiempo real sin requerir recargar la página.
- **FR-004**: La interfaz de inicio (`admin-starter-kit/src/pages/index.vue`) DEBE transformarse en un Dashboard interactivo con tarjetas de KPIs, tarjeta de turno de caja, panel de alertas de reorden de inventario, ranking de productos top y lanzador de acciones rápidas.
- **FR-005**: La barra lateral izquierda (`admin-starter-kit/src/navigation/vertical/index.js`) DEBE ser reorganizada en una jerarquía funcional estructurada en bloques (*Inicio*, *Ventas y Mostrador*, *Inventario y Abastecimiento*, *Reportes y Finanzas*, *Administración*), eliminando la lista plana actual.
- **FR-006**: La barra lateral DEBE incluir badges distintivos y etiquetas semánticas claras, garantizando compatibilidad con el sistema de permisos CASL para ocultar opciones no autorizadas al rol de Vendedor.
- **FR-007**: La interfaz completa del Dashboard y la navegación DEBEN asegurar compatibilidad 100% con el Modo Oscuro (Dark Theme), empleando tokens semánticos de Vuetify 3 (`bg-surface`, `text-high-emphasis`, `text-medium-emphasis`, `elevation-1/2`) para garantizar alto contraste y legibilidad.
- **FR-008**: La preferencia de tema del usuario (Claro, Oscuro o Sistema) DEBE almacenarse en cookies/localStorage mediante el componente `NavbarThemeSwitcher`, manteniéndose consistente entre sesiones y recargas de página.

---

### Key Entities *(include if feature involves data)*

- **DashboardSummary**: Agrupador virtual de métricas en tiempo real que integra totales de ventas de la fecha, cantidad de transacciones, ticket promedio, margen bruto del día (solo Dueño), estado del turno de caja y lista de alertas de stock crítico.
- **CriticalStockAlert**: Entidad de lectura que contiene el identificador del producto, nombre, SKU, stock actual disponible, stock mínimo configurado y estado de criticidad (agotado vs. bajo stock).
- **TopSellingProduct**: Registro de los productos más demandados en el periodo con nombre del producto, categoría, unidades vendidas y monto total facturado en `Bs.`.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El Dueño o Administrador puede conocer la facturación del día, el estado de la caja y las alertas de stock crítico en menos de 5 segundos tras iniciar sesión en la plataforma.
- **SC-002**: El 100% de los datos financieros confidenciales (utilidades y costos) quedan completamente excluidos del payload API y de la interfaz gráfica cuando el usuario es un Vendedor o Cajero.
- **SC-003**: El tiempo promedio que tarda un operador en ubicar y acceder a una función clave (POS, caja, inventario) se reduce a un solo clic o interacción directa en el menú estructurado.
- **SC-004**: La alternancia entre Modo Claro y Modo Oscuro ocurre en menos de 100 milisegundos sin parpadeos, inconsistencias de contraste ni textos ilegibles.
- **SC-005**: La totalidad del conjunto de pruebas automatizadas del backend se mantiene con 0 regresiones y 100% de aserciones exitosas.

---

## Assumptions

- Se reutiliza la tabla de turnos de caja chica (`cash_shifts`), ventas (`sales`) y productos (`products`) ya construidos en capítulos previos para consolidar los indicadores sin requerir nuevas tablas complejas de base de datos.
- La moneda utilizada en todos los indicadores es el Boliviano (`Bs.`) con formato decimal local (`es-BO`).
- El selector de tema en la barra de navegación superior utiliza la infraestructura nativa de `@core` y `@layouts` provista por la plantilla Sneat/Materio de Vuetify 3.
