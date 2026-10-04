<?php

namespace App\Application\PaymentMethod;

use App\Domain\PaymentMethod\PaymentMethodRepositoryInterface;
use InvalidArgumentException;

class TogglePaymentMethodStatusUseCase
{
    public function __construct(
        private PaymentMethodRepositoryInterface $repository
    ) {}

    public function execute(int $id): array
    {
        $method = $this->repository->findById($id);
        if (!$method) {
            throw new InvalidArgumentException("Método de pago con ID {$id} no encontrado.");
        }

        $method->toggleStatus();
        $saved = $this->repository->save($method);

        return $saved->toArray();
    }
}
