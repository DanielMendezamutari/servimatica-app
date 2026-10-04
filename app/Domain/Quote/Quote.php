<?php

namespace App\Domain\Quote;

final readonly class Quote
{
    /**
     * @param QuoteItem[] $items
     */
    public function __construct(
        public int $id,
        public string $quoteNumber,
        public int $sellerId,
        public ?int $clientId = null,
        public string $clientName = 'Cliente General',
        public ?string $clientPhone = null,
        public float $subtotal = 0.00,
        public float $discountAmount = 0.00,
        public float $totalAmount = 0.00,
        public string $validUntil = '',
        public string $status = 'active',
        public ?string $notes = null,
        public ?string $sellerName = null,
        public ?string $createdAt = null,
        public array $items = [],
        public ?string $publicToken = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'quote_number' => $this->quoteNumber,
            'public_token' => $this->publicToken,
            'seller_id' => $this->sellerId,
            'seller_name' => $this->sellerName,
            'client_id' => $this->clientId,
            'client_name' => $this->clientName,
            'client_phone' => $this->clientPhone,
            'subtotal' => number_format($this->subtotal, 2, '.', ''),
            'discount_amount' => number_format($this->discountAmount, 2, '.', ''),
            'total_amount' => number_format($this->totalAmount, 2, '.', ''),
            'valid_until' => $this->validUntil,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'items' => array_map(fn($it) => $it instanceof QuoteItem ? $it->toArray() : $it, $this->items),
        ];
    }
}

