<?php

namespace App\Domain\Purchase;

interface PurchaseRepositoryInterface
{
    public function findById(int $id): ?Purchase;
    public function findByPurchaseNumber(string $purchaseNumber): ?Purchase;
    public function save(array $data, array $items): Purchase;
    public function cancel(int $id, int $cancelledBy, string $reason): Purchase;
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        ?int $supplierId = null,
        ?string $search = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;
    public function generateNextPurchaseNumber(): string;
}
