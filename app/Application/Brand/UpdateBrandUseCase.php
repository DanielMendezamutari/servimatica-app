<?php

namespace App\Application\Brand;

use App\Domain\Brand\Brand;
use App\Domain\Brand\BrandRepositoryInterface;

final readonly class UpdateBrandUseCase
{
    public function __construct(private BrandRepositoryInterface $brands)
    {
    }

    public function execute(int $id, array $data): Brand
    {
        return $this->brands->update($id, $data);
    }
}
