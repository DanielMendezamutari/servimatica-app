# Feature Specification: Galería de Imágenes, Vitrina 360°/3D para TikTok Live y Enlace Seguro de Proformas

**Feature Branch**: `014-galeria-imagenes-vitrina-360-proformas-seguras`  
**Created**: 2026-10-04  
**Status**: Specified / Ready for Planning  
**Input**: Requerimiento del usuario: "quiero que sea la muestra de imagenes de forma tipo 3d o algo asombrante que en su celular digamos se pueda tipo 360 o algo asi analiza eso y todo lo demas que ya estamos analizando (ingreso de fotos a productos y seguridad de proformas)"

---

## Contexto y Visión de Negocio

Servimática busca potenciar sus ventas en transmisiones en vivo (TikTok Live y redes sociales). Para ello, los espectadores necesitan ingresar desde su celular a un catálogo público sin iniciar sesión, explorar los equipos con una experiencia visual asombrosa (tarjetas con efecto 3D Parallax/Tilt, giro interactivo en 360° deslizando el dedo y soporte opcional de Realidad Aumentada), y cerrar la compra con un solo toque hacia WhatsApp. 

Adicionalmente, el sistema actual no cuenta con carga de imágenes en el catálogo de productos y expone enlaces secuenciales (`/api/quotes/3/print`) vulnerables a enumeración, los cuales deben ser asegurados con identificadores/tokens criptográficos públicos.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Gestión de Imagen Principal y Galería Multi-Ángulo 360° en Catálogo (Priority: P1) 🎯 MVP

Como **Administrador / Dueño**, quiero subir la foto principal y una galería de fotos en distintos ángulos (frente, teclado, laterales/puertos, tapa trasera) al registrar o editar un producto en `AddProductDrawer.vue`, para que el catálogo cuente con soporte visual completo para la venta presencial y digital.

**Why this priority**: Es el prerrequisito técnico fundacional. Sin fotos reales en la base de datos y en el disco, ninguna vitrina ni experiencia 360° puede funcionar.

**Independent Test**: Editar un producto existente desde el panel, subir una foto de portada y 4 fotos de ángulos, guardar y verificar que se almacenen en disco y se previsualicen en la tabla y en el formulario.

**Acceptance Scenarios**:
1. **Given** el formulario de creación/edición de producto, **When** el usuario selecciona una imagen principal (JPG/PNG/WEBP hasta 5MB), **Then** el sistema genera una vista previa instantánea y la almacena en `storage/app/public/products/`.
2. **Given** un equipo tecnológico (ej. laptop), **When** el usuario adjunta entre 2 y 8 fotos de diferentes ángulos en la sección "Galería 360°", **Then** el sistema las ordena secuencialmente para permitir el giro interactivo.
3. **Given** un producto sin foto cargada, **When** se visualiza en el sistema, **Then** muestra un placeholder corporativo estilizado con el logotipo de Servimática y el icono de su categoría.

---

### User Story 2 - Enlaces Públicos Seguros / Tokenizados de Proforma PDF (Priority: P2)

Como **Cliente** que recibe una cotización por WhatsApp, quiero abrir el enlace de mi proforma en mi teléfono de manera segura y visualizar un documento adaptado a móviles con botón directo de descarga PDF, sin que nadie pueda adivinar o curiosear cotizaciones ajenas cambiando números secuenciales.

**Why this priority**: Protege la confidencialidad de los clientes, evita la enumeración de presupuestos y garantiza que el enlace funcione en cualquier dispositivo fuera de la red local.

**Independent Test**: Generar una cotización, consultar el enlace público generado (con token UUID/hash no secuencial), abrirlo en modo incógnito/sin login y constatar que renderiza la proforma oficial sin exponer IDs internos.

**Acceptance Scenarios**:
1. **Given** una proforma creada, **When** se solicita el enlace para WhatsApp o cliente, **Then** el sistema entrega una URL con token seguro de acceso público: `/quotes/public/{public_token}`.
2. **Given** un visitante anónimo que accede con un token válido, **When** carga la página, **Then** visualiza la cotización completa con sus términos, garantías y cuentas bancarias, con un botón destacado "📥 Descargar PDF".
3. **Given** un usuario que intenta adivinar proformas cambiando números (ej. `/quotes/1/print`), **When** no posee el token seguro o no está autenticado como vendedor, **Then** el sistema deniega el acceso con 404 o 403.

---

### User Story 3 - Vitrina Pública E-Commerce para TikTok Live con Efecto 3D Tilt y Giro 360° (Priority: P3)

Como **Espectador de un TikTok Live**, quiero entrar desde el enlace de la biografía (`servimatica.com.bo/catalogo` o `/live`) desde mi celular, explorar el catálogo con tarjetas dinámicas 3D y girar las laptops en 360° con mi pulgar, y tocar un botón para comprar directamente por WhatsApp con el mensaje pre-llenado.

**Why this priority**: Es la herramienta de conversión de ventas masivas que capitaliza el tráfico del streaming en vivo.

**Independent Test**: Abrir la ruta `/catalogo` en el navegador móvil sin iniciar sesión, filtrar por laptops, deslizar el dedo sobre una laptop para verla girar 360°, y hacer clic en "Pedir por WhatsApp" verificando que abra el chat con el mensaje prearmado.

**Acceptance Scenarios**:
1. **Given** un visitante anónimo en `/catalogo`, **When** navega por la vitrina, **Then** visualiza tarjetas de productos con efecto 3D Parallax/Tilt al deslizar o mover el teléfono, mostrando precio en Bs., condición y garantía.
2. **Given** un producto con galería multi-ángulo, **When** el usuario desliza horizontalmente el dedo (swipe) sobre la imagen en la ficha del producto, **Then** el equipo rota suavemente en 360° mostrando todos sus ángulos.
3. **Given** cualquier producto de la vitrina, **When** el cliente toca "Comprar por WhatsApp", **Then** redirige a WhatsApp con el mensaje: *"¡Hola Servimática! Vi en el Live la [Laptop X] de Bs. [Y]. ¿Sigue disponible para entrega hoy?"*.
4. **Given** la carga pública del catálogo, **When** se consultan los endpoints de API, **Then** el backend filtra estrictamente y nunca expone precios de costo, proveedores ni márgenes comerciales.

---

## Functional Requirements

- **FR-001**: La tabla `products` debe incorporar `image_path` (string nullable), `gallery_images` (json/relación nullable) y `public_token` (uuid único) en las proformas `quotes`.
- **FR-002**: `AddProductDrawer.vue` debe permitir seleccionar/arrastrar imagen principal y múltiples fotos secundarias con previsualización inmediata y eliminación antes de guardar.
- **FR-003**: El backend debe validar tipos MIME (`image/jpeg,image/png,image/webp`), peso máximo de 5MB por imagen y optimizar/almacenar en disco público (`storage/products/`).
- **FR-004**: Las cotizaciones deben contar con un `public_token` generado automáticamente al crearse, accesible públicamente vía `GET /api/quotes/public/{public_token}` sin requerir JWT.
- **FR-005**: La vista web de proforma pública debe detectar resolución móvil y presentar una interfaz ejecutiva con acciones claras: "Descargar PDF" y "Contactar Asesor".
- **FR-006**: Debe existir un endpoint público `GET /api/public/catalog` que soporte búsqueda por texto, filtro por categoría, marca, condición y rango de precios, retornando exclusivamente datos comerciales públicos.
- **FR-007**: La ruta web pública `/catalogo` debe ser accesible sin autenticación (bypass de guard de CASL/Auth) con diseño optimizado para celulares (Mobile-First).
- **FR-008**: La ficha del producto en `/catalogo` debe integrar un visor 360° táctil (touch drag turntable) utilizando la secuencia de fotos de la galería.

---

## Non-Functional Requirements & Constraints

- **NFR-001 (Performance en Celulares):** La vitrina pública debe cargar en menos de 1.5 segundos en redes móviles 4G. Las imágenes deben soportar lazy-loading.
- **NFR-002 (Cero Servidor Dedicado 3D / KISS):** El giro 360° no debe depender de librerías pesadas de renderizado (>500KB); debe utilizar rotación de secuencia de imágenes optimizada en canvas o DOM nativo.
- **NFR-003 (Seguridad Comercial):** Ningún payload del catálogo público puede contener `cost_price`, `supplier_id` o cálculos de utilidad.
- **NFR-004 (Apego Estricto al Stack):** Implementado con Laravel 12 (backend hexagonal), Vue 3 + Vuetify 3 (`admin-starter-kit`), respetando las reglas de `AGENTS.md` (PROHIBIDO `php artisan serve`).

---

## Success Criteria

- **SC-001**: 100% de los productos creados o editados pueden guardar su imagen principal y fotos de galería visualizables en el panel.
- **SC-002**: Ninguna cotización puede ser accedida por ID correlativo sin autorización; 100% de los accesos públicos se realizan mediante `public_token`.
- **SC-003**: Un visitante en `/catalogo` puede girar una laptop en 360° mediante swipe táctil en su smartphone con fluidez de 60fps.
- **SC-004**: 100% de las pruebas automatizadas de Laravel (`php artisan test`) pasan con 0 fallos.
