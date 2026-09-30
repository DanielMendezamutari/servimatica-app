# Especificación: Capítulo 2 — Catálogo de Productos e Inventario v1.0

**Feature**: `002-catalogo-inventario`
**Created**: 2026-09-26
**Status**: Draft

---

## 1. Objetivo y contexto de negocio

La tienda de computadoras necesita un registro centralizado de todo lo que vende: computadoras armadas, laptops, componentes individuales (procesadores, memorias RAM, tarjetas de video, discos, fuentes), periféricos (teclados, ratones, monitores, audífonos) y accesorios (cables, fundas, mochilas, adaptadores). Sin este catálogo, el Dueño no puede conocer qué tiene en stock, a qué precio lo compró ni cuánto debe cobrar, y el Vendedor no puede consultar la disponibilidad real ni los precios actualizados al atender a un cliente en mostrador.

Este capítulo resuelve:
- **Descontrol de inventario**: No existe un registro digital del stock real en tienda; hoy se trabaja de memoria o con listas manuales.
- **Opacidad de márgenes**: El Dueño necesita ver el costo de compra junto al precio de venta para calcular su ganancia, pero esa información debe ser invisible para el Vendedor.
- **Consultas lentas en mostrador**: El Vendedor pierde tiempo buscando precios y disponibilidad; necesita un catálogo rápido y filtrable.
- **Organización por categorías**: Los productos deben agruparse en categorías claras para facilitar la búsqueda y la navegación.

Todos los precios se expresan en **Bolivianos (Bs.)** conforme a la Constitución del proyecto.

---

## Clarifications

### Session 2026-09-26
- Q: ¿A partir de qué cantidad de unidades en stock se debe considerar un producto con "stock bajo" y mostrarlo como alerta visual al Dueño? → A: Umbral configurable por producto; cada producto tiene un campo "stock mínimo" que el Dueño define al crearlo o editarlo. Si el stock actual ≤ stock mínimo, se muestra un indicador visual (badge/chip) en la lista.
- Q: ¿Las categorías necesitan un estado activo/inactivo o solo CRUD simple (crear, editar, eliminar con reasignación)? → A: Categorías con estado activo/inactivo; las categorías inactivas y sus productos dejan de aparecer en el filtro del Vendedor, pero siguen visibles para el Dueño.
- Q: ¿El historial de movimientos de stock debe ser consultable como vista separada o solo se registra internamente? → A: Vista separada de historial completo accesible desde un botón en la fila de cada producto, con filtros por fecha y tipo de movimiento.

---

## 2. Usuarios

- **Dueño de la tienda (Administrador):**
  - Crea y edita productos, asigna precios de compra y venta, gestiona categorías y ajusta el stock.
  - Tiene visibilidad completa: ve costos de compra, márgenes de ganancia y toda la información financiera.
  - Necesita reportes visuales rápidos del estado del inventario (stock bajo, valor total).

- **Vendedor de salón (Usuario Operativo):**
  - Consulta el catálogo para atender clientes: busca productos por nombre, categoría o código.
  - Ve el precio de venta al público y la cantidad disponible en stock.
  - **No tiene acceso** a los costos de compra, márgenes de ganancia ni datos de proveedores.

---

## 3. Escenarios de usuario (Historias de Usuario)

### HU5 — Gestión de categorías de productos (Prioridad: P1)

Como **Dueño de la tienda**,
quiero crear y administrar categorías para agrupar mis productos (Laptops, Componentes, Periféricos, Accesorios, etc.),
para mantener el catálogo organizado y facilitar la búsqueda tanto para mí como para mis vendedores.

- **Por qué esta prioridad:** Las categorías son la estructura base sobre la que se registran los productos; sin ellas no hay organización posible del catálogo.
- **Prueba independiente:** Crear las categorías "Laptops", "Componentes", "Periféricos" y "Accesorios" y verificar que aparecen en el listado y pueden editarse.
- **Escenarios de Aceptación:**
  1. **Dado** que el Dueño está en el módulo de categorías, **cuando** pulsa "Nueva categoría" e ingresa el nombre "Laptops" con una descripción opcional, **entonces** la categoría se guarda y aparece en la lista.
  2. **Dado** que existe la categoría "Periféricos", **cuando** el Dueño la edita cambiando el nombre a "Periféricos y Accesorios", **entonces** el nombre se actualiza en la lista y en todos los productos que la tenían asignada.
  3. **Dado** que la categoría "Accesorios" tiene 5 productos asignados, **cuando** el Dueño intenta eliminarla, **entonces** el sistema le advierte que tiene productos vinculados y le ofrece reasignarlos a otra categoría antes de eliminar, o cancelar la operación.
  4. **Dado** que el Dueño intenta crear una categoría con un nombre que ya existe (ej. "Laptops" duplicado), **entonces** el sistema rechaza la operación y muestra un mensaje de error indicando que el nombre ya está en uso.
  5. **Dado** que el Dueño desactiva la categoría "Accesorios", **cuando** un Vendedor consulta el catálogo, **entonces** ni la categoría "Accesorios" ni sus productos aparecen en los filtros ni en la lista del Vendedor, pero el Dueño las sigue viendo con un indicador de estado inactivo.

---

### HU6 — Registro y edición de productos por el Dueño (Prioridad: P2)

Como **Dueño de la tienda**,
quiero registrar cada producto con su nombre, descripción, categoría, código interno (SKU), precio de compra, precio de venta, cantidad en stock y estado (activo/inactivo),
para tener un control exacto de mi inventario y mis márgenes de ganancia.

- **Por qué esta prioridad:** El registro de productos es la funcionalidad central del catálogo; sin ella el inventario no existe digitalmente.
- **Prueba independiente:** Registrar una "Laptop HP 15-ef2xxx" con costo Bs. 2.500, precio de venta Bs. 3.200, stock de 3 unidades, y verificar que aparece en la lista con el margen calculado.
- **Escenarios de Aceptación:**
  1. **Dado** que el Dueño está en el módulo de productos, **cuando** pulsa "Nuevo producto" y completa el formulario con nombre, categoría "Laptops", SKU "LAP-HP-001", costo Bs. 2.500, precio venta Bs. 3.200 y stock 3, **entonces** el producto se guarda y aparece en la lista mostrando la ganancia de Bs. 700 (28%).
  2. **Dado** que el producto "Teclado Mecánico Redragon" existe con stock 10, **cuando** el Dueño lo edita cambiando el stock a 8 y el precio de venta de Bs. 350 a Bs. 380, **entonces** los cambios se reflejan inmediatamente en la lista.
  3. **Dado** que el Dueño deja el campo "Nombre" vacío o no selecciona categoría, **cuando** intenta guardar, **entonces** el sistema muestra mensajes de validación indicando los campos obligatorios faltantes.
  4. **Dado** que el Dueño ingresa un SKU que ya está en uso por otro producto, **cuando** intenta guardar, **entonces** el sistema rechaza la operación indicando que el código interno ya existe.
  5. **Dado** que el Dueño desea retirar temporalmente un producto de la vista de los vendedores, **cuando** cambia su estado a "Inactivo", **entonces** el producto deja de aparecer en las consultas del Vendedor pero sigue visible para el Dueño con un indicador de estado inactivo.

---

### HU7 — Consulta del catálogo por el Vendedor (Prioridad: P3)

Como **Vendedor de salón**,
quiero buscar productos por nombre, categoría o SKU y ver su precio de venta y disponibilidad en stock,
para informar al cliente de forma rápida y precisa durante la atención en mostrador.

- **Por qué esta prioridad:** El vendedor necesita acceso de solo lectura al catálogo para atender clientes ágilmente; depende de que existan productos registrados (HU6).
- **Prueba independiente:** Iniciar sesión como vendedor, buscar "Laptop HP" y verificar que aparece con precio de venta Bs. 3.200 y stock 3, sin mostrar el costo de compra.
- **Escenarios de Aceptación:**
  1. **Dado** que el Vendedor está en el módulo de catálogo, **cuando** escribe "Laptop" en el campo de búsqueda, **entonces** el sistema filtra y muestra todos los productos cuyo nombre o SKU contengan "Laptop", con su precio de venta en Bs. y stock disponible.
  2. **Dado** que el Vendedor selecciona la categoría "Periféricos" en el filtro de categorías, **cuando** la lista se actualiza, **entonces** solo muestra los productos de esa categoría, con precio de venta y stock.
  3. **Dado** que el Vendedor consulta cualquier producto, **entonces** en ningún momento se muestra el costo de compra, el margen de ganancia ni datos del proveedor.
  4. **Dado** que un producto tiene stock 0, **cuando** el Vendedor lo ve en la lista, **entonces** aparece claramente marcado como "Agotado" para no ofrecerlo al cliente.
  5. **Dado** que un producto está en estado "Inactivo" (desactivado por el Dueño), **entonces** no aparece en la vista del Vendedor.

---

### HU8 — Ajuste rápido de stock por el Dueño (Prioridad: P4)

Como **Dueño de la tienda**,
quiero ajustar rápidamente la cantidad en stock de un producto (incrementar cuando llega mercadería, decrementar por merma o devolución),
para que el inventario refleje siempre la realidad física de la tienda.

- **Por qué esta prioridad:** Complementa el registro de productos con la capacidad de mantener el stock actualizado día a día sin necesidad de editar todo el producto.
- **Prueba independiente:** Incrementar el stock de "Laptop HP 15-ef2xxx" de 3 a 6 unidades tras recibir mercadería, y verificar que el nuevo stock se refleja en la lista del Vendedor.
- **Escenarios de Aceptación:**
  1. **Dado** que el Dueño está en la lista de productos, **cuando** pulsa el botón de ajuste de stock de un producto con stock actual de 3 e ingresa +3 con motivo "Recepción de mercadería", **entonces** el stock se actualiza a 6.
  2. **Dado** que el Dueño ajusta el stock con -1 y motivo "Merma / defectuoso", **entonces** el stock disminuye de 6 a 5.
  3. **Dado** que el Dueño intenta restar una cantidad mayor al stock actual (ej. restar 10 cuando hay 5), **entonces** el sistema rechaza la operación indicando que no puede haber stock negativo.
  4. **Dado** que se realiza un ajuste de stock, **entonces** el sistema registra un historial con la fecha, cantidad anterior, cantidad nueva, motivo y usuario que realizó el ajuste.
  5. **Dado** que el Dueño pulsa el botón de historial en la fila de un producto, **cuando** se abre la vista de historial, **entonces** ve una tabla con todos los movimientos de stock del producto (fecha, cantidad anterior, nueva, motivo, usuario) con filtros por rango de fechas y tipo de movimiento (ingreso/egreso).

---

### Casos borde

- **¿Qué pasa si se elimina una categoría con productos?** El sistema bloquea la eliminación y ofrece reasignar los productos antes de proceder.
- **¿Qué pasa si el Dueño intenta poner un precio de venta menor al costo?** El sistema muestra una advertencia visual ("El precio de venta es inferior al costo de compra") pero permite guardar, ya que pueden existir ofertas o liquidaciones intencionales.
- **¿Qué pasa si se busca un producto que no existe?** El sistema muestra un estado vacío con el mensaje "No se encontraron productos con ese criterio".
- **¿Cómo se manejan productos sin SKU?** El SKU es opcional; el sistema genera uno automático si el Dueño lo deja vacío (formato: `CAT-NNNN` donde `CAT` son las primeras 3 letras de la categoría y `NNNN` es un correlativo).
- **¿Qué sucede con el stock al editar directamente el campo?** La edición directa del campo stock en el formulario de producto solo se permite al crear; para productos existentes, el stock se modifica únicamente mediante el ajuste rápido (HU8) para mantener la trazabilidad del historial.
- **¿Qué pasa con los productos de una categoría inactiva?** Los productos de una categoría inactiva dejan de aparecer en la vista del Vendedor, incluso si individualmente están activos. El Dueño los sigue viendo. Al reactivar la categoría, los productos activos vuelven a ser visibles para el Vendedor.

---

## 4. Requisitos

### Requisitos Funcionales

- **RF-010**: El sistema DEBE permitir al Dueño crear, editar, activar/desactivar y eliminar categorías de productos con nombre único y descripción opcional.
- **RF-011**: El sistema DEBE impedir la eliminación de una categoría que tenga productos asignados, ofreciendo reasignación previa.
- **RF-012**: El sistema DEBE permitir al Dueño registrar productos con: nombre (obligatorio), descripción, categoría (obligatoria), SKU (único, auto-generado si se omite), precio de compra en Bs., precio de venta en Bs., stock inicial y estado activo/inactivo.
- **RF-013**: El sistema DEBE calcular y mostrar automáticamente el margen de ganancia (en Bs. y porcentaje) al Dueño cuando se registra o edita un producto con precios de compra y venta.
- **RF-014**: El sistema DEBE mostrar una advertencia visual cuando el precio de venta sea inferior al costo de compra, sin bloquear la operación.
- **RF-015**: El sistema DEBE ocultar completamente el precio de compra, el margen de ganancia y cualquier dato financiero privado del Dueño en las vistas del Vendedor.
- **RF-016**: El sistema DEBE permitir al Vendedor consultar el catálogo de productos activos con búsqueda por nombre, categoría o SKU, mostrando únicamente precio de venta y stock disponible.
- **RF-017**: El sistema DEBE marcar visualmente como "Agotado" los productos con stock igual a cero en las vistas del Vendedor.
- **RF-018**: El sistema DEBE permitir al Dueño ajustar el stock de un producto (incremento o decremento) indicando una cantidad y un motivo obligatorio.
- **RF-019**: El sistema DEBE impedir ajustes de stock que resulten en cantidades negativas.
- **RF-020**: El sistema DEBE registrar un historial de cada ajuste de stock con: fecha, stock anterior, stock nuevo, cantidad ajustada, motivo y usuario responsable.
- **RF-021**: El sistema DEBE permitir al Dueño activar/desactivar productos; los productos inactivos no aparecen en la vista del Vendedor pero permanecen visibles para el Dueño.
- **RF-022**: El sistema DEBE validar la unicidad del SKU al crear o editar productos, rechazando duplicados con un mensaje claro.
- **RF-023**: El sistema DEBE permitir al Dueño definir un campo "stock mínimo" por producto. Cuando el stock actual sea menor o igual al stock mínimo, el sistema DEBE mostrar un indicador visual destacado (badge/chip) en la lista de productos del Dueño.
- **RF-024**: El sistema DEBE ocultar las categorías inactivas y todos sus productos asociados de las vistas del Vendedor (filtros y listado). El Dueño debe seguir viéndolas con un indicador de estado inactivo.
- **RF-025**: El sistema DEBE ofrecer al Dueño una vista de historial de movimientos de stock por producto, accesible desde un botón en la fila del producto, con filtros por rango de fechas y tipo de movimiento (ingreso/egreso).

### Entidades Clave

- **Categoría (Category)**: Agrupación lógica de productos. Atributos: nombre único, descripción opcional, estado (activo/inactivo). Al desactivar una categoría, sus productos dejan de ser visibles para el Vendedor independientemente del estado individual de cada producto.
- **Producto (Product)**: Artículo registrado en el catálogo. Atributos: nombre, descripción, SKU (código interno único), categoría asociada, precio de compra (Bs.), precio de venta (Bs.), stock actual, stock mínimo (umbral de alerta configurable), estado (activo/inactivo), imagen (opcional, fase futura).
- **Movimiento de Stock (StockMovement)**: Registro histórico de cada ajuste. Atributos: producto asociado, cantidad anterior, cantidad nueva, cantidad ajustada (+/-), motivo, usuario responsable, fecha y hora.

---

## 5. Criterios de Éxito

### Resultados Medibles

- **SC-005**: El Dueño puede registrar un producto completo (con categoría, precios y stock) en menos de 2 minutos.
- **SC-006**: El Vendedor puede localizar un producto en el catálogo por nombre o categoría en menos de 10 segundos.
- **SC-007**: En ningún escenario de uso del Vendedor se expone el costo de compra ni el margen de ganancia del producto.
- **SC-008**: Cada ajuste de stock queda registrado con trazabilidad completa (fecha, motivo, responsable) y es consultable por el Dueño desde una vista dedicada de historial por producto con filtros por fecha y tipo de movimiento.
- **SC-009**: El 100% de los productos con stock 0 se muestran como "Agotado" en la vista del Vendedor.
- **SC-010**: Los productos inactivos no aparecen en la consulta del Vendedor bajo ninguna condición de búsqueda o filtro.
- **SC-011**: El 100% de los productos cuyo stock actual es menor o igual a su stock mínimo configurado muestran un indicador visual de alerta en la lista del Dueño.

---

## 6. Supuestos

- Se reutiliza la autenticación JWT y el sistema de roles (Dueño/Vendedor) implementados en el Capítulo 1.
- Se reutilizan los componentes de tabla, drawers y formularios de la plantilla `admin-full-version/` conforme a la regla mandatoria de AGENTS.md.
- La moneda es exclusivamente Bolivianos (Bs.) conforme a la Constitución v1.0.0 (Principio II).
- Las imágenes de producto están fuera del alcance de este capítulo; se implementarán en una fase futura.
- No se implementa un módulo de proveedores en este capítulo; el precio de compra se registra como dato numérico simple.
- El módulo de catálogo para la app móvil Android queda fuera de este capítulo; se abordará en su propio capítulo dedicado.
- La paginación de la lista de productos se implementa del lado del servidor para soportar catálogos extensos (100+ productos) sin degradar la experiencia.
