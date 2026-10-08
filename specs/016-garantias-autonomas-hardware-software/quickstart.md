# Quickstart Validation Guide: 016-garantias-autonomas-hardware-software

**Feature**: 016-garantias-autonomas-hardware-software  
**Date**: 2026-10-08  
**Status**: Ready  

---

## Escenario de Validación Rápida de Extremo a Extremo (< 2 minutos)

### 1. Prerrequisitos
- Base de datos local migrada con la nueva estructura de garantías duales.
- Usuario administrador activo (`admin` / `password`).

### 2. Flujo de Validación Paso a Paso

#### Paso 1: Configurar Producto con Garantías Autónomas
1. Entrar al panel web (`http://servimatica-app.test`).
2. Ir a **Catálogo de Productos** -> **Nuevo Producto**.
3. Llenar los datos básicos (ej. *Laptop ASUS Vivobook 15*).
4. En la sección de Garantías:
   - Seleccionar **Garantía de Hardware**: `730 días (2 años)`.
   - Seleccionar **Garantía de Software**: `90 días (3 meses)`.
5. Guardar el producto.
6. **Resultado Esperado**: El producto se guarda correctamente y muestra sus distintivos de Hardware (2a) y Software (3m).

#### Paso 2: Venta en el Punto de Venta (POS)
1. Ir al **Punto de Venta (POS)**.
2. Hacer clic sobre la laptop creada para agregarla al carrito.
3. Observar los micro-chips en la fila del carrito (`🛡️ HW: 730d` y `💻 SW: 90d`).
4. Tocar el chip para abrir el diálogo de garantías:
   - Cambiar opcionalmente el Software a `180 días`.
   - Pistolear o escribir el número de serie: `ASUS-SN-8822001`.
   - Tocar **Guardar Cambios**.
5. Cobrar la venta (Efectivo / Cobro exacto).
6. **Resultado Esperado**: La venta se registra exitosamente.

#### Paso 3: Validación del Comprobante / Ticket Térmico
1. En el diálogo de impresión posterior a la venta (o en el listado de ventas):
2. Revisar el desglose del comprobante para la laptop:
   - Debe listar `🛡️ G. Hardware: 730 días (Vence: DD/MM/AAAA)`.
   - Debe listar `💻 G. Software: 180 días (Vence: DD/MM/AAAA)`.
   - Debe listar `S/N: ASUS-SN-8822001`.
3. **Resultado Esperado**: Ambas fechas están calculadas fielmente y desglosadas.

#### Paso 4: Verificación Postventa
1. Ir a **Ventas** -> Menú de opciones de la venta recién creada -> **Verificar Garantía**.
2. **Resultado Esperado**: Se abre el diálogo mostrando las dos columnas o badges de evaluación:
   - Hardware: **Vigente** (Activo).
   - Software: **Vigente** (Activo).
