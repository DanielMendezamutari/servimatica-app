# Memoria Técnica Persistente - Servimática App

## 1. Stack & Arquitectura
- **Backend:** PHP 8.x, Laravel en la raíz, Arquitectura Hexagonal, Domain-Driven Design (DDD), principios SOLID. Provee una API REST compartida por la web y la app móvil.
- **Frontend Web:** Vue.js, Vuetify y CASL como SPA en `admin-starter-kit/`.
- **Mobile:** App nativa Android desarrollada en **Kotlin** con **Retrofit** para consumo de la API REST de Laravel.
- **Autenticación API:** **JWT** (JSON Web Tokens con tokens Bearer) para autenticación en la web y la app móvil.
- **Convenciones:** PSR-12 para PHP, Style Guide oficial de Vue.js, Kotlin coding conventions para Android.

## 2. Reglas de Plantillas y UI (Web)
- `admin-starter-kit/`: Entorno limpio de desarrollo en la raíz. Aquí es donde se construye la aplicación web.
- `admin-full-version/`: Catálogo de referencia de componentes y vistas.
- **Regla Mandatoria:** NUNCA inventar componentes desde cero si ya existen en `admin-full-version/`. Consultar siempre `admin-full-version/`, extraer los componentes/layouts e implementarlos dentro de `admin-starter-kit/`.

## 3. Filosofía de Iteración: "Capítulo por Capítulo" (No el libro completo)
- **Metodología:** **Spec-Driven Development (SDD)** guiado por SpecKit (`specify` -> `clarify` -> `plan` -> `tasks` -> `implement`). Cada funcionalidad se especifica y planifica antes de escribir código.
- **Iteraciones Autocontenidas:** Cada iteración se trabaja como un capítulo único y cerrado. No se intenta abarcar todo el sistema ni múltiples módulos a la vez.
- **Rebanada Vertical (Vertical Slice):** Cada capítulo debe resolver una necesidad puntual de punta a punta (Backend -> Frontend Web / App Android -> Pruebas), dejándola 100% funcional, estable y verificada antes de avanzar al siguiente capítulo.
- **Apego Estricto al Conocimiento del Desarrollador:** Se debe respetar rigurosamente el stack establecido (PHP/Laravel, Vue/Vuetify/CASL, Kotlin/Retrofit, JWT). **PROHIBIDO** introducir librerías exóticas, dependencias innecesarias o paradigmas desconocidos que obliguen al desarrollador a estudiar nuevas tecnologías fuera de su dominio.
- **Pragmatismo (KISS & YAGNI):** Cero sobre-ingeniería. Construir exclusivamente lo que el capítulo actual requiere, sin anticipar abstracciones complejas que no aportan al objetivo inmediato.

## 4. Entorno Local & Comandos
- **Host Local:** `http://servimatica-app.test` (gestionado por Laragon/Herd/Valet).
- **Regla Estricta:** **PROHIBIDO** ejecutar `php artisan serve`.
- **Frontend:** `pnpm run dev` para compilación.
- **Pruebas:** `php artisan test` (o `pest`).
