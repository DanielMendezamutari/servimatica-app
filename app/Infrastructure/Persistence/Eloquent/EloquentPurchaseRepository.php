<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Purchase\Purchase;
use App\Domain\Purchase\PurchaseItem;
use App\Domain\Purchase\PurchaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EloquentPurchaseRepository implements PurchaseRepositoryInterface
{
    public function findById(int $id): ?Purchase
    {
        $model = PurchaseModel::with(['supplier', 'user', 'cancelledByUser', 'items.product', 'paymentMethod'])
            ->find($id);

        return $model ? $this->map($model) : null;
    }

    public function findByPurchaseNumber(string $purchaseNumber): ?Purchase
    {
        $model = PurchaseModel::with(['supplier', 'user', 'cancelledByUser', 'items.product', 'paymentMethod'])
            ->where('purchase_number', $purchaseNumber)
            ->first();

        return $model ? $this->map($model) : null;
    }

    public function save(array $data, array $items): Purchase
    {
        $purchase = PurchaseModel::create([
            'purchase_number' => $data['purchase_number'] ?? $this->generateNextPurchaseNumber(),
            'invoice_number' => trim($data['invoice_number']),
            'supplier_id' => (int)$data['supplier_id'],
            'user_id' => (int)$data['user_id'],
            'purchase_date' => $data['purchase_date'],
            'payment_condition' => $data['payment_condition'] ?? 'contado',
            'payment_method' => $data['payment_method'] ?? 'transferencia',
            'payment_method_id' => !empty($data['payment_method_id']) ? (int)$data['payment_method_id'] : null,
            'reference_number' => !empty($data['reference_number']) ? trim($data['reference_number']) : null,
            'payment_status' => ($data['payment_condition'] ?? 'contado') === 'credito' ? 'pendiente' : 'pagado',
            'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
            'subtotal' => $data['subtotal'],
            'total_amount' => $data['total_amount'],
            'status' => 'received',
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
        ]);

        foreach ($items as $item) {
            $purchase->items()->create([
                'product_id' => (int)$item['product_id'],
                'product_name' => $item['product_name'],
                'product_sku' => $item['product_sku'],
                'quantity' => (int)$item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'subtotal' => $item['subtotal'],
                'previous_cost' => $item['previous_cost'] ?? null,
                'previous_sale_price' => $item['previous_sale_price'] ?? null,
                'new_sale_price' => $item['new_sale_price'] ?? null,
            ]);
        }

        return $this->findById($purchase->id);
    }

    public function cancel(int $id, int $cancelledBy, string $reason): Purchase
    {
        $model = PurchaseModel::findOrFail($id);
        $model->update([
            'status' => 'cancelled',
            'cancellation_reason' => trim($reason),
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => Carbon::now(),
        ]);

        return $this->findById($model->id);
    }

    public function paginate(
        int $page = 1,
        int $perPage = 15,
        ?int $supplierId = null,
        ?string $search = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = PurchaseModel::with(['supplier', 'user', 'cancelledByUser', 'paymentMethod'])
            ->withCount('items');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if (!empty($search)) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('purchase_number', 'like', "%{$term}%")
                    ->orWhere('invoice_number', 'like', "%{$term}%")
                    ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$term}%"));
            });
        }

        if ($status && in_array($status, ['received', 'cancelled'])) {
            $query->where('status', $status);
        }

        if (!empty($startDate)) {
            $query->whereDate('purchase_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('purchase_date', '<=', $endDate);
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn($m) => [
                'id' => (int)$m->id,
                'purchase_number' => (string)$m->purchase_number,
                'invoice_number' => (string)$m->invoice_number,
                'supplier_id' => (int)$m->supplier_id,
                'supplier_name' => (string)($m->supplier?->name ?? 'Proveedor no especificado'),
                'supplier_nit' => (string)($m->supplier?->nit ?? ''),
                'user_id' => (int)$m->user_id,
                'user_name' => (string)($m->user?->name ?? 'Usuario'),
                'purchase_date' => $m->purchase_date instanceof Carbon ? $m->purchase_date->format('Y-m-d') : (string)$m->purchase_date,
                'payment_condition' => (string)$m->payment_condition,
                'payment_method' => (string)$m->payment_method,
                'payment_method_id' => $m->payment_method_id ? (int)$m->payment_method_id : null,
                'reference_number' => $m->reference_number,
                'payment_method_name' => $m->paymentMethod?->name,
                'payment_status' => (string)$m->payment_status,
                'due_date' => $m->due_date ? ($m->due_date instanceof Carbon ? $m->due_date->format('Y-m-d') : (string)$m->due_date) : null,
                'subtotal' => number_format((float)$m->subtotal, 2, '.', ''),
                'total_amount' => number_format((float)$m->total_amount, 2, '.', ''),
                'status' => (string)$m->status,
                'cancellation_reason' => $m->cancellation_reason,
                'cancelled_by_name' => $m->cancelledByUser?->name,
                'cancelled_at' => $m->cancelled_at?->format('Y-m-d H:i:s'),
                'notes' => $m->notes,
                'items_count' => (int)($m->items_count ?? 0),
                'created_at' => $m->created_at?->format('Y-m-d H:i:s'),
            ])->all(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    public function generateNextPurchaseNumber(): string
    {
        $lastId = PurchaseModel::max('id') ?? 0;
        $next = $lastId + 1;
        return 'COM-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }

    private function map(PurchaseModel $m): Purchase
    {
        $items = [];
        if ($m->relationLoaded('items')) {
            foreach ($m->items as $item) {
                $items[] = new PurchaseItem(
                    id: (int)$item->id,
                    purchaseId: (int)$item->purchase_id,
                    productId: (int)$item->product_id,
                    productName: (string)$item->product_name,
                    productSku: (string)$item->product_sku,
                    quantity: (int)$item->quantity,
                    unitCost: (float)$item->unit_cost,
                    subtotal: (float)$item->subtotal,
                    previousCost: $item->previous_cost !== null ? (float)$item->previous_cost : null,
                    previousSalePrice: $item->previous_sale_price !== null ? (float)$item->previous_sale_price : null,
                    newSalePrice: $item->new_sale_price !== null ? (float)$item->new_sale_price : null
                );
            }
        }

        return new Purchase(
            id: (int)$m->id,
            purchaseNumber: (string)$m->purchase_number,
            invoiceNumber: (string)$m->invoice_number,
            supplierId: (int)$m->supplier_id,
            supplierName: (string)($m->supplier?->name ?? 'Proveedor no especificado'),
            userId: (int)$m->user_id,
            userName: $m->user?->name,
            purchaseDate: $m->purchase_date instanceof Carbon ? $m->purchase_date->format('Y-m-d') : (string)$m->purchase_date,
            paymentCondition: (string)$m->payment_condition,
            paymentMethod: (string)$m->payment_method,
            paymentStatus: (string)$m->payment_status,
            dueDate: $m->due_date ? ($m->due_date instanceof Carbon ? $m->due_date->format('Y-m-d') : (string)$m->due_date) : null,
            subtotal: (float)$m->subtotal,
            totalAmount: (float)$m->total_amount,
            status: (string)$m->status,
            paymentMethodId: $m->payment_method_id ? (int)$m->payment_method_id : null,
            referenceNumber: $m->reference_number,
            paymentMethodName: $m->paymentMethod?->name,
            cancellationReason: $m->cancellation_reason,
            cancelledBy: $m->cancelled_by ? (int)$m->cancelled_by : null,
            cancelledByName: $m->cancelledByUser?->name,
            cancelledAt: $m->cancelled_at?->format('Y-m-d H:i:s'),
            notes: $m->notes,
            itemsCount: count($items),
            items: $items,
            createdAt: $m->created_at?->format('Y-m-d H:i:s')
        );
    }
}
