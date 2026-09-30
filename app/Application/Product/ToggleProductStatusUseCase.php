<?php

namespace App\Application\Product;

use App\Domain\Product\Product;
use App\Domain\Product\ProductRepositoryInterface;

final readonly class ToggleProductStatusUseCase
{
    public function __construct(private ProductRepositoryInterface $products)
    {
    }

    public function execute(int $id): Product
    {
        return $this->products->toggle($id);
    }
}
