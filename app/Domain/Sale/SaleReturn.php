<?php

namespace App\Domain\Sale;

final readonly class SaleReturn
{
    /**
     * @param list<SaleReturnItem> $items
     */
    public function __construct(
        public ?int $id,
        public string $returnNumber,
        public int $saleId,
        public ?int $clientId,
        public string $clientName,
        public int $userId,
        public ?int $cashShiftId,
        public string $resolution, // 'cambio_fisico', 'reembolso_efectivo', 'nota_credito'
        public float $totalRefundAmount,
        public string $reason,
        public string $status, // 'completed', 'cancelled'
        public ?string $userName = null,
        public ?string $invoiceNumber = null,
        public ?string $createdAt = null,
        public array $items = []
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'return_number' => $this->returnNumber,
            'sale_id' => $this->saleId,
            'invoice_number' => $this->invoiceNumber,
            'client_id' => $this->clientId,
            'client_name' => $this->clientName,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'cash_shift_id' => $this->cashShiftId,
            'resolution' => $this->resolution,
            'total_refund_amount' => number_format($this->totalRefundAmount, 2, '.', ''),
            'reason' => $this->reason,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'items' => array_map(fn(SaleReturnItem $it) => $it->toArray(), $this->items),
        ];
    }
}
