# Feature Specification: Kardex Completo (Físico y Valorizado) y Reportes Financieros / Rentabilidad

**Feature Branch**: `010-kardex-reportes-rentabilidad`

**Created**: 2026-10-03

**Status**: Draft

**Input**: User description: "Opción 2: Kardex Completo (Físico y Valorizado) y Reportes Financieros / Rentabilidad"

## Clarifications

### Session 2026-10-03
- Q: ¿Cómo debe valorizarse monetariamente un ajuste manual positivo de inventario cuando no proviene de una factura de compra directa? → A: Se toma el último costo de compra registrado del producto (o costo base del catálogo).
- Q: ¿Qué nivel de agrupación y vista predeterminada debe ofrecer el reporte de rentabilidad financiera del Dueño? (FR-006) → A: Vista multi-pestaña flexible: tarjetas ejecutivas globales (Ventas, Costos, Utilidad en Bs., Margen %) con desglose cronológico (diario/mensual) y desglose por producto/categoría.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Consulta de Kardex Físico y Valorizado por Producto (Priority: P1 - MVP)

Como Administrador/Dueño o Personal de Almacén, quiero consultar el historial cronológico detallado de movimientos de un producto (entradas por compra, salidas por venta, ajustes de stock, devoluciones) para auditar con total transparencia física y económica las existencias de la tienda.

**Why this priority**: Es la base contable y operativa del negocio. Permite saber exactamente de dónde proviene cada unidad y a qué costo ingresó o salió, resolviendo discrepancias de stock y cumpliendo con el método legal de Costo Promedio Ponderado (CPP).

**Independent Test**: Puede probarse de forma independiente seleccionando cualquier producto del catálogo con movimientos (compras y ventas previas) y verificando que la tabla de Kardex calcule de forma exacta los saldos físicos y los saldos valorizados en Bolivianos (`Bs.`).

**Acceptance Scenarios**:

1. **Dado** un usuario con rol "Dueño" o "Administrador" que busca un producto en la vista de Kardex, **Cuando** filtra por un rango de fechas, **Entonces** el sistema muestra la tabla con entradas (cantidad, costo unitario, costo total), salidas (cantidad, costo unitario de salida, costo total) y saldos acumulados (unidades físicas y valor en `Bs.`), calculados según el Costo Promedio Ponderado.
2. **Dado** un usuario con rol "Vendedor" o "Cajero" que accede a la consulta de Kardex, **Cuando** visualiza el historial de un producto, **Entonces** el sistema presenta únicamente las columnas del Kardex Físico (fecha, tipo de movimiento, comprobante de referencia, cantidades de entrada/salida y saldo en unidades), ocultando completamente costos unitarios, totales en `Bs.` y márgenes monetarios (conforme al Principio VI de Confidencialidad).
3. **Dado** un producto con ventas y compras sucesivas a diferentes costos de adquisición, **Cuando** se registra una nueva venta en el POS, **Entonces** el movimiento de salida en el Kardex adopta el costo promedio ponderado vigente al momento de la transacción sin alterar el historial pasado.

---

### User Story 2 - Reporte Ejecutivo de Rentabilidad y Utilidad por Periodo (Priority: P2)

Como Dueño de la tienda, quiero visualizar un reporte financiero consolidado que contraste los ingresos por ventas contra el costo de la mercancía vendida (COGS) en un rango de fechas (hoy, semana, mes, año o personalizado) para conocer la ganancia real neta y el margen porcentual de rentabilidad de la empresa.

**Why this priority**: Permite al dueño tomar decisiones comerciales estratégicas (fijación de precios, liquidación de líneas de producto, promociones) conociendo el margen de ganancia real en Bolivianos (`Bs.`).

**Independent Test**: Se prueba seleccionando un rango de fechas con ventas registradas y verificando que: `Ingresos Totales (Bs.)` - `Costo Total de Ventas (Bs.)` = `Utilidad Bruta (Bs.)`, coincidiendo exactamente con la sumatoria de las partidas vendidas.

**Acceptance Scenarios**:

1. **Dado** el Dueño en la sección de Reportes Financieros, **Cuando** selecciona el rango del mes en curso, **Entonces** visualiza tarjetas resumen con: Total Facturado/Vendido (`Bs.`), Costo Total de Ventas (`Bs.`), Utilidad Bruta (`Bs.`) y Margen Promedio (`%`).
2. **Dado** el reporte de rentabilidad generado, **Cuando** se consulta el desglose por producto, **Entonces** el sistema lista los artículos ordenados por mayor margen de contribución monetaria y volumen vendido.
3. **Dado** un usuario sin rol de Dueño/Administrador, **Cuando** intenta acceder a la ruta o solicitar el reporte de rentabilidad, **Entonces** el sistema bloquea el acceso con mensaje de permisos insuficientes (403 Forbidden).

---

### User Story 3 - Reporte de Valoración Global de Inventario y Capital Inmovilizado (Priority: P3)

Como Dueño o Administrador, quiero consultar un reporte consolidado del capital total invertido en inventario físico, discriminando entre productos vendibles en vitrina y artículos en garantía o defectuosos, con capacidad de exportación a Excel.

**Why this priority**: Evita el estancamiento de capital de trabajo y proporciona información patrimonial fidedigna para balances contables o solicitudes de crédito comercial.

**Independent Test**: Puede probarse emitiendo el reporte de inventario valorizado global y comprobando que la sumatoria de `(Stock Actual * Costo Promedio)` coincide con el total de capital inmovilizado mostrado, y que al exportar a Excel el archivo descargado refleja las mismas cifras y columnas.

**Acceptance Scenarios**:

1. **Dado** el administrador en el reporte de inventario valorizado, **Cuando** consulta el estado general, **Entonces** observa el total de ítems en stock, la cantidad total de unidades y el capital total valorizado en `Bs.`.
2. **Dado** el reporte de inventario, **Cuando** se discrimina por estado físico de stock, **Entonces** se muestra claramente qué parte del capital está en mercadería vendible activa vs. mercadería retenida por garantías pendientes o devoluciones a proveedor.
3. **Dado** el reporte en pantalla, **Cuando** el usuario hace clic en "Exportar a Excel / CSV", **Entonces** el navegador descarga de inmediato la hoja de cálculo con encabezados claros, datos formateados en Bolivianos y fórmulas de totales listas para auditoría.

---

### Edge Cases

- **Stock inicial o producto sin compras previas registradas:** Cuando un producto fue creado con stock inicial antes del módulo de compras, el sistema toma el costo de compra base registrado en la ficha del producto como punto de partida para el cálculo del Kardex.
- **Devolución de venta (anulación o retorno a stock):** Si un cliente devuelve un producto mediante nota de crédito o anulación, el reingreso al Kardex debe registrarse con el mismo costo ponderado con el que salió en la venta original, para no distorsionar el costo promedio futuro.
- **Stock en cero y posterior reingreso:** Si las existencias de un producto llegan a 0 y luego ingresa un nuevo lote de compras con costo diferente, el nuevo Costo Promedio Ponderado se reinicia exactamente con el costo unitario de esa nueva compra.
- **Ajustes manuales de inventario (sobrantes o faltantes):** Los ajustes positivos (sobrantes o conteos físicos) se valorizan monetariamente adoptando el último costo unitario de compra registrado del producto (o su costo base si no tiene compras previas), preservando la coherencia del Costo Promedio Ponderado sin distorsiones contables. En caso de ajustes negativos (mermas/pérdidas), la salida se liquida al Costo Promedio Ponderado vigente al momento de registrar la baja.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE registrar y calcular de forma persistente o determinística cada movimiento en el Kardex asociando: fecha y hora, tipo de movimiento (Compra, Venta, Devolución Cliente, Devolución Proveedor, Ajuste Manual), comprobante de referencia (Factura/Nota de Venta/Orden de Compra), cantidad entrada, cantidad salida, saldo en cantidad física.
- **FR-002**: El sistema DEBE calcular el Kardex Valorizado utilizando estrictamente la metodología de Costo Promedio Ponderado (CPP), registrando: costo unitario de entrada, total Debe (Bs.), costo unitario ponderado de salida, total Haber (Bs.) y saldo valorizado acumulado (Bs.).
- **FR-003**: El sistema DEBE aplicar control estricto de visibilidad y autorización: únicamente usuarios con rol "Dueño" o "Administrador" pueden ver costos unitarios, totales monetarios, márgenes de ganancia y saldos valorizados en `Bs.`; los roles operativos sólo visualizarán cantidades y referencias físicas.
- **FR-004**: La interfaz de usuario DEBE permitir filtrar el Kardex por producto (búsqueda por nombre o código de barras), por rango de fechas (desde/hasta) y por tipo de transacción (todas, entradas, salidas, ajustes).
- **FR-005**: El sistema DEBE proveer un Reporte Financiero de Rentabilidad que calcule: Ventas Totales (Bs.), Costo de Mercancía Vendida (COGS en Bs.), Utilidad Bruta (Bs.) y Margen Porcentual Bruto (%) en el rango temporal seleccionado.
- **FR-006**: El sistema DEBE ofrecer una interfaz multi-pestaña flexible para el Reporte de Rentabilidad: tarjetas KPI globales (Ingresos por Ventas, Costo de Ventas, Utilidad Bruta en Bs. y Margen %) y dos vistas conmutables: desglose cronológico (diario/mensual) y desglose por producto y categoría destacando margen de ganancia y volumen.
- **FR-007**: El sistema DEBE proveer un Reporte de Valoración Total de Inventario que totalice las existencias en almacén multiplicadas por su costo promedio vigente, distinguiendo stock disponible para venta de stock inmovilizado por garantías o mermas.
- **FR-008**: El sistema DEBE permitir exportar el Kardex por producto y los reportes financieros a formato de hoja de cálculo descargable (Excel `.xlsx` / `.csv`) con formateo monetario en Bolivianos (`Bs.`).
- **FR-009**: Todos los cálculos monetarios DEBEN redondearse a 2 decimales para totales y saldos, y manejar hasta 4 decimales en el costo unitario promedio para evitar desfasajes por redondeo en grandes volúmenes.

### Key Entities

- **KardexMovement / StockMovement**: Representa cada evento unitario que altera el inventario físico o monetario de un producto. Atributos: producto, usuario responsable, fecha/hora, tipo de operación, comprobante origen (tipo y número de referencia), cantidad entrada/salida, saldo físico, costo unitario, total debe/haber, saldo valorizado y costo promedio resultante.
- **ProfitabilitySummary**: Vista o entidad de cálculo de rendimiento económico por periodo. Atributos: rango de fechas, total ingresos por ventas, total costo de venta asociado, utilidad bruta, margen sobre ventas (%), desglose por producto/comprobante.
- **InventoryValuation**: Entidad de cálculo patrimonial de inventario. Atributos: producto, categoría, stock físico disponible, stock en garantía/cuarentena, costo promedio unitario, capital total inmovilizado en Bolivianos (`Bs.`).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Los usuarios pueden consultar el Kardex completo de cualquier producto en menos de 2 segundos, incluso con historiales de más de 1,000 transacciones.
- **SC-002**: El cálculo del Saldo Físico y Saldo Valorizado coincide al 100% con la sumatoria matemática auditada de entradas y salidas según Costo Promedio Ponderado (discrepancia = 0.00 Bs.).
- **SC-003**: El 100% de los datos de costos de compra, saldos valorizados y márgenes de ganancia quedan completamente inaccesibles para usuarios sin privilegios administrativos (tanto en interfaz web como en payloads de la API REST).
- **SC-004**: La descarga de reportes en Excel o CSV se genera en menos de 3 segundos con un solo clic desde el panel de control.
- **SC-005**: El reporte de rentabilidad permite al dueño de la empresa conocer con exactitud la ganancia bruta del día o del mes en menos de 3 clics desde el menú principal.

## Assumptions

- Se asume que el método oficial y único para valuación de inventario en Servimática es el Costo Promedio Ponderado (CPP), estándar en Bolivia.
- Se asume que las compras (`purchases`) y ventas (`sales`) ya existentes en la base de datos cuentan con las partidas (`purchase_items`, `sale_items`) con sus respectivos costos y precios históricos inmutables.
- Las devoluciones de clientes reintegran el producto al inventario y reversan la salida en el Kardex al costo en que fue despachado.
- La moneda utilizada en todos los reportes, columnas valorizadas y exportaciones es el Boliviano (`Bs.`).
- La exportación a Excel se realizará utilizando librerías nativas estándar compatibles con el frontend Vue.js / backend Laravel sin introducir dependencias pesadas.
