<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Product\{
    CreateProductUseCase,
    ExportProductsToExcelUseCase,
    ListProductsUseCase,
    ToggleProductStatusUseCase,
    UpdateProductUseCase,
    UploadProductImagesService
};
use App\Application\StockMovement\{
    AdjustStockUseCase,
    ListStockMovementsUseCase
};
use App\Infrastructure\Http\Requests\{ProductRequest, StockAdjustmentRequest};
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Http\{JsonResponse, Request};
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProductController
{
    public function index(Request $request, ListProductsUseCase $list): JsonResponse
    {
        $user = $request->user('api');
        $isOwner = $user && in_array($user->role, ['dueno', 'administrador'], true);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'category_id' => $request->query('category_id'),
            'subfamily_id' => $request->query('subfamily_id'),
            'brand_id' => $request->query('brand_id'),
            'condition' => $request->query('condition'),
            'status' => $request->query('status'),
            'page' => (int) $request->query('page', 1),
            'per_page' => min((int) $request->query('per_page', 15), 50),
        ];

        return response()->json($list->execute($filters, $isOwner));
    }

    public function exportExcel(Request $request, ExportProductsToExcelUseCase $export): StreamedResponse
    {
        $user = $request->user('api');
        $isOwner = $user && in_array($user->role, ['dueno', 'administrador'], true);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'category_id' => $request->query('category_id'),
            'subfamily_id' => $request->query('subfamily_id'),
            'brand_id' => $request->query('brand_id'),
            'condition' => $request->query('condition'),
            'status' => $request->query('status'),
        ];

        return $export->execute($filters, $isOwner);
    }

    public function store(ProductRequest $request, CreateProductUseCase $create, UploadProductImagesService $uploadService): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $uploadService->storeCover($request->file('image'));
        }

        if ($request->hasFile('gallery')) {
            $data['gallery_images'] = $uploadService->storeGallery($request->file('gallery'));
        }

        $product = $create->execute($data);

        return response()->json([
            'message' => 'Producto creado exitosamente.',
            'data' => $product->toArray(true),
        ], 201);
    }

    public function update(ProductRequest $request, int $id, UpdateProductUseCase $update, UploadProductImagesService $uploadService): JsonResponse
    {
        $data = $request->validated();
        $existing = ProductModel::find($id);

        if ($request->hasFile('image')) {
            if ($existing && $existing->image_path) {
                $uploadService->deleteFiles($existing->image_path);
            }
            $data['image_path'] = $uploadService->storeCover($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            if ($existing && $existing->image_path) {
                $uploadService->deleteFiles($existing->image_path);
            }
            $data['image_path'] = null;
        }

        if ($request->hasFile('gallery')) {
            $newGallery = $uploadService->storeGallery($request->file('gallery'));
            // If existing had gallery and remove_gallery is false, append or replace
            if ($request->boolean('replace_gallery') && $existing && $existing->gallery_images) {
                $uploadService->deleteFiles($existing->gallery_images);
                $data['gallery_images'] = $newGallery;
            } else {
                $currentGallery = is_array($existing?->gallery_images) ? $existing->gallery_images : [];
                $data['gallery_images'] = array_values(array_merge($currentGallery, $newGallery));
            }
        } elseif ($request->boolean('remove_gallery')) {
            if ($existing && $existing->gallery_images) {
                $uploadService->deleteFiles($existing->gallery_images);
            }
            $data['gallery_images'] = null;
        }

        $product = $update->execute($id, $data);

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
