# Technical Research: Capítulo 2 — Catálogo de Productos e Inventario

**Feature**: `002-catalogo-inventario`
**Date**: 2026-09-26
**Status**: Completed

---

## 1. Decisiones de Arquitectura y Stack

### Decisión 1: Precisión monetaria para precios en Bolivianos (Bs.)

- **Decisión:** Usar `DECIMAL(10,2)` para los campos `cost_price` y `sale_price` en la tabla `products`. Formato: hasta 99.999.999,99 Bs.
- **Justificación:** El estándar ISO 4217 para BOB (Boliviano) define 2 decimales. `DECIMAL(10,2)` ofrece precisión exacta sin errores de redondeo propios de `FLOAT`/`DOUBLE`, y cubre con creces el rango de precios de una tienda de computadoras (desde cables a Bs. 5 hasta servidores a Bs. 50.000+).
- **Alternativas descartadas:** `FLOAT` (descartado por errores de redondeo en operaciones monetarias); `INTEGER` en centavos (descartado por complejidad innecesaria al formatear; KISS).

### Decisión 2: Generación automática de SKU

- **Decisión:** Cuando el Dueño no proporciona un SKU, el sistema genera uno automático con formato `CAT-NNNN`, donde `CAT` son las primeras 3 letras de la categoría en mayúsculas y `NNNN` es el siguiente correlativo de productos en esa categoría.
- **Justificación:** Garantiza un código legible y organizado por categoría sin obligar al Dueño a inventar códigos manualmente. El correlativo se calcula como `MAX(correlativo actual en esa categoría) + 1`.
- **Alternativas descartadas:** UUID (descartado por ilegibilidad en mostrador); slug del nombre (descartado por colisiones con productos de nombre similar).

### Decisión 3: Paginación del lado del servidor

- **Decisión:** Implementar paginación con `paginate()` de Laravel (15 elementos por página por defecto). El frontend usa los links de paginación que devuelve la API para solicitar las siguientes páginas.
- **Justificación:** La spec anticipa 100+ productos. La carga completa en cliente degrada la UX y consume memoria innecesariamente. La paginación de Laravel es nativa, probada y sencilla.
- **Alternativas descartadas:** Carga completa en el cliente con paginación solo en frontend (descartado por rendimiento); cursor-based pagination (descartado por complejidad innecesaria para un catálogo de tienda pequeña; YAGNI).

### Decisión 4: Filtrado de datos financieros por rol

- **Decisión:** Doble barrera de seguridad:
  1. **Backend:** El endpoint `GET /api/products` detecta el rol del usuario autenticado. Si es `vendedor`, el serializer excluye los campos `cost_price` y `min_stock` de la respuesta JSON. El margen nunca se calcula ni transmite al vendedor.
  2. **Frontend:** Las columnas de costo y margen se ocultan con directivas CASL (`v-if="can('manage', 'Product')"`) para que nunca se rendericen en la interfaz del Vendedor.
- **Justificación:** Cumple con el Principio VI de la Constitución (Privacidad y Seguridad). La doble barrera previene la exposición accidental incluso si el frontend es manipulado.
- **Alternativas descartadas:** Solo filtrar en el frontend (descartado por vulnerabilidad: el JSON con costos estaría disponible en DevTools).

### Decisión 5: Historial de stock como entidad separada (StockMovement)

- **Decisión:** Cada ajuste de stock genera un registro inmutable en la tabla `stock_movements` con stock anterior, stock nuevo, cantidad ajustada, motivo, usuario y timestamp. Los movimientos no se editan ni eliminan.
- **Justificación:** Garantiza la trazabilidad completa exigida por RF-020 y SC-008. La inmutabilidad previene manipulación del historial.
- **Alternativas descartadas:** Columna `stock_log` tipo JSON en la tabla `products` (descartado por limitaciones de consulta, filtrado y ordenamiento en MySQL).

### Decisión 6: Estructura de navegación del catálogo

- **Decisión:** Agregar un grupo de navegación "Catálogo" en el sidebar con dos subentradas:
  - "Categorías" → `/categories` (solo Dueño)
  - "Productos" → `/products` (Dueño y Vendedor, con vistas diferenciadas)
- **Justificación:** Separa la gestión de categorías (administración) del catálogo de consulta (operación). El Vendedor solo ve "Productos" bajo el grupo "Catálogo".
- **Alternativas descartadas:** Menú plano sin agrupación (descartado porque se prevé crecimiento de subentradas en capítulos futuros como Proformas/Cotizaciones).

---

## 2. Resumen de Respuestas Técnicas a los Requisitos

| Requisito de Negocio | Solución Técnica en el Plan |
|---|---|
| **Categorías con CRUD y estado** | Tabla `categories` con `status ENUM('active','inactive')`, CRUD completo con middleware `owner`. |
| **Productos con costos privados** | Campos `cost_price` y `sale_price` en `products`; serializer condicional por rol en la respuesta JSON. |
| **Margen automático (Bs. y %)** | Calculado en el frontend del Dueño: `margin = sale_price - cost_price`, `percent = (margin / cost_price) * 100`. No se almacena; es derivado. |
| **SKU único auto-generado** | Columna `sku VARCHAR(50) UNIQUE`; si vacío al crear, se genera `CAT-NNNN` con correlativo por categoría. |
| **Stock mínimo configurable** | Campo `min_stock INT UNSIGNED DEFAULT 0` en `products`; badge visual si `stock <= min_stock`. |
| **Ajuste de stock con historial** | Tabla `stock_movements` con `product_id`, `previous_stock`, `new_stock`, `quantity`, `reason`, `user_id`, `type ENUM('in','out')`. |
| **Categoría inactiva oculta productos** | Query del Vendedor: `WHERE categories.status = 'active' AND products.status = 'active'`. |
| **Productos agotados visibles como "Agotado"** | Condicional en la vista: si `stock == 0`, mostrar chip rojo "Agotado". |
| **Búsqueda rápida** | `WHERE (name LIKE %term% OR sku LIKE %term%)` con filtro opcional por `category_id`. |
| **Paginación servidor** | `Product::paginate(15)` con parámetros `page` y `per_page` en el query string. |
