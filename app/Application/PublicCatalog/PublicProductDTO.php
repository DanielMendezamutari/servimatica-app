<?php

namespace App\Application\PublicCatalog;

final readonly class PublicProductDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $sku,
        public string $categoryName,
        public ?string $brandName,
        public ?string $modelName,
        public string $condition,
        public float $salePrice,
        public int $stock,
        public int $warrantyDays,
        public string $imageUrl,
        public array $galleryUrls,
        public bool $has360,
        public string $whatsappOrderLink
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'category_name' => $this->categoryName,
            'brand_name' => $this->brandName,
            'model_name' => $this->modelName,
            'condition' => $this->condition,
            'sale_price' => number_format($this->salePrice, 2, '.', ''),
            'stock' => $this->stock,
            'warranty_days' => $this->warrantyDays,
            'image_url' => $this->imageUrl,
            'gallery_urls' => $this->galleryUrls,
            'has_360' => $this->has360,
            'whatsapp_order_link' => $this->whatsappOrderLink,
        ];
    }
}
