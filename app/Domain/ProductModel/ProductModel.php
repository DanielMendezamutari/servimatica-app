<?php

namespace App\Domain\ProductModel;

final readonly class ProductModel
{
    public function __construct(
        public int $id,
        public int $brandId,
        public string $name,
        public ?string $notes = null,
        public bool $isActive = true,
        public ?string $brandName = null,
        public ?string $createdAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brandId,
            'brand_name' => $this->brandName,
            'name' => $this->name,
            'notes' => $this->notes,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
        ];
    }
}
