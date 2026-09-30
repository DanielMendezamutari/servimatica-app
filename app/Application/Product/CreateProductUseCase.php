<?php

namespace App\Application\Product;

use App\Domain\Product\Product;
use App\Domain\Product\ProductRepositoryInterface;

final readonly class CreateProductUseCase
{
    public function __construct(private ProductRepositoryInterface $products)
    {
    }

    public function execute(array $data): Product
    {
        return $this->products->create($data);
    }
}
