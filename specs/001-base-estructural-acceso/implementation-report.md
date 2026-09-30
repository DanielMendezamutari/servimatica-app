# Avance de implementación — 2026-09-25

## Implementado

- Laravel 12 en la raíz, API REST JWT con TTL de 480 minutos y blacklist persistente.
- Migración, cuenta inicial idempotente, dominio, repositorio Eloquent y casos de uso.
- Login por correo/alias y contraseña/PIN; protección por estado y rol en cada petición.
- Alta, listado, edición y cambio de estado de personal; hashes de credenciales y controles de unicidad.
- Protección contra desactivación y pérdida del propio rol Dueño.
- Login Vue con teclado PIN táctil/físico y envío automático al cuarto dígito.
- Panel y navegación CASL; cabecera con nombre/rol y cierre de sesión.
- Drawer reutilizado para alta/edición y tabla con confirmación del cambio de estado.
- Apache, dominio local, MySQL y compilación servida desde `public/app/`.

## Verificación realizada

- `php artisan test`: 12 pruebas, 115 assertions, todas aprobadas.
- `npm run build`: compilación aprobada.
- ESLint de los componentes y módulos modificados: aprobado.
- Laravel Pint PSR-12: aprobado.
- HTTP real en Apache: página login 200, recurso JavaScript servido, login y logout de admin correctos.
- Migraciones y seeder ejecutados sobre MySQL local sin borrar datos.
- No se ejecutó `php artisan serve`.
- No hay `.specify/extensions.yml`; no hay hooks registrados.

## Ajustes del plan

- Arquitectura SPA Vue/Vuetify + API confirmada por el usuario; AGENTS.md alineado.
- Laravel 12 por PHP 8.2 y soporte de seguridad.
- Edición de personal añadida a tareas/contrato para cumplir RF-006 y FA-005.
- Rutas explícitas con Vue Router: el generador antiguo de la plantilla no compilaba con sus dependencias resueltas.
- Se deshabilitó el plugin opcional de Vue DevTools incompatible.
- Tiptap se alineó a 2.3.0 y se retiraron dependencias de video no utilizadas para resolver npm.

## Referencias de componentes

- Login: `admin-full-version/src/pages/login.vue`.
- Tabla: `admin-full-version/src/pages/apps/user/list/index.vue`.
- Drawer: `admin-full-version/src/views/apps/user/list/AddNewUserDrawer.vue`.
- CASL/guards: `admin-full-version/src/plugins/casl/` y `src/plugins/1.router/guards.js`.
- Layouts, campos, botones, tarjetas y diálogos de la plantilla Vuetify.

## Pendiente para cerrar el capítulo

- T026 y T030: revisión visual y escenarios en navegador. Las herramientas de esta sesión no tienen navegador disponible.
- La instalación de la plantilla reportó 51 avisos de dependencias (38 moderados, 10 altos, 3 críticos).
  No se aplicó una actualización masiva con cambios de versión. Revisar las dependencias antes de un despliegue público.
- La aceptación visual del usuario exigida por la Constitución sigue pendiente.

## Corrección del entorno de desarrollo — 2026-09-26

- Gestor oficial: pnpm 9.0.6, declarado por la plantilla. Instalación con `pnpm install --frozen-lockfile` usando su lockfile original; retirado el package-lock de npm.
- Se restauró el conjunto de dependencias original para evitar versiones transitivas mezcladas de Tiptap.
- Frontend: `pnpm run dev` en admin-starter-kit, http://127.0.0.1:5173. API: http://servimatica-app.test/api mediante el proxy de Vite.
- Configuración Laravel cacheada para estabilizar la lectura de MySQL/cache/sesiones en Apache; PHPUnit usa una ruta de caché independiente para mantener las pruebas aisladas.
- Las compilaciones posteriores conservan chunks existentes para no romper pestañas abiertas.
- Un fallo al cargar el panel después de autenticarse ya no se presenta como un error de conexión al servidor.
- Suite de backend tras el ajuste de configuración: 12 pruebas y 115 comprobaciones aprobadas.