<?php

namespace App\Application\Sale;

use App\Domain\Sale\SaleRepositoryInterface;

final readonly class GenerateCommissionsReportUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository
    ) {}

    public function execute(?int $sellerId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->saleRepository->getCommissionsReport($sellerId, $startDate, $endDate);
    }
}
