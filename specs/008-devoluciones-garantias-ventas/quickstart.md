# Guía de Verificación en 2 Minutos: Devoluciones y Garantías de Venta

**Feature**: `008-devoluciones-garantias-ventas` | **Fecha**: 2026-10-03

---

## 🎯 Objetivo de la Guía
Permitir a una persona no técnica (el Dueño de Servimática) comprobar en 2 minutos que el sistema registra los periodos de garantía en la venta, valida su vigencia y procesa devoluciones físicas o monetarias protegiendo el stock y la caja chica.

---

### Paso 1: Venta con Garantía y Serial en el POS (1 min)
1. Iniciar sesión como vendedor o Dueño.
2. Asegurar que haya un turno de caja abierto en el POS.
3. Agregar un producto al carrito (ej. un disco duro o tarjeta gráfica).
4. Observar que el ítem muestra automáticamente el periodo de garantía configurado (ej. `6 meses`) y permite ingresar opcionalmente el Serial `S/N: WD-1029384`.
5. Cobrar la venta e imprimir el ticket térmico.
6. **Verificación visual del ticket**:
   - Debajo del producto aparece claramente: `Garantía: 180 días (Vence: DD/MM/AAAA) | S/N: WD-1029384`.

---

### Paso 2: Consulta de Garantía y Devolución (45 seg)
1. Navegar en el menú a **Ventas > Historial de Ventas** o a la pestaña **Garantías y Devoluciones**.
2. Buscar la venta recién realizada con el folio del comprobante.
3. Comprobar el distintivo verde: `✓ Garantía Vigente (faltan 180 días)`.
4. Hacer clic en **"Procesar Devolución / Garantía"**.
5. Seleccionar:
   - Motivo: `Falla técnica / Sectores dañados`.
   - Condición del producto: `Defectuoso / RMA`.
   - Resolución: `Cambio Físico 1 a 1` o `Reembolso en Efectivo`.
6. Confirmar la operación.

---

### Paso 3: Verificación del Comprobante y del Inventario (15 seg)
1. El sistema abre automáticamente el **Comprobante de Devolución y Cambio de Garantía** con el membrete oficial dinámico de la tienda.
2. Al revisar el catálogo de productos:
   - El stock para la venta se mantiene protegido (o disminuye en 1 si fue cambio físico).
   - El producto defectuoso queda apartado en el registro de fallas técnicas sin contaminar el stock vendible.
3. Si la resolución fue reembolso en efectivo, el arqueo de caja chica refleja el egreso exacto de dinero devuelto.
