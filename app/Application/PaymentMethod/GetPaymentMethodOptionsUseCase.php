<?php

namespace App\Application\PaymentMethod;

use App\Domain\PaymentMethod\PaymentMethodRepositoryInterface;

class GetPaymentMethodOptionsUseCase
{
    public function __construct(
        private PaymentMethodRepositoryInterface $repository
    ) {}

    public function execute(?string $context = null): array
    {
        $methods = $this->repository->findActive($context);

        return array_map(fn($method) => $method->toArray(), $methods);
    }
}
