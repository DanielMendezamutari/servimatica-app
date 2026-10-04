<?php

namespace App\Domain\Purchase;

final readonly class PurchaseItem
{
    public function __construct(
        public int $id,
        public int $purchaseId,
        public int $productId,
        public string $productName,
        public string $productSku,
        public int $quantity,
        public float $unitCost,
        public float $subtotal,
        public ?float $previousCost = null,
        public ?float $previousSalePrice = null,
        public ?float $newSalePrice = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'purchase_id' => $this->purchaseId,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'quantity' => $this->quantity,
            'unit_cost' => number_format($this->unitCost, 2, '.', ''),
            'subtotal' => number_format($this->subtotal, 2, '.', ''),
            'previous_cost' => $this->previousCost !== null ? number_format($this->previousCost, 2, '.', '') : null,
            'previous_sale_price' => $this->previousSalePrice !== null ? number_format($this->previousSalePrice, 2, '.', '') : null,
            'new_sale_price' => $this->newSalePrice !== null ? number_format($this->newSalePrice, 2, '.', '') : null,
        ];
    }
}
