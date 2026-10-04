<?php

namespace App\Application\CashShift;

use App\Domain\CashShift\CashShift;
use App\Domain\CashShift\CashShiftRepositoryInterface;

final readonly class GetCurrentCashShiftUseCase
{
    public function __construct(
        private CashShiftRepositoryInterface $repository
    ) {}

    public function execute(int $userId): ?CashShift
    {
        return $this->repository->findCurrentByUserId($userId);
    }
}
