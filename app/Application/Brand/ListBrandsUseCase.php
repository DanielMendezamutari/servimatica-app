<?php

namespace App\Application\Brand;

use App\Domain\Brand\BrandRepositoryInterface;

final readonly class ListBrandsUseCase
{
    public function __construct(private BrandRepositoryInterface $brands)
    {
    }

    public function execute(string $search = '', ?bool $activeOnly = null): array
    {
        return array_map(fn($b) => $b->toArray(), $this->brands->all($search, $activeOnly));
    }
}
