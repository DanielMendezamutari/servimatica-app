<?php

namespace App\Domain\Client;

interface ClientRepositoryInterface
{
    public function findById(int $id): ?Client;
    public function paginate(array $filters = [], int $perPage = 15, int $page = 1): array;
    public function search(?string $query = null, int $perPage = 20): array;
    public function save(array $data): Client;
    public function update(int $id, array $data): Client;
    public function toggleStatus(int $id): Client;
    public function getClientStats(int $id): array;
    public function getClientSales(int $id, int $perPage = 10, int $page = 1): array;
    public function getClientQuotes(int $id, int $perPage = 10, int $page = 1): array;
    public function getClientWarranties(int $id): array;
    public function getAllForExport(): array;
}
