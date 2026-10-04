# Feature Specification: Configuración Centralizada de Mi Empresa, Sucursal y Comprobantes Dinámicos

**Feature Branch**: `007-datos-empresa-sucursal`  
**Created**: 2026-10-03  
**Status**: Draft  
**Input**: User description: "porque me aparece trinidad. debe haber una forma de poder modificar desde el sistema osea la sucursal y toda esa información que no este hardcodeada. debería jalar la información de la empresa. y poder digamos cuando creo yo en el sistema haber un apartado que diga mi negocio o mi empresa. Opción 1: Implementar módulo Mi Empresa / Datos del Negocio (Monotienda/Sucursal configurable desde Ajustes)."

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Parametrización y Administración de "Mi Empresa" por el Dueño (Priority: P1)

Como **Dueño del Negocio**, quiero disponer de una vista dedicada en **Ajustes > Mi Empresa** donde pueda configurar y actualizar en cualquier momento el nombre comercial, razón social, NIT, sucursal, ciudad/departamento, dirección física, teléfonos/WhatsApp, logotipo y políticas de comprobantes, para que ningún dato institucional de mi tienda esté fijo ni quemado en el código fuente.

**Why this priority**: Es la base transversal que alimenta la identidad de la tienda en todo el software. Sin esta parametrización, los tickets y proformas muestran ciudades o datos ficticios que confunden a clientes y proveedores.

**Independent Test**: El Dueño ingresa a Ajustes > Mi Empresa, cambia la ciudad a "Riberalta, Beni — Bolivia", actualiza el teléfono comercial y guarda los cambios. Al recargar la pantalla o volver a consultar el formulario, los datos persisten exactamente como fueron editados.

**Acceptance Scenarios**:
1. **Given** que el Dueño tiene sesión activa, **When** navega a la sección "Mi Empresa" en Ajustes, **Then** ve un formulario intuitivo con todos los campos institucionales precargados (nombre comercial, NIT, sucursal, ciudad, dirección, teléfonos, email, logotipo y políticas).
2. **Given** que el Dueño modifica la sucursal a "Casa Matriz Riberalta" y el teléfono a "77012345", **When** presiona "Guardar Información", **Then** el sistema valida los campos requeridos, persiste los cambios y emite un mensaje de confirmación exitoso.
3. **Given** que un usuario con rol Vendedor intenta acceder a esta configuración o a sus rutas administrativas, **Then** el sistema deniega el acceso con error 403 (Exclusivo para Dueño).
4. **Given** que el Dueño sube un nuevo archivo de imagen como logotipo oficial, **When** guarda la configuración, **Then** el sistema almacena el logotipo y actualiza la previsualización en tiempo real.

---

### User Story 2 - Inyección Dinámica en Proformas de Cotización (Carta y PDF) (Priority: P2)

Como **Vendedor o Dueño**, cuando emito o imprimo una cotización formal (proforma en tamaño Carta o formato PDF para el cliente), quiero que el encabezado, membrete, logo, dirección, teléfonos de contacto y términos de validez se generen dinámicamente con los datos de "Mi Empresa", reflejando fielmente la ciudad y sucursal configuradas.

**Why this priority**: Las proformas son documentos comerciales que se entregan al cliente para cerrar ventas. Deben mostrar datos fidedignos y actualizados de la tienda.

**Independent Test**: Configurar en Mi Empresa la ciudad "Riberalta, Beni" y el teléfono "77112233". Al pulsar "Imprimir proforma Carta / PDF" en cualquier cotización, el encabezado muestra automáticamente "Riberalta, Beni", el nuevo teléfono y el logo de la empresa sin intervención manual.

**Acceptance Scenarios**:
1. **Given** que la proforma se visualiza para imprimir, **When** carga el documento, **Then** el membrete muestra el logotipo configurado, nombre comercial, actividad/slogan, ciudad/departamento y teléfonos parametrizados en Mi Empresa.
2. **Given** que el Dueño configuró notas y términos de validez predeterminados en Mi Empresa, **When** se imprime una proforma que no tiene observaciones personalizadas, **Then** se renderizan automáticamente los términos comerciales oficiales del negocio.

---

### User Story 3 - Inyección Dinámica en Tickets Térmicos de Venta (80mm), Recepción y Arqueo (Priority: P3)

Como **Cajero o Administrador en el POS**, cuando imprimo un ticket de venta en mostrador de 80mm, un comprobante de compra o un ticket de arqueo de caja chica, quiero que el membrete térmico contenga el nombre del negocio, NIT, sucursal, dirección y pie de página de garantía configurados en "Mi Empresa".

**Why this priority**: Los tickets de mostrador representan el comprobante que el cliente se lleva en mano. Deben certificar la compra con la dirección y teléfonos exactos del local.

**Independent Test**: Realizar una venta en el POS e imprimir el ticket térmico. Verificar que la cabecera térmica contenga la razón social, sucursal, NIT y teléfonos guardados en Mi Empresa, y que el pie de página muestre el mensaje de garantía configurado.

**Acceptance Scenarios**:
1. **Given** una venta cobrada en el Punto de Venta, **When** se genera el ticket térmico de 80mm, **Then** el encabezado y pie de página imprimen dinámicamente los datos de la sucursal activa.
2. **Given** una compra recepcionada o un cierre de caja chica, **When** se genera el ticket de arqueo o nota de compra, **Then** la cabecera adopta los datos institucionales del negocio.

---

### User Story 4 - Consulta Pública de Identidad Institucional para el Frontend (Priority: P4)

Como **Usuario del Sistema (Vendedor, Dueño o Cliente en mostrador)**, quiero que la interfaz gráfica (barra superior, pie de página, login y encabezados visuales) consuma de manera reactiva el nombre y logo del negocio sin exponer datos sensibles.

**Why this priority**: Proporciona coherencia de marca en toda la experiencia de usuario y prepara el terreno para la futura app Android de los vendedores.

**Independent Test**: Consultar el endpoint público de identidad institucional y verificar que retorna el nombre comercial, sucursal, teléfono y URL del logo sin exigir credenciales de administrador.

**Acceptance Scenarios**:
1. **Given** un cliente o vendedor accediendo a la plataforma, **When** el frontend solicita la información de marca, **Then** el sistema responde con los datos públicos del negocio de manera inmediata y en caché ligera.

---

### Edge Cases

- **Configuración inicial en blanco (Cold Start):** Si el sistema se instala por primera vez o la base de datos está recién migrada sin configuración guardada, el sistema debe utilizar valores predeterminados seguros y coherentes (ej. "Servimática Computación", "Bolivia", logo predeterminado) para que ningún ticket falle con error 500.
- **Subida de logo en formatos no admitidos o archivos muy pesados:** El sistema debe validar que el logo sea una imagen válida (`.png`, `.jpg`, `.jpeg`, `.webp`, `.svg`) con un peso máximo razonable (ej. 2 MB) y rechazar archivos inválidos con mensajes en español claro.
- **Campos opcionales sin completar:** Si la empresa no cuenta con NIT o no tiene teléfono fijo, los comprobantes no deben mostrar etiquetas vacías (ej. no imprimir "NIT: " o "Tel: " en blanco).

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE proveer un módulo administrativo exclusivo para el rol Dueño (`owner`) denominado "Mi Empresa" en la sección de Ajustes del menú lateral.
- **FR-002**: El sistema DEBE permitir registrar y actualizar los siguientes atributos institucionales:
  - Nombre Comercial (obligatorio, máx. 120 caracteres).
  - Razón Social / Titular Legal (opcional, máx. 150 caracteres).
  - NIT / Identificación Tributaria (opcional, máx. 30 caracteres).
  - Slogan / Actividad Comercial (opcional, máx. 200 caracteres).
  - Sucursal (obligatorio, ej. "Sucursal Central", "Casa Matriz", máx. 100 caracteres).
  - Ciudad y Departamento (obligatorio, máx. 100 caracteres).
  - Dirección física del establecimiento (obligatorio, máx. 250 caracteres).
  - Celular / WhatsApp de atención al cliente (obligatorio, máx. 30 caracteres).
  - Teléfono fijo secundario (opcional, máx. 30 caracteres).
  - Correo electrónico de contacto (opcional, formato email válido).
  - Logotipo institucional (opcional, imagen `.png`, `.jpg`, `.jpeg`, `.webp`, `.svg`).
  - Términos y condiciones predeterminados de cotizaciones (opcional, texto libre).
  - Política de garantía y pie de página para tickets de venta (opcional, texto libre).
- **FR-003**: El sistema DEBE almacenar el archivo de logotipo en el almacenamiento público del servidor y retornar su URL absoluta accesible públicamente.
- **FR-004**: El sistema DEBE garantizar que si no existe una configuración previa guardada, se proporcione una configuración por defecto (Seeder / Fallback) para evitar interrupciones en la facturación y cotizaciones.
- **FR-005**: Las vistas de comprobantes (proforma en tamaño Carta [quotes/print.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/resources/views/quotes/print.blade.php), ticket de venta 80mm [sales/receipt.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/resources/views/sales/receipt.blade.php), ticket de compra [purchases/receipt.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/resources/views/purchases/receipt.blade.php) y ticket de caja chica [cash_shifts/receipt.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/servimatica-app/resources/views/cash_shifts/receipt.blade.php)) DEBEN inyectar dinámicamente los datos de "Mi Empresa".
- **FR-006**: Se prohíbe la presencia de ciudades, sucursales, teléfonos o leyendas institucionales hardcodeadas en las vistas de impresión.
- **FR-007**: El sistema DEBE exponer un endpoint administrativo protegido (`GET /api/company-settings` y `POST /api/company-settings`) exclusivo para el Dueño, y un endpoint público ligero (`GET /api/company-settings/public`) para consumo de identidad de marca por vendedores y clientes.

---

### Key Entities

- **CompanySetting (Configuración de la Empresa / Sucursal)**: Entidad que encapsula los datos institucionales únicos del negocio.
  - Atributos: `id`, `trade_name`, `legal_name`, `tax_id`, `slogan`, `branch_name`, `city`, `address`, `phone`, `mobile`, `email`, `logo_url`, `default_quote_terms`, `receipt_footer_message`, `updated_at`.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El Dueño puede actualizar la ciudad, dirección, teléfono y logo de su negocio en menos de 1 minuto desde el panel web.
- **SC-002**: El 100% de los comprobantes impresos (proformas Carta y tickets térmicos de 80mm) reflejan de forma inmediata cualquier cambio realizado en los datos de la empresa sin requerir reinicio del servidor ni modificaciones en código.
- **SC-003**: Cero errores 500 al imprimir o cotizar en caso de instalaciones nuevas o campos opcionales vacíos (resuelto mediante fallbacks elegantes).
- **SC-004**: Los usuarios con rol Vendedor tienen acceso de solo lectura a la información pública del negocio y restricción total de modificación.

---

## Assumptions

- **Arquitectura Monotienda / Sucursal Principal Activa**: Para la versión actual, el negocio opera con una sucursal principal centralizada cuyos datos aplican para todas las operaciones en curso.
- **Almacenamiento Local de Assets**: Los archivos de logotipo se almacenan en el disco público de Laravel (`storage/app/public/company`) con enlace simbólico accesible desde el navegador.
- **Idioma y Moneda**: Todos los campos e interfaces respetan el español boliviano y la moneda oficial en Bolivianos (`Bs.`).
