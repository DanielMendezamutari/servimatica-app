<?php

namespace App\Domain\Product;

interface ProductRepositoryInterface
{
    public function paginate(array $filters, bool $owner): array;
    public function create(array $data): Product;
    public function update(int $id, array $data): Product;
    public function toggle(int $id): Product;
    public function getForExport(array $filters, bool $owner): array;
}
