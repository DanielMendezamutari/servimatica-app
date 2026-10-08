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
        public ?string $serialNumber = null,
        public int $warrantyHardwareDays = 0,
        public ?string $warrantyHardwareExpiresAt = null,
        public int $warrantySoftwareDays = 0,
        public ?string $warrantySoftwareExpiresAt = null
    ) {}

    public function isWarrantyActive(): bool
    {
        $expiry = $this->warrantyHardwareExpiresAt ?: $this->warrantyExpiresAt;
        if (($this->warrantyHardwareDays <= 0 && $this->warrantyDays <= 0) || empty($expiry)) {
            return false;
        }

        return strtotime($expiry) >= strtotime(date('Y-m-d'));
    }

    public function isHardwareWarrantyActive(): bool
    {
        return $this->isWarrantyActive();
    }

    public function isSoftwareWarrantyActive(): bool
    {
        if ($this->warrantySoftwareDays <= 0 || empty($this->warrantySoftwareExpiresAt)) {
            return false;
        }

        return strtotime($this->warrantySoftwareExpiresAt) >= strtotime(date('Y-m-d'));
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
            'warranty_days' => $this->warrantyHardwareDays ?: $this->warrantyDays,
            'warranty_expires_at' => $this->warrantyHardwareExpiresAt ?: $this->warrantyExpiresAt,
            'warranty_hardware_days' => $this->warrantyHardwareDays ?: $this->warrantyDays,
            'warranty_hardware_expires_at' => $this->warrantyHardwareExpiresAt ?: $this->warrantyExpiresAt,
            'warranty_software_days' => $this->warrantySoftwareDays,
            'warranty_software_expires_at' => $this->warrantySoftwareExpiresAt,
            'serial_number' => $this->serialNumber,
            'is_warranty_active' => $this->isWarrantyActive(),
            'is_hardware_warranty_active' => $this->isHardwareWarrantyActive(),
            'is_software_warranty_active' => $this->isSoftwareWarrantyActive(),
        ];
    }
}
