# Technical Research & Decisions: Capítulo 3 — Catálogo Jerárquico Avanzado, Marcas, Condición y Auditoría

**Feature**: `003-catalogo-avanzado-auditoria`
**Date**: 2026-10-03
**Status**: Completed

---

## 1. Generación y Exportación de Planillas Excel (.xlsx)

- **Decisión**: Utilizar `phpoffice/phpspreadsheet` (versión 5.x) para generar archivos binarios `.xlsx` nativos con formato de celdas específico (moneda en Bolivianos `Bs.`, texto para CI/SKU, y porcentajes), autoajuste de ancho de columnas y encabezados visuales profesionales.
- **Racional**:
  - Produce archivos `.xlsx` estándar y 100% compatibles con Microsoft Excel, LibreOffice Calc y Google Sheets.
  - Permite formatear explícitamente celdas monetarias con el formato `[$Bs.-80A] #,##0.00` o texto formateado para evitar que números largos de documento (CI o SKU) se desborden en notación científica.
  - Se integra de forma limpia en Laravel como una respuesta de descarga (`response()->streamDownload(...)`) sin generar archivos temporales huérfanos en disco.
- **Alternativas Evaluadas**:
  - *Generación de archivos CSV con extensión .csv*: Rechazada porque los navegadores y sistemas operativos en español a menudo abren CSV con problemas de codificación de caracteres acentuados (UTF-8 con/sin BOM) y no conservan formato de moneda ni columnas protegidas.
  - *Maatwebsite/Excel*: Es una envoltura sobre PhpSpreadsheet. Para los requisitos de Servimática, instanciar directamente PhpSpreadsheet evita capas adicionales de abstracción y simplifica el mantenimiento sin dependencias extras de service providers.

---

## 2. Jerarquía de Categorías (Categoría Principal y Subfamilias)

- **Decisión**: Patrón **Adjacency List** mediante una columna `parent_id` (foreign key auto-referenciada nullable a `categories.id` con `onDelete('restrict')`).
- **Racional**:
  - Cumple estrictamente el Principio I de la Constitución (Simplicidad / KISS y YAGNI).
  - El modelo requiere exactamente dos niveles (Categoría Principal y Subfamilia). Una relación auto-referenciada `parent()` y `children()` en Eloquent resuelve la jerarquía en una sola tabla sin sobre-ingeniería (sin árboles de closure ni nested sets innecesarios).
  - `onDelete('restrict')` garantiza en el motor de base de datos que ninguna categoría padre pueda eliminarse si existen subfamilias o productos asignados (RF-027).
- **Alternativas Evaluadas**:
  - *Tablas separadas (`categories` y `subcategories`)*: Rechazada porque duplicaría modelos, migraciones, controladores y repositorios para la misma estructura de datos taxonómica.
  - *Nested Set Model (`baum` o `laravel-nestedset`)*: Rechazada por ser una sobre-ingeniería que viola el principio KISS y agrega complejidad innecesaria para un catálogo de 2 niveles.

---

## 3. Catálogo de Marcas y Modelos con Creación al Vuelo

- **Decisión**: Dos entidades separadas y relacionadas: `Brand` (Marca) y `ProductModel` (Modelo), con soporte en el endpoint de creación/edición de productos para registrar un modelo al vuelo si el usuario ingresa un nombre no registrado.
- **Racional**:
  - Estandariza los nombres de fabricantes en hardware (evita que un vendedor escriba "Asus", otro "ASUS" y otro "ASUS ROG").
  - El modelo pertenece a una marca (`brand_id`).
  - La creación al vuelo (combobox en Vue 3 + `firstOrCreate(['brand_id' => $brandId, 'name' => trim($modelName)])` en backend) agiliza drásticamente el flujo de trabajo del usuario en mostrador.
- **Alternativas Evaluadas**:
  - *Campo modelo como texto libre sin tabla*: Rechazada porque impediría filtrar eficazmente por modelo en el catálogo y generaría inconsistencias tipográficas.
  - *Solo selección estricta sin creación al vuelo*: Rechazada en la sesión de aclaraciones porque obligaría al usuario a salir del formulario de producto cada vez que llegue un modelo nuevo a tienda.

---

## 4. Registro Inmutable de Auditoría de Inicios de Sesión (LoginLog)

- **Decisión**: Crear la entidad y tabla `login_logs` y registrar el intento de acceso directamente en el flujo de autenticación (`AuthController::login` / caso de uso de autenticación), capturando intentos exitosos y fallidos.
- **Racional**:
  - Registra: `user_id` (nullable), `attempted_username`, `ip_address`, `user_agent`, `status` (`success`, `failed_credentials`, `failed_inactive_user`) y `created_at`.
  - Es inmutable: la API solo expondrá método `GET` (listado paginado con filtros) exclusivo para el rol `administrador`. No se implementará actualización ni borrado.
  - Cumple el Principio VI de la Constitución (Privacidad y Seguridad).
- **Alternativas Evaluadas**:
  - *Usar logs de archivo de Laravel (`storage/logs/laravel.log`)*: Rechazada porque no es accesible ni consultable por el Dueño desde la interfaz web sin conocimientos técnicos (viola el Principio IV).
  - *Auditoría completa de base de datos (`owen-it/laravel-auditing`)*: Rechazada por violar YAGNI; el requisito solicita específicamente auditoría de accesos/login, no auditoría de cambios en todas las tablas del sistema.

---

## 5. Ficha de Usuario Completa y Gestión de Fotografías

- **Decisión**: Ampliar la tabla `users` mediante migración con los campos obligatorios (`ci`, `username`) y opcionales (`phone`, `address`, `gender`, `sales_commission`, `branch`, `avatar`), almacenando imágenes de perfil en `storage/app/public/avatars` con acceso vía enlace simbólico público o base64 optimizado.
- **Racional**:
  - Satisface fielmente la estructura visual solicitada por el usuario en su modelo de referencia "Gestión de Usuario".
  - La distinción estricta entre obligatorios (CI, Nombres, Usuario, Password, Rol, Estado) y opcionales (Teléfono, Dirección, Sexo, Correo, Comisión, Foto) previene fricciones al crear usuarios de prueba o vendedores rápidos.
  - Si no se carga foto, el frontend genera dinámicamente un avatar con iniciales o icono genérico de usuario.
- **Alternativas Evaluadas**:
  - *Tabla separada `user_profiles`*: Innecesaria para una relación 1:1 estricta con volumen de usuarios menor a 100 personas en la tienda. Extender la tabla `users` mantiene las consultas simples (KISS).

---

## 6. Componentes Frontend de la Plantilla (`admin-full-version`)

- **Decisión**: Extraer e implementar componentes probados de `admin-full-version/`:
  - `VCombobox` / `VAutocomplete` con filtrado y slot para agregar nuevo elemento al vuelo para Modelos.
  - `VFileInput` con previsualización de imagen para la fotografía de usuario.
  - `VChip` con variantes de color semánticas para la condición del producto (`Nuevo` = verde/success, `Seminuevo/Open Box` = azul/info, `Usado` = naranja/warning, `Reacondicionado` = morado/secondary).
  - Botón de acción con icono `tabler-file-spreadsheet` para disparar la descarga de Excel mediante `window.open` o descarga de blob seguro con token JWT.
