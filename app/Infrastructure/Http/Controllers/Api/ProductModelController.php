<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\ProductModel\{
    CreateProductModelUseCase,
    QuickCreateProductModelUseCase,
    ToggleProductModelStatusUseCase,
    UpdateProductModelUseCase
};
use Illuminate\Http\{JsonResponse, Request};

final class ProductModelController
{
    public function quickCreate(Request $request, QuickCreateProductModelUseCase $quickCreate): JsonResponse
    {
        $validated = $request->validate([
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:150'],
        ], [
            'brand_id.required' => 'Debe seleccionar una marca para vincular el modelo.',
            'name.required' => 'El nombre del modelo es obligatorio.',
        ]);

        $model = $quickCreate->execute((int)$validated['brand_id'], $validated['name']);

        return response()->json([
            'message' => 'Modelo vinculado exitosamente.',
            'data' => $model->toArray(),
        ]);
    }

    public function store(Request $request, CreateProductModelUseCase $create): JsonResponse
    {
        $validated = $request->validate([
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $model = $create->execute($validated);

        return response()->json([
            'message' => 'Modelo creado exitosamente.',
            'data' => $model->toArray(),
        ], 201);
    }

    public function update(int $id, Request $request, UpdateProductModelUseCase $update): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $model = $update->execute($id, $validated);

        return response()->json([
            'message' => 'Modelo actualizado correctamente.',
            'data' => $model->toArray(),
        ]);
    }

    public function toggleStatus(int $id, ToggleProductModelStatusUseCase $toggle): JsonResponse
    {
        $model = $toggle->execute($id);

        return response()->json([
            'message' => 'Estado del modelo actualizado correctamente.',
            'data' => $model->toArray(),
        ]);
    }
}
