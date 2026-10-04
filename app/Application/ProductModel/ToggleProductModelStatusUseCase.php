<?php

namespace App\Application\ProductModel;

use App\Domain\ProductModel\ProductModel;
use App\Domain\ProductModel\ProductModelRepositoryInterface;

final readonly class ToggleProductModelStatusUseCase
{
    public function __construct(private ProductModelRepositoryInterface $models)
    {
    }

    public function execute(int $id): ProductModel
    {
        return $this->models->toggle($id);
    }
}
