<?php

namespace App\Application\CashShift;

use App\Domain\CashShift\CashShift;
use App\Domain\CashShift\CashShiftRepositoryInterface;
use DomainException;

final readonly class OpenCashShiftUseCase
{
    public function __construct(
        private CashShiftRepositoryInterface $repository
    ) {}

    public function execute(int $userId, float $openingAmount, ?string $notes = null): CashShift
    {
        if ($openingAmount < 0) {
            throw new DomainException('El monto inicial no puede ser negativo.');
        }

        $current = $this->repository->findCurrentByUserId($userId);
        if ($current !== null) {
            throw new DomainException('Ya tiene un turno de caja abierto. Debe cerrarlo antes de iniciar uno nuevo.');
        }

        return $this->repository->openShift($userId, $openingAmount, $notes);
    }
}
