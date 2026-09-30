<?php
namespace App\Domain\StockMovement;
interface StockMovementRepositoryInterface {
    public function adjust(int $productId,int $userId,array $data): array;
    public function paginate(int $productId,array $filters): array;
}
