<?php

namespace App\Application\Category;

use App\Domain\Category\Category;
use App\Domain\Category\CategoryRepositoryInterface;

final readonly class CreateCategoryUseCase
{
    public function __construct(private CategoryRepositoryInterface $categories)
    {
    }

    public function execute(array $data): Category
    {
        return $this->categories->create($data);
    }
}
