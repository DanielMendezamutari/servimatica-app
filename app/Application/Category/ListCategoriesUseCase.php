<?php

namespace App\Application\Category;

use App\Domain\Category\CategoryRepositoryInterface;

final readonly class ListCategoriesUseCase
{
    public function __construct(private CategoryRepositoryInterface $categories)
    {
    }

    public function execute(string $search = ''): array
    {
        return array_map(fn ($category) => $category->toArray(), $this->categories->all($search));
    }
}
