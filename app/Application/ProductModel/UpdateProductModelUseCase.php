<?php

namespace App\Application\ProductModel;

use App\Domain\ProductModel\ProductModel;
use App\Domain\ProductModel\ProductModelRepositoryInterface;

final readonly class UpdateProductModelUseCase
{
    public function __construct(private ProductModelRepositoryInterface $models)
    {
    }

    public function execute(int $id, array $data): ProductModel
    {
        return $this->models->update($id, $data);
    }
}
