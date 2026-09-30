# Validación rápida — Capítulo 1

## Abrir la aplicación

Iniciar Apache y MySQL de XAMPP. Abrir http://servimatica-app.test.
El virtual host apunta a `C:/xampp/htdocs/servimatica-app/public`.

Para recompilar: `cd admin-starter-kit` y `pnpm run build`.
Para desarrollar con Vite: `pnpm run dev` y abrir http://127.0.0.1:5173.
No usar `php artisan serve`.

En una instalación nueva, seguir el README. Ejecutar `php artisan migrate --seed`, sin `migrate:fresh`.
El seeder no restablece una cuenta admin ya existente.

## 1. Dueño por contraseña

1. Abrir la aplicación.
2. Correo/alias: `admin@servimatica.com`; contraseña: `password`.
3. Pulsar **Ingresar**.
4. Comprobar panel, nombre **Administrador Dueño**, rol **Dueño** y menú **Personal / Usuarios**.

## 2. Dueño por PIN

1. Pulsar **Cerrar sesión**.
2. Escribir alias `admin` y seleccionar **PIN de 4 dígitos**.
3. Pulsar los botones 1, 2, 3 y 4.
4. Comprobar ingreso inmediato al cuarto dígito.
5. Repetir con el teclado físico. Probar un PIN incorrecto: mantiene alias, muestra error y permite reintentar.

## 3. Personal

1. Entrar a **Personal / Usuarios**, pulsar **Nuevo usuario**.
2. Nombre: Carlos Vendedor; alias: `carlos`; correo: `carlos@tienda.com`.
3. Contraseña: `carlos123`; PIN: `2468`; rol: **Vendedor**.
4. Guardar y comprobar estado **Activo**.
5. Editar el nombre. Dejar contraseña y PIN vacíos para conservarlos.
6. Verificar que un alias/correo duplicado o PIN inválido muestra errores sin guardar.

## 4. Vendedor, permisos y salida

1. Cerrar sesión; entrar con `carlos` y PIN `2468`.
2. Comprobar nombre, rol Vendedor y ausencia del menú de personal.
3. Escribir `http://servimatica-app.test/users`: debe mostrar acceso restringido.
4. Cerrar sesión; pulsar Atrás: debe solicitar autenticación.
5. Volver como dueño, desactivar a Carlos y confirmar. Carlos no debe poder entrar por PIN ni contraseña.
6. Reactivar a Carlos si se desea conservarlo como cuenta de demostración.
7. El dueño no puede desactivarse a sí mismo ni quitarse el rol Dueño.

## Pruebas automatizadas

Desde la raíz: `php artisan test`.
Las pruebas cubren autenticación dual, validación, ocho horas de TTL, expiración, logout,
estado inactivo, permisos, unicidad, edición, hashes y bloqueo de intentos repetidos.

## Estado de la revisión visual

Pendiente de ejecutar en un navegador conectado o por el usuario. La compilación y las
pruebas HTTP no sustituyen esta revisión. No marcar T026/T030 hasta comprobar los escenarios.
