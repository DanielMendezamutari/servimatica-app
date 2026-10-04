# Quickstart & Verification Scenarios: Gestión Integral de Clientes (CRM)

## Prerrequisitos
- Base de datos local actualizada con migraciones: `php artisan migrate`.
- Servidor de desarrollo frontend: `pnpm --prefix admin-starter-kit run dev`.
- Host local: `http://servimatica-app.test`.

---

## Escenario 1: Directorio Centralizado y Búsqueda en Mostrador (US1)
1. Iniciar sesión como `vendedor` o `admin`.
2. En la barra lateral izquierda, hacer clic en el acceso directo **Clientes** (ubicado en el bloque *Operaciones*).
3. Verificar que se despliega la tabla con los clientes existentes, mostrando: Nombre, NIT/CI, Tipo, WhatsApp y Total Compras.
4. En el buscador superior, tipear un número de teléfono o NIT. Comprobar que la tabla filtra los resultados en tiempo real sin recargar la página.
5. Hacer clic en el icono de WhatsApp de un cliente con teléfono válido y verificar que abre una pestaña hacia `https://wa.me/591...`.

---

## Escenario 2: Registro Rápido de Nuevo Cliente con Validación (US1)
1. Desde `/clients`, hacer clic en el botón superior **"+ Nuevo Cliente"**.
2. Se abre el Drawer modal lateral.
3. Rellenar:
   - Razón Social / Nombre: `Empresa de Transportes Mamoré SRL`
   - NIT/CI: `1029384756`
   - Celular / WhatsApp: `71234567`
   - Tipo de Cliente: Seleccionar `Empresa / Corporativo`
   - Ciudad: `Trinidad`
4. Guardar. Comprobar que aparece la notificación verde y el cliente aparece encabezando la tabla.

---

## Escenario 3: Ficha 360° del Cliente (US2)
1. En la fila de un cliente que tenga ventas o garantías, hacer clic en el botón de acción **"Ver Ficha 360°"** (`/clients/:id`).
2. Constatar las tarjetas de métricas superiores: *Total Compras Acumuladas (Bs.)*, *Cantidad de Compras*, *Garantías Vigentes*.
3. Navegar por las 3 pestañas:
   - **Historial de Ventas:** Visualizar tickets pasados con fecha y total.
   - **Cotizaciones:** Visualizar proformas pendientes y enlace para PDF/WhatsApp.
   - **Garantías de Equipos:** Visualizar equipos comprados, número de serie y badge con los días de garantía restantes.

---

## Escenario 4: Exportación Segura (US3)
1. Con sesión de `dueno` o `admin`, hacer clic en **"Exportar Directorio"** en `/clients`.
2. Se descarga el archivo `.csv` con todos los clientes y métricas de facturación acumulada.
3. Iniciar sesión como `vendedor` y constatar que el botón de exportación masiva no se muestra (o si invoca el endpoint directo, la API retorna `403 Forbidden`).
