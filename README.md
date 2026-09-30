# Servimática — Capítulo 1

Laravel 12 (API REST con JWT) + Vue 3/Vuetify/CASL en `admin-starter-kit/`.
Catálogo de referencia: `admin-full-version/`.

## Abrir el avance

Frontend de desarrollo: http://127.0.0.1:5173 (ejecutar `pnpm run dev` en `admin-starter-kit/`).
API: http://servimatica-app.test/api

Cuenta inicial: alias **admin**, correo **admin@servimatica.com**, contraseña **password**, PIN **1234**.
El dueño administra personal; el vendedor solamente accede al panel del capítulo actual.

## Entorno local

PHP 8.2+, Composer, Node.js y MySQL/MariaDB. Apache debe apuntar a `public/`.
`scripts/setup-local.ps1` prepara el virtual host, agrega el dominio a hosts y arranca los servicios de XAMPP.
Su modificación de hosts requiere una consola de Windows con permisos de administrador.
No se utiliza `php artisan serve`.

1. `composer install`
2. Copiar `.env.example` a `.env` y configurar la conexión local.
3. `php artisan key:generate` y `php artisan jwt:secret`.
4. `php scripts/create-database.php` prepara únicamente la base local `servimatica_app`.
5. `php artisan migrate --seed` (sin borrar datos).
6. En `admin-starter-kit/`: `pnpm install --frozen-lockfile` y `pnpm run build`.

La compilación genera `public/app/`, servido por Apache desde el dominio local.
Durante desarrollo, `pnpm run dev` abre Vite en http://127.0.0.1:5173 y reenvía `/api` al dominio local.

## Validación

- `php artisan test`: pruebas aisladas en SQLite en memoria; no modifican MySQL local.
- `php vendor/bin/pint --preset psr12 --test`: formato PHP.
- `pnpm run build` en el frontend: compilación de producción.
- Escenarios de pantalla: [quickstart](specs/001-base-estructural-acceso/quickstart.md).
- Estado y limitaciones: [reporte de implementación](specs/001-base-estructural-acceso/implementation-report.md).

Los secretos se generan en `.env`, excluido del control de versiones.
La sesión JWT dura ocho horas; logout invalida el token mediante la caché persistente configurada.

En XAMPP se estabilizó la lectura de configuración con `php artisan config:cache`. Si cambia `.env`, vuelva a ejecutar ese comando. PHPUnit usa una ruta de caché separada y SQLite en memoria.
