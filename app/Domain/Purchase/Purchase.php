<?php

namespace App\Domain\Purchase;

final readonly class Purchase
{
    /**
     * @param PurchaseItem[] $items
     */
    public function __construct(
        public int $id,
        public string $purchaseNumber,
        public string $invoiceNumber,
        public int $supplierId,
        public string $supplierName,
        public int $userId,
        public ?string $userName = null,
        public string $purchaseDate = '',
        public string $paymentCondition = 'contado',
        public string $paymentMethod = 'efectivo',
        public string $paymentStatus = 'pagado',
        public ?string $dueDate = null,
        public float $subtotal = 0.0,
        public float $totalAmount = 0.0,
        public string $status = 'received',
        public ?int $paymentMethodId = null,
        public ?string $referenceNumber = null,
        public ?string $paymentMethodName = null,
        public ?string $cancellationReason = null,
        public ?int $cancelledBy = null,
        public ?string $cancelledByName = null,
        public ?string $cancelledAt = null,
        public ?string $notes = null,
        public int $itemsCount = 0,
        public array $items = [],
        public ?string $createdAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'purchase_number' => $this->purchaseNumber,
            'invoice_number' => $this->invoiceNumber,
            'supplier_id' => $this->supplierId,
            'supplier_name' => $this->supplierName,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'purchase_date' => $this->purchaseDate,
            'payment_condition' => $this->paymentCondition,
            'payment_method' => $this->paymentMethod,
            'payment_method_id' => $this->paymentMethodId,
            'reference_number' => $this->referenceNumber,
            'payment_method_name' => $this->paymentMethodName,
            'payment_status' => $this->paymentStatus,
            'due_date' => $this->dueDate,
            'subtotal' => number_format($this->subtotal, 2, '.', ''),
            'total_amount' => number_format($this->totalAmount, 2, '.', ''),
            'status' => $this->status,
            'cancellation_reason' => $this->cancellationReason,
            'cancelled_by' => $this->cancelledBy,
            'cancelled_by_name' => $this->cancelledByName,
            'cancelled_at' => $this->cancelledAt,
            'notes' => $this->notes,
            'items_count' => $this->itemsCount,
            'items' => array_map(fn(PurchaseItem $item) => $item->toArray(), $this->items),
            'created_at' => $this->createdAt,
        ];
    }
}
