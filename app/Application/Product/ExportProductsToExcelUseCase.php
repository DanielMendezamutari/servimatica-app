<?php

namespace App\Application\Product;

use App\Domain\Product\ProductRepositoryInterface;
use App\Infrastructure\Services\Excel\ExcelExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ExportProductsToExcelUseCase
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private ExcelExportService $excel
    ) {}

    public function execute(array $filters, bool $owner): StreamedResponse
    {
        $items = $this->products->getForExport($filters, $owner);

        $conditionLabels = [
            'nuevo' => 'Nuevo',
            'open_box' => 'Seminuevo / Open Box',
            'usado' => 'Usado',
            'reacondicionado' => 'Reacondicionado',
        ];

        $today = date('Y-m-d');
        $filename = "inventario_servimatica_{$today}.xlsx";
        $sheetTitle = 'Inventario';

        if ($owner) {
            $headers = [
                'SKU',
                'Producto / Artículo',
                'Categoría',
                'Subfamilia',
                'Marca',
                'Modelo',
                'Condición',
                'Costo Compra (Bs.)',
                'Precio Venta (Bs.)',
                'Margen (Bs.)',
                'Margen (%)',
                'Stock Actual',
                'Stock Mínimo',
                'Estado',
            ];

            $formats = [
                1 => 'text',
                2 => 'text',
                3 => 'text',
                4 => 'text',
                5 => 'text',
                6 => 'text',
                7 => 'text',
                8 => 'currency',
                9 => 'currency',
                10 => 'currency',
                11 => 'percentage',
                12 => 'number',
                13 => 'number',
                14 => 'text',
            ];

            $rows = array_map(function ($p) use ($conditionLabels) {
                $cost = (float)($p['costPrice'] ?? 0);
                $sale = (float)($p['salePrice'] ?? 0);
                $marginAmount = $sale - $cost;
                $marginPercent = $cost > 0 ? (($sale - $cost) / $cost) * 100 : 0;

                return [
                    $p['sku'],
                    $p['name'],
                    $p['categoryName'] ?? '',
                    $p['subfamilyName'] ?? '—',
                    $p['brandName'] ?? '—',
                    $p['productModelName'] ?? '—',
                    $conditionLabels[$p['condition'] ?? 'nuevo'] ?? $p['condition'],
                    $cost,
                    $sale,
                    $marginAmount,
                    $marginPercent,
                    $p['stock'] ?? 0,
                    $p['minStock'] ?? 0,
                    ($p['status'] ?? 'active') === 'active' ? 'Activo' : 'Inactivo',
                ];
            }, $items);
        } else {
            // Seller view: NEVER expose costs or margins
            $headers = [
                'SKU',
                'Producto / Artículo',
                'Categoría',
                'Subfamilia',
                'Marca',
                'Modelo',
                'Condición',
                'Precio Venta (Bs.)',
                'Stock Actual',
                'Estado',
            ];

            $formats = [
                1 => 'text',
                2 => 'text',
                3 => 'text',
                4 => 'text',
                5 => 'text',
                6 => 'text',
                7 => 'text',
                8 => 'currency',
                9 => 'number',
                10 => 'text',
            ];

            $rows = array_map(function ($p) use ($conditionLabels) {
                $stock = (int)($p['stock'] ?? 0);
                $statusText = ($p['status'] ?? 'active') === 'active'
                    ? ($stock > 0 ? 'Disponible' : 'Agotado')
                    : 'Inactivo';

                return [
                    $p['sku'],
                    $p['name'],
                    $p['categoryName'] ?? '',
                    $p['subfamilyName'] ?? '—',
                    $p['brandName'] ?? '—',
                    $p['productModelName'] ?? '—',
                    $conditionLabels[$p['condition'] ?? 'nuevo'] ?? $p['condition'],
                    (float)($p['salePrice'] ?? 0),
                    $stock,
                    $statusText,
                ];
            }, $items);
        }

        return $this->excel->download($filename, $sheetTitle, $headers, $rows, $formats);
    }
}
