<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Brand\{
    CreateBrandUseCase,
    ListBrandsUseCase,
    ToggleBrandStatusUseCase,
    UpdateBrandUseCase
};
use App\Application\ProductModel\ListBrandModelsUseCase;
use App\Domain\Brand\BrandRepositoryInterface;
use Illuminate\Http\{JsonResponse, Request};

final class BrandController
{
    public function index(Request $request, ListBrandsUseCase $list): JsonResponse
    {
        $search = (string) $request->query('search', '');
        $status = $request->query('status');
        $activeOnly = $status === 'active' ? true : ($status === 'inactive' ? false : null);

        // Sellers only see active brands by default
        if ($request->user()?->role === 'vendedor') {
            $activeOnly = true;
        }

        return response()->json(['data' => $list->execute($search, $activeOnly)]);
    }

    public function options(BrandRepositoryInterface $brands): JsonResponse
    {
        return response()->json(['data' => $brands->options()]);
    }

    public function models(int $id, Request $request, ListBrandModelsUseCase $listModels): JsonResponse
    {
        $status = $request->query('status');
        $activeOnly = $status === 'active' ? true : ($status === 'inactive' ? false : null);

        if ($request->user()?->role === 'vendedor') {
            $activeOnly = true;
        }

        return response()->json(['data' => $listModels->execute($id, $activeOnly)]);
    }

    public function store(Request $request, CreateBrandUseCase $create): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:brands,name'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'El nombre de la marca es obligatorio.',
            'name.unique' => 'Ya existe una marca con ese nombre.',
            'name.max' => 'El nombre no debe superar 100 caracteres.',
        ]);

        $brand = $create->execute($validated);

        return response()->json([
            'message' => 'Marca creada exitosamente.',
            'data' => $brand->toArray(),
        ], 201);
    }

    public function update(int $id, Request $request, UpdateBrandUseCase $update): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', "unique:brands,name,{$id}"],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'El nombre de la marca es obligatorio.',
            'name.unique' => 'Ya existe una marca con ese nombre.',
            'name.max' => 'El nombre no debe superar 100 caracteres.',
        ]);

        $brand = $update->execute($id, $validated);

        return response()->json([
            'message' => 'Marca actualizada correctamente.',
            'data' => $brand->toArray(),
        ]);
    }

    public function toggleStatus(int $id, ToggleBrandStatusUseCase $toggle): JsonResponse
    {
        $brand = $toggle->execute($id);

        return response()->json([
            'message' => 'Estado de marca actualizado correctamente.',
            'data' => $brand->toArray(),
        ]);
    }
}
