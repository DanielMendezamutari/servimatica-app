# Feature Specification: 009-gestion-garantias-pos

**Feature Branch**: `009-gestion-garantias-pos`  
**Created**: 2026-10-03  
**Status**: Draft  
**Input**: User description: "como deberia ser al momento de ingresarlo al sistema deberia poner garantia cierto. luego eso de garantias deberia yo poder administrar porque esta harcodeada o en la base de datos la garantia pero eso yo deberia gestionarlo. al momento de vender ya mire donde esta eso hay la opcion de poner la garantia pero analiza donde deveria ir. analzia como un senior programador analista"

---

## Clarifications

### Session 2026-10-03
- Q: ¿Qué nivel de restricción debe aplicarse si un vendedor o cajero intenta modificar en el POS los días de garantía técnica predeterminados del catálogo? → A: Libre edición con auditoría: cualquier vendedor puede ajustar el plazo de garantía en el carrito para agilidad comercial, y el sistema asocia de forma inmutable la venta y sus condiciones al usuario responsable.
- Q: ¿Cómo debe capturarse el número de serie (S/N) cuando se vende más de una unidad de un producto serializado en una sola línea del carrito del POS? → A: Campo flexible multi-serie: un único campo de texto donde se pueden pistolear o registrar varias series separadas por comas o saltos de línea para esa línea de venta.
- Q: ¿Cómo deben gestionarse los términos y condiciones legales de la garantía técnica para su impresión al final de los comprobantes de venta? → A: Configuración general editable por el Dueño: un campo de texto en Ajustes de Empresa (`settings/company.vue`) donde se definen las políticas generales de garantía del negocio que se imprimen en tickets y comprobantes.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Configuración y Administración de Garantía en el Catálogo de Productos (Priority: P1) 🎯 MVP

Como Administrador o Dueño del negocio, quiero definir y editar el tiempo de garantía técnica estándar al crear o actualizar un producto en el catálogo (ej. Sin garantía, 30 días, 3 meses, 6 meses, 1 año, 2 años o un número personalizado de días), para que cada producto tenga su política de garantía preconfigurada sin depender de valores fijos o cables rígidos en la interfaz.

**Why this priority**: Es la raíz de la verdad del negocio. Un producto como un cable USB o mouse genérico no tiene la misma garantía que una placa madre, procesador o laptop. Configurar la garantía desde el catálogo garantiza consistencia automática en todas las ventas.

**Independent Test**:
1. Crear o editar un producto "Monitor Gamer 24" en el catálogo asignándole "12 meses (365 días)" de garantía.
2. Guardar el producto y verificar que al consultar la ficha o recargar la página, el valor de garantía configurado persiste intacto.

**Acceptance Scenarios**:
1. **Given** el usuario Dueño en el formulario de creación/edición de producto en el catálogo, **When** visualiza el campo de garantía técnica, **Then** dispone de opciones preconfiguradas comunes (0 días / Sin garantía, 15 días, 30 días, 3 meses / 90 días, 6 meses / 180 días, 1 año / 365 días, 2 años / 730 días) y un selector para ingresar una cantidad personalizada de días.
2. **Given** un producto guardado con garantía de 365 días, **When** el producto es consultado en el catálogo o seleccionado en otros módulos, **Then** expone su tiempo de garantía predeterminado.
3. **Given** un producto consumible (como pasta térmica o alfombrilla) guardado sin garantía (0 días), **When** se guarda, **Then** el sistema almacena 0 días sin errores de validación.

---

### User Story 2 - Aplicación Ergonómica y Edición Rápida en el Punto de Venta (POS) (Priority: P2)

Como Cajero o Vendedor en el Punto de Venta (POS), quiero que al añadir un producto al carrito de venta este herede automáticamente su garantía predeterminada del catálogo, mostrando un distintivo visual limpio y compacto en la fila del carrito, con la posibilidad de pulsar dicho distintivo para registrar el número de serie (S/N) o ajustar excepcionalmente el plazo de garantía si el Dueño lo autoriza, sin sobrecargar la pantalla ni entorpecer la velocidad de cobro.

**Why this priority**: La velocidad en caja es vital en una tienda de informática. Los vendedores agregan decenas de productos rápidos; saturar cada fila con menús desplegables obligatorios reduce la agilidad. Un indicador compacto con acceso emergente mantiene la fluidez para accesorios y permite rigurosidad para equipos serializados.

**Independent Test**:
1. Agregar al carrito del POS un producto que tiene configurado 1 año de garantía en el catálogo.
2. Verificar que la fila muestra un chip/insignia limpio `🛡️ 365 días` sin desplegables estorbosos.
3. Hacer clic en el chip para abrir el micro-panel y asignar el número de serie `SN-998822`, cerrándolo y finalizando la venta.
4. Verificar que la venta almacena tanto el plazo de garantía de 365 días como el número de serie.

**Acceptance Scenarios**:
1. **Given** un cajero agregando un ítem al carrito de venta, **When** el ítem se incorpora a la lista de venta, **Then** su garantía se establece por defecto según lo registrado en el catálogo de productos.
2. **Given** un ítem en el carrito con garantía activa, **When** el cajero pulsa sobre la insignia de garantía o el botón de serie, **Then** se abre un diálogo modal o menú emergente enfocado donde puede escanear/escribir el número de serie y, si es necesario, cambiar el plazo de garantía para esa venta puntual.
3. **Given** un ítem que no posee garantía (0 días), **When** se lista en el carrito, **Then** el distintivo muestra `Sin garantía` de forma discreta o atenuada.

---

### User Story 3 - Respaldo Formal en Comprobante de Venta y Términos de Cobertura (Priority: P3)

Como Cliente y como Dueño de la tienda, quiero que el ticket térmico o nota de venta impresa y digital desglose con precisión la fecha límite de garantía y el número de serie de cada artículo adquirido, acompañado de la política general de garantía del negocio (ej. qué cubre y qué invalida la garantía), para respaldar formalmente la transacción y evitar malentendidos legales o reclamos improcedentes.

**Why this priority**: Proporciona certeza jurídica y comercial a ambas partes. El cliente sabe exactamente hasta qué día puede reclamar soporte técnico de fábrica y bajo qué condiciones (sellos intactos, no daño por sobretensión o líquidos).

**Independent Test**:
Emitir un comprobante de venta de un equipo con 180 días de garantía y número de serie; verificar que en el pie o detalle del comprobante figura la fecha exacta de expiración (calculada a partir de la fecha de venta + 180 días) y los términos de validez técnica.

**Acceptance Scenarios**:
1. **Given** una venta confirmada en el POS con productos con garantía, **When** se visualiza o imprime el ticket de venta, **Then** cada línea detalla: `Garantía: [X días / meses] - Vence: [DD/MM/AAAA]` y el `S/N: [Número de serie]` si fue ingresado.
2. **Given** la configuración institucional de la empresa, **When** el Dueño define las cláusulas de garantía del negocio (ej. "La garantía no cubre daños por variaciones de voltaje ni golpes"), **Then** este texto se incluye al final del comprobante de venta o documento de entrega.

---

### Edge Cases

- **Producto vendido sin garantía predeterminada (null o 0):** El sistema debe tratarlo como 0 días (`Sin garantía`), sin fecha límite de expiración en el ticket ni bloqueo en la venta.
- **Venta de múltiples unidades del mismo producto serializado (Cantidad > 1):** Si el vendedor vende 3 laptops del mismo modelo en una sola línea del carrito, ¿se debe permitir capturar múltiples números de serie o separar en líneas individuales por unidad?
  - *Regla estándar*: Si se requiere control estricto por número de serie unitario, se permite ingresar los seriales separados por comas o saltos de línea, o ingresar una línea por cada serie física para trazabilidad exacta en devoluciones.
- **Modificación excepcional de garantía por encima de la política estándar:** Si un vendedor modifica la garantía predeterminada del catálogo a un periodo mayor (ej. extender de 3 a 6 meses para cerrar una venta grande), el sistema debe registrar el valor final acordado en la venta y asociar al usuario vendedor responsable de la transacción.
- **Devolución de ítem con garantía vencida:** Si el cliente solicita cambio cuando la fecha actual supera la fecha de vencimiento (`sale_date + warranty_days`), el sistema alertará que la garantía ha expirado y solo permitirá recepción si cuenta con autorización expresa de Dueño.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El formulario de catálogo de productos (creación y edición) DEBE permitir registrar y actualizar el campo de tiempo de garantía técnica en días (`warranty_days`).
- **FR-002**: La interfaz de administración del catálogo DEBE ofrecer selectores rápidos con opciones habituales (Sin garantía, 15 días, 30 días, 3 meses, 6 meses, 1 año, 2 años) además de un campo numérico para plazos especiales.
- **FR-003**: Al agregar cualquier producto al carrito de ventas del Punto de Venta (POS), el sistema DEBE precargar automáticamente el tiempo de garantía definido en el catálogo de dicho producto.
- **FR-004**: La interfaz del carrito del POS DEBE mantener un diseño limpio y compacto, presentando la garantía mediante una insignia interactiva (chip) que no sature visualmente la grilla de productos.
- **FR-005**: Al interactuar con la insignia de garantía o control de serie de una fila del carrito, el sistema DEBE desplegar un micro-control o diálogo modal enfocado que permita:
  1. Modificar el plazo de garantía para la venta en curso con fines de agilidad comercial.
  2. Capturar o escanear mediante lector de código de barras el número de serie (`serial_number`), con soporte para registrar una o múltiples series si la cantidad es mayor a 1 (separadas por comas o saltos de línea).
- **FR-006**: La persistencia de la venta y del detalle de venta (`sale_items`) DEBE almacenar de forma inmutable el número de serie (`serial_number`) y los días de garantía acordados (`warranty_days`), garantizando que futuros cambios en el catálogo de productos no alteren ventas pasadas.
- **FR-007**: El comprobante de venta (impreso y vista previa digital) DEBE calcular y mostrar la fecha exacta de expiración de la garantía de cada producto que posea días de cobertura (`fecha de venta + warranty_days`).
- **FR-008**: El módulo de configuración de la empresa DEBE permitir configurar un texto estándar con las "Políticas y Términos de Garantía" de la tienda para que se imprima automáticamente en los comprobantes y notas de entrega.

---

### Key Entities *(include if feature involves data)*

- **Product (Producto)**:
  - Atributo: `warranty_days` (Entero, número de días de cobertura técnica por defecto. 0 representa sin garantía).
- **SaleItem (Detalle de Venta)**:
  - Atributo: `warranty_days` (Entero, días de garantía pactados en la venta).
  - Atributo: `serial_number` (Texto opcional, número de serie único del equipo físico entregado).
- **CompanySetting (Configuración de Empresa)**:
  - Atributo: `warranty_terms` (Texto, condiciones, exclusiones y términos de validez técnica de la empresa).

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los productos creados o editados en el catálogo pueden tener su plazo de garantía asignado en menos de 5 segundos mediante presets intuitivos.
- **SC-002**: Al añadir productos al carrito del POS, el tiempo de garantía se precarga automáticamente en el 100% de los casos sin requerir clics ni selecciones manuales obligatorias por parte del cajero.
- **SC-003**: La captura de número de serie y verificación de garantía en el POS se realiza en una ventana emergente o micro-control con foco automático en el campo de texto, permitiendo escaneo directo con pistola en menos de 3 segundos por equipo.
- **SC-004**: Los comprobantes de venta emitidos detallan con claridad la fecha de vencimiento de la garantía de cada ítem y los términos de servicio del negocio, reduciendo a 0 las dudas de los clientes sobre el plazo de validez.

---

## Assumptions

- Los días de garantía almacenados como un entero simple (`warranty_days`) son el estándar óptimo y universal para calcular fechas sumando días naturales a la fecha de la venta (`sale_date`).
- Un valor de `0` o `null` en `warranty_days` representa un producto consumible o de reventa sin cobertura de garantía por parte de la tienda.
- Los presets estándar del mercado informático boliviano corresponden a: Sin garantía (0d), 15 días, 1 mes (30d), 3 meses (90d), 6 meses (180d), 1 año (365d), 2 años (730d).
- La asignación de número de serie en la venta es opcional pero recomendada para hardware principal (laptops, placas madre, procesadores, fuentes, tarjetas de video, monitores).
