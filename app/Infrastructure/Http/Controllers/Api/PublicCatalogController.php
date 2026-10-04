<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\PublicCatalog\ListPublicCatalogUseCase;
use App\Infrastructure\Persistence\Eloquent\BrandModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\CompanySettingModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PublicCatalogController
{
    public function index(Request $request, ListPublicCatalogUseCase $listUseCase): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'category_id' => $request->query('category_id'),
            'brand_id' => $request->query('brand_id'),
        ];

        $catalog = $listUseCase->execute($filters);

        // Opciones de categorías y marcas para los filtros rápidos en Vue
        $categories = CategoryModel::where('status', 'active')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = BrandModel::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $company = CompanySettingModel::first();

        return response()->json([
            'data' => $catalog,
            'categories' => $categories,
            'brands' => $brands,
            'company' => [
                'trade_name' => $company?->trade_name ?: 'SERVIMÁTICA',
                'slogan' => $company?->slogan ?: 'Tecnología y Soluciones Digitales',
                'city' => $company?->city ?: 'Trinidad',
                'address' => $company?->address ?: '',
                'mobile' => $company?->mobile ?: '',
                'logo_url' => $company?->logo_url,
            ],
        ]);
    }
}
