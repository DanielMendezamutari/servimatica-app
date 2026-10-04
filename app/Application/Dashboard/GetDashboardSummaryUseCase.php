<?php

namespace App\Application\Dashboard;

use App\Infrastructure\Persistence\Eloquent\CashShiftModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SaleItemModel;
use App\Infrastructure\Persistence\Eloquent\SaleModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class GetDashboardSummaryUseCase
{
    public function execute(User $user, string $period = 'today'): array
    {
        $isPrivileged = in_array(strtolower((string) $user->role), ['dueno', 'dueño', 'administrador', 'admin'], true);

        // 1. Rango de fechas
        $now = Carbon::now();
        switch ($period) {
            case 'this_week':
                $from = $now->copy()->startOfWeek();
                $to = $now->copy()->endOfWeek();
                $prevFrom = $now->copy()->subWeek()->startOfWeek();
                $prevTo = $now->copy()->subWeek()->endOfWeek();
                $periodLabel = 'Esta Semana (' . $from->format('d/m') . ' - ' . $to->format('d/m') . ')';
                break;

            case 'this_month':
                $from = $now->copy()->startOfMonth();
                $to = $now->copy()->endOfMonth();
                $prevFrom = $now->copy()->subMonth()->startOfMonth();
                $prevTo = $now->copy()->subMonth()->endOfMonth();
                $periodLabel = 'Este Mes (' . $now->translatedFormat('F Y') . ')';
                break;

            case 'today':
            default:
                $period = 'today';
                $from = $now->copy()->startOfDay();
                $to = $now->copy()->endOfDay();
                $prevFrom = $now->copy()->subDay()->startOfDay();
                $prevTo = $now->copy()->subDay()->endOfDay();
                $periodLabel = 'Hoy (' . $now->translatedFormat('d \d\e F, Y') . ')';
                break;
        }

        // 2. Ventas del periodo actual
        $salesQuery = SaleModel::where('status', 'completed')
            ->whereBetween('created_at', [$from, $to]);

        if (!$isPrivileged) {
            $salesQuery->where('seller_id', $user->id);
        }

        $salesCount = (int) $salesQuery->count();
        $totalSalesBs = (float) $salesQuery->sum('total_amount');
        $averageTicketBs = $salesCount > 0 ? round($totalSalesBs / $salesCount, 2) : 0.0;

        // 3. Ventas del periodo previo para comparativa
        $prevSalesQuery = SaleModel::where('status', 'completed')
            ->whereBetween('created_at', [$prevFrom, $prevTo]);

        if (!$isPrivileged) {
            $prevSalesQuery->where('seller_id', $user->id);
        }

        $prevSalesBs = (float) $prevSalesQuery->sum('total_amount');
        if ($prevSalesBs > 0) {
            $salesTrend = round((($totalSalesBs - $prevSalesBs) / $prevSalesBs) * 100, 2);
        } elseif ($totalSalesBs > 0) {
            $salesTrend = 100.0;
        } else {
            $salesTrend = 0.0;
        }

        // 4. Utilidades (Principio VI: solo para Dueño)
        $totalProfitBs = null;
        $profitMarginPercentage = null;

        if ($isPrivileged) {
            $itemsProfit = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.status', 'completed')
                ->whereBetween('sales.created_at', [$from, $to])
                ->selectRaw('SUM((sale_items.unit_price - COALESCE(sale_items.unit_cost, 0)) * sale_items.quantity) as profit')
                ->value('profit');

            $totalProfitBs = round((float) ($itemsProfit ?? 0.0), 2);
            $profitMarginPercentage = $totalSalesBs > 0 ? round(($totalProfitBs / $totalSalesBs) * 100, 2) : 0.0;
        }

        // 5. Turno de Caja Activo
        $activeShift = CashShiftModel::with('user')
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $cashShiftData = [
            'has_active_shift' => (bool) $activeShift,
            'shift_id' => $activeShift?->id,
            'cashier_name' => $activeShift?->user?->name,
            'opened_at' => $activeShift?->opened_at ? Carbon::parse($activeShift->opened_at)->format('H:i:s') : null,
            'initial_cash_bs' => (float) ($activeShift?->opening_amount ?? 0.0),
            'current_cash_sales_bs' => (float) ($activeShift?->total_cash_sales ?? 0.0),
            'current_total_collected_bs' => (float) (($activeShift?->total_cash_sales ?? 0.0) + ($activeShift?->total_qr_sales ?? 0.0)),
        ];

        // 6. Alertas de Stock Crítico / Mínimo
        $criticalStockProducts = ProductModel::where('status', 'active')
            ->where(function ($q) {
                $q->whereColumn('stock', '<=', 'min_stock')
                  ->orWhere('stock', '<=', 0);
            })
            ->orderBy('stock', 'asc')
            ->limit(8)
            ->get();

        $criticalStockList = $criticalStockProducts->map(function ($p) {
            $stock = (int) ($p->stock ?? 0);
            $minStock = (int) ($p->min_stock ?? 0);
            return [
                'product_id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'current_stock' => $stock,
                'min_stock' => $minStock,
                'status' => $stock <= 0 ? 'out_of_stock' : 'low_stock',
            ];
        })->values()->all();

        // 7. Top 5 Productos más vendidos en el periodo
        $topProductsQuery = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to])
            ->select(
                'sale_items.product_id',
                'sale_items.product_name as name',
                DB::raw('COALESCE(categories.name, "General") as category_name'),
                DB::raw('SUM(sale_items.quantity) as units_sold'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue_bs')
            )
            ->groupBy('sale_items.product_id', 'sale_items.product_name', 'categories.name')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get();

        $topProductsList = $topProductsQuery->map(function ($tp) {
            return [
                'product_id' => (int) $tp->product_id,
                'name' => $tp->name,
                'category_name' => $tp->category_name,
                'units_sold' => (int) $tp->units_sold,
                'total_revenue_bs' => round((float) $tp->total_revenue_bs, 2),
            ];
        })->all();

        return [
            'period' => $period,
            'period_label' => $periodLabel,
            'kpis' => [
                'total_sales_bs' => round($totalSalesBs, 2),
                'sales_count' => $salesCount,
                'average_ticket_bs' => $averageTicketBs,
                'previous_period_sales_bs' => round($prevSalesBs, 2),
                'sales_trend_percentage' => $salesTrend,
                'total_profit_bs' => $totalProfitBs,
                'profit_margin_percentage' => $profitMarginPercentage,
            ],
            'cash_shift' => $cashShiftData,
            'critical_stock' => $criticalStockList,
            'top_products' => $topProductsList,
        ];
    }
}
