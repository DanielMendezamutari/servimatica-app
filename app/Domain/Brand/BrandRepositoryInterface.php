<?php

namespace App\Domain\Brand;

interface BrandRepositoryInterface
{
    public function all(string $search = '', ?bool $activeOnly = null): array;
    public function findById(int $id): ?Brand;
    public function create(array $data): Brand;
    public function update(int $id, array $data): Brand;
    public function toggle(int $id): Brand;
    public function options(): array;
}
