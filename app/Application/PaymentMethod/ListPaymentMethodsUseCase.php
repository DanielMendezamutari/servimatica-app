<?php

namespace App\Application\PaymentMethod;

use App\Domain\PaymentMethod\PaymentMethodRepositoryInterface;

class ListPaymentMethodsUseCase
{
    public function __construct(
        private PaymentMethodRepositoryInterface $repository
    ) {}

    public function execute(): array
    {
        $methods = $this->repository->findAll();

        return array_map(fn($method) => $method->toArray(), $methods);
    }
}
