<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Product\{
    CreateProductUseCase,
    ListProductsUseCase,
    ToggleProductStatusUseCase,
    UpdateProductUseCase
};
use App\Application\StockMovement\{
    AdjustStockUseCase,
    ListStockMovementsUseCase
};
use App\Infrastructure\Http\Requests\{ProductRequest, StockAdjustmentRequest};
use Illuminate\Http\{JsonResponse, Request};

final class ProductController
{
    public function index(Request $request, ListProductsUseCase $list): JsonResponse
    {
        $user = $request->user('api');
        $isOwner = $user && $user->role === 'dueno';

        $filters = [
            'search' => (string) $request->query('search', ''),
            'category_id' => $request->query('category_id'),
            'status' => $request->query('status'),
            'page' => (int) $request->query('page', 1),
            'per_page' => min((int) $request->query('per_page', 15), 50),
        ];

        return response()->json($list->execute($filters, $isOwner));
    }

    public function store(ProductRequest $request, CreateProductUseCase $create): JsonResponse
    {
        $product = $create->execute($request->validated());

        return response()->json([
            'message' => 'Producto creado exitosamente.',
            'data' => $product->toArray(true),
        ], 201);
    }

    public function update(ProductRequest $request, int $id, UpdateProductUseCase $update): JsonResponse
    {
        $product = $update->execute($id, $request->validated());

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'data' => $product->toArray(true),
        ]);
    }

    public function toggleStatus(int $id, ToggleProductStatusUseCase $toggle): JsonResponse
    {
        $product = $toggle->execute($id);

        return response()->json([
            'message' => 'Estado de producto actualizado correctamente.',
            'data' => $product->toArray(true),
        ]);
    }

    public function adjustStock(StockAdjustmentRequest $request, int $id, AdjustStockUseCase $adjust): JsonResponse
    {
        $userId = $request->user('api')->id;
        $result = $adjust->execute($id, $userId, $request->validated());

        return response()->json([
            'message' => 'Stock ajustado correctamente.',
            'data' => $result,
        ]);
    }

    public function stockHistory(Request $request, int $id, ListStockMovementsUseCase $history): JsonResponse
    {
        $filters = [
            'type' => $request->query('type'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'page' => (int) $request->query('page', 1),
            'per_page' => min((int) $request->query('per_page', 20), 50),
        ];

        return response()->json($history->execute($id, $filters));
    }
}
