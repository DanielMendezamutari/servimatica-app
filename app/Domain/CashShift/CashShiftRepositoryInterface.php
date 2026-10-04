<?php

namespace App\Domain\CashShift;

interface CashShiftRepositoryInterface
{
    public function findById(int $id): ?CashShift;
    public function findCurrentByUserId(int $userId): ?CashShift;
    public function openShift(int $userId, float $openingAmount, ?string $notes = null): CashShift;
    public function recordSale(int $shiftId, float $amount, string $paymentMethod): void;
    public function closeShift(int $shiftId, float $closingAmount, ?string $notes = null): CashShift;
    public function paginate(int $page = 1, int $perPage = 15, ?int $userId = null): array;
}
