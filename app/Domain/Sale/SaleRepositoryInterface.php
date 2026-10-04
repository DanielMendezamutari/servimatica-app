<?php

namespace App\Domain\Sale;

interface SaleRepositoryInterface
{
    public function findById(int $id): ?Sale;
    public function findByInvoiceNumber(string $invoiceNumber): ?Sale;
    public function save(array $saleData, array $itemsData): Sale;
    public function cancel(int $saleId, int $cancelledBy, string $reason): void;
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?int $sellerId = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array;
    public function getNextInvoiceNumber(): string;
    public function getCommissionsReport(?int $sellerId = null, ?string $startDate = null, ?string $endDate = null): array;
}
