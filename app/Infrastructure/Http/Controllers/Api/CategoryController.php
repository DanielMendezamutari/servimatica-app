<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Category\{
    CreateCategoryUseCase,
    DeleteCategoryUseCase,
    ListCategoriesUseCase,
    ToggleCategoryStatusUseCase,
    UpdateCategoryUseCase
};
use App\Domain\Category\CategoryRepositoryInterface;
use App\Infrastructure\Http\Requests\CategoryRequest;
use Illuminate\Http\{JsonResponse, Request};

final class CategoryController
{
    public function index(Request $request, ListCategoriesUseCase $list): JsonResponse
    {
        $search = (string) $request->query('search', '');
        $tree = $request->boolean('tree');

        return response()->json(['data' => $list->execute($search, $tree)]);
    }

    public function options(Request $request, CategoryRepositoryInterface $categories): JsonResponse
    {
        $owner = $request->user()?->role === 'administrador';
        return response()->json(['data' => $categories->options($owner)]);
    }

    public function subfamilies(int $id, CategoryRepositoryInterface $categories): JsonResponse
    {
        return response()->json(['data' => $categories->subfamilies($id)]);
    }

    public function store(CategoryRequest $request, CreateCategoryUseCase $create): JsonResponse
    {
        return response()->json([
            'message' => 'Categoría creada exitosamente.',
            'data' => $create->execute($request->validated())->toArray(),
        ], 201);
    }

    public function update(CategoryRequest $request, int $id, UpdateCategoryUseCase $update): JsonResponse
    {
        return response()->json([
            'message' => 'Categoría actualizada correctamente.',
            'data' => $update->execute($id, $request->validated())->toArray(),
        ]);
    }

    public function toggleStatus(int $id, ToggleCategoryStatusUseCase $toggle): JsonResponse
    {
        $category = $toggle->execute($id);
        return response()->json([
            'message' => 'Estado de categoría actualizado correctamente.',
            'data' => $category->toArray(),
        ]);
    }

    public function destroy(int $id, DeleteCategoryUseCase $delete): JsonResponse
    {
        $delete->execute($id);
        return response()->json([
            'message' => 'Categoría eliminada correctamente.',
        ]);
    }
}
