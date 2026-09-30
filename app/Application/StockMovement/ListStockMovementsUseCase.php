<?php

namespace App\Application\StockMovement;

use App\Domain\StockMovement\StockMovementRepositoryInterface;

final readonly class ListStockMovementsUseCase
{
    public function __construct(private StockMovementRepositoryInterface $stockMovements)
    {
    }

    public function execute(int $productId, array $filters): array
    {
        return $this->stockMovements->paginate($productId, $filters);
    }
}
