# Guía de Verificación en 2 Minutos: "Mi Empresa" y Comprobantes Dinámicos

**Feature**: `007-datos-empresa-sucursal` | **Fecha**: 2026-10-03

---

## 🎯 Objetivo de la Guía
Permitir a una persona no técnica (el Dueño de Servimática) comprobar en menos de 2 minutos que los datos de su tienda (ciudad, sucursal, teléfonos, logo) ya no están quemados en código y se actualizan al instante en todos los comprobantes impresos.

---

### Paso 1: Configurar los Datos Reales del Negocio (1 min)
1. Iniciar sesión en el sistema como **Dueño**.
2. En el menú lateral, ingresar a **Ajustes > Mi Empresa**.
3. Modificar los campos:
   - **Nombre de la Sucursal:** `Sucursal Central Riberalta`
   - **Ciudad y Departamento:** `Riberalta, Beni — Bolivia`
   - **Dirección:** `Av. Plácido Méndez N° 450`
   - **Celular / WhatsApp:** `77123456`
   - **Términos de Cotización:** `Cotización oficial Servimática. Válida por 5 días hábiles.`
4. Presionar el botón **"Guardar Cambios"**.
5. Verificar el mensaje verde de éxito: *"Información de la empresa actualizada exitosamente."*

---

### Paso 2: Verificar la Proforma Formal (30 seg)
1. Navegar en el menú a **Cotizaciones**.
2. Hacer clic en el icono de la impresora (🖨️) en cualquier proforma.
3. **Verificación visual del membrete**:
   - En la cabecera ahora dice explícitamente: **`Riberalta, Beni — Bolivia`** (ya no aparece "Trinidad").
   - El teléfono de contacto y los términos y condiciones coinciden exactamente con lo que acabas de guardar.

---

### Paso 3: Verificar el Ticket Térmico del POS (30 seg)
1. Ir al **Punto de Venta (POS)** o al **Historial de Ventas**.
2. En cualquier venta realizada, hacer clic en "Imprimir Ticket".
3. **Verificación visual del ticket**:
   - El encabezado térmico de 80mm imprime:
     - `SERVIMÁTICA PC`
     - `Sucursal Central Riberalta`
     - `Riberalta, Beni — Bolivia`
     - `Cel: 77123456`
   - El pie de página imprime la política de garantía configurada.
