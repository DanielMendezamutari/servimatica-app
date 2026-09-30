<?php
namespace App\Domain\StockMovement;
final readonly class StockMovement {
    public function __construct(public int $id, public string $type, public int $quantity, public int $previousStock,
        public int $newStock, public string $reason, public int $userId, public string $userName, public string $createdAt) {}
    public function toArray(): array { return get_object_vars($this); }
}
