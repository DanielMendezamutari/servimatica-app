<?php

namespace App\Domain\CashShift;

final readonly class CashShift
{
    public function __construct(
        public int $id,
        public int $userId,
        public float $openingAmount,
        public ?float $closingAmount = null,
        public ?float $expectedAmount = null,
        public ?float $difference = null,
        public float $totalCashSales = 0.00,
        public float $totalQrSales = 0.00,
        public string $status = 'open',
        public ?string $openedAt = null,
        public ?string $closedAt = null,
        public ?string $notes = null,
        public ?string $userName = null,
        public array $digitalTotalsByMethod = []
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'opening_amount' => number_format($this->openingAmount, 2, '.', ''),
            'closing_amount' => $this->closingAmount !== null ? number_format($this->closingAmount, 2, '.', '') : null,
            'expected_amount' => $this->expectedAmount !== null ? number_format($this->expectedAmount, 2, '.', '') : null,
            'difference' => $this->difference !== null ? number_format($this->difference, 2, '.', '') : null,
            'total_cash_sales' => number_format($this->totalCashSales, 2, '.', ''),
            'total_qr_sales' => number_format($this->totalQrSales, 2, '.', ''),
            'digital_totals_by_method' => $this->digitalTotalsByMethod,
            'status' => $this->status,
            'opened_at' => $this->openedAt,
            'closed_at' => $this->closedAt,
            'notes' => $this->notes,
        ];
    }
}
