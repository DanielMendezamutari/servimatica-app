<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Sale\Sale;
use App\Domain\Sale\SaleItem;
use App\Domain\Sale\SaleRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentSaleRepository implements SaleRepositoryInterface
{
    public function findById(int $id): ?Sale
    {
        $model = SaleModel::with(['items', 'seller', 'client', 'cancelledByUser', 'paymentMethod'])->find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByInvoiceNumber(string $invoiceNumber): ?Sale
    {
        $model = SaleModel::with(['items', 'seller', 'client', 'cancelledByUser', 'paymentMethod'])->where('invoice_number', $invoiceNumber)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function save(array $saleData, array $itemsData): Sale
    {
        return DB::transaction(function () use ($saleData, $itemsData) {
            $sale = SaleModel::create($saleData);

            foreach ($itemsData as $it) {
                SaleItemModel::create([
                    'sale_id' => $sale->id,
                    'product_id' => $it['product_id'],
                    'product_name' => $it['product_name'],
                    'product_sku' => $it['product_sku'] ?? '',
                    'quantity' => $it['quantity'],
                    'unit_cost' => $it['unit_cost'] ?? 0.00,
                    'unit_price' => $it['unit_price'],
                    'subtotal' => $it['subtotal'] ?? ($it['quantity'] * $it['unit_price']),
                    'warranty_days' => $it['warranty_days'] ?? 0,
                    'warranty_expires_at' => $it['warranty_expires_at'] ?? null,
                    'warranty_hardware_days' => $it['warranty_hardware_days'] ?? ($it['warranty_days'] ?? 0),
                    'warranty_hardware_expires_at' => $it['warranty_hardware_expires_at'] ?? ($it['warranty_expires_at'] ?? null),
                    'warranty_software_days' => $it['warranty_software_days'] ?? 0,
                    'warranty_software_expires_at' => $it['warranty_software_expires_at'] ?? null,
                    'serial_number' => $it['serial_number'] ?? null,
                ]);
            }

            return $this->toDomain($sale->fresh(['items', 'seller', 'client', 'cancelledByUser', 'paymentMethod']));
        });
    }

    public function cancel(int $saleId, int $cancelledBy, string $reason): void
    {
        SaleModel::where('id', $saleId)->update([
            'status' => 'cancelled',
            'cancelled_by' => $cancelledBy,
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
            'commission_amount' => 0.00, // Comision queda invalidada
        ]);
    }

    public function paginate(
        int $page = 1,
        int $perPage = 15,
        ?string $search = null,
        ?int $sellerId = null,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $q = SaleModel::with(['items', 'seller', 'client', 'cancelledByUser', 'paymentMethod'])->orderByDesc('id');

        if ($sellerId) {
            $q->where('seller_id', $sellerId);
        }

        if ($status && $status !== 'all') {
            $q->where('status', $status);
        }

        if ($startDate) {
            $q->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $q->whereDate('created_at', '<=', $endDate);
        }

        if ($search && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('invoice_number', 'like', $term)
                    ->orWhere('client_name', 'like', $term)
                    ->orWhere('client_nit_ci', 'like', $term);
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

    public function getNextInvoiceNumber(): string
    {
        $last = SaleModel::latest('id')->first();
        $next = $last ? ($last->id + 1) : 1;
        return 'VTA-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }

    public function getCommissionsReport(?int $sellerId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $q = SaleModel::with('seller')
            ->where('status', 'completed');

        if ($sellerId) {
            $q->where('seller_id', $sellerId);
        }

        if ($startDate) {
            $q->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $q->whereDate('created_at', '<=', $endDate);
        }

        $sales = $q->get();

        $grouped = $sales->groupBy('seller_id');
        $report = [];

        foreach ($grouped as $sId => $sellerSales) {
            $first = $sellerSales->first();
            $sellerName = $first->seller ? $first->seller->name : "Vendedor #$sId";
            $totalSalesAmount = (float) $sellerSales->sum('total_amount');
            $totalCommissionAmount = (float) $sellerSales->sum('commission_amount');
            $salesCount = $sellerSales->count();

            $report[] = [
                'seller_id' => $sId,
                'seller_name' => $sellerName,
                'sales_count' => $salesCount,
                'total_sales_amount' => number_format($totalSalesAmount, 2, '.', ''),
                'total_commission_amount' => number_format($totalCommissionAmount, 2, '.', ''),
            ];
        }

        return $report;
    }

    private function toDomain(SaleModel $m): Sale
    {
        $items = $m->items->map(function ($it) {
            $hwDays = (int) ($it->warranty_hardware_days ?? $it->warranty_days ?? 0);
            $hwExpires = $it->warranty_hardware_expires_at ?? $it->warranty_expires_at;
            $hwExpiresFormatted = $hwExpires ? (is_string($hwExpires) ? $hwExpires : $hwExpires->format('Y-m-d')) : null;

            $swDays = (int) ($it->warranty_software_days ?? 0);
            $swExpires = $it->warranty_software_expires_at;
            $swExpiresFormatted = $swExpires ? (is_string($swExpires) ? $swExpires : $swExpires->format('Y-m-d')) : null;

            return new SaleItem(
                id: $it->id,
                saleId: $it->sale_id,
                productId: $it->product_id,
                productName: $it->product_name,
                productSku: $it->product_sku ?? '',
                quantity: (int) $it->quantity,
                unitCost: (float) $it->unit_cost,
                unitPrice: (float) $it->unit_price,
                subtotal: (float) $it->subtotal,
                warrantyDays: $hwDays,
                warrantyExpiresAt: $hwExpiresFormatted,
                serialNumber: $it->serial_number,
                warrantyHardwareDays: $hwDays,
                warrantyHardwareExpiresAt: $hwExpiresFormatted,
                warrantySoftwareDays: $swDays,
                warrantySoftwareExpiresAt: $swExpiresFormatted
            );
        })->all();

        return new Sale(
            id: $m->id,
            invoiceNumber: $m->invoice_number,
            quoteId: $m->quote_id,
            sellerId: $m->seller_id,
            clientId: $m->client_id,
            clientName: $m->client_name,
            clientNitCi: $m->client_nit_ci,
            cashShiftId: $m->cash_shift_id,
            paymentMethod: $m->payment_method,
            subtotal: (float) $m->subtotal,
            discountAmount: (float) $m->discount_amount,
            totalAmount: (float) $m->total_amount,
            cashTendered: $m->cash_tendered !== null ? (float) $m->cash_tendered : null,
            changeDue: $m->change_due !== null ? (float) $m->change_due : null,
            commissionRate: (float) $m->commission_rate,
            commissionAmount: (float) $m->commission_amount,
            status: $m->status,
            paymentMethodId: $m->payment_method_id,
            referenceNumber: $m->reference_number,
            paymentMethodName: $m->paymentMethod?->name,
            cancellationReason: $m->cancellation_reason,
            cancelledBy: $m->cancelled_by,
            cancelledAt: $m->cancelled_at?->format('Y-m-d H:i:s'),
            sellerName: $m->seller?->name,
            cancelledByName: $m->cancelledByUser?->name,
            createdAt: $m->created_at?->format('Y-m-d H:i:s'),
            items: $items
        );
    }
}
