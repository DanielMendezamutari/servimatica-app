# Quickstart Validation Guide: 009-gestion-garantias-pos

Este documento describe los escenarios paso a paso para verificar de forma visual y funcional la gestión de garantías técnicas en el catálogo y su aplicación ergonómica en el Punto de Venta.

---

## 1. Prerrequisitos
- Servidor de base de datos MySQL (XAMPP) y Apache activos.
- Frontend activo (`pnpm --prefix admin-starter-kit run dev` en `http://localhost:5173`).
- Usuario con rol Dueño (ej. `admin@servimatica.com`) y un turno de caja abierto en el POS.

---

## 2. Escenario 1: Configurar Garantía Técnica en el Catálogo de Productos
1. Iniciar sesión como Dueño y navegar a **Productos** (`/products`).
2. Abrir el formulario "Nuevo Producto" (o editar uno existente, ej. un Procesador o Laptop).
3. Localizar el selector de **Garantía Técnica**:
   - Probar seleccionar el preset rápido `1 año (365 días)`.
   - Alternativamente, seleccionar `Personalizado` y escribir `45` días.
4. Guardar el producto.
5. Recargar la tabla o volver a abrir el producto y verificar que los días de garantía guardados se muestran correctamente.

---

## 3. Escenario 2: Precarga y Ergonomía en el Carrito del POS
1. Navegar a **Punto de Venta** (`/pos`).
2. Si la caja está cerrada, abrir un turno con monto inicial.
3. Buscar y agregar al carrito el producto guardado con garantía de 365 días.
4. **Verificación visual ergonómica:**
   - La fila del carrito NO contiene menús desplegables toscos ni inputs invasivos.
   - En su lugar, luce un chip compacto y legible: `🛡️ 365 días`.
5. Hacer clic sobre el chip o el botón de serie:
   - Se abre el micro-modal emergente enfocado directamente en el campo **Número de Serie (S/N)**.
   - Ingresar o escanear una serie (ej. `SN-RYZEN-554422`).
   - (Opcional) Modificar el plazo de garantía si hubo un acuerdo comercial especial.
   - Confirmar el modal.
6. Notar que la fila del carrito ahora muestra el chip con la garantía y un icono de código de barras indicando que la serie está asignada.

---

## 4. Escenario 3: Cobro y Emisión de Comprobante con Expiración y Términos
1. Seleccionar el método de pago (ej. Efectivo) y presionar **Procesar Venta**.
2. Al confirmarse el cobro, se abre el diálogo del comprobante de venta (`ReceiptDialog`):
   - Verificar que el detalle del producto muestra:
     `Garantía: 365 días (Vence: [Fecha actual + 1 año])`
     `S/N: SN-RYZEN-554422`
   - Si la empresa configuró términos de garantía en Ajustes, verificar que al pie del ticket se imprimen las cláusulas de garantía del negocio.
3. Imprimir el ticket térmico o descargar el comprobante.
