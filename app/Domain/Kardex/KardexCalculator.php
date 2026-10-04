<?php

namespace App\Domain\Kardex;

final class KardexCalculator
{
    /**
     * Procesa una secuencia cronológica de movimientos y retorna los objetos KardexMovement calculados.
     *
     * @param array $rawMovements Colección de modelos o arrays ordenados cronológicamente por created_at e id ASC
     * @param float $defaultCost Costo base del producto por si no hay historial previo
     * @return array{movements: KardexMovement[], finalBalanceQuantity: int, finalBalanceValue: float, currentAverageCost: float}
     */
    public function calculate(array $rawMovements, float $defaultCost = 0.0): array
    {
        $calculatedMovements = [];
        $runningQuantity = 0;
        $runningValue = 0.0;
        $runningAverageCost = $defaultCost;

        foreach ($rawMovements as $m) {
            $isObject = is_object($m);
            $type = $isObject ? $m->type : $m['type'];
            $quantity = (int) ($isObject ? $m->quantity : $m['quantity']);
            $explicitUnitCost = $isObject ? ($m->unit_cost ?? null) : ($m['unit_cost'] ?? null);
            $explicitUnitCost = $explicitUnitCost !== null ? (float) $explicitUnitCost : null;
            $id = (int) ($isObject ? $m->id : $m['id']);
            $reason = (string) ($isObject ? $m->reason : $m['reason']);
            $refType = $isObject ? ($m->reference_type ?? null) : ($m['reference_type'] ?? null);
            $refId = $isObject ? ($m->reference_id ?? null) : ($m['reference_id'] ?? null);
            $refId = $refId !== null ? (int) $refId : null;
            $createdAt = $isObject ? (string) $m->created_at : (string) $m['created_at'];
            $userName = $isObject
                ? ($m->user->name ?? 'Sistema')
                : ($m['user_name'] ?? ($m['user']['name'] ?? 'Sistema'));

            if ($type === 'in') {
                $entryQty = $quantity;
                $exitQty = 0;

                // Si no tiene costo explícito, tomar el último promedio o el default
                $unitCost = $explicitUnitCost ?? ($runningAverageCost > 0 ? $runningAverageCost : $defaultCost);
                $debit = round($entryQty * $unitCost, 2);
                $credit = 0.0;

                $runningQuantity += $entryQty;
                $runningValue += $debit;

                if ($runningQuantity > 0) {
                    $runningAverageCost = round($runningValue / $runningQuantity, 4);
                } else {
                    $runningAverageCost = $unitCost;
                }
            } else {
                $entryQty = 0;
                $exitQty = $quantity;

                // Para salidas, el costo unitario de salida es el CPP vigente antes de la salida
                $unitCost = $explicitUnitCost ?? $runningAverageCost;
                if ($unitCost <= 0.0 && $defaultCost > 0.0) {
                    $unitCost = $defaultCost;
                }

                $credit = round($exitQty * $unitCost, 2);
                $debit = 0.0;

                $runningQuantity = max(0, $runningQuantity - $exitQty);
                $runningValue = max(0.0, round($runningValue - $credit, 2));

                if ($runningQuantity === 0) {
                    $runningValue = 0.0;
                    // Mantener el último CPP para futuras referencias si no hay nueva compra
                } else {
                    // En teoría las salidas no cambian el costo promedio unitario, pero recalculamos para evitar drift
                    $runningAverageCost = round($runningValue / $runningQuantity, 4);
                }
            }

            $calculatedMovements[] = new KardexMovement(
                id: $id,
                date: $createdAt,
                type: $type,
                reason: $reason,
                referenceType: $refType,
                referenceId: $refId,
                userName: $userName,
                entryQuantity: $entryQty,
                exitQuantity: $exitQty,
                balanceQuantity: $runningQuantity,
                unitCost: $unitCost,
                debitAmount: $debit,
                creditAmount: $credit,
                averageUnitCost: $runningAverageCost,
                balanceValue: $runningValue
            );
        }

        return [
            'movements' => $calculatedMovements,
            'finalBalanceQuantity' => $runningQuantity,
            'finalBalanceValue' => $runningValue,
            'currentAverageCost' => $runningAverageCost,
        ];
    }
}
