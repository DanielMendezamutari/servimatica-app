<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Quote\Quote;
use App\Domain\Quote\QuoteItem;
use App\Domain\Quote\QuoteRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentQuoteRepository implements QuoteRepositoryInterface
{
    public function findById(int $id): ?Quote
    {
        $model = QuoteModel::with(['items', 'seller', 'client'])->find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByQuoteNumber(string $quoteNumber): ?Quote
    {
        $model = QuoteModel::with(['items', 'seller', 'client'])->where('quote_number', $quoteNumber)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByPublicToken(string $token): ?Quote
    {
        $model = QuoteModel::with(['items', 'seller', 'client'])->where('public_token', $token)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function save(array $quoteData, array $itemsData): Quote

    {
        return DB::transaction(function () use ($quoteData, $itemsData) {
            $quote = QuoteModel::create($quoteData);

            foreach ($itemsData as $it) {
                QuoteItemModel::create([
                    'quote_id' => $quote->id,
                    'product_id' => $it['product_id'],
                    'product_name' => $it['product_name'],
                    'quantity' => $it['quantity'],
                    'unit_price' => $it['unit_price'],
                    'subtotal' => $it['quantity'] * $it['unit_price'],
                ]);
            }

            return $this->toDomain($quote->fresh(['items', 'seller', 'client']));
        });
    }

    public function updateStatus(int $id, string $status): void
    {
        QuoteModel::where('id', $id)->update(['status' => $status]);
    }

    public function paginate(int $page = 1, int $perPage = 15, ?string $search = null, ?int $sellerId = null): array
    {
        $q = QuoteModel::with(['items', 'seller', 'client'])->orderByDesc('id');

        if ($sellerId) {
            $q->where('seller_id', $sellerId);
        }

        if ($search && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('quote_number', 'like', $term)
                    ->orWhere('client_name', 'like', $term)
                    ->orWhere('client_phone', 'like', $term);
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

    public function getNextCorrelativeNumber(): string
    {
        $last = QuoteModel::latest('id')->first();
        $next = $last ? ($last->id + 1) : 1;
        return 'PRF-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }

    private function toDomain(QuoteModel $m): Quote
    {
        $items = $m->items->map(function ($it) {
            return new QuoteItem(
                id: $it->id,
                quoteId: $it->quote_id,
                productId: $it->product_id,
                productName: $it->product_name,
                quantity: (int) $it->quantity,
                unitPrice: (float) $it->unit_price,
                subtotal: (float) $it->subtotal
            );
        })->all();

        return new Quote(
            id: $m->id,
            quoteNumber: $m->quote_number,
            sellerId: $m->seller_id,
            clientId: $m->client_id,
            clientName: $m->client_name,
            clientPhone: $m->client_phone,
            subtotal: (float) $m->subtotal,
            discountAmount: (float) $m->discount_amount,
            totalAmount: (float) $m->total_amount,
            validUntil: $m->valid_until?->format('Y-m-d') ?? '',
            status: $m->status,
            notes: $m->notes,
            sellerName: $m->seller?->name,
            createdAt: $m->created_at?->format('Y-m-d H:i:s'),
            items: $items,
            publicToken: $m->public_token
        );
    }
}

