<?php

namespace App\Domain\Sale;

final readonly class SaleItem
{
    public function __construct(
        public ?int $id,
        public int $saleId,
        public int $productId,
        public string $productName,
        public string $productSku,
        public int $quantity,
        public float $unitCost,
        public float $unitPrice,
        public float $subtotal,
        public int $warrantyDays = 0,
        public ?string $warrantyExpiresAt = null,
        public ?string $serialNumber = null
    ) {}

    public function isWarrantyActive(): bool
    {
        if ($this->warrantyDays <= 0 || empty($this->warrantyExpiresAt)) {
            return false;
        }

        return strtotime($this->warrantyExpiresAt) >= strtotime(date('Y-m-d'));
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sale_id' => $this->saleId,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'quantity' => $this->quantity,
            // unit_cost is preserved internally but usually hidden for sellers
            'unit_cost' => number_format($this->unitCost, 2, '.', ''),
            'unit_price' => number_format($this->unitPrice, 2, '.', ''),
            'subtotal' => number_format($this->subtotal, 2, '.', ''),
            'warranty_days' => $this->warrantyDays,
            'warranty_expires_at' => $this->warrantyExpiresAt,
            'serial_number' => $this->serialNumber,
            'is_warranty_active' => $this->isWarrantyActive(),
        ];
    }
}
