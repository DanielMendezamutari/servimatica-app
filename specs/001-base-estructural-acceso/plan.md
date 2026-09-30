# Implementation Plan: Capítulo 1 — Base Estructural y Acceso al Sistema

**Branch**: `main` | **Date**: 2026-09-25 | **Spec**: [spec.md](file:///c:/xampp/htdocs/servimatica-app/specs/001-base-estructural-acceso/spec.md)  
**Input**: Feature specification from `specs/001-base-estructural-acceso/spec.md`  

---

## 1. Summary

Este capítulo establece los cimientos técnicos, la base de datos y el control de acceso inicial para la tienda de computadoras Servimática App.
Se implementa una arquitectura desacoplada:
1. **Backend (Laravel API REST):** Arquitectura Hexagonal que expone endpoints seguros de autenticación (`/api/auth/*`) y administración de personal (`/api/users/*`) usando tokens JWT con vigencia de 8 horas. Soporta autenticación dual (Contraseña estándar y PIN rápido de 4 dígitos hasheado).
2. **Frontend Web (Vue 3 + Vuetify + CASL en `admin-starter-kit/`):** Pantalla de login dual (Contraseña vs PIN de 4 dígitos con teclado visual táctil y auto-submit al 4to dígito) y panel de administración de usuarios reutilizando estrictamente componentes del catálogo `admin-full-version/`.

---

## 2. Technical Context

- **Lenguaje y Versiones:** PHP 8.2+ / Node.js 18+ / Vue 3.4+.
- **Framework Backend:** Laravel 12.x con Arquitectura Hexagonal (DDD y principios SOLID), compatible con PHP 8.2 y con soporte de seguridad vigente.
- **Librería de Autenticación:** `tymon/jwt-auth` (JWT stateless con Bearer tokens y TTL de 480 minutos).
- **Frontend Framework:** Vue 3 (Composition API `<script setup>`), Vuetify 3.5+, Vite 5.x, Pinia 2.1+, `@casl/vue` 2.2+.
- **Almacenamiento / Base de Datos:** MySQL / MariaDB (vía Laragon/XAMPP).
- **Pruebas:** PHPUnit / Pest (`php artisan test`).
- **Plataforma Objetivo:** Web local en `http://servimatica-app.test` y preparado para consumo nativo Android Kotlin (Retrofit).
- **Restricciones Mandatorias:** 
  - Prohibido `php artisan serve`.
  - Reutilización estricta de componentes de `admin-full-version/` hacia `admin-starter-kit/`.
  - Cero dependencias exóticas que se salgan del conocimiento del desarrollador.

---

## 3. Constitution Check

*Evaluación de cumplimiento contra la Constitución v1.0.0 de Servimática App:*

| Principio Constitucional | Estado en el Plan | Justificación |
|---|---|---|
| **I. Simplicidad ante todo (KISS & YAGNI)** | ✅ Cumple | Sin microservicios ni sobre-ingeniería; login y usuarios directos y autocontenidos. |
| **II. Idioma y Mercado (Bolivia / BOB)** | ✅ Cumple | Todos los mensajes, validaciones y vistas en español neutro / Bolivia. |
| **III. Cero Alcance Fantasma** | ✅ Cumple | Estrictamente limitado al login, PIN 4 dígitos y roles `Dueño` / `Vendedor`. Cero productos ni inventario aún. |
| **IV. Verificable por persona no técnica** | ✅ Cumple | Probable en pantalla en 1 minuto con las credenciales maestras y PIN de 4 dígitos. |
| **V. Verdad Única de Datos en Tiempo Real** | ✅ Cumple | La misma tabla `users` alimenta la web y los endpoints listos para la app Kotlin. |
| **VI. Privacidad y Seguridad de Datos** | ✅ Cumple | Contraseñas y PINs hasheados con `bcrypt`; control de roles estricto vía CASL y middleware backend. |

---

## 4. Project Structure

### Documentación del Capítulo
```text
specs/001-base-estructural-acceso/
├── spec.md                  # Especificación aprobada y clarificada
├── plan.md                  # Este plan de implementación
├── research.md              # Decisiones técnicas (Phase 0)
├── data-model.md            # Esquema de entidad User y migraciones (Phase 1)
├── contracts/               # Contratos de API REST (Phase 1)
│   ├── auth-contract.md     # /api/auth/login, logout, me
│   └── users-contract.md    # /api/users CRUD y activación
├── checklists/
│   └── requirements.md      # Lista de verificación de requisitos (13/13)
└── quickstart.md            # Guía de prueba y ejecución paso a paso
```

### Estructura del Código Fuente
```text
c:/xampp/htdocs/servimatica-app/
├── admin-starter-kit/              # Frontend Vue 3 + Vuetify en desarrollo
│   ├── src/
│   │   ├── pages/
│   │   │   ├── login.vue           # Login dual (Contraseña y PIN con teclado táctil)
│   │   │   ├── index.vue           # Panel de bienvenida según rol
│   │   │   └── users/index.vue     # Gestión de personal (Dueño)
│   │   ├── plugins/casl/           # Control de permisos por rol
│   │   └── navigation/             # Menús según rol (Dueño vs Vendedor)
│   └── package.json
│
├── admin-full-version/             # Catálogo de referencia de componentes (solo lectura)
│
├── app/                            # Backend Laravel (Arquitectura Hexagonal)
│   ├── Domain/User/                # Entidades, Value Objects, Repositorio Interfaz
│   ├── Application/User/           # Casos de uso (Authenticate, Register, ToggleStatus)
│   ├── Infrastructure/
│   │   ├── Persistence/Eloquent/   # Modelo User y EloquentUserRepository
│   │   └── Http/Controllers/Api/   # AuthController, UserController
│   └── Providers/
│
├── routes/
│   └── api.php                     # Endpoints /api/auth/* y /api/users/*
├── database/
│   ├── migrations/                 # Migración de tabla users (con username, pin_code, role, status)
│   └── seeders/DatabaseSeeder.php  # Usuario maestro de fábrica
└── tests/Feature/                  # Pruebas automatizadas de autenticación y permisos
```

---

## 5. Fases de Ejecución

### Ajustes confirmados durante implementación (2026-09-25)

- El usuario confirmó Vue + Vuetify + API Laravel; `AGENTS.md` se alinea con SPA en `admin-starter-kit/` y API en la raíz.
- Se utiliza Laravel 12 por compatibilidad con PHP 8.2 y soporte de seguridad vigente (https://laravel.com/docs/12.x/releases).
- RF-006 y FA-005 requieren edición de usuarios y restablecimiento de contraseña/PIN: se agrega `UpdateUserUseCase`, `PUT /api/users/{id}` y reutilización del drawer para edición. En edición, contraseña/PIN vacíos conservan el valor existente; al crear son obligatorios.
- La propia cuenta no puede desactivarse ni perder el rol Dueño. Los permisos y el estado se verifican en cada petición protegida; no se agregan módulos de ventas o productos.

1. **Fase Preparatoria:** Descomprimir `admin-starter-kit.zip` y `admin-full-version.zip` en el directorio raíz.
2. **Fase Backend:** Migración de `users` (con `username` y `pin_code`), instalación/configuración de JWT, implementación de casos de uso y controladores API con tests.
3. **Fase Frontend:** Adaptar `login.vue` con la pestaña PIN y teclado numérico táctil interactivo, configurar reglas de CASL para roles y crear la vista de gestión de personal para el dueño.
4. **Fase Verificación:** Ejecutar la suite de pruebas automatizadas y verificar los escenarios del [quickstart.md](file:///c:/xampp/htdocs/servimatica-app/specs/001-base-estructural-acceso/quickstart.md).
