<?php

namespace App\Domain\Kardex;

final class KardexMovement
{
    public function __construct(
        public readonly int $id,
        public readonly string $date,
        public readonly string $type,
        public readonly string $reason,
        public readonly ?string $referenceType,
        public readonly ?int $referenceId,
        public readonly string $userName,
        public readonly int $entryQuantity,
        public readonly int $exitQuantity,
        public readonly int $balanceQuantity,
        public readonly float $unitCost,
        public readonly float $debitAmount,
        public readonly float $creditAmount,
        public readonly float $averageUnitCost,
        public readonly float $balanceValue
    ) {
    }

    public function toArray(bool $includeFinancial = true): array
    {
        $data = [
            'id' => $this->id,
            'date' => $this->date,
            'type' => $this->type,
            'reason' => $this->reason,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
            'user_name' => $this->userName,
            'physical' => [
                'entry' => $this->entryQuantity,
                'exit' => $this->exitQuantity,
                'balance' => $this->balanceQuantity,
            ],
        ];

        if ($includeFinancial) {
            $data['financial'] = [
                'unit_cost' => round($this->unitCost, 4),
                'debit' => round($this->debitAmount, 2),
                'credit' => round($this->creditAmount, 2),
                'average_cost' => round($this->averageUnitCost, 4),
                'balance_value' => round($this->balanceValue, 2),
            ];
        }

        return $data;
    }
}
