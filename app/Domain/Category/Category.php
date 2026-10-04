<?php

namespace App\Domain\Category;

final readonly class Category
{
    public function __construct(
        public int $id,
        public CategoryName $name,
        public ?string $description,
        public string $status,
        public int $productsCount = 0,
        public ?int $parentId = null,
        public ?string $parentName = null,
        public array $children = [],
        public ?string $createdAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name->value,
            'description' => $this->description,
            'status' => $this->status,
            'productsCount' => $this->productsCount,
            'parent_id' => $this->parentId,
            'parent_name' => $this->parentName,
            'children' => $this->children,
            'created_at' => $this->createdAt,
        ];
    }
}
