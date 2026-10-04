<?php

namespace App\Domain\Brand;

final readonly class Brand
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isActive,
        public int $modelsCount = 0,
        public ?string $createdAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->isActive,
            'models_count' => $this->modelsCount,
            'created_at' => $this->createdAt,
        ];
    }
}
