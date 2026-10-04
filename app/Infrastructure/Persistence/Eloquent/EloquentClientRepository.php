<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Client\Client;
use App\Domain\Client\ClientRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EloquentClientRepository implements ClientRepositoryInterface
{
    public function findById(int $id): ?Client
    {
        $model = ClientModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function paginate(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        $query = ClientModel::query();

        if (! empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', $term)
                    ->orWhere('nit_ci', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if (! empty($filters['client_type']) && $filters['client_type'] !== 'all') {
            $query->where('client_type', $filters['client_type']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $total = $query->count();

        // Subconsultas para métricas agregadas por cliente sin N+1
        $items = $query->orderBy('id', 'desc')
            ->forPage($page, $perPage)
            ->get();

        $clientIds = $items->pluck('id')->all();

        // Totales de ventas agregados
        $salesAgg = DB::table('sales')
            ->whereIn('client_id', $clientIds)
            ->where('status', '!=', 'cancelled')
            ->groupBy('client_id')
            ->select(
                'client_id',
                DB::raw('COALESCE(SUM(total_amount), 0) as total_spent'),
                DB::raw('COUNT(id) as sales_count'),
                DB::raw('MAX(created_at) as last_purchase_at')
            )
            ->get()
            ->keyBy('client_id');

        $data = $items->map(function ($m) use ($salesAgg) {
            $domain = $this->toDomain($m);
            $arr = $domain->toArray();
            $agg = $salesAgg->get($m->id);

            $arr['total_spent_bs'] = $agg ? round((float) $agg->total_spent, 2) : 0.00;
            $arr['sales_count'] = $agg ? (int) $agg->sales_count : 0;
            $arr['last_purchase_at'] = $agg?->last_purchase_at ? Carbon::parse($agg->last_purchase_at)->toDateTimeString() : null;

            return $arr;
        })->all();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / max(1, $perPage)),
            ],
        ];
    }

    public function search(?string $query = null, int $perPage = 20): array
    {
        $q = ClientModel::query()->where('is_active', true);

        if ($query && trim($query) !== '') {
            $term = '%' . trim($query) . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('name', 'like', $term)
                    ->orWhere('nit_ci', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        $items = $q->orderBy('name')->limit($perPage)->get();

        return $items->map(fn($m) => $this->toDomain($m)->toArray())->all();
    }

    public function save(array $data): Client
    {
        $model = ClientModel::create([
            'name' => $data['name'],
            'nit_ci' => $data['nit_ci'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'client_type' => $data['client_type'] ?? 'final',
            'city' => $data['city'] ?? 'Trinidad',
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->toDomain($model);
    }

    public function update(int $id, array $data): Client
    {
        $model = ClientModel::findOrFail($id);
        $model->update($data);
        return $this->toDomain($model->fresh());
    }

    public function toggleStatus(int $id): Client
    {
        $model = ClientModel::findOrFail($id);
        $model->is_active = ! $model->is_active;
        $model->save();
        return $this->toDomain($model);
    }

    public function getClientStats(int $id): array
    {
        $salesAgg = DB::table('sales')
            ->where('client_id', $id)
            ->where('status', '!=', 'cancelled')
            ->select(
                DB::raw('COALESCE(SUM(total_amount), 0) as total_spent'),
                DB::raw('COUNT(id) as sales_count'),
                DB::raw('MAX(created_at) as last_purchase_at')
            )
            ->first();

        $quotesCount = DB::table('quotes')
            ->where('client_id', $id)
            ->count();

        // Conteo de garantías vigentes
        $warranties = $this->getClientWarranties($id);
        $activeWarrantiesCount = count(array_filter($warranties, fn($w) => $w['is_valid']));

        return [
            'total_spent_bs' => $salesAgg ? round((float) $salesAgg->total_spent, 2) : 0.00,
            'sales_count' => $salesAgg ? (int) $salesAgg->sales_count : 0,
            'quotes_count' => $quotesCount,
            'active_warranties_count' => $activeWarrantiesCount,
            'last_purchase_at' => $salesAgg?->last_purchase_at ? Carbon::parse($salesAgg->last_purchase_at)->toDateTimeString() : null,
        ];
    }

    public function getClientSales(int $id, int $perPage = 10, int $page = 1): array
    {
        $query = DB::table('sales')
            ->where('client_id', $id)
            ->orderBy('id', 'desc');

        $total = $query->count();
        $records = $query->forPage($page, $perPage)->get();

        $saleIds = $records->pluck('id')->all();
        $itemsCounts = DB::table('sale_items')
            ->whereIn('sale_id', $saleIds)
            ->groupBy('sale_id')
            ->select('sale_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('COUNT(id) as items_count'))
            ->get()
            ->keyBy('sale_id');

        $data = $records->map(function ($s) use ($itemsCounts) {
            $itemMeta = $itemsCounts->get($s->id);
            return [
                'id' => $s->id,
                'ticket_code' => $s->invoice_number ?? "V-{$s->id}",
                'total' => round((float) $s->total_amount, 2),
                'payment_method' => $s->payment_method ?? 'Efectivo',
                'status' => $s->status,
                'items_count' => $itemMeta ? (int) $itemMeta->items_count : 0,
                'total_units' => $itemMeta ? (int) $itemMeta->total_qty : 0,
                'created_at' => Carbon::parse($s->created_at)->toDateTimeString(),
            ];
        })->all();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / max(1, $perPage)),
            ],
        ];
    }

    public function getClientQuotes(int $id, int $perPage = 10, int $page = 1): array
    {
        $query = DB::table('quotes')
            ->where('client_id', $id)
            ->orderBy('id', 'desc');

        $total = $query->count();
        $records = $query->forPage($page, $perPage)->get();

        $quoteIds = $records->pluck('id')->all();
        $itemsCounts = DB::table('quote_items')
            ->whereIn('quote_id', $quoteIds)
            ->groupBy('quote_id')
            ->select('quote_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('COUNT(id) as items_count'))
            ->get()
            ->keyBy('quote_id');

        $data = $records->map(function ($q) use ($itemsCounts) {
            $itemMeta = $itemsCounts->get($q->id);
            return [
                'id' => $q->id,
                'quote_number' => $q->quote_number ?? "COT-{$q->id}",
                'total' => round((float) $q->total_amount, 2),
                'status' => $q->status ?? 'pending',
                'items_count' => $itemMeta ? (int) $itemMeta->items_count : 0,
                'created_at' => Carbon::parse($q->created_at)->toDateTimeString(),
            ];
        })->all();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / max(1, $perPage)),
            ],
        ];
    }

    public function getClientWarranties(int $id): array
    {
        $items = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.client_id', $id)
            ->where('sales.status', '!=', 'cancelled')
            ->where('sale_items.warranty_days', '>', 0)
            ->select(
                'sale_items.id as item_id',
                'sale_items.product_name',
                'sale_items.serial_number',
                'sale_items.warranty_days',
                'sale_items.quantity',
                'sale_items.unit_price',
                'sales.id as sale_id',
                'sales.invoice_number as ticket_code',
                'sales.created_at as sale_date'
            )
            ->orderBy('sales.created_at', 'desc')
            ->get();

        $now = Carbon::now();

        return $items->map(function ($row) use ($now) {
            $saleDate = Carbon::parse($row->sale_date);
            $warrantyDays = (int) $row->warranty_days;
            $expiresAt = (clone $saleDate)->addDays($warrantyDays);
            $isValid = $expiresAt->greaterThanOrEqualTo($now);
            $daysRemaining = $isValid ? (int) $now->diffInDays($expiresAt, false) : 0;

            return [
                'id' => $row->item_id,
                'product_name' => $row->product_name,
                'serial_number' => $row->serial_number ?: 'S/N no registrado',
                'quantity' => (int) $row->quantity,
                'unit_price' => round((float) $row->unit_price, 2),
                'sale_id' => $row->sale_id,
                'ticket_code' => $row->ticket_code ?? "V-{$row->sale_id}",
                'sale_date' => $saleDate->toDateTimeString(),
                'warranty_days' => $warrantyDays,
                'warranty_until' => $expiresAt->toDateTimeString(),
                'is_valid' => $isValid,
                'days_remaining' => $daysRemaining,
                'status_label' => $isValid ? "Vigente ({$daysRemaining} días restantes)" : 'Expirada',
            ];
        })->all();
    }

    public function getAllForExport(): array
    {
        $clients = ClientModel::query()->orderBy('name')->get();
        $clientIds = $clients->pluck('id')->all();

        $salesAgg = DB::table('sales')
            ->whereIn('client_id', $clientIds)
            ->where('status', '!=', 'cancelled')
            ->groupBy('client_id')
            ->select(
                'client_id',
                DB::raw('COALESCE(SUM(total_amount), 0) as total_spent'),
                DB::raw('COUNT(id) as sales_count'),
                DB::raw('MAX(created_at) as last_purchase_at')
            )
            ->get()
            ->keyBy('client_id');

        return $clients->map(function ($m) use ($salesAgg) {
            $domain = $this->toDomain($m);
            $arr = $domain->toArray();
            $agg = $salesAgg->get($m->id);

            $arr['total_spent_bs'] = $agg ? round((float) $agg->total_spent, 2) : 0.00;
            $arr['sales_count'] = $agg ? (int) $agg->sales_count : 0;
            $arr['last_purchase_at'] = $agg?->last_purchase_at ? Carbon::parse($agg->last_purchase_at)->toDateTimeString() : null;

            return $arr;
        })->all();
    }

    private function toDomain(ClientModel $m): Client
    {
        return new Client(
            id: $m->id,
            name: $m->name,
            nitCi: $m->nit_ci,
            phone: $m->phone,
            email: $m->email,
            address: $m->address,
            clientType: $m->client_type ?? 'final',
            city: $m->city ?? 'Trinidad',
            notes: $m->notes,
            isActive: (bool) $m->is_active,
            createdAt: $m->created_at?->toIso8601String(),
            updatedAt: $m->updated_at?->toIso8601String()
        );
    }
}
