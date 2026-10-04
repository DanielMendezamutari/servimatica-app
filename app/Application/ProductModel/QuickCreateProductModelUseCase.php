<?php

namespace App\Application\ProductModel;

use App\Domain\ProductModel\ProductModel;
use App\Domain\ProductModel\ProductModelRepositoryInterface;

final readonly class QuickCreateProductModelUseCase
{
    public function __construct(private ProductModelRepositoryInterface $models)
    {
    }

    public function execute(int $brandId, string $name): ProductModel
    {
        return $this->models->findOrCreateByName($brandId, $name);
    }
}
