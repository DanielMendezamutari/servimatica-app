<?php

namespace App\Application\Supplier;

use App\Domain\Supplier\Supplier;
use App\Domain\Supplier\SupplierRepositoryInterface;

final readonly class CreateSupplierUseCase
{
    public function __construct(
        private SupplierRepositoryInterface $repository
    ) {}

    public function execute(array $data): Supplier
    {
        return $this->repository->save($data);
    }
}
