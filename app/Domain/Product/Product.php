<?php

namespace App\Domain\Product;

final readonly class Product
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public Sku $sku,
        public int $categoryId,
        public string $categoryName,
        public Price $costPrice,
        public Price $salePrice,
        public StockQuantity $stock,
        public StockQuantity $minStock,
        public string $status,
        public ?int $subfamilyId = null,
        public ?string $subfamilyName = null,
        public ?int $brandId = null,
        public ?string $brandName = null,
        public ?int $productModelId = null,
        public ?string $productModelName = null,
        public string $condition = 'nuevo',
        public ?string $createdAt = null,
        public int $warrantyDays = 0,
        public int $defectiveStock = 0,
        public ?string $imagePath = null,
        public ?array $galleryImages = null,
        public ?string $imageUrl = null,
        public array $galleryUrls = []
    ) {}

    public function toArray(bool $owner): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku->value,
            'categoryId' => $this->categoryId,
            'categoryName' => $this->categoryName,
            'subfamilyId' => $this->subfamilyId,
            'subfamilyName' => $this->subfamilyName,
            'brandId' => $this->brandId,
            'brandName' => $this->brandName,
            'productModelId' => $this->productModelId,
            'productModelName' => $this->productModelName,
            'condition' => $this->condition,
            'salePrice' => $this->salePrice->value,
            'sale_price' => $this->salePrice->value,
            'stock' => $this->stock->value,
            'warrantyDays' => $this->warrantyDays,
            'warranty_days' => $this->warrantyDays,
            'defectiveStock' => $this->defectiveStock,
            'defective_stock' => $this->defectiveStock,
            'imagePath' => $this->imagePath,
            'image_path' => $this->imagePath,
            'galleryImages' => $this->galleryImages,
            'gallery_images' => $this->galleryImages,
            'imageUrl' => $this->imageUrl,
            'image_url' => $this->imageUrl,
            'galleryUrls' => $this->galleryUrls,
            'gallery_urls' => $this->galleryUrls,
        ];

        if ($owner) {
            $data += [
                'status' => $this->status,
                'costPrice' => $this->costPrice->value,
                'cost_price' => $this->costPrice->value,
                'minStock' => $this->minStock->value,
                'min_stock' => $this->minStock->value,
                'createdAt' => $this->createdAt,
                'created_at' => $this->createdAt,
            ];
        }

        return $data;
    }
}

