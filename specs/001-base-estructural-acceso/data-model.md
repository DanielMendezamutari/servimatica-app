# Modelo de Datos: Capítulo 1 — Base Estructural y Acceso al Sistema

**Feature**: `001-base-estructural-acceso`  
**Date**: 2026-09-25  
**Status**: Ready  

---

## 1. Entidad: `User` (Usuarios del Sistema)

Representa a los miembros del personal que tienen acceso al sistema (Dueño y Vendedores).

### Atributos y Esquema de Base de Datos (`users`)

| Campo | Tipo | Nulo | Índice / Clave | Descripción | Reglas de Validación |
|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | No | Primary Key, Auto-increment | Identificador único del usuario | - |
| `name` | `VARCHAR(150)` | No | - | Nombre completo del empleado | Obligatorio, máx 150 caracteres |
| `username` | `VARCHAR(50)` | No | Unique | Alias o nombre de usuario corto | Obligatorio, único, min 3, max 50 chars, alfanumérico sin espacios |
| `email` | `VARCHAR(150)` | No | Unique | Correo electrónico oficial | Obligatorio, único, formato email válido |
| `password` | `VARCHAR(255)` | No | - | Contraseña encriptada (`bcrypt`) | Mínimo 6 caracteres en texto plano |
| `pin_code` | `VARCHAR(255)` | No | - | PIN de 4 dígitos encriptado (`bcrypt`) | Exactamente 4 dígitos numéricos (`/^[0-9]{4}$/`) |
| `role` | `ENUM('dueno', 'vendedor')` | No | - | Rol y nivel de acceso en la tienda | Valores permitidos: `dueno`, `vendedor` |
| `status` | `ENUM('active', 'inactive')` | No | Default: `active` | Estado de la cuenta de usuario | Valores permitidos: `active`, `inactive` |
| `created_at` | `TIMESTAMP` | Sí | - | Fecha y hora de creación | Autogenerado por el sistema |
| `updated_at` | `TIMESTAMP` | Sí | - | Fecha y hora de última actualización | Autogenerado por el sistema |

---

## 2. Reglas de Validación de Dominio

- **Unicidad:** No se permite duplicar ni `email` ni `username`. El sistema verifica tanto a nivel de aplicación como en restricción única de la base de datos.
- **Formato PIN:** Debe cumplir estrictamente la expresión regular `^[0-9]{4}$`.
- **Integridad del Dueño:** No se permite eliminar ni marcar como inactivo al usuario con rol `dueno` si es el único dueño activo del sistema.
- **Acceso Permitido:** Solo los usuarios con `status === 'active'` pueden obtener token JWT en el endpoint de autenticación.

---

## 3. Datos Iniciales de Fábrica (Seeder Inicial)

Al ejecutar la migración inicial del sistema, se siembra la cuenta maestra de configuración:

```sql
INSERT INTO users (name, username, email, password, pin_code, role, status, created_at, updated_at)
VALUES (
  'Administrador Dueño',
  'admin',
  'admin@servimatica.com',
  '$2y$10$... (hash de "password")',
  '$2y$10$... (hash de "1234")',
  'dueno',
  'active',
  NOW(),
  NOW()
);
```
