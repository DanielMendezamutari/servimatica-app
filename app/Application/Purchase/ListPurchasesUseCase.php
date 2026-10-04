<?php

namespace App\Application\Purchase;

use App\Domain\Purchase\PurchaseRepositoryInterface;

final readonly class ListPurchasesUseCase
{
    public function __construct(
        private PurchaseRepositoryInterface $purchaseRepository
    ) {}

    public function execute(
        int $page = 1,
        int $perPage = 15,
        ?int $supplierId = null,
        ?string $search = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        return $this->purchaseRepository->paginate(
            page: $page,
            perPage: $perPage,
            supplierId: $supplierId,
            search: $search,
            status: $status,
            startDate: $startDate,
            endDate: $endDate
        );
    }
}
