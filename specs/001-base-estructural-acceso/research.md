# Technical Research: Capítulo 1 — Base Estructural y Acceso al Sistema

**Feature**: `001-base-estructural-acceso`  
**Date**: 2026-09-25  
**Status**: Completed  

---

## 1. Decisiones de Arquitectura y Stack

### Decisión 1: Estrategia de Autenticación (JWT Stateless)
- **Decisión:** Implementar autenticación basada en **JWT (JSON Web Tokens)** con tokens Bearer para todas las comunicaciones de API (`/api/auth/*`).
- **Justificación:** Cumple con la directiva obligatoria de [AGENTS.md](file:///c:/xampp/htdocs/servimatica-app/AGENTS.md). Permite que tanto la aplicación web Vue 3 (`admin-starter-kit`) como la futura app nativa Android en Kotlin (vía Retrofit) consuman los mismos endpoints exactamente con el mismo protocolo sin depender de cookies de sesión propietarias.
- **Alternativas descartadas:** Laravel Sanctum SPA cookies (descartado porque complica la integración con Android nativo en Kotlin y se aleja de la preferencia acordada de JWT).

### Decisión 2: Mecanismo de Validación Dual (Contraseña vs PIN de 4 dígitos)
- **Decisión:** El endpoint `POST /api/auth/login` aceptará:
  ```json
  {
    "login": "admin@servimatica.com", // o alias corto "admin"
    "password": "password",          // opcional si se envía pin
    "pin": "1234"                    // opcional si se envía password
  }
  ```
  El campo `pin` se almacenará en la base de datos de forma encriptada/hasheada (`Hash::make($pin)`) en la columna `pin_code` de la tabla `users`.
- **Justificación:** Garantiza la seguridad de los PINs (nunca se guardan en texto plano en la base de datos) y unifica la lógica de autenticación en un solo caso de uso (`AuthenticateUserUseCase`).
- **Alternativas descartadas:** Guardar el PIN en texto plano (descartado por vulnerar las normas de seguridad de la Constitución).

### Decisión 3: Integración Frontend con la Plantilla Materialize (Vuetify + CASL)
- **Decisión:** Utilizar los componentes nativos de la plantilla en `admin-starter-kit/` (`VTextField`, `VBtn`, `VCard`, etc.). La pantalla de login adaptará `src/pages/login.vue` agregando el selector de modo ("Contraseña" / "PIN de 4 dígitos") y el teclado táctil numérico (0 al 9) en pantalla. El control de permisos se gestiona mediante `@casl/ability` con los roles `Dueño` (acceso a `all`) y `Vendedor` (acceso restringido a catálogo y proformas).
- **Justificación:** Respeta la Regla Mandatoria de [AGENTS.md](file:///c:/xampp/htdocs/servimatica-app/AGENTS.md): reutilizar los componentes y estilos ya existentes en `admin-full-version/` sin inventar desde cero.
- **Alternativas descartadas:** Crear componentes HTML/CSS desde cero (descartado por la regla de oro de UI).

### Decisión 4: Arquitectura Hexagonal en Backend Laravel
- **Decisión:**
  - **Dominio (`src/Domain/User`):** Entidad `User`, Value Objects (`Email`, `Username`, `Password`, `PinCode`, `Role`, `UserStatus`), Repositorio Interfaz (`UserRepositoryInterface`).
  - **Aplicación (`src/Application/User`):** Casos de uso `AuthenticateUserUseCase`, `RegisterUserUseCase`, `ListUsersUseCase`, `ToggleUserStatusUseCase`.
  - **Infraestructura (`src/Infrastructure`):** Adaptador de persistencia Eloquent (`EloquentUserRepository`), Controlador API (`AuthController`, `UserController`), Servicio JWT (`JwtAuthService`).
- **Justificación:** Respeta la directiva de arquitectura limpia y principios SOLID de [AGENTS.md](file:///c:/xampp/htdocs/servimatica-app/AGENTS.md), desacoplando las reglas de negocio de los detalles del framework.

---

## 2. Resumen de Respuestas Técnicas a los Requisitos

| Requisito de Negocio | Solución Técnica en el Plan |
|---|---|
| **Identificación flexible (Correo o Alias)** | Consulta en BD: `WHERE email = :login OR username = :login`. |
| **PIN rápido de 4 dígitos** | Campo `pin_code` hasheado; validación de `Regex: /^[0-9]{4}$/`. |
| **Ingreso al 4to dígito** | Watcher en Vue sobre el input PIN: si `pin.length === 4`, dispara el submit automáticamente. |
| **Sesión de 8 horas** | Configuración de TTL de token JWT en 480 minutos (8 horas). |
| **Teclado numérico táctil** | Componente visual en Vue con grid de botones (0-9, Backspace) que concatena al input. |
