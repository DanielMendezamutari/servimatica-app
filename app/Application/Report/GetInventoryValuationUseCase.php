<?php

namespace App\Application\Report;

use App\Infrastructure\Persistence\Eloquent\ProductModel;

final class GetInventoryValuationUseCase
{
    public function execute(array $filters = []): array
    {
        $query = ProductModel::with(['category', 'brand'])
            ->where('status', 'active');

        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%")
                    ->orWhere('barcode', 'like', "%{$s}%");
            });
        }

        $products = $query->orderBy('name', 'asc')->get();

        $totalSellableUnits = 0;
        $totalDefectiveUnits = 0;
        $sellableValuation = 0.0;
        $defectiveValuation = 0.0;
        $productsList = [];

        foreach ($products as $p) {
            $cost = (float) ($p->cost_price ?? 0.0);
            $stock = (int) ($p->stock ?? 0);
            $defective = (int) ($p->defective_stock ?? 0);

            $sellableVal = round($stock * $cost, 2);
            $defectiveVal = round($defective * $cost, 2);
            $totalVal = round(($stock + $defective) * $cost, 2);

            $totalSellableUnits += $stock;
            $totalDefectiveUnits += $defective;
            $sellableValuation += $sellableVal;
            $defectiveValuation += $defectiveVal;

            $productsList[] = [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'category_name' => $p->category->name ?? 'General',
                'brand_name' => $p->brand->name ?? null,
                'stock' => $stock,
                'defective_stock' => $defective,
                'average_cost' => $cost,
                'sale_price' => (float) ($p->sale_price ?? 0.0),
                'sellable_value_bs' => $sellableVal,
                'defective_value_bs' => $defectiveVal,
                'total_value_bs' => $totalVal,
            ];
        }

        return [
            'summary' => [
                'total_products_count' => count($productsList),
                'total_sellable_units' => $totalSellableUnits,
                'total_defective_units' => $totalDefectiveUnits,
                'sellable_valuation_bs' => round($sellableValuation, 2),
                'defective_valuation_bs' => round($defectiveValuation, 2),
                'total_inventory_valuation_bs' => round($sellableValuation + $defectiveValuation, 2),
            ],
            'products' => $productsList,
        ];
    }
}
