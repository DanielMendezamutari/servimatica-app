<?php

namespace App\Application\Report;

use App\Infrastructure\Persistence\Eloquent\SaleModel;
use Carbon\CarbonImmutable;

final class GetProfitabilityReportUseCase
{
    public function execute(array $filters = []): array
    {
        $timezone = 'America/La_Paz';
        $now = CarbonImmutable::now($timezone);

        $fromInput = !empty($filters['from']) ? $filters['from'] : $now->startOfMonth()->format('Y-m-d');
        $toInput = !empty($filters['to']) ? $filters['to'] : $now->format('Y-m-d');

        $fromUtc = CarbonImmutable::parse($fromInput, $timezone)->startOfDay()->utc();
        $toUtc = CarbonImmutable::parse($toInput, $timezone)->endOfDay()->utc();

        $query = SaleModel::with(['items.product.category'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$fromUtc, $toUtc])
            ->orderBy('created_at', 'asc');

        $sales = $query->get();

        $totalSales = 0.0;
        $totalCogs = 0.0;
        $totalItemsSold = 0;
        $transactionsCount = $sales->count();

        $timelineData = [];
        $productsMap = [];
        $categoriesMap = [];

        foreach ($sales as $sale) {
            $saleTotal = (float) $sale->total_amount;
            $totalSales += $saleTotal;

            $dateKey = CarbonImmutable::parse($sale->created_at)->setTimezone($timezone)->format('Y-m-d');
            if (!isset($timelineData[$dateKey])) {
                $timelineData[$dateKey] = [
                    'date' => $dateKey,
                    'sales' => 0.0,
                    'cost' => 0.0,
                    'profit' => 0.0,
                    'transactions' => 0,
                    'items_count' => 0,
                ];
            }
            $timelineData[$dateKey]['sales'] += $saleTotal;
            $timelineData[$dateKey]['transactions'] += 1;

            foreach ($sale->items as $item) {
                $qty = (int) $item->quantity;
                $unitCost = (float) $item->unit_cost;
                $unitPrice = (float) $item->unit_price;
                $itemRevenue = round($qty * $unitPrice, 2);
                $itemCost = round($qty * $unitCost, 2);

                $totalCogs += $itemCost;
                $totalItemsSold += $qty;

                $timelineData[$dateKey]['cost'] += $itemCost;
                $timelineData[$dateKey]['items_count'] += $qty;

                // Agrupación por Producto
                $prodId = $item->product_id;
                $categoryName = $item->product?->category?->name ?? 'General';

                if (!isset($productsMap[$prodId])) {
                    $productsMap[$prodId] = [
                        'id' => $prodId,
                        'name' => $item->product_name,
                        'sku' => $item->product_sku,
                        'category_name' => $categoryName,
                        'units_sold' => 0,
                        'total_revenue' => 0.0,
                        'total_cost' => 0.0,
                        'profit' => 0.0,
                    ];
                }
                $productsMap[$prodId]['units_sold'] += $qty;
                $productsMap[$prodId]['total_revenue'] += $itemRevenue;
                $productsMap[$prodId]['total_cost'] += $itemCost;

                // Agrupación por Categoría
                if (!isset($categoriesMap[$categoryName])) {
                    $categoriesMap[$categoryName] = [
                        'category_name' => $categoryName,
                        'units_sold' => 0,
                        'total_revenue' => 0.0,
                        'total_cost' => 0.0,
                        'profit' => 0.0,
                    ];
                }
                $categoriesMap[$categoryName]['units_sold'] += $qty;
                $categoriesMap[$categoryName]['total_revenue'] += $itemRevenue;
                $categoriesMap[$categoryName]['total_cost'] += $itemCost;
            }
        }

        // Formatear timeline
        $timeline = [];
        foreach ($timelineData as $entry) {
            $profit = round($entry['sales'] - $entry['cost'], 2);
            $margin = $entry['sales'] > 0 ? round(($profit / $entry['sales']) * 100, 2) : 0.0;
            $timeline[] = [
                'date' => $entry['date'],
                'sales' => round($entry['sales'], 2),
                'cost' => round($entry['cost'], 2),
                'profit' => $profit,
                'margin_percentage' => $margin,
                'transactions' => $entry['transactions'],
                'items_count' => $entry['items_count'],
            ];
        }

        // Formatear y ordenar productos por utilidad DESC
        $topProducts = array_values(array_map(function ($p) {
            $profit = round($p['total_revenue'] - $p['total_cost'], 2);
            $margin = $p['total_revenue'] > 0 ? round(($profit / $p['total_revenue']) * 100, 2) : 0.0;
            return [
                'id' => $p['id'],
                'name' => $p['name'],
                'sku' => $p['sku'],
                'category_name' => $p['category_name'],
                'units_sold' => $p['units_sold'],
                'total_revenue' => round($p['total_revenue'], 2),
                'total_cost' => round($p['total_cost'], 2),
                'profit' => $profit,
                'margin_percentage' => $margin,
            ];
        }, $productsMap));

        usort($topProducts, fn ($a, $b) => $b['profit'] <=> $a['profit']);

        // Formatear y ordenar categorías por utilidad DESC
        $topCategories = array_values(array_map(function ($c) {
            $profit = round($c['total_revenue'] - $c['total_cost'], 2);
            $margin = $c['total_revenue'] > 0 ? round(($profit / $c['total_revenue']) * 100, 2) : 0.0;
            return [
                'category_name' => $c['category_name'],
                'units_sold' => $c['units_sold'],
                'total_revenue' => round($c['total_revenue'], 2),
                'total_cost' => round($c['total_cost'], 2),
                'profit' => $profit,
                'margin_percentage' => $margin,
            ];
        }, $categoriesMap));

        usort($topCategories, fn ($a, $b) => $b['profit'] <=> $a['profit']);

        $grossProfit = round($totalSales - $totalCogs, 2);
        $profitMargin = $totalSales > 0 ? round(($grossProfit / $totalSales) * 100, 2) : 0.0;

        return [
            'period' => [
                'from' => $fromInput,
                'to' => $toInput,
            ],
            'kpis' => [
                'total_sales' => round($totalSales, 2),
                'total_cogs' => round($totalCogs, 2),
                'gross_profit' => $grossProfit,
                'profit_margin_percentage' => $profitMargin,
                'transactions_count' => $transactionsCount,
                'items_sold_count' => $totalItemsSold,
            ],
            'timeline' => $timeline,
            'top_products' => $topProducts,
            'top_categories' => $topCategories,
        ];
    }
}
