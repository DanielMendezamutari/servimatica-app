# Validación rápida — Capítulo 2

## Abrir la aplicación

Iniciar Apache y MySQL de XAMPP. Abrir http://servimatica-app.test.
Para desarrollar con Vite: `pnpm run dev` en `admin-starter-kit/` y abrir http://127.0.0.1:5173.
No usar `php artisan serve`.

Si es una instalación nueva, seguir el README y ejecutar `php artisan migrate --seed`.
Las migraciones del Capítulo 2 son incrementales; no es necesario `migrate:fresh`.

## 1. Categorías (Dueño)

1. Entrar como Dueño (`admin` / PIN `1234` o contraseña `password`).
2. Navegar a **Catálogo > Categorías** en el menú lateral.
3. Pulsar **Nueva categoría**. Nombre: "Laptops", descripción: "Computadoras portátiles". Guardar.
4. Repetir para: "Componentes", "Periféricos", "Accesorios".
5. Editar "Periféricos" y cambiar nombre a "Periféricos y Audio". Confirmar que se actualizó.
6. Intentar crear otra categoría con nombre "Laptops" (duplicado): debe mostrar error.
7. Desactivar la categoría "Accesorios": debe mostrar indicador de estado inactivo.

## 2. Productos (Dueño)

1. Navegar a **Catálogo > Productos**.
2. Pulsar **Nuevo producto**. Completar:
   - Nombre: "Laptop HP 15-ef2xxx"
   - Categoría: Laptops
   - SKU: "LAP-HP-001"
   - Costo: 2500 Bs.
   - Precio de venta: 3200 Bs.
   - Stock: 3
   - Stock mínimo: 2
3. Guardar. Verificar que aparece en la lista con ganancia Bs. 700 (28%).
4. Crear un producto sin SKU: verificar que se genera automáticamente (ej. "LAP-0001").
5. Crear un "Teclado Mecánico Redragon" en categoría "Periféricos y Audio", costo 180, venta 350, stock 10, stock mínimo 3.
6. Crear un producto con venta menor al costo: debe mostrar advertencia pero permitir guardar.
7. Intentar crear otro producto con SKU "LAP-HP-001": debe rechazar por duplicado.
8. Editar "Laptop HP" cambiando precio de venta a 3400. Verificar que el margen se recalcula.
9. Desactivar un producto: debe mostrar indicador inactivo.

## 3. Stock bajo y Agotado

1. Crear un producto con stock 1 y stock mínimo 3: debe mostrar badge de "Stock bajo".
2. Crear un producto con stock 0: debe mostrar chip "Agotado".

## 4. Ajuste de stock

1. En la fila de "Laptop HP", pulsar el botón de ajuste de stock.
2. Tipo: Ingreso, cantidad: 3, motivo: "Recepción de mercadería". Guardar.
3. Verificar que el stock pasó de 3 a 6.
4. Ajustar tipo: Egreso, cantidad: 1, motivo: "Merma / defectuoso". Guardar.
5. Verificar que el stock pasó de 6 a 5.
6. Intentar egresar 10 unidades (más que el stock actual de 5): debe rechazar.

## 5. Historial de stock

1. En la fila de "Laptop HP", pulsar el botón de historial.
2. Verificar que aparecen los 2 movimientos registrados (ingreso y egreso) con fecha, cantidad, motivo y usuario.
3. Filtrar por tipo "Ingreso": solo debe aparecer el ingreso de 3 unidades.
4. Filtrar por rango de fechas de hoy: debe mostrar ambos movimientos.

## 6. Vista del Vendedor

1. Cerrar sesión del Dueño.
2. Entrar como vendedor (ej. `carlos` / PIN `2468`).
3. Verificar que el menú muestra **Catálogo > Productos** pero NO "Categorías".
4. Verificar que la lista de productos muestra precio de venta y stock pero NO costo, margen ni stock mínimo.
5. Verificar que los productos de la categoría inactiva "Accesorios" NO aparecen.
6. Verificar que los productos desactivados por el Dueño NO aparecen.
7. Buscar "Laptop" en el buscador: debe filtrar correctamente.
8. Filtrar por categoría "Periféricos y Audio": debe mostrar solo esos productos.
9. Verificar que un producto con stock 0 muestra "Agotado".
10. Intentar acceder a `/categories` directamente: debe mostrar acceso restringido.

## Pruebas automatizadas

Desde la raíz: `php artisan test`.
Las pruebas cubren CRUD de categorías, CRUD de productos, unicidad de SKU, auto-generación de SKU,
ajuste de stock con validación de stock negativo, historial inmutable, filtrado por rol
(Vendedor no ve costos ni productos/categorías inactivos), y paginación.

## Estado de la revisión visual

Pendiente de ejecutar en un navegador conectado o por el usuario. La compilación y las
pruebas HTTP no sustituyen esta revisión.
