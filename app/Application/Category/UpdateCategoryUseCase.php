<?php

namespace App\Application\Category;

use App\Domain\Category\Category;
use App\Domain\Category\CategoryRepositoryInterface;

final readonly class UpdateCategoryUseCase
{
    public function __construct(private CategoryRepositoryInterface $categories)
    {
    }

    public function execute(int $id, array $data): Category
    {
        return $this->categories->update($id, $data);
    }
}
