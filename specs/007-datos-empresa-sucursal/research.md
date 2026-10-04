# Research & Decisiones Técnicas: Configuración de Mi Empresa y Sucursal

**Feature**: `007-datos-empresa-sucursal` | **Fecha**: 2026-10-03

---

## 1. Patrón de Persistencia para Configuración de Tienda Única (Singleton Pattern)

### Decisión
Implementar una tabla dedicada `company_settings` con una única fila de registro activo (`id = 1`), administrada mediante un repositorio hexagonal `CompanySettingRepositoryInterface`.

### Justificación
- **KISS & YAGNI:** El 99% de las operaciones de Servimática corresponden a la tienda activa. Modelar una entidad singleton con campos explícitos fuertemente tipados (`trade_name`, `city`, `address`, etc.) es infinitamente más seguro, fácil de consultar y validar que una tabla genérica de clave-valor (`key-value`) o múltiples JSON amorfos.
- Permite aplicar migraciones formales con tipos de datos e índices claros en MySQL/PostgreSQL/SQLite.
- Permite inicializar un registro por defecto mediante un `CompanySettingSeeder` con valores coherentes para que el sistema nunca quede vacío.

### Alternativas Evaluadas
- *Tabla Key-Value (`settings: key, value`):* Rechazada por falta de tipado estricto, dificultad de validación de esquemas y queries engorrosas para extraer todos los datos del membrete en una sola pasada.
- *Archivo `.env` o archivo de configuración PHP estático:* Rechazado terminantemente porque el Dueño debe poder cambiar la sucursal, ciudad, teléfonos y logo desde la interfaz web sin tocar archivos en el servidor ni reiniciar Apache/PHP.

---

## 2. Inyección Dinámica en Vistas de Impresión Blade

### Decisión
Crear un servicio de dominio/aplicación `CompanyInfoProvider` (o View Composer de Laravel) que inyecte un objeto `$company` listo para usar en todas las vistas de comprobantes:
- `quotes/print.blade.php` (Proforma Carta)
- `sales/receipt.blade.php` (Ticket de Venta 80mm)
- `purchases/receipt.blade.php` (Comprobante de Recepción)
- `cash_shifts/receipt.blade.php` (Ticket de Arqueo de Caja Chica)

### Justificación
- Evita que cada controlador tenga que consultar manualmente el repositorio de la empresa y pasarlo por separado a la vista.
- Si en el futuro se añade un nuevo tipo de comprobante (ej: notas de crédito o kardex impreso), los datos institucionales ya están disponibles automáticamente.
- Provee valores de fallback elegantes en caso de que algún campo opcional no haya sido completado por el usuario.

---

## 3. Manejo y Almacenamiento del Logotipo Institucional

### Decisión
Utilizar el disco `public` de Laravel (`storage/app/public/company`), generando nombres únicos hash para evitar problemas de caché del navegador al actualizar el logotipo.

### Justificación
- Las imágenes en `public/storage` son servidas directamente por el servidor web con alto rendimiento y cabeceras de caché adecuadas.
- Es compatible tanto con entornos locales (Laragon, XAMPP, Valet) mediante `asset('storage/' . $path)` como con despliegues en producción.
- Si no se sube un logo personalizado, el sistema hace fallback automático al logotipo predeterminado `public/images/logo_servimatica.png`.

---

## 4. UI y Experiencia de Usuario en Frontend (Vuetify 3)

### Decisión
Crear la vista [admin-starter-kit/src/pages/settings/company.vue](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/admin-starter-kit/src/pages/settings/company.vue) dividida en 3 tarjetas o pestañas temáticas:
1. **Identidad y Sucursal:** Nombre Comercial, Razón Social, NIT, Slogan, Sucursal, Ciudad/Departamento y Dirección.
2. **Contacto y Logotipo:** Teléfonos/WhatsApp, Email y previsualización/subida de imagen del logotipo con botón de carga inmediata.
3. **Políticas y Comprobantes:** Términos predeterminados para proformas y leyenda de pie de página para tickets de venta (política de garantía).

### Justificación
- Diseñado para no saturar al usuario con un formulario vertical infinito.
- Los cambios se guardan con un botón principal flotante o en la cabecera con notificación Snackbar de éxito.
- Protegido por CASL y middleware backend exclusivo para el rol `dueno` (`owner`).
