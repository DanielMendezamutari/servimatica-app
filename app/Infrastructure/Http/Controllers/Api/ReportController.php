<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Report\GetProfitabilityReportUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController
{
    public function profitability(Request $request, GetProfitabilityReportUseCase $useCase): JsonResponse|StreamedResponse
    {
        $user = $request->user('api') ?? $request->user();
        $isPrivileged = $user && in_array(strtolower((string) $user->role), ['dueno', 'dueño', 'administrador', 'admin'], true);

        if (!$isPrivileged) {
            abort(403, 'Acceso restringido. Información financiera confidencial exclusiva para el Dueño.');
        }

        $filters = [
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $report = $useCase->execute($filters);

        if ($request->query('export') === 'csv') {
            return $this->exportProfitabilityCsv($report);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    private function exportProfitabilityCsv(array $report): StreamedResponse
    {
        $from = $report['period']['from'];
        $to = $report['period']['to'];
        $filename = "rentabilidad_{$from}_{$to}_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['REPORTE DE RENTABILIDAD Y UTILIDAD - SERVIMÁTICA'], ';');
            fputcsv($handle, ['Periodo:', "{$report['period']['from']} hasta {$report['period']['to']}"], ';');
            fputcsv($handle, ['Ventas Totales (Bs.):', number_format($report['kpis']['total_sales'], 2, '.', '')], ';');
            fputcsv($handle, ['Costo Mercancía Vendida COGS (Bs.):', number_format($report['kpis']['total_cogs'], 2, '.', '')], ';');
            fputcsv($handle, ['Utilidad Bruta (Bs.):', number_format($report['kpis']['gross_profit'], 2, '.', '')], ';');
            fputcsv($handle, ['Margen de Utilidad (%):', number_format($report['kpis']['profit_margin_percentage'], 2, '.', '') . '%'], ';');
            fputcsv($handle, ['Transacciones Realizadas:', $report['kpis']['transactions_count']], ';');
            fputcsv($handle, ['Total Unidades Vendidas:', $report['kpis']['items_sold_count']], ';');
            fputcsv($handle, [], ';');

            // Sección 1: Evolución por Día
            fputcsv($handle, ['--- DESGLOSE CRONOLÓGICO POR DÍA ---'], ';');
            fputcsv($handle, ['Fecha', 'Ventas (Bs.)', 'Costo (Bs.)', 'Utilidad (Bs.)', 'Margen (%)', 'Ventas Realizadas', 'Unidades Vendidas'], ';');
            foreach ($report['timeline'] as $t) {
                fputcsv($handle, [
                    $t['date'],
                    number_format($t['sales'], 2, '.', ''),
                    number_format($t['cost'], 2, '.', ''),
                    number_format($t['profit'], 2, '.', ''),
                    number_format($t['margin_percentage'], 2, '.', '') . '%',
                    $t['transactions'],
                    $t['items_count'],
                ], ';');
            }

            fputcsv($handle, [], ';');

            // Sección 2: Ranking de Productos más Rentables
            fputcsv($handle, ['--- RANKING POR PRODUCTO ---'], ';');
            fputcsv($handle, ['SKU', 'Producto', 'Categoría', 'Unidades Vendidas', 'Ingresos (Bs.)', 'Costo Total (Bs.)', 'Utilidad Ganada (Bs.)', 'Margen (%)'], ';');
            foreach ($report['top_products'] as $p) {
                fputcsv($handle, [
                    $p['sku'],
                    $p['name'],
                    $p['category_name'],
                    $p['units_sold'],
                    number_format($p['total_revenue'], 2, '.', ''),
                    number_format($p['total_cost'], 2, '.', ''),
                    number_format($p['profit'], 2, '.', ''),
                    number_format($p['margin_percentage'], 2, '.', '') . '%',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function inventoryValuation(Request $request, \App\Application\Report\GetInventoryValuationUseCase $useCase): JsonResponse|StreamedResponse
    {
        $user = $request->user('api') ?? $request->user();
        $isPrivileged = $user && in_array(strtolower((string) $user->role), ['dueno', 'dueño', 'administrador', 'admin'], true);

        if (!$isPrivileged) {
            abort(403, 'Acceso restringido. Información patrimonial confidencial exclusiva para el Dueño.');
        }

        $filters = [
            'category_id' => $request->query('category_id'),
            'search' => $request->query('search'),
        ];

        $report = $useCase->execute($filters);

        if ($request->query('export') === 'csv') {
            return $this->exportInventoryValuationCsv($report);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    private function exportInventoryValuationCsv(array $report): StreamedResponse
    {
        $filename = "valoracion_inventario_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['VALORACIÓN TOTAL DE INVENTARIO Y CAPITAL INMOVILIZADO - SERVIMÁTICA'], ';');
            fputcsv($handle, ['Fecha de Emisión:', date('Y-m-d H:i:s')], ';');
            fputcsv($handle, ['Variedad de Artículos:', $report['summary']['total_products_count']], ';');
            fputcsv($handle, ['Total Unidades Vendibles:', $report['summary']['total_sellable_units']], ';');
            fputcsv($handle, ['Total Unidades en Garantía / Defectuoso:', $report['summary']['total_defective_units']], ';');
            fputcsv($handle, ['Capital Vendible Activo (Bs.):', number_format($report['summary']['sellable_valuation_bs'], 2, '.', '')], ';');
            fputcsv($handle, ['Capital en Garantía / Cuarentena (Bs.):', number_format($report['summary']['defective_valuation_bs'], 2, '.', '')], ';');
            fputcsv($handle, ['Capital Total Inmovilizado (Bs.):', number_format($report['summary']['total_inventory_valuation_bs'], 2, '.', '')], ';');
            fputcsv($handle, [], ';');

            fputcsv($handle, [
                'SKU',
                'Producto',
                'Categoría',
                'Marca',
                'Stock Vendible',
                'Stock Garantía',
                'Costo Base (Bs.)',
                'Precio Venta (Bs.)',
                'Capital Vendible (Bs.)',
                'Capital Garantía (Bs.)',
                'Capital Total (Bs.)',
            ], ';');

            foreach ($report['products'] as $p) {
                fputcsv($handle, [
                    $p['sku'],
                    $p['name'],
                    $p['category_name'],
                    $p['brand_name'] ?? '-',
                    $p['stock'],
                    $p['defective_stock'],
                    number_format($p['average_cost'], 2, '.', ''),
                    number_format($p['sale_price'], 2, '.', ''),
                    number_format($p['sellable_value_bs'], 2, '.', ''),
                    number_format($p['defective_value_bs'], 2, '.', ''),
                    number_format($p['total_value_bs'], 2, '.', ''),
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, [
                'TOTALES',
                '-',
                '-',
                '-',
                $report['summary']['total_sellable_units'],
                $report['summary']['total_defective_units'],
                '-',
                '-',
                number_format($report['summary']['sellable_valuation_bs'], 2, '.', ''),
                number_format($report['summary']['defective_valuation_bs'], 2, '.', ''),
                number_format($report['summary']['total_inventory_valuation_bs'], 2, '.', ''),
            ], ';');

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
