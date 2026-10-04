<?php

namespace App\Application\Supplier;

use App\Domain\Supplier\Supplier;
use App\Domain\Supplier\SupplierRepositoryInterface;

final readonly class ToggleSupplierStatusUseCase
{
    public function __construct(
        private SupplierRepositoryInterface $repository
    ) {}

    public function execute(int $id): Supplier
    {
        return $this->repository->toggleStatus($id);
    }
}
