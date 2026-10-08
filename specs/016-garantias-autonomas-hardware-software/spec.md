# Feature Specification: 016-garantias-autonomas-hardware-software

**Feature Branch**: `016-garantias-autonomas-hardware-software`  
**Created**: 2026-10-08  
**Status**: Draft  
**Input**: User description: "tiene que tener dos tipos de garantia. garantia de hardware y garantia de software y cada garantia debe ser autonoma. un producto puede tener garantia de 1 año de software y 2 años de hardware y viceversa"

---

## Clarifications

### Session 2026-10-08
- Q: ¿Cómo deben gestionarse los términos y condiciones legales de garantía en la configuración institucional de la empresa y en el pie de los comprobantes impresos? → A: Términos Consolidados: un único bloque general en Ajustes de Empresa (`warranty_terms`) donde el Dueño redacta las políticas de cobertura de hardware y software de forma unificada, manteniéndose simple e imprimiéndose al pie de los comprobantes.
- Q: ¿Cómo deben mostrarse las dos garantías en la tabla principal del Catálogo de Productos (`pages/products/index.vue`)? → A: Badges Duales en Misma Columna: dentro de la columna "Garantía", mostrar dos micro-chips compactos (`🛡️ HW: 1a` y `💻 SW: 3m`). Si ambos valores son 0, mostrar un único chip neutro `Sin garantía`.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Parametrización Dual e Independiente de Garantías en el Catálogo de Productos (Priority: P1) 🎯 MVP

Como Administrador o Dueño del negocio, quiero definir en la ficha de cada producto dos tiempos de garantía completamente autónomos: **Garantía de Hardware** y **Garantía de Software**, para que el catálogo refleje fielmente las políticas comerciales y técnicas de la tienda de informática (por ejemplo, una laptop con 2 años de garantía de hardware y 1 año de soporte de software, o un mouse con 6 meses de hardware y 0 días de software).

**Why this priority**: Es la base de datos y la regla de verdad del negocio. Sin esta separación en el catálogo, ni el punto de venta, ni las proformas, ni los comprobantes pueden calcular los plazos de forma diferenciada.

**Independent Test**:
1. Crear o editar un producto (ej. "Laptop ASUS TUF F15") configurando:
   - Garantía de Hardware: 730 días (2 años).
   - Garantía de Software: 365 días (1 año).
2. Guardar y verificar que ambos valores persisten intactos y se exponen en la ficha y listado del catálogo.
3. Crear un periférico (ej. "Mouse Logitech G203") configurando:
   - Garantía de Hardware: 180 días (6 meses).
   - Garantía de Software: 0 días (Sin garantía).
4. Guardar y comprobar que ambos valores se almacenan sin errores ni bloqueos.

**Acceptance Scenarios**:
1. **Given** el usuario Dueño en el formulario de creación o edición de producto, **When** accede a la sección de garantías, **Then** dispone de dos selectores independientes (uno para Hardware y otro para Software) con opciones rápidas (Sin garantía / 0 días, 15 días, 30 días, 90 días, 180 días, 1 año / 365 días, 2 años / 730 días) y un campo numérico para días personalizados.
2. **Given** un producto con 2 años de hardware y 1 año de software, **When** se consulta en el listado de productos o en la vitrina 360°, **Then** se muestran ambos distintivos de garantía de forma clara y diferenciada.
3. **Given** un producto que no requiere garantía de software (como un disco sólido o memoria RAM), **When** se guarda con 0 días de software y 365 días de hardware, **Then** el sistema almacena los datos correctamente.

---

### User Story 2 - Aplicación Fluida y Ajuste Autónomo en el Punto de Venta (POS) (Priority: P2)

Como Cajero o Vendedor en el POS, quiero que al añadir un producto al carrito de compras se hereden automáticamente ambos plazos de garantía predeterminados del catálogo, permitiéndome abrir el modal de garantía técnica para visualizar o modificar de forma independiente cualquiera de los dos plazos (Hardware o Software) y registrar el número de serie (S/N) del equipo físico antes de cobrar.

**Why this priority**: Permite agilidad comercial al cerrar ventas en mostrador (ej. negociar con un cliente 6 meses extra de soporte de software como cortesía de venta sin alterar la garantía oficial de fábrica del hardware).

**Independent Test**:
1. Agregar al carrito del POS una laptop con 730 días de Hardware y 365 días de Software.
2. Verificar que en la fila del carrito se visualiza la presencia de ambas garantías.
3. Abrir el modal de configuración de la fila, cambiar la Garantía de Software a 180 días (manteniendo Hardware en 730 días) e ingresar el número de serie `SN-LTP-99441`.
4. Guardar y confirmar la venta.
5. Verificar que la venta persistió exactamente 730 días de hardware, 180 días de software y el número de serie.

**Acceptance Scenarios**:
1. **Given** un ítem agregado al carrito de venta, **When** se visualiza su fila, **Then** se muestran las insignias correspondientes a la garantía de hardware y a la garantía de software.
2. **Given** el diálogo modal de garantía de una fila del carrito, **When** el vendedor interactúa con él, **Then** puede cambiar de forma separada los días de hardware y los días de software mediante selectores rápidos o valor personalizado, además de pistolear/ingresar el número de serie físico.
3. **Given** la confirmación de la venta, **When** el sistema guarda el detalle de los ítems (`sale_items`), **Then** almacena de manera inmutable `warranty_hardware_days`, `warranty_software_days`, las fechas calculadas de vencimiento de cada una, y el `serial_number`.

---

### User Story 3 - Desglose Oficial en Comprobante / Ticket Térmico y Proformas (Priority: P3)

Como Cliente y como Dueño del negocio, quiero que el ticket impreso de venta (térmico de 80mm/58mm), la vista digital del comprobante y las cotizaciones/proformas desglosen por separado la fecha límite de vencimiento de la Garantía de Hardware y la fecha límite de la Garantía de Software para cada producto serializado, respaldando con exactitud los derechos del cliente y protegiendo a la tienda ante reclamos imprecisos.

**Why this priority**: Es el documento formal que se entrega al cliente. Evita litigios o reclamos cuando un cliente solicita servicio técnico gratuito para software cuando sólo su hardware sigue en garantía.

**Independent Test**:
1. Realizar una venta en el POS con fecha actual de un ítem con 365 días de Hardware y 90 días de Software.
2. Imprimir o visualizar el ticket de venta.
3. Verificar que el ticket detalla:
   - `🛡️ G. Hardware: 365 días (Vence: [Fecha venta + 365 días])`
   - `💻 G. Software: 90 días (Vence: [Fecha venta + 90 días])`
   - `S/N: [Serie]`
4. Verificar que las cláusulas institucionales de garantía al pie del ticket respaldan ambas coberturas.

**Acceptance Scenarios**:
1. **Given** una venta registrada con garantías activas, **When** se genera el ticket o comprobante, **Then** se calculan y muestran las dos fechas de expiración independientes (`fecha_venta + dias_hardware` y `fecha_venta + dias_software`).
2. **Given** un ítem que sólo tiene garantía de hardware (software = 0 días), **When** se imprime el comprobante, **Then** únicamente se desglosa la garantía de hardware, omitiendo la de software para mantener limpio el ticket.
3. **Given** una cotización formal enviada por WhatsApp o PDF, **When** el cliente la recibe, **Then** puede ver con claridad la oferta de garantía separada de hardware y software ofrecida por el negocio.

---

### User Story 4 - Consulta y Dictamen de Cobertura Postventa (Priority: P4)

Como Técnico o Administrador en el módulo de Ventas / Postventa, quiero consultar el estado de garantía de una venta ingresando su número de comprobante o número de serie, y ver un dictamen claro y diferenciado para cada ítem: **Hardware Vigente/Vencido** y **Software Vigente/Vencido**, para saber de inmediato qué tipo de servicio o repuesto corresponde sin costo y cuál debe cotizarse.

**Why this priority**: Da soporte operativo al taller de servicio técnico de Servimática, reduciendo tiempos de consulta en mostrador.

**Independent Test**:
1. Consultar una venta realizada hace 120 días que tenía 365 días de Hardware y 90 días de Software.
2. Verificar que el sistema reporta:
   - Hardware: **Vigente** (quedan 245 días).
   - Software: **Vencido** (expiró hace 30 días).

**Acceptance Scenarios**:
1. **Given** la consulta de verificación de garantía de una venta, **When** el sistema evalúa los ítems, **Then** calcula y expone el estado de cada una de las dos garantías de manera independiente (`Activa`, `Vencida` o `Sin garantía`).
2. **Given** un cliente que trae un equipo con hardware vigente pero software vencido, **When** el técnico verifica en pantalla, **Then** el sistema muestra una alerta visual diferenciada que le indica que el soporte lógico no tiene cobertura vigente.

---

## Edge Cases

- **Producto sin ninguna garantía (Hardware = 0 y Software = 0):** El sistema lo trata como `Sin garantía` para ambos rubros; no muestra fechas de vencimiento ni entorpece la venta.
- **Producto con Garantía de Hardware pero Cero Software (o viceversa):** Casos cotidianos como memorias RAM, discos duros o placas de video tienen garantía física pero 0 software. El sistema debe operar con total autonomía permitiendo `hardware_days > 0` y `software_days = 0` (y viceversa para licencias o servicios lógicos).
- **Venta de múltiples unidades del mismo ítem:** Si se venden 2 o más unidades en una misma línea, ambas heredan los mismos plazos de garantía acordados en esa línea, y se permite ingresar las series de cada unidad física separadas por coma.
- **Ajuste de días en el POS con fecha retroactiva:** Si por alguna razón administrativa se registra una venta, los días de vencimiento se calculan estrictamente a partir de la fecha de la venta (`sale_date`).

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El catálogo de productos DEBE permitir definir y actualizar de manera autónoma el tiempo de garantía de hardware en días (`warranty_hardware_days`) y el tiempo de garantía de software en días (`warranty_software_days`).
- **FR-002**: El formulario de catálogo DEBE ofrecer para ambos tipos de garantía selectores rápidos (Sin garantía / 0 días, 15 días, 30 días, 90 días, 180 días, 1 año, 2 años) y un campo numérico para días personalizados.
- **FR-003**: Al agregar un producto al carrito de compras del POS o a una cotización, el sistema DEBE precargar automáticamente tanto la garantía de hardware como la de software configuradas en el catálogo.
- **FR-004**: La interfaz del carrito de ventas del POS DEBE mostrar distintivos compactos para ambas garantías y permitir abrir un diálogo modal para modificarlas de forma independiente para esa venta puntual.
- **FR-005**: El registro inmutable de la venta (`sale_items`) DEBE guardar: `warranty_hardware_days`, `warranty_hardware_expires_at`, `warranty_software_days`, `warranty_software_expires_at` y `serial_number`.
- **FR-006**: La fecha de expiración de hardware DEBE calcularse automáticamente como `fecha de venta + warranty_hardware_days`.
- **FR-007**: La fecha de expiración de software DEBE calcularse automáticamente como `fecha de venta + warranty_software_days`.
- **FR-008**: El ticket impreso y la vista previa del comprobante DEBEN desglosar de forma independiente los días y fechas de vencimiento de Hardware y Software para cada producto que tenga más de 0 días en cada tipo.
- **FR-009**: El diálogo de verificación de garantía de venta (`WarrantyCheckDialog`) DEBE evaluar y exhibir con colores e insignias el estado autónomo de cada garantía (Vigente / Vencida / Sin garantía).
- **FR-010**: El sistema DEBE garantizar compatibilidad con los datos existentes asignando por defecto los valores actuales de `warranty_days` a la garantía de hardware (`warranty_hardware_days`) y 0 días a software si no estuviese definido.

---

### Key Entities *(include if feature involves data)*

- **Producto (`products`)**: Representa el artículo de inventario. Nuevos atributos clave:
  - `warranty_hardware_days`: Días de garantía física predeterminada (entero >= 0).
  - `warranty_software_days`: Días de garantía lógica/soporte predeterminada (entero >= 0).
- **Detalle de Venta (`sale_items`)**: Representa la línea vendida y las condiciones pactadas:
  - `warranty_hardware_days`: Días pactados de garantía de hardware.
  - `warranty_hardware_expires_at`: Fecha límite de garantía de hardware.
  - `warranty_software_days`: Días pactados de garantía de software.
  - `warranty_software_expires_at`: Fecha límite de garantía de software.
  - `serial_number`: Código o números de serie físicos del equipo.
- **Verificación de Cobertura (`WarrantyCheck`)**: Objeto de respuesta que dictamina el estado de cobertura:
  - Hardware: Días restantes, fecha de vencimiento, estado (`active`, `expired`, `none`).
  - Software: Días restantes, fecha de vencimiento, estado (`active`, `expired`, `none`).

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un vendedor o administrador puede registrar un producto con garantías distintas de Hardware y Software en menos de 30 segundos.
- **SC-002**: El 100% de las ventas generadas en el POS con garantías configuradas almacenan las dos fechas exactas de vencimiento sin discrepancias de cálculo.
- **SC-003**: El ticket de venta impreso refleja con claridad ambas garantías en formato legible para cualquier cliente en menos de 2 segundos de emisión.
- **SC-004**: En el módulo de verificación postventa, un técnico puede dictaminar de forma visual e inequívoca el estado de cobertura de hardware y software de un equipo en menos de 5 segundos.

---

## Assumptions

- La unidad de cómputo para ambas garantías es en días para mantener precisión aritmética y coherencia con el motor actual del sistema.
- El número de serie (`serial_number`) corresponde al equipo físico y ampara a ambos tipos de cobertura.
- Si un producto sólo tiene garantía física (ej. piezas de hardware), la garantía de software permanece en 0 días y no genera líneas innecesarias en el comprobante.
