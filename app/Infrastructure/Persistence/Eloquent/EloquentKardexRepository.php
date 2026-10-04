<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Kardex\KardexCalculator;
use App\Domain\Kardex\KardexRepositoryInterface;
use Carbon\CarbonImmutable;

final class EloquentKardexRepository implements KardexRepositoryInterface
{
    public function __construct(
        private readonly KardexCalculator $calculator = new KardexCalculator()
    ) {
    }

    public function getProductKardex(int $productId, array $filters = []): array
    {
        $product = ProductModel::with(['brand', 'category'])->findOrFail($productId);

        // Obtenemos todos los movimientos históricos del producto ordenados cronológicamente
        $allMovements = StockMovementModel::with('user')
            ->where('product_id', $productId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $calculated = $this->calculator->calculate(
            rawMovements: $allMovements->all(),
            defaultCost: (float) $product->cost_price
        );

        $fromDate = !empty($filters['from'])
            ? CarbonImmutable::parse($filters['from'], 'America/La_Paz')->startOfDay()->utc()
            : null;

        $toDate = !empty($filters['to'])
            ? CarbonImmutable::parse($filters['to'], 'America/La_Paz')->endOfDay()->utc()
            : null;

        $typeFilter = !empty($filters['type']) && in_array($filters['type'], ['in', 'out'], true)
            ? $filters['type']
            : null;

        $initialBalance = [
            'quantity' => 0,
            'average_cost' => (float) $product->cost_price,
            'total_value' => 0.0,
        ];

        $filteredMovements = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $totalEntries = 0;
        $totalExits = 0;

        foreach ($calculated['movements'] as $movement) {
            $movementTime = CarbonImmutable::parse($movement->date)->utc();

            // Si está antes de 'from', acumula para el saldo inicial del periodo
            if ($fromDate && $movementTime->lt($fromDate)) {
                $initialBalance['quantity'] = $movement->balanceQuantity;
                $initialBalance['average_cost'] = $movement->averageUnitCost;
                $initialBalance['total_value'] = $movement->balanceValue;
                continue;
            }

            // Si está después de 'to', omitir
            if ($toDate && $movementTime->gt($toDate)) {
                continue;
            }

            // Filtro por tipo
            if ($typeFilter && $movement->type !== $typeFilter) {
                continue;
            }

            $filteredMovements[] = $movement;
            $totalDebit += $movement->debitAmount;
            $totalCredit += $movement->creditAmount;
            $totalEntries += $movement->entryQuantity;
            $totalExits += $movement->exitQuantity;
        }

        $lastFilteredMovement = end($filteredMovements);
        $finalBalanceQuantity = $lastFilteredMovement
            ? $lastFilteredMovement->balanceQuantity
            : ($fromDate ? $initialBalance['quantity'] : $calculated['finalBalanceQuantity']);
        $finalBalanceValue = $lastFilteredMovement
            ? $lastFilteredMovement->balanceValue
            : ($fromDate ? $initialBalance['total_value'] : $calculated['finalBalanceValue']);

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'category_name' => $product->category->name ?? 'General',
                'brand_name' => $product->brand->name ?? null,
                'current_stock' => (int) $product->stock,
                'defective_stock' => (int) $product->defective_stock,
                'cost_price' => (float) $product->cost_price,
                'sale_price' => (float) $product->sale_price,
                'current_cpp' => $calculated['currentAverageCost'],
            ],
            'filter' => [
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
                'type' => $filters['type'] ?? 'all',
            ],
            'initial_balance' => $initialBalance,
            'movements' => $filteredMovements,
            'totals' => [
                'total_entries_quantity' => $totalEntries,
                'total_exits_quantity' => $totalExits,
                'final_balance_quantity' => $finalBalanceQuantity,
                'total_debit_amount' => round($totalDebit, 2),
                'total_credit_amount' => round($totalCredit, 2),
                'final_balance_value' => round($finalBalanceValue, 2),
            ],
        ];
    }
}
