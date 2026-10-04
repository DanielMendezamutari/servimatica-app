<?php

namespace App\Domain\Quote;

final readonly class QuoteItem
{
    public function __construct(
        public int $id,
        public int $quoteId,
        public int $productId,
        public string $productName,
        public int $quantity,
        public float $unitPrice,
        public float $subtotal
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'quote_id' => $this->quoteId,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'unit_price' => number_format($this->unitPrice, 2, '.', ''),
            'subtotal' => number_format($this->subtotal, 2, '.', ''),
        ];
    }
}
