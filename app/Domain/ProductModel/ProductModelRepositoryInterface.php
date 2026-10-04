<?php

namespace App\Domain\ProductModel;

interface ProductModelRepositoryInterface
{
    public function getByBrand(int $brandId, ?bool $activeOnly = null): array;
    public function findById(int $id): ?ProductModel;
    public function create(array $data): ProductModel;
    public function update(int $id, array $data): ProductModel;
    public function toggle(int $id): ProductModel;
    public function findOrCreateByName(int $brandId, string $name): ProductModel;
}
