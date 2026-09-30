<?php

namespace App\Application\StockMovement;

use App\Domain\StockMovement\StockMovementRepositoryInterface;

final readonly class AdjustStockUseCase
{
    public function __construct(private StockMovementRepositoryInterface $stockMovements)
    {
    }

    public function execute(int $productId, int $userId, array $data): array
    {
        return $this->stockMovements->adjust($productId, $userId, $data);
    }
}
