<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Kardex\GetProductKardexUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class KardexController
{
    public function __construct(
        private readonly GetProductKardexUseCase $getProductKardexUseCase
    ) {
    }

    public function show(int $productId, Request $request): JsonResponse|StreamedResponse
    {
        $user = $request->user('api') ?? $request->user();
        $isPrivileged = $user && in_array(strtolower((string) $user->role), ['dueno', 'dueño', 'administrador', 'admin'], true);

        $filters = [
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'type' => $request->query('type'),
        ];

        $kardex = $this->getProductKardexUseCase->execute($productId, $filters);

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($kardex, $isPrivileged);
        }

        // Sanitización para cumplimiento estricto del Principio VI (Privacidad de Costos)
        $product = $kardex['product'];
        $initialBalance = $kardex['initial_balance'];
        $totals = $kardex['totals'];

        if (!$isPrivileged) {
            unset($product['cost_price'], $product['current_cpp']);
            unset($initialBalance['average_cost'], $initialBalance['total_value']);
            unset($totals['total_debit_amount'], $totals['total_credit_amount'], $totals['final_balance_value']);
        }

        $movementsData = array_map(function ($m) use ($isPrivileged) {
            return is_object($m) && method_exists($m, 'toArray')
                ? $m->toArray($isPrivileged)
                : $m;
        }, $kardex['movements']);

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'filter' => $kardex['filter'],
                'initial_balance' => $initialBalance,
                'movements' => $movementsData,
                'totals' => $totals,
            ],
        ]);
    }

    private function exportCsv(array $kardex, bool $isPrivileged): StreamedResponse
    {
        $product = $kardex['product'];
        $sku = $product['sku'] ?? 'producto';
        $filename = "kardex_{$sku}_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($kardex, $isPrivileged) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para compatibilidad absoluta con Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Encabezados del reporte
            fputcsv($handle, ['KARDEX DE INVENTARIO - SERVIMÁTICA'], ';');
            fputcsv($handle, ['Producto:', $kardex['product']['name'] ?? ''], ';');
            fputcsv($handle, ['SKU:', $kardex['product']['sku'] ?? ''], ';');
            fputcsv($handle, ['Stock Actual:', $kardex['product']['current_stock'] ?? 0], ';');
            if ($isPrivileged) {
                fputcsv($handle, ['Costo Base (Bs.):', number_format($kardex['product']['cost_price'] ?? 0, 2, '.', '')], ';');
                fputcsv($handle, ['CPP Vigente (Bs.):', number_format($kardex['product']['current_cpp'] ?? 0, 4, '.', '')], ';');
            }
            fputcsv($handle, [], ';');

            // Columnas de la tabla
            if ($isPrivileged) {
                $columns = [
                    'Fecha y Hora',
                    'Tipo',
                    'Concepto / Motivo',
                    'Referencia Doc',
                    'Responsable',
                    'Entrada Fís.',
                    'Salida Fís.',
                    'Saldo Físico',
                    'Costo Unit. (Bs.)',
                    'Debe (Bs.)',
                    'Haber (Bs.)',
                    'CPP Móvil (Bs.)',
                    'Saldo Valorizado (Bs.)',
                ];
            } else {
                $columns = [
                    'Fecha y Hora',
                    'Tipo',
                    'Concepto / Motivo',
                    'Referencia Doc',
                    'Responsable',
                    'Entrada Fís.',
                    'Salida Fís.',
                    'Saldo Físico',
                ];
            }
            fputcsv($handle, $columns, ';');

            // Saldo inicial
            if (!empty($kardex['initial_balance'])) {
                $initRow = [
                    'SALDO INICIAL',
                    '-',
                    'Saldo inicial acumulado del periodo',
                    '-',
                    '-',
                    '-',
                    '-',
                    $kardex['initial_balance']['quantity'] ?? 0,
                ];
                if ($isPrivileged) {
                    $initRow[] = number_format($kardex['initial_balance']['average_cost'] ?? 0, 4, '.', '');
                    $initRow[] = '-';
                    $initRow[] = '-';
                    $initRow[] = number_format($kardex['initial_balance']['average_cost'] ?? 0, 4, '.', '');
                    $initRow[] = number_format($kardex['initial_balance']['total_value'] ?? 0, 2, '.', '');
                }
                fputcsv($handle, $initRow, ';');
            }

            // Filas de movimientos
            foreach ($kardex['movements'] as $m) {
                $ref = ($m->referenceType ? ucfirst($m->referenceType) . ' #' . $m->referenceId : '-');
                $row = [
                    $m->date,
                    $m->type === 'in' ? 'Entrada' : 'Salida',
                    $m->reason,
                    $ref,
                    $m->userName,
                    $m->entryQuantity > 0 ? $m->entryQuantity : '-',
                    $m->exitQuantity > 0 ? $m->exitQuantity : '-',
                    $m->balanceQuantity,
                ];

                if ($isPrivileged) {
                    $row[] = number_format($m->unitCost, 4, '.', '');
                    $row[] = $m->debitAmount > 0 ? number_format($m->debitAmount, 2, '.', '') : '-';
                    $row[] = $m->creditAmount > 0 ? number_format($m->creditAmount, 2, '.', '') : '-';
                    $row[] = number_format($m->averageUnitCost, 4, '.', '');
                    $row[] = number_format($m->balanceValue, 2, '.', '');
                }

                fputcsv($handle, $row, ';');
            }

            // Totales finales
            fputcsv($handle, [], ';');
            $totalsRow = [
                'TOTALES',
                '-',
                '-',
                '-',
                '-',
                $kardex['totals']['total_entries_quantity'] ?? 0,
                $kardex['totals']['total_exits_quantity'] ?? 0,
                $kardex['totals']['final_balance_quantity'] ?? 0,
            ];
            if ($isPrivileged) {
                $totalsRow[] = '-';
                $totalsRow[] = number_format($kardex['totals']['total_debit_amount'] ?? 0, 2, '.', '');
                $totalsRow[] = number_format($kardex['totals']['total_credit_amount'] ?? 0, 2, '.', '');
                $totalsRow[] = '-';
                $totalsRow[] = number_format($kardex['totals']['final_balance_value'] ?? 0, 2, '.', '');
            }
            fputcsv($handle, $totalsRow, ';');

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
