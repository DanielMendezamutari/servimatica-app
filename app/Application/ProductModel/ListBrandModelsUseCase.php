<?php

namespace App\Application\ProductModel;

use App\Domain\ProductModel\ProductModelRepositoryInterface;

final readonly class ListBrandModelsUseCase
{
    public function __construct(private ProductModelRepositoryInterface $models)
    {
    }

    public function execute(int $brandId, ?bool $activeOnly = null): array
    {
        return array_map(fn($m) => $m->toArray(), $this->models->getByBrand($brandId, $activeOnly));
    }
}
