<?php

namespace App\Application\Supplier;

use App\Domain\Supplier\Supplier;
use App\Domain\Supplier\SupplierRepositoryInterface;

final readonly class UpdateSupplierUseCase
{
    public function __construct(
        private SupplierRepositoryInterface $repository
    ) {}

    public function execute(int $id, array $data): Supplier
    {
        return $this->repository->update($id, $data);
    }
}
