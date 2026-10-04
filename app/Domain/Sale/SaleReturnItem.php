<?php

namespace App\Domain\Sale;

final readonly class SaleReturnItem
{
    public function __construct(
        public ?int $id,
        public int $saleReturnId,
        public int $saleItemId,
        public int $productId,
        public string $productName,
        public int $quantity,
        public float $unitPrice,
        public float $subtotal,
        public string $condition, // 'stock_operativo', 'stock_defectuoso_rma'
        public ?string $serialNumber = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sale_return_id' => $this->saleReturnId,
            'sale_item_id' => $this->saleItemId,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'unit_price' => number_format($this->unitPrice, 2, '.', ''),
            'subtotal' => number_format($this->subtotal, 2, '.', ''),
            'condition' => $this->condition,
            'serial_number' => $this->serialNumber,
        ];
    }
}
