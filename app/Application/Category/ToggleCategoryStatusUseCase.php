<?php

namespace App\Application\Category;

use App\Domain\Category\Category;
use App\Domain\Category\CategoryRepositoryInterface;

final readonly class ToggleCategoryStatusUseCase
{
    public function __construct(private CategoryRepositoryInterface $categories)
    {
    }

    public function execute(int $id): Category
    {
        return $this->categories->toggle($id);
    }
}
