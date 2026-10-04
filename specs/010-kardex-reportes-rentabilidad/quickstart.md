# Quickstart & Validation Guide: Kardex y Reportes Financieros

**Feature**: `010-kardex-reportes-rentabilidad`
**Date**: 2026-10-03

## 1. Prerrequisitos de Entorno

- Servidor Apache/MySQL activo (vía XAMPP).
- Migración aditiva ejecutada: `php artisan migrate`.
- Servidor frontend activo: `cd admin-starter-kit && pnpm run dev`.

---

## 2. Escenarios de Validación Manual (Paso a Paso)

### Escenario 1: Auditoría de Kardex Físico y Valorizado (Dueño)
1. Iniciar sesión como `admin@servimatica.com` (Rol Dueño).
2. En el menú lateral, ingresar a **Inventario y Reportes > Kardex de Inventario**.
3. Seleccionar un producto que tenga compras y ventas registradas (ej. "Memoria RAM DDR4 16GB").
4. **Verificación visual**:
   - Comprobar que la tabla muestra las columnas físicas (Entrada, Salida, Saldo Stock) y las columnas valorizadas (Costo Unitario, Debe, Haber, Costo Promedio Ponderado, Saldo en `Bs.`).
   - El saldo final coincide con el stock actual del producto.
   - Presionar el botón **Exportar a Excel / CSV** y verificar que el archivo descargado abre en hoja de cálculo con los mismos valores y formato en Bolivianos (`Bs.`).

### Escenario 2: Restricción de Privacidad para Vendedor / Cajero (Principio VI)
1. Iniciar sesión como un usuario con rol `cajero` o `vendedor`.
2. Navegar a la vista de Kardex del mismo producto.
3. **Verificación visual y de red**:
   - En la interfaz, las columnas valorizadas (Costo Unitario, Debe, Haber, CPP, Saldo en Bs.) no existen ni están en el DOM.
   - En la pestaña Network de las DevTools del navegador, la respuesta JSON de `GET /api/v1/kardex/{id}` no contiene el nodo `financial`.
   - Si el cajero intenta ingresar por URL a `/reports/profitability`, el sistema redirige a `/not-authorized` o muestra error 403.

### Escenario 3: Reporte de Rentabilidad y Utilidad del Dueño
1. Iniciar sesión como `admin@servimatica.com`.
2. Ingresar a **Reportes > Rentabilidad y Margen**.
3. Seleccionar el rango de fechas del mes actual.
4. **Verificación visual**:
   - Tarjetas KPI: Ventas Totales, Costo de Ventas (COGS), Utilidad Bruta (`Bs.`) y Margen Promedio (`%`).
   - Pestaña cronológica: gráfico o tabla con el desglose día por día.
   - Pestaña productos: ranking de artículos por margen de utilidad.
   - Exportar reporte a Excel/CSV.

### Escenario 4: Valoración Global de Inventario
1. Ingresar a **Reportes > Valoración de Inventario**.
2. Verificar el resumen de capital total inmovilizado en `Bs.` desglosado entre stock vendible y mercadería en garantía.

---

## 3. Comandos de Prueba Automatizada

```bash
# Ejecutar suite de pruebas de Kardex y Reportes Financieros
php artisan test --filter=KardexAndReportsTest

# Ejecutar verificación completa de la suite de pruebas
php artisan test
```
