<?php

namespace App\Domain\Sale;

final readonly class Sale
{
    /**
     * @param SaleItem[] $items
     */
    public function __construct(
        public int $id,
        public string $invoiceNumber,
        public ?int $quoteId,
        public int $sellerId,
        public ?int $clientId,
        public string $clientName,
        public ?string $clientNitCi,
        public int $cashShiftId,
        public string $paymentMethod,
        public float $subtotal,
        public float $discountAmount,
        public float $totalAmount,
        public ?float $cashTendered,
        public ?float $changeDue,
        public float $commissionRate,
        public float $commissionAmount,
        public string $status,
        public ?int $paymentMethodId = null,
        public ?string $referenceNumber = null,
        public ?string $paymentMethodName = null,
        public ?string $cancellationReason = null,
        public ?int $cancelledBy = null,
        public ?string $cancelledAt = null,
        public ?string $sellerName = null,
        public ?string $cancelledByName = null,
        public ?string $createdAt = null,
        public array $items = []
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoiceNumber,
            'quote_id' => $this->quoteId,
            'seller_id' => $this->sellerId,
            'seller_name' => $this->sellerName,
            'client_id' => $this->clientId,
            'client_name' => $this->clientName,
            'client_nit_ci' => $this->clientNitCi,
            'cash_shift_id' => $this->cashShiftId,
            'payment_method' => $this->paymentMethod,
            'payment_method_id' => $this->paymentMethodId,
            'reference_number' => $this->referenceNumber,
            'payment_method_name' => $this->paymentMethodName,
            'subtotal' => number_format($this->subtotal, 2, '.', ''),
            'discount_amount' => number_format($this->discountAmount, 2, '.', ''),
            'total_amount' => number_format($this->totalAmount, 2, '.', ''),
            'cash_tendered' => $this->cashTendered !== null ? number_format($this->cashTendered, 2, '.', '') : null,
            'change_due' => $this->changeDue !== null ? number_format($this->changeDue, 2, '.', '') : null,
            'commission_rate' => number_format($this->commissionRate, 2, '.', ''),
            'commission_amount' => number_format($this->commissionAmount, 2, '.', ''),
            'status' => $this->status,
            'cancellation_reason' => $this->cancellationReason,
            'cancelled_by' => $this->cancelledBy,
            'cancelled_by_name' => $this->cancelledByName,
            'cancelled_at' => $this->cancelledAt,
            'created_at' => $this->createdAt,
            'items' => array_map(fn($it) => $it instanceof SaleItem ? $it->toArray() : $it, $this->items),
        ];
    }
}
