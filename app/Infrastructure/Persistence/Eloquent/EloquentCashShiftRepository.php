<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\CashShift\CashShift;
use App\Domain\CashShift\CashShiftRepositoryInterface;
use Carbon\Carbon;

class EloquentCashShiftRepository implements CashShiftRepositoryInterface
{
    public function findById(int $id): ?CashShift
    {
        $model = CashShiftModel::with('user')->find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findCurrentByUserId(int $userId): ?CashShift
    {
        $model = CashShiftModel::with('user')
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function openShift(int $userId, float $openingAmount, ?string $notes = null): CashShift
    {
        $model = CashShiftModel::create([
            'user_id' => $userId,
            'opening_amount' => $openingAmount,
            'status' => 'open',
            'opened_at' => Carbon::now(),
            'notes' => $notes,
        ]);

        return $this->toDomain($model->fresh(['user']));
    }

    public function recordSale(int $shiftId, float $amount, string $paymentMethod): void
    {
        $shift = CashShiftModel::findOrFail($shiftId);

        if ($paymentMethod === 'efectivo') {
            $shift->increment('total_cash_sales', $amount);
        } else {
            $shift->increment('total_qr_sales', $amount);
        }
    }

    public function closeShift(int $shiftId, float $closingAmount, ?string $notes = null): CashShift
    {
        $shift = CashShiftModel::findOrFail($shiftId);

        $expected = (float) $shift->opening_amount + (float) $shift->total_cash_sales;
        $diff = $closingAmount - $expected;

        $shift->update([
            'closing_amount' => $closingAmount,
            'expected_amount' => $expected,
            'difference' => $diff,
            'status' => 'closed',
            'closed_at' => Carbon::now(),
            'notes' => $notes ?? $shift->notes,
        ]);

        return $this->toDomain($shift->fresh(['user']));
    }

    public function paginate(int $page = 1, int $perPage = 15, ?int $userId = null): array
    {
        $q = CashShiftModel::with('user')->orderByDesc('opened_at');

        if ($userId) {
            $q->where('user_id', $userId);
        }

        $paginator = $q->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn($m) => $this->toDomain($m)->toArray())->all(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    private function toDomain(CashShiftModel $m): CashShift
    {
        $digitalTotals = $this->resolveDigitalTotals($m->id);

        return new CashShift(
            id: $m->id,
            userId: $m->user_id,
            openingAmount: (float) $m->opening_amount,
            closingAmount: $m->closing_amount !== null ? (float) $m->closing_amount : null,
            expectedAmount: $m->expected_amount !== null ? (float) $m->expected_amount : null,
            difference: $m->difference !== null ? (float) $m->difference : null,
            totalCashSales: (float) $m->total_cash_sales,
            totalQrSales: (float) $m->total_qr_sales,
            status: $m->status,
            openedAt: $m->opened_at?->format('Y-m-d H:i:s'),
            closedAt: $m->closed_at?->format('Y-m-d H:i:s'),
            notes: $m->notes,
            userName: $m->user?->name,
            digitalTotalsByMethod: $digitalTotals
        );
    }

    private function resolveDigitalTotals(int $shiftId): array
    {
        $sales = SaleModel::with('paymentMethod')
            ->where('cash_shift_id', $shiftId)
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->where('payment_method', '!=', 'efectivo')
                    ->orWhereNull('payment_method');
            })
            ->get();

        $grouped = [];
        foreach ($sales as $sale) {
            $method = $sale->paymentMethod;
            if ($method && $method->type === 'cash') {
                continue;
            }

            $key = $method ? (string)$method->id : ($sale->payment_method ?? 'digital');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'payment_method_id' => $method?->id,
                    'name' => $method?->name ?? ucfirst($sale->payment_method ?? 'Digital'),
                    'type' => $method?->type ?? ($sale->payment_method ?? 'other'),
                    'bank_name' => $method?->bank_name,
                    'account_number' => $method?->account_number,
                    'total_amount' => 0.00,
                    'count' => 0,
                ];
            }
            $grouped[$key]['total_amount'] += (float)$sale->total_amount;
            $grouped[$key]['count']++;
        }

        return array_values(array_map(function ($row) {
            $row['total_amount'] = number_format($row['total_amount'], 2, '.', '');
            return $row;
        }, $grouped));
    }
}
