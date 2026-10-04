# Guía de Verificación en 5 Minutos: Formas de Pago y Filtros UX

**Feature**: `006-gestion-formas-pago` | **Fecha**: 2026-10-03

---

## 🎯 Objetivo de la Guía
Permitir a una persona no técnica (el Dueño de Servimática) comprobar visualmente en menos de 5 minutos el funcionamiento de la gestión de formas de pago, su integración en el POS y la experiencia con los selectores de fecha.

---

### Paso 1: Configurar una Cuenta Bancaria y QR Oficial (1 min)
1. Iniciar sesión en el sistema como **Dueño**.
2. Navegar en el menú lateral a **Ajustes > Formas de Pago**.
3. Presionar el botón **"+ Nueva Forma de Pago"**.
4. Completar los datos:
   - Nombre: `QR Banco Unión`
   - Tipo: `Pago QR`
   - Banco: `Banco Unión`
   - N° Cuenta / Celular: `10000012345678`
   - Titular: `Servimática Bolivia`
   - Marcar: `Requiere Comprobante / Referencia`
   - Subir imagen de QR de prueba (`.png` o `.jpg`).
5. Guardar y verificar que aparezca en la lista con su switch de estado en verde (Activo).

---

### Paso 2: Venta en Mostrador (POS) con Pago QR Oficial (1.5 min)
1. Ir al **Punto de Venta (POS)** desde el menú.
2. Si la caja no está abierta, abrir turno con `Bs. 100.00`.
3. Agregar cualquier producto al carrito (ej. Memoria RAM por `Bs. 320.00`).
4. Hacer clic en **"Cobrar (Bs. 320.00)"**.
5. En el diálogo de cobro, seleccionar la opción **"QR Banco Unión"**.
6. **Verificación visual**:
   - Se muestra el código QR cargado en pantalla completa.
   - El monto a cobrar indica claramente `Bs. 320.00`.
   - Aparece el campo obligatorio para ingresar el número de comprobante/referencia bancaria.
7. Escribir el comprobante (ej: `REF-849201`) y confirmar la venta.
8. Comprobar que la venta se guarde y genere el ticket térmico con el método y la referencia registrados.

---

### Paso 3: Arqueo y Cierre de Caja Diferenciado (1 min)
1. En la barra superior del POS, presionar **"Cerrar Turno / Arqueo"**.
2. **Verificación de Conciliación**:
   - El diálogo debe indicar que el **Efectivo en Caja Físico Esperado** sigue siendo `Bs. 100.00` (el fondo inicial), ya que los `Bs. 320.00` del QR ingresaron a la cuenta bancaria.
   - En el desglose digital debe listar: `QR Banco Unión: Bs. 320.00`.
3. Contar y colocar `100.00` en efectivo físico y confirmar el cierre sin diferencias (cuadre exacto).

---

### Paso 4: Filtrado Rápido con `AppDateTimePicker` (30 seg)
1. Navegar a **Historial de Ventas** o **Historial de Compras**.
2. Probar los botones de acceso rápido:
   - Clic en **"Hoy"**: los campos `Desde` y `Hasta` se autocompletan con la fecha de hoy y la tabla se filtra.
   - Clic en **"7d"**: filtra los últimos 7 días.
   - Clic en **"Mes"**: filtra desde el primer día del mes actual.
3. Abrir el campo `Desde`: se despliega un calendario moderno sin el texto apretado `dd/mm/aaaa`.
