# Research & Technical Decisions: Galería de Imágenes, Vitrina 360°/3D y Proformas Seguras

---

## Decision 1: Almacenamiento y Estructura de Imágenes de Producto

- **Decisión**: 
  - Agregar a la tabla `products`:
    - `image_path` (VARCHAR 255, nullable): Ruta relativa de la foto de portada (ej. `products/cover_123.webp`).
    - `gallery_images` (JSON, nullable): Array ordenado de rutas de fotos para la galería y secuencia 360° (ej. `["products/gallery_1_1.webp", "products/gallery_1_2.webp", ...]`).
  - Las imágenes se procesan y almacenan en `storage/app/public/products/`, con enlace simbólico público en `public/storage/products/`.
- **Razón**: 
  - La estructura con JSON en `gallery_images` evita sobrecargar con una tabla intermedia para colecciones de 4 a 8 imágenes, manteniendo operaciones atómicas de lectura en una sola consulta SQL (`SELECT * FROM products`).
  - Soporta ordenamiento explícito de ángulos para que el giro 360° sea fluido en el orden correcto.
- **Alternativas consideradas**:
  - *Tabla separada `product_images`*: Añade joins innecesarios para lecturas de vitrina pública y mayor complejidad de sincronización en formularios reactivos de Vue.

---

## Decision 2: Implementación del Visor 360° Táctil en Celulares (Turntable Spin)

- **Decisión**: 
  - Implementar un componente Vue ligero autónomo (`Product360Viewer.vue`) usando eventos nativos táctiles (`pointerdown`, `pointermove`, `pointerup` o touch events) con precarga de imágenes.
  - Al deslizar el dedo en el eje X (horizontal), calcula el índice de la foto proporcional al ancho del contenedor: `frameIndex = Math.floor((deltaX / sensitivity) % totalFrames)`.
  - Para el listado de productos, tarjetas con efecto CSS 3D Tilt (`perspective: 1000px`, `transform: rotateX(...) rotateY(...)` sensible al puntero/giroscopio).
- **Razón**: 
  - Cero dependencias pesadas ni librerías obsoletas (como Three.js completo que pesaría más de 600KB). El componente pesará menos de 15KB y correrá a 60 FPS en cualquier smartphone Android o iOS.
  - No requiere que Servimática modele objetos en 3D: basta con tomar entre 4 y 8 fotos girando la laptop en la tienda.
- **Alternativas consideradas**:
  - *Librería SpriteSpin / Three.js*: Excesivo peso para usuarios que llegan desde un Live de TikTok con redes móviles.

---

## Decision 3: Seguridad de Proformas mediante `public_token` (Sin Enumeración de IDs)

- **Decisión**: 
  - Agregar columna `public_token` (CHAR 36 / UUID v4, indexado y único) en la tabla `quotes`.
  - Al crear una proforma, se genera automáticamente un UUID v4 criptográficamente seguro (`Str::uuid()`).
  - El enlace generado para WhatsApp y clientes pasa a ser:
    `http://servimatica-app.test/api/quotes/public/{public_token}` (o `/quotes/public/{public_token}`).
  - El acceso por ID correlativo `/api/quotes/{id}/print` se restringe al usuario autenticado (vendedor o dueño). Visitantes anónimos solo pueden ver cotizaciones si poseen el `public_token`.
- **Razón**: 
  - Elimina de raíz la vulnerabilidad de enumeración (Insecure Direct Object Reference - IDOR). Nadie puede adivinar o explorar cotizaciones de otros clientes.
- **Alternativas consideradas**:
  - *Signed URLs con expiración*: Válidas pero URLs muy largas con parámetros de firma query string que a veces se rompen al compartirse por chat de WhatsApp. Un token limpio `/quotes/public/{token}` es mucho más robusto y amigable.

---

## Decision 4: Endpoint Público y Aislamiento de Datos Comerciales para TikTok Live

- **Decisión**: 
  - Crear el controlador `App\Infrastructure\Http\Controllers\Api\PublicCatalogController` con el endpoint:
    `GET /api/public/catalog`
  - Filtro estricto en la consulta Eloquent:
    - Solo productos activos (`status = 'active'`) y con stock positivo (`stock > 0`).
    - Atributos seleccionados: `id`, `name`, `sku`, `condition`, `sale_price`, `warranty_days`, `stock` (indicador booleano o conteo comercial), `image_path`, `gallery_images`, `brand_id`, `category_id`, `subfamily_id`, `description`.
    - **PROHIBIDO**: `cost_price`, `supplier_id`, márgenes o auditorías internas.
- **Razón**: 
  - Seguridad comercial absoluta.
  - Velocidad de respuesta inferior a 150ms gracias al índice en `status` y paginación rápida.
