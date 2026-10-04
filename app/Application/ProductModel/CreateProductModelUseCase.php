<?php

namespace App\Application\ProductModel;

use App\Domain\ProductModel\ProductModel;
use App\Domain\ProductModel\ProductModelRepositoryInterface;

final readonly class CreateProductModelUseCase
{
    public function __construct(private ProductModelRepositoryInterface $models)
    {
    }

    public function execute(array $data): ProductModel
    {
        return $this->models->create($data);
    }
}
