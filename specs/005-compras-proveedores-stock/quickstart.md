# Quickstart & Guía de Verificación: Capítulo 5

**Feature**: `005-compras-proveedores-stock`  
**Date**: 2026-10-03  
**Status**: Ready  

Esta guía permite verificar en menos de 5 minutos el funcionamiento completo de Proveedores, Compras, Incremento de Inventario y Privacidad de Costos en Servimática App.

---

## 1. Prerrequisitos
- Servidor local activo en `http://servimatica-app.test` o Apache de XAMPP.
- Frontend activo en `http://127.0.0.1:5173/`.
- Migraciones ejecutadas:
  ```bash
  php artisan migrate
  ```

---

## 2. Escenarios de Prueba Rápida

### Escenario A: Registro de Proveedor Mayorista
1. Iniciar sesión como Dueño (`admin` / `password`).
2. En el menú lateral, ingresar a **Proveedores**.
3. Hacer clic en **"Nuevo Proveedor"**.
4. Completar:
   - Razón Social: `Deltron Bolivia SRL`
   - NIT: `1029384019`
   - Contacto: `Lic. Mario Terán`
   - Celular / WhatsApp: `77123456`
   - Ciudad: `Santa Cruz`
5. Guardar. **Verificación visual:** El proveedor aparece de inmediato en la tabla de proveedores activos.

### Escenario B: Recepción de Compra e Incremento de Stock
1. Ir a **Compras y Recepción** en el menú lateral.
2. Hacer clic en **"Nueva Compra"**.
3. Seleccionar el proveedor `Deltron Bolivia SRL`.
4. Ingresar N° de Factura: `FC-90812` y fecha de compra.
5. Seleccionar Condición de Pago: `Al Contado` con método `Transferencia`.
6. En la lista de productos:
   - Buscar un producto con stock conocido (ej. un producto con stock 2 y costo actual Bs. 200).
   - Ingresar Cantidad: `5`.
   - Ingresar Costo Unitario de Compra: `Bs. 220,00`.
   - Fijar Nuevo Precio de Venta: `Bs. 290,00`.
7. Presionar **"Confirmar Recepción de Compra"**.
8. **Verificación visual:**
   - Se genera el correlativo `COM-000001`.
   - El sistema emite la Nota de Recepción formal de mercadería.

### Escenario C: Verificación en Catálogo de Productos y POS
1. Ir a **Productos** en el menú lateral.
2. Localizar el producto comprado en el Escenario B.
3. **Verificación:**
   - El stock subió automáticamente de `2` a `7`.
   - El costo unitario visible para el Dueño se actualizó a `Bs. 220,00`.
   - El precio de venta al público en el catálogo y en el Punto de Venta (POS) se actualizó a `Bs. 290,00`.
4. Hacer clic en **Historial de Stock** del producto: se observa la traza inmutable tipo `ENTRADA` por 5 unidades con el motivo `Compra Proveedor Deltron Bolivia SRL - Doc FC-90812`.

### Escenario D: Privacidad Estricta del Vendedor (Seguridad)
1. Cerrar sesión e iniciar sesión como Vendedor (`carlos` o usuario vendedor con PIN/clave).
2. **Verificación:**
   - Los enlaces "Proveedores" y "Compras" **no aparecen** en el menú de navegación.
   - Si intenta forzar la URL `http://127.0.0.1:5173/purchases` o `http://127.0.0.1:5173/suppliers`, el enrutador CASL lo redirige a "No Autorizado".
   - Al consultar el catálogo de productos o el POS, el vendedor ve las 7 unidades disponibles al precio de Bs. 290,00 pero **no tiene acceso visual ni en payload API al costo de Bs. 220,00 ni a los datos del proveedor**.

### Escenario E: Anulación de Compra con Restitución de Stock
1. Iniciar sesión nuevamente como Dueño (`admin`).
2. Ir a **Compras y Recepción**.
3. Seleccionar la compra `COM-000001` y hacer clic en **"Anular Compra"**.
4. Ingresar el motivo: `Devolución de mercadería por falla de origen`.
5. Confirmar anulación.
6. **Verificación:**
   - El estado de la compra cambia a `Anulada`.
   - El stock del producto vuelve a restar las 5 unidades regresando a `2`.
   - En el historial de movimientos de stock queda registrada la salida con el motivo de anulación.
