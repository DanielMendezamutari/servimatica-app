# Research & Architecture Decisions: 009-gestion-garantias-pos

## 1. Contexto Técnico e Identificación de Decisiones

Este documento resuelve las decisiones arquitectónicas y técnicas para la gestión centralizada de garantías técnicas en el catálogo de productos y su consumo ergonómico en el Punto de Venta (POS).

---

## 2. Decisiones de Arquitectura y Diseño

### Decisión 1: Persistencia y Entrada de Garantía en Catálogo de Productos
- **Decisión**: Exponer el campo `warranty_days` en `ProductRequest.php`, `EloquentProductRepository.php` y en el componente Vue `AddProductDrawer.vue`. En la interfaz, proporcionar un selector con presets populares (0d - Sin garantía, 15d, 30d, 90d, 180d, 365d, 730d) y una opción "Personalizado" que habilite un `VTextField` numérico.
- **Razón**: Permite configurar rápidamente productos comunes con 1 solo clic y a la vez da soporte a garantías inusuales (ej. 45 días, 3 años) sin limitar al usuario.
- **Alternativas descartadas**:
  - *Hardcodear solo en el frontend*: Rechazado porque genera inconsistencia entre lo que se vende y lo que se muestra en catálogo, y viola el principio de verdad única de datos en tiempo real.
  - *Tabla separada de garantías (`warranties`)*: Sobrediseño (YAGNI). Un entero `warranty_days` en `products` es suficiente, simple y eficiente.

### Decisión 2: Ergonomía de Fila en Carrito del POS y Captura de Series (S/N)
- **Decisión**: Limpiar la fila del carrito del POS eliminando los `VSelect` y `VTextField` incrustados en cada fila. En su lugar, mostrar un badge/chip compacto:
  - Si `warranty_days > 0`: `VChip` color `primary` o `info` con icono `ri-shield-check-line` y texto (ej. `🛡️ 180 días` o `🛡️ 1 año`). Si tiene serie, agregar icono `ri-barcode-line`.
  - Si `warranty_days == 0`: `VChip` atenuado `Sin garantía`.
  - Al hacer clic en el chip o en un botón de acción rápida `ri-shield-keyhole-line`, se abre un micro-modal centrado con foco automático en el campo `serial_number`.
  - El modal permite escanear una o múltiples series (separadas por comas o saltos de línea) y ajustar los días de garantía si se acuerda una excepción comercial con el cliente.
- **Razón**: Maximiza la velocidad del cajero al escanear productos sin saturar la pantalla con controles innecesarios para cables, mouses o productos rápidos.

### Decisión 3: Términos Institucionales de Garantía en Comprobantes
- **Decisión**: Añadir el campo `warranty_terms` (`TEXT`, nullable) a la tabla `company_settings` mediante una migración Laravel, y exponerlo en el formulario de configuración institucional `settings/company.vue`. En el modal de impresión de tickets térmicos/recibos (`ReceiptDialog.vue`), incluir la sección de "Términos y Condiciones de Garantía" al final del comprobante si existe dicho texto configurado.
- **Razón**: Permite al Dueño redactar y actualizar libremente las exclusiones de garantía (ej. caídas, variaciones eléctricas, sellos rotos) sin necesidad de modificar plantillas de código fuente.

---

## 3. Matriz de Compatibilidad con la Constitución

| Principio Constitucional | Evaluación | Mitigación / Cumplimiento |
|---|---|---|
| **I. Simplicidad (KISS & YAGNI)** | Cumple al 100% | Reutiliza la columna `products.warranty_days` y `sale_items.warranty_days` ya existentes. No inventa microservicios ni tablas innecesarias. |
| **II. Mercado Boliviano / BOB** | Cumple al 100% | Presets en español y adaptados a las prácticas comunes del mercado informático boliviano. |
| **III. Cero Alcance Fantasma** | Cumple al 100% | El alcance se restringe exactamente a los 3 pilares clarificados: Catálogo, Carrito POS y Términos en Empresa. |
| **IV. Verificable por no técnico** | Cumple al 100% | El Dueño puede crear un producto con garantía, venderlo en el POS y verificar que el ticket desglosa la garantía en menos de 2 minutos. |
| **V. Verdad Única en Tiempo Real** | Cumple al 100% | El POS hereda la garantía del producto directamente desde la base de datos central. |
| **VI. Privacidad y Seguridad** | Cumple al 100% | Los costos de compra se preservan confidenciales y no se exponen al vendedor. |
