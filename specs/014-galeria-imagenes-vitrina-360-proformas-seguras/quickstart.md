# Quickstart & Verification Scenarios: Galería de Imágenes, Vitrina 360° y Proformas Seguras

---

## 1. Escenario A: Subida de Foto de Portada y Galería 360° a un Producto

1. Iniciar sesión como Dueño (`admin` / `1234`).
2. Navegar a **Inventario > Productos** y abrir el drawer de edición de un equipo (ej. Laptop).
3. En la sección "Imágenes del Producto":
   - Cargar imagen de portada (`laptop_front.jpg`).
   - Cargar 4 fotos de galería ordenadas en 360° (`frente.jpg`, `teclado.jpg`, `puertos.jpg`, `tapa.jpg`).
4. Guardar cambios.
5. **Verificación:**
   - La respuesta HTTP devuelve 200 con `image_path` y `gallery_images`.
   - La foto se visualiza en la tabla de productos y al volver a abrir el drawer.

---

## 2. Escenario B: Generación y Acceso a Enlace Seguro de Proforma

1. Crear una cotización en el POS o módulo de Proformas para el cliente "Jhoshua".
2. Consultar el enlace generado de WhatsApp (`GET /api/quotes/{id}/whatsapp-link`).
3. **Verificación:**
   - El enlace contiene `/quotes/public/{public_token}` (UUID v4), no `/quotes/3/print`.
   - Abrir el enlace en una ventana de incógnito sin token JWT: La proforma carga perfectamente.
   - Intentar abrir `/api/quotes/1/print` sin token de sesión: Retorna 401/403. Solo el poseedor del `public_token` puede ver la proforma de forma pública.

---

## 3. Escenario C: Navegación de la Vitrina Pública para TikTok Live y Giro 360°

1. En el celular o navegador en modo responsivo móvil (375x667px o similar), ingresar a `/catalogo` sin login.
2. **Verificación:**
   - La vitrina carga inmediatamente sin pedir login ni redirigir a `/login`.
   - Se aprecian las tarjetas con efecto 3D Parallax Tilt al deslizar la pantalla.
   - Tocar la laptop para abrir su ficha detallada: el visor 360° permite deslizar horizontalmente con el pulgar para rotar la laptop con las 4 fotos cargadas.
   - Tocar el botón "📲 Pedir por WhatsApp": abre WhatsApp Web/App con el mensaje pre-llenado de cotización para ese producto específico.
