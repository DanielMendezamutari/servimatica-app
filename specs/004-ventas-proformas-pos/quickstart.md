# Quickstart & Guía de Verificación: Capítulo 4

**Feature**: `004-ventas-proformas-pos`  
**Date**: 2026-10-03  
**Status**: Ready

Esta guía permite a cualquier persona (técnica o no técnica) validar el 100% de la funcionalidad del Punto de Venta, Proformas y Caja en menos de 5 minutos navegando en el panel administrativo.

---

## 1. Prerrequisitos de Ejecución

- Servidor local activo en `http://127.0.0.1/servimatica-app/public` o `http://servimatica-app.test`.
- Frontend activo en `http://127.0.0.1:5173/`.
- Migraciones ejecutadas:
  ```bash
  php artisan migrate
  ```

---

## 2. Escenarios de Prueba Rápida

### Escenario A: Apertura de Turno de Caja
1. Iniciar sesión como Vendedor (`vendedor` / `password123`) o Dueño (`admin` / `password123`).
2. Ir a **Punto de Venta (POS)** en el menú lateral.
3. El sistema muestra el diálogo interactivo de **Apertura de Turno de Caja**.
4. Ingresar un fondo inicial de cambio en efectivo de `Bs. 100,00` y presionar "Abrir Turno de Caja".
5. **Verificación visual:** Se desbloquea la pantalla de ventas y en la parte superior se observa el chip indicador: `Caja Abierta: Bs. 100,00`.

### Escenario B: Emisión de Proforma y Envío a WhatsApp
1. En la pantalla del POS, buscar un producto (ej. "Laptop") y agregarlo al carrito con cantidad `1`.
2. Presionar el botón **"Generar Proforma"**.
3. Ingresar datos del cliente: Nombre "Alejandro Siles", Celular "71234567".
4. Presionar "Guardar Proforma".
5. **Verificación:**
   - Se muestra el resumen con código correlativo `PRF-000001`.
   - El botón "Descargar PDF" genera la hoja membretada en tamaño Carta.
   - El botón "Enviar por WhatsApp" abre un enlace con el mensaje preformateado y el total en Bolivianos.
   - Ir a **Catálogo de Productos** y verificar que el stock físico **no fue descontado**.

### Escenario C: Venta en Mostrador con Pago en Efectivo y Vuelto
1. Regresar a **Punto de Venta (POS)**.
2. Seleccionar un producto con stock 5 y precio de `Bs. 120,00`.
3. Presionar **"Cobrar Venta"**.
4. Seleccionar método: **Efectivo**.
5. Ingresar monto entregado: `Bs. 200,00`.
6. **Verificación visual:** El sistema calcula y resalta en pantalla en color verde el cambio: `Vuelto: Bs. 80,00`.
7. Presionar "Confirmar Venta".
8. **Verificación:** Se abre el diálogo del Ticket Térmico con diseño de 80mm listo para imprimir, y el stock del producto disminuye inmediatamente a `4`.

### Escenario D: Venta con Pago QR y Verificación de Caja
1. En el POS, agregar otro producto de `Bs. 300,00`.
2. Presionar **"Cobrar Venta"** y seleccionar **Pago QR / Transferencia**.
3. Confirmar la venta.
4. **Verificación:** Se genera el recibo `VNT-000002`.

### Escenario E: Arqueo y Cierre de Caja
1. En la esquina superior del POS, hacer clic en **"Cerrar Caja / Arqueo"**.
2. El sistema muestra:
   - Fondo inicial: `Bs. 100,00`
   - Ventas en efectivo: `Bs. 120,00`
   - Efectivo esperado en caja: `Bs. 220,00` (el pago QR de Bs. 300 no suma a caja física).
3. Ingresar el efectivo real contado: `Bs. 220,00`.
4. **Verificación:** El balance muestra `Diferencia: Bs. 0,00 (Caja Cuadrada)`.
5. Presionar "Confirmar Cierre de Caja". El turno se cierra con éxito y genera el comprobante de arqueo.
