<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Category\{
    CreateCategoryUseCase,
    DeleteCategoryUseCase,
    ListCategoriesUseCase,
    ToggleCategoryStatusUseCase,
    UpdateCategoryUseCase
};
use App\Infrastructure\Http\Requests\CategoryRequest;
use Illuminate\Http\{JsonResponse, Request};

final class CategoryController
{
    public function index(Request $request, ListCategoriesUseCase $list): JsonResponse
    {
        return response()->json(['data' => $list->execute((string) $request->query('search', ''))]);
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
