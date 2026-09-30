<?php

namespace App\Application\Category;

use App\Domain\Category\CategoryRepositoryInterface;

final readonly class DeleteCategoryUseCase
{
    public function __construct(private CategoryRepositoryInterface $categories)
    {
    }

    public function execute(int $id): void
    {
        $this->categories->delete($id);
    }
}
