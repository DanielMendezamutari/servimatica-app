# Modelo de Datos: Capítulo 3 — Catálogo Jerárquico Avanzado, Marcas, Condición y Auditoría

**Feature**: `003-catalogo-avanzado-auditoria`
**Date**: 2026-10-03
**Status**: Ready

---

## 1. Entidad: `Category` (Categorías Jerárquicas)

Estructura taxonómica en dos niveles: Categoría Principal (Familia) y Subcategoría (Subfamilia).

### Modificación a la tabla `categories`

| Campo | Tipo | Nulo | Índice / Clave | Descripción | Reglas de Validación |
|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Primary Key | Identificador único | Existente |
| `parent_id` | `BIGINT UNSIGNED` | Sí | Foreign Key → `categories.id` (`ON DELETE RESTRICT`) | Categoría padre superior | Opcional. Si es `NULL`, es Categoría Principal. Si tiene valor, es Subfamilia. |
| `name` | `VARCHAR(100)` | No | Unique | Nombre de la categoría o subfamilia | Obligatorio, único, máx 100 caracteres |
| `description` | `TEXT` | Sí | - | Descripción complementaria | Opcional, máx 500 caracteres |
| `status` | `ENUM('active', 'inactive')` | No | Default: `active` | Estado de visibilidad | `active`, `inactive` |
| `created_at` | `TIMESTAMP` | Sí | - | Fecha de creación | Autogenerado |
| `updated_at` | `TIMESTAMP` | Sí | - | Fecha de actualización | Autogenerado |

### Relaciones
- **Category (Padre) → Category (Hijas/Subfamilias)**: One-to-Many (`parent_id`).
- **Category → Product**: One-to-Many (como categoría principal o subfamilia).

---

## 2. Entidad: `Brand` (Marcas de Hardware)

Fabricante comercial de los componentes, equipos y periféricos.

### Esquema de Base de Datos (`brands`)

| Campo | Tipo | Nulo | Índice / Clave | Descripción | Reglas de Validación |
|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Primary Key, Auto-increment | Identificador de la marca | - |
| `name` | `VARCHAR(100)` | No | Unique | Nombre de la marca (ej. ASUS, Kingston) | Obligatorio, único, máx 100 caracteres |
| `is_active` | `BOOLEAN` | No | Default: `true` | Estado activo/inactivo | Obligatorio |
| `created_at` | `TIMESTAMP` | Sí | - | Fecha de creación | Autogenerado |
| `updated_at` | `TIMESTAMP` | Sí | - | Fecha de actualización | Autogenerado |

### Relaciones
- **Brand → ProductModel**: One-to-Many. Una marca tiene múltiples modelos.
- **Brand → Product**: One-to-Many. Una marca se asigna a múltiples productos.

---

## 3. Entidad: `ProductModel` (Modelos y Series)

Serie o modelo específico asociado obligatoriamente a una marca.

### Esquema de Base de Datos (`product_models`)

| Campo | Tipo | Nulo | Índice / Clave | Descripción | Reglas de Validación |
|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Primary Key, Auto-increment | Identificador del modelo | - |
| `brand_id` | `BIGINT UNSIGNED` | No | Foreign Key → `brands.id` (`ON DELETE RESTRICT`) | Marca a la que pertenece | Obligatorio, debe existir en `brands` |
| `name` | `VARCHAR(150)` | No | Index | Nombre del modelo (ej. TUF Gaming F15) | Obligatorio, máx 150 caracteres |
| `notes` | `TEXT` | Sí | - | Notas técnicas o compatibilidad | Opcional |
| `is_active` | `BOOLEAN` | No | Default: `true` | Estado activo/inactivo | Obligatorio |
| `created_at` | `TIMESTAMP` | Sí | - | Fecha de creación | Autogenerado |
| `updated_at` | `TIMESTAMP` | Sí | - | Fecha de actualización | Autogenerado |

*Índice compuesto único:* `UNIQUE KEY (brand_id, name)` para evitar modelos repetidos bajo una misma marca.

---

## 4. Entidad: `Product` (Ampliación Ficha Técnica y Condición)

### Modificación a la tabla `products`

| Campo | Tipo | Nulo | Índice / Clave | Descripción | Reglas de Validación |
|---|---|---|---|---|---|
| `subfamily_id` | `BIGINT UNSIGNED` | Sí | Foreign Key → `categories.id` (`ON DELETE SET NULL`) | Subfamilia clasificada | Opcional, debe tener `parent_id = category_id` |
| `brand_id` | `BIGINT UNSIGNED` | Sí | Foreign Key → `brands.id` (`ON DELETE RESTRICT`) | Marca comercial | Opcional para genéricos |
| `product_model_id` | `BIGINT UNSIGNED` | Sí | Foreign Key → `product_models.id` (`ON DELETE RESTRICT`) | Modelo del producto | Opcional |
| `condition` | `ENUM('nuevo', 'open_box', 'usado', 'reacondicionado')` | No | Default: `'nuevo'` | Condición física y comercial | Obligatorio. Valores: `nuevo`, `open_box`, `usado`, `reacondicionado` |

---

## 5. Entidad: `User` (Ampliación Ficha Completa del Personal)

### Modificación a la tabla `users`

| Campo | Tipo | Nulo | Índice / Clave | Descripción | Reglas de Validación |
|---|---|---|---|---|---|
| `ci` | `VARCHAR(30)` | Sí | Index | Documento de Identidad (CI/DNI) | Obligatorio para altas nuevas, máx 30 caracteres |
| `phone` | `VARCHAR(30)` | Sí | - | Teléfono o celular de contacto | Opcional, máx 30 caracteres |
| `address` | `VARCHAR(255)` | Sí | - | Dirección domiciliaria | Opcional, máx 255 caracteres |
| `gender` | `ENUM('masculino', 'femenino', 'otro')` | Sí | - | Sexo del usuario | Opcional |
| `sales_commission` | `DECIMAL(5,2)` | No | Default: `0.00` | Porcentaje de comisión por ventas | Opcional (predeterminado 0.00%), min 0, max 100 |
| `branch` | `VARCHAR(100)` | No | Default: `'Casa Matriz'` | Sucursal asignada | Opcional (predeterminado "Casa Matriz") |
| `avatar` | `VARCHAR(255)` | Sí | - | Ruta de la imagen de perfil | Opcional, archivo JPG/PNG hasta 2MB |

---

## 6. Entidad: `LoginLog` (Auditoría de Inicios de Sesión)

Registro inmutable de intentos de autenticación en la plataforma.

### Esquema de Base de Datos (`login_logs`)

| Campo | Tipo | Nulo | Índice / Clave | Descripción | Reglas de Validación |
|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Primary Key, Auto-increment | Identificador único del evento | - |
| `user_id` | `BIGINT UNSIGNED` | Sí | Foreign Key → `users.id` (`ON DELETE SET NULL`) | Usuario asociado (si existe) | Opcional |
| `attempted_username` | `VARCHAR(100)` | No | Index | Usuario o correo ingresado al intentar login | Obligatorio |
| `ip_address` | `VARCHAR(45)` | Sí | Index | Dirección IPv4 o IPv6 del cliente | Automático desde request |
| `user_agent` | `TEXT` | Sí | - | Cadena de navegador y dispositivo | Automático desde request |
| `status` | `ENUM('success', 'failed_credentials', 'failed_inactive_user')` | No | Index | Resultado del intento de autenticación | Obligatorio |
| `created_at` | `TIMESTAMP` | No | Index | Fecha y hora exacta del evento | Automático |
