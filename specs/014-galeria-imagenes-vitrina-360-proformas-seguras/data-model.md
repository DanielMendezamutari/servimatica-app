# Data Model: Galería de Imágenes, Vitrina 360° y Proformas Seguras

---

## 1. Modificaciones en Esquema de Base de Datos

### Tabla `products` (Migración de Alteración)

```sql
ALTER TABLE products 
  ADD COLUMN image_path VARCHAR(255) NULL AFTER description,
  ADD COLUMN gallery_images JSON NULL AFTER image_path;
```

- `image_path`: Ruta relativa de la imagen de portada (ej. `products/cover_12.webp`).
- `gallery_images`: Array JSON de rutas de imágenes ordenadas secuencialmente para la galería / giro 360° (ej. `["products/gal_12_1.webp", "products/gal_12_2.webp", "products/gal_12_3.webp", "products/gal_12_4.webp"]`).

### Tabla `quotes` (Migración de Alteración)

```sql
ALTER TABLE quotes 
  ADD COLUMN public_token CHAR(36) NOT NULL UNIQUE AFTER quote_number;
```

- `public_token`: UUID v4 generado automáticamente al instanciar la cotización (`Str::uuid()`), utilizado como llave pública no secuencial para clientes y enlaces en WhatsApp.

---

## 2. Entidades del Dominio

### Entidad `Product` (`app/Domain/Product/Product.php`)
Propiedades agregadas:
- `public readonly ?string $imagePath`
- `public readonly ?array $galleryImages` (array de strings con rutas relativas)
- `public readonly ?string $imageUrl` (URL absoluta calculada o fallback a placeholder por categoría)
- `public readonly ?array $galleryUrls` (URLs absolutas de la galería)

### Entidad `Quote` (`app/Domain/Quote/Quote.php`)
Propiedades agregadas:
- `public readonly string $publicToken`
- `public readonly string $publicUrl` (URL pública basada en el token)

---

## 3. Modelo de Transferencia para Catálogo Público (`PublicProductDTO`)

Para el endpoint `GET /api/public/catalog`:

```php
final readonly class PublicProductDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $sku,
        public string $condition,
        public ?string $categoryName,
        public ?string $brandName,
        public ?string $modelName,
        public float $price,
        public int $warrantyDays,
        public string $warrantyLabel,
        public bool $inStock,
        public int $stockAvailable,
        public ?string $coverImageUrl,
        public array $galleryUrls,
        public ?string $description,
    ) {}
}
```
*(Nótese la ausencia total de `cost_price` y `supplier_id`)*.
