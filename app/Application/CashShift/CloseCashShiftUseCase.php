<?php

namespace App\Application\CashShift;

use App\Domain\CashShift\CashShift;
use App\Domain\CashShift\CashShiftRepositoryInterface;
use DomainException;

final readonly class CloseCashShiftUseCase
{
    public function __construct(
        private CashShiftRepositoryInterface $repository
    ) {}

    public function execute(int $userId, float $closingAmount, ?string $notes = null): CashShift
    {
        if ($closingAmount < 0) {
            throw new DomainException('El monto de cierre físico no puede ser negativo.');
        }

        $current = $this->repository->findCurrentByUserId($userId);
        if ($current === null) {
            throw new DomainException('No tiene un turno de caja abierto para realizar el arqueo de cierre.');
        }

        return $this->repository->closeShift($current->id, $closingAmount, $notes);
    }
}
