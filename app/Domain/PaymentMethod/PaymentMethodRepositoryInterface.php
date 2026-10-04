<?php

namespace App\Domain\PaymentMethod;

interface PaymentMethodRepositoryInterface
{
    /**
     * @return PaymentMethod[]
     */
    public function findAll(): array;

    /**
     * @param string|null $scope 'sales', 'purchases' or null for all
     * @return PaymentMethod[]
     */
    public function findActive(?string $scope = null): array;

    public function findById(int $id): ?PaymentMethod;

    public function save(PaymentMethod $method): PaymentMethod;

    public function delete(int $id): bool;
}
