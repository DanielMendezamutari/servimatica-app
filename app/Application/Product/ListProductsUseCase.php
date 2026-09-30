<?php

namespace App\Application\Product;

use App\Domain\Product\ProductRepositoryInterface;

final readonly class ListProductsUseCase
{
    public function __construct(private ProductRepositoryInterface $products)
    {
    }

    public function execute(array $filters, bool $owner): array
    {
        return $this->products->paginate($filters, $owner);
    }
}
