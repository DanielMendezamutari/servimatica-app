<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Sale\SaleReturn;
use App\Domain\Sale\SaleReturnItem;
use App\Domain\Sale\SaleReturnRepositoryInterface;

class EloquentSaleReturnRepository implements SaleReturnRepositoryInterface
{
    public function findById(int $id): ?SaleReturn
    {
        $model = SaleReturnModel::with(['sale', 'user', 'items'])->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByReturnNumber(string $number): ?SaleReturn
    {
        $model = SaleReturnModel::with(['sale', 'user', 'items'])
            ->where('return_number', $number)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function getNextReturnNumber(): string
    {
        $last = SaleReturnModel::latest('id')->first();
        $next = $last ? ($last->id + 1) : 1;

        return 'DEV-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }

    public function save(SaleReturn $saleReturn): SaleReturn
    {
        $returnNumber = $saleReturn->returnNumber ?: $this->getNextReturnNumber();

        $model = SaleReturnModel::updateOrCreate(
            ['id' => $saleReturn->id],
            [
                'return_number' => $returnNumber,
                'sale_id' => $saleReturn->saleId,
                'client_id' => $saleReturn->clientId,
                'client_name' => $saleReturn->clientName,
                'user_id' => $saleReturn->userId,
                'cash_shift_id' => $saleReturn->cashShiftId,
                'resolution' => $saleReturn->resolution,
                'total_refund_amount' => $saleReturn->totalRefundAmount,
                'reason' => $saleReturn->reason,
                'status' => $saleReturn->status,
            ]
        );

        if (!empty($saleReturn->items)) {
            // Eliminar ítems previos si es actualización
            $model->items()->delete();

            foreach ($saleReturn->items as $it) {
                $model->items()->create([
                    'sale_item_id' => $it->saleItemId,
                    'product_id' => $it->productId,
                    'product_name' => $it->productName,
                    'quantity' => $it->quantity,
                    'unit_price' => $it->unitPrice,
                    'subtotal' => $it->subtotal,
                    'condition' => $it->condition,
                    'serial_number' => $it->serialNumber,
                ]);
            }
        }

        $model->load(['sale', 'user', 'items']);

        return $this->toDomain($model);
    }

    public function list(int $page = 1, int $perPage = 15, ?string $resolution = null, ?string $search = null): array
    {
        $q = SaleReturnModel::with(['sale', 'user', 'items'])->latest('id');

        if ($resolution) {
            $q->where('resolution', $resolution);
        }

        if ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('return_number', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhereHas('sale', function ($sq) use ($search) {
                        $sq->where('invoice_number', 'like', "%{$search}%");
                    });
            });
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

    public function getReturnedQuantitiesBySaleId(int $saleId): array
    {
        $items = SaleReturnItemModel::whereHas('saleReturn', function ($q) use ($saleId) {
            $q->where('sale_id', $saleId)
              ->where('status', 'completed');
        })->get();

        $map = [];
        foreach ($items as $item) {
            $saleItemId = (int) $item->sale_item_id;
            $map[$saleItemId] = ($map[$saleItemId] ?? 0) + (int) $item->quantity;
        }

        return $map;
    }

    private function toDomain(SaleReturnModel $m): SaleReturn
    {
        $items = $m->items->map(function ($it) {
            return new SaleReturnItem(
                id: $it->id,
                saleReturnId: $it->sale_return_id,
                saleItemId: $it->sale_item_id,
                productId: $it->product_id,
                productName: $it->product_name,
                quantity: (int) $it->quantity,
                unitPrice: (float) $it->unit_price,
                subtotal: (float) $it->subtotal,
                condition: $it->condition,
                serialNumber: $it->serial_number
            );
        })->all();

        return new SaleReturn(
            id: $m->id,
            returnNumber: $m->return_number,
            saleId: $m->sale_id,
            clientId: $m->client_id,
            clientName: $m->client_name,
            userId: $m->user_id,
            cashShiftId: $m->cash_shift_id,
            resolution: $m->resolution,
            totalRefundAmount: (float) $m->total_refund_amount,
            reason: $m->reason,
            status: $m->status,
            userName: $m->user?->name,
            invoiceNumber: $m->sale?->invoice_number,
            createdAt: $m->created_at?->format('Y-m-d H:i:s'),
            items: $items
        );
    }
}
