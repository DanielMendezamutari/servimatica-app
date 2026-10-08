# Research & Architectural Decisions: 016-garantias-autonomas-hardware-software

**Feature**: 016-garantias-autonomas-hardware-software  
**Date**: 2026-10-08  
**Status**: Resolved  

---

## Decision 1: Modelo de Persistencia para Garantías Autónomas

### Contexto
En tiendas de informática como Servimática, el soporte físico (piezas de hardware amparadas por fabricante y número de serie) opera con reglas y plazos completamente distintos al soporte lógico (sistema operativo, controladores, programas y configuración expuestos a desconfiguración o virus del usuario). Actualmente existía una única columna `warranty_days` en `products` y `sale_items`.

### Decisión
Agregar columnas especializadas e independientes en base de datos:
- En `products`:
  - `warranty_hardware_days` (unsigned integer, default 0).
  - `warranty_software_days` (unsigned integer, default 0).
  - Conservar temporalmente `warranty_days` mapeado a `warranty_hardware_days` para evitar regresiones en cualquier vista secundaria.
- En `sale_items`:
  - `warranty_hardware_days` (unsigned integer, default 0).
  - `warranty_hardware_expires_at` (date, nullable).
  - `warranty_software_days` (unsigned integer, default 0).
  - `warranty_software_expires_at` (date, nullable).
  - `serial_number` (string 100, nullable).

### Rationale
- Cumple el principio **KISS & YAGNI**: evita crear tablas relacionales pesadas de "pólizas" o "tipos de garantía" que agregarían complejidad innecesaria a un POS que debe cobrar rápido.
- Almacenamiento directo e inmutable en cada ítem de venta (`sale_items`), garantizando que si el catálogo cambia en el futuro, las ventas históricas preservan exactamente los plazos pactados con el cliente.

### Alternativas Consideradas
- *Tabla relacional `product_warranties`:* Rechazada por sobre-ingeniería; requeriría múltiples joins en el carrito y transacciones complejas para algo que siempre son exactamente dos dimensiones (Hardware y Software).

---

## Decision 2: Ergonomía Visual en Catálogo y Punto de Venta (POS)

### Contexto
El vendedor en mostrador necesita rapidez para cobrar. No puede verse obligado a interactuar con múltiples campos de texto obligatorios para cada producto si este no requiere garantía, pero cuando vende una laptop o equipo armado, debe tener la facilidad de ajustar Hardware o Software de forma independiente.

### Decisión
1. **En Catálogo (`AddProductDrawer.vue` / `EditProductDrawer.vue`):**
   - Dos bloques limpios en paralelo o apilados:
     - 🛡️ **Garantía de Hardware:** Chips rápidos (0d, 15d, 30d, 90d, 180d, 1 año, 2 años) + selector de días personalizados.
     - 💻 **Garantía de Software:** Chips rápidos (0d, 15d, 30d, 90d, 180d, 1 año, 2 años) + selector de días personalizados.
2. **En POS (`pages/pos/index.vue`):**
   - En la fila del carrito: Dos micro-chips distintivos:
     - `🛡️ HW: 1a` (o días)
     - `💻 SW: 3m` (o días)
   - Al tocar cualquiera de los dos o el botón de serie, se abre el `WarrantyModal`:
     - Selector dual para ajustar días de Hardware y días de Software de forma independiente.
     - Campo de texto enfocado para escanear/pistolear el número de serie (S/N).

### Rationale
- Mantiene la pantalla despejada para accesorios rápidos (cables, pastas térmicas) y ofrece precisión quirúrgica para laptops y equipos serializados.

---

## Decision 3: Formato del Ticket Térmico de Venta y Comprobantes

### Contexto
El cliente recibe un ticket impreso de 80mm o 58mm o un comprobante digital. El ticket debe ser transparente para evitar reclamos injustificados en mostrador.

### Decisión
El motor de impresión y vista previa (`ReceiptPrintDialog.vue` y `routes/web.php` / endpoint PDF) evaluará cada ítem de la venta:
- Si `warranty_hardware_days > 0`:
  `🛡️ G. Hardware: {dias} días (Vence: {DD/MM/AAAA})`
- Si `warranty_software_days > 0`:
  `💻 G. Software: {dias} días (Vence: {DD/MM/AAAA})`
- Si tiene serie:
  `S/N: {serial_number}`
- Si ambos días son 0: No se imprime bloque de garantía para ese ítem, ahorrando papel térmico.

### Rationale
- Da certeza jurídica y operativa total a la tienda y al cliente.

---

## Decision 4: Verificación Dual en Postventa (`/sales/{id}/warranty-check`)

### Contexto
Cuando un cliente regresa a la tienda con una queja técnica (ej. "se colgó Windows", "no da video"), el técnico en taller o el cajero abre la verificación de garantía.

### Decisión
El endpoint y el diálogo `WarrantyCheckDialog.vue` mostrarán dos columnas o tarjetas de estado por producto:
1. **Estado de Hardware:**
   - Vigente (verde) / Vencida (rojo) / Sin cobertura (gris).
   - Fecha de vencimiento y días restantes.
2. **Estado de Software:**
   - Vigente (verde) / Vencida (rojo) / Sin cobertura (gris).
   - Fecha de vencimiento y días restantes.

### Rationale
- Permite dictaminar con certeza: *"Estimado cliente, su hardware está cubierto al 100%, pero su garantía de software venció hace 2 meses; el mantenimiento preventivo y formateo tiene un costo de servicio técnico de Bs. 50"*.
