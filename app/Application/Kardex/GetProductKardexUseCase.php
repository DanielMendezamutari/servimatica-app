<?php

namespace App\Application\Kardex;

use App\Domain\Kardex\KardexRepositoryInterface;

final class GetProductKardexUseCase
{
    public function __construct(
        private readonly KardexRepositoryInterface $kardexRepository
    ) {
    }

    public function execute(int $productId, array $filters = []): array
    {
        return $this->kardexRepository->getProductKardex($productId, $filters);
    }
}
