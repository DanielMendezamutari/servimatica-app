<?php

namespace App\Application\Supplier;

use App\Domain\Supplier\SupplierRepositoryInterface;

final readonly class ListSuppliersUseCase
{
    public function __construct(
        private SupplierRepositoryInterface $repository
    ) {}

    public function execute(int $page = 1, int $perPage = 15, ?string $search = null, ?string $status = null): array
    {
        return $this->repository->paginate($page, $perPage, $search, $status);
    }

    public function options(): array
    {
        return $this->repository->options();
    }
}
