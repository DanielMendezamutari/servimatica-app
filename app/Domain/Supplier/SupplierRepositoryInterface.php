<?php

namespace App\Domain\Supplier;

interface SupplierRepositoryInterface
{
    public function findById(int $id): ?Supplier;
    public function save(array $data): Supplier;
    public function update(int $id, array $data): Supplier;
    public function toggleStatus(int $id): Supplier;
    public function paginate(int $page = 1, int $perPage = 15, ?string $search = null, ?string $status = null): array;
    public function options(): array;
}
