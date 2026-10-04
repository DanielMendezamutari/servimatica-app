<?php

namespace App\Application\Brand;

use App\Domain\Brand\Brand;
use App\Domain\Brand\BrandRepositoryInterface;

final readonly class ToggleBrandStatusUseCase
{
    public function __construct(private BrandRepositoryInterface $brands)
    {
    }

    public function execute(int $id): Brand
    {
        return $this->brands->toggle($id);
    }
}
