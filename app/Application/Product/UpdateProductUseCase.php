<?php

namespace App\Application\Product;

use App\Domain\Product\Product;
use App\Domain\Product\ProductRepositoryInterface;

final readonly class UpdateProductUseCase
{
    public function __construct(private ProductRepositoryInterface $products)
    {
    }

    public function execute(int $id, array $data): Product
    {
        return $this->products->update($id, $data);
    }
}
