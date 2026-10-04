# Quickstart & Guía de Verificación: Capítulo 3

**Feature**: `003-catalogo-avanzado-auditoria`
**Date**: 2026-10-03
**Status**: Ready

Esta guía permite a cualquier persona (técnica o no técnica) validar el 100% de la funcionalidad implementada en menos de 5 minutos navegando en el panel administrativo.

---

## 1. Prerrequisitos de Ejecución

- Servidor Apache/MySQL activo en XAMPP (`http://127.0.0.1/servimatica-app/public` o `http://servimatica-app.test`).
- Frontend Vite en ejecución (`pnpm run dev` en `admin-starter-kit/` en `http://127.0.0.1:5173/`).
- Base de datos actualizada con las nuevas migraciones:
  ```bash
  php artisan migrate
  ```

---

## 2. Escenarios de Prueba Rápida

### Escenario A: Jerarquía de Categorías (Familia y Subfamilia)
1. Iniciar sesión como Dueño (`admin` / `password123`).
2. Ir a **Catálogo > Categorías**.
3. Crear una categoría principal "Componentes de PC" (sin padre).
4. Crear una segunda categoría "Tarjetas de Video", seleccionando "Componentes de PC" como Padre / Familia.
5. **Verificación visual:** En la tabla, "Tarjetas de Video" se muestra visualmente indentada o con un badge indicador de que es subfamilia de "Componentes de PC".

### Escenario B: Marcas y Modelos con Creación al Vuelo
1. Ir a **Catálogo > Marcas**.
2. Registrar la marca "Logitech".
3. Ir a **Catálogo > Productos** y abrir el Drawer de **Nuevo Producto**.
4. Seleccionar Marca "Logitech".
5. En el campo **Modelo**, escribir un modelo que no exista (ej. "MX Master 3S") y presionar Enter / seleccionar la opción "Crear MX Master 3S".
6. **Verificación:** El modelo se asocia y guarda de forma inmediata sin abandonar la ficha de producto.

### Escenario C: Condición Comercial del Producto y Exportación de Inventario a Excel
1. En el mismo formulario de Producto, seleccionar la **Condición**: "Seminuevo / Open Box".
2. Asignar precio de venta en Bolivianos (ej. `Bs. 650.00`) y stock `5`.
3. Guardar el producto.
4. **Verificación visual:** En la tabla de productos aparece un chip de color informativo destacando "Seminuevo / Open Box".
5. Hacer clic en el botón superior **"Exportar Excel"** (icono de hoja de cálculo).
6. Abrir el archivo `.xlsx` descargado: verificar que contenga las columnas de Categoría, Subfamilia, Marca, Modelo, Condición, Costo, Precio de venta y Stock.
7. *(Prueba de seguridad)*: Iniciar sesión con un usuario Vendedor, ir a Productos, exportar a Excel y confirmar que el archivo resultante **NO contiene** precios de compra ni margen de ganancia.

### Escenario D: Ficha Completa de Usuario con Foto y Exportación de Personal
1. Ir a **Gestión de Personal > Usuarios**.
2. Abrir el drawer **"Nuevo Usuario"**:
   - Completar campos obligatorios: CI (`7382910 LP`), Nombre (`Javier Rojas`), Usuario (`jrojas`), Contraseña (`vendedor123`), Rol (`Vendedor`).
   - Completar opcionales: Teléfono (`71234567`), Dirección (`Calle Murillo #120`), Comisión (`3.00%`).
3. Guardar el usuario.
4. Hacer clic en el botón **"Exportar Nómina a Excel"**.
5. Abrir la planilla `.xlsx` y verificar que figuren todas las columnas de la ficha personal.

### Escenario E: Auditoría de Inicios de Sesión (Logins)
1. Cerrar sesión.
2. Intentar ingresar con usuario `fake_user` y contraseña errónea `wrongpass`.
3. Iniciar sesión como Dueño (`admin` / `password123`).
4. Ir a **Seguridad > Auditoría de Accesos**.
5. **Verificación visual:** Se muestra en primer lugar el intento fallido de `fake_user` con badge rojo (Credenciales inválidas) y su IP, y debajo el inicio exitoso de `admin` con badge verde.
