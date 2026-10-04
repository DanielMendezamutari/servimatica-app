<?php

namespace App\Application\Brand;

use App\Domain\Brand\Brand;
use App\Domain\Brand\BrandRepositoryInterface;

final readonly class CreateBrandUseCase
{
    public function __construct(private BrandRepositoryInterface $brands)
    {
    }

    public function execute(array $data): Brand
    {
        return $this->brands->create($data);
    }
}
