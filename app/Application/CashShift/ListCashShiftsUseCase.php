<?php

namespace App\Application\CashShift;

use App\Domain\CashShift\CashShiftRepositoryInterface;

final readonly class ListCashShiftsUseCase
{
    public function __construct(
        private CashShiftRepositoryInterface $repository
    ) {}

    public function execute(int $page = 1, int $perPage = 15, ?int $userId = null): array
    {
        return $this->repository->paginate($page, $perPage, $userId);
    }
}
