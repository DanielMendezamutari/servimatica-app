<?php

namespace App\Application\Quote;

use App\Domain\Quote\Quote;
use App\Domain\Quote\QuoteRepositoryInterface;
use Carbon\Carbon;
use DomainException;

final readonly class CreateQuoteUseCase
{
    public function __construct(
        private QuoteRepositoryInterface $repository
    ) {}

    public function execute(array $data, int $sellerId): Quote
    {
        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new DomainException('La proforma debe incluir al menos un producto.');
        }

        $subtotal = 0.00;
        $itemsData = [];

        foreach ($items as $it) {
            $qty = (int) ($it['quantity'] ?? 1);
            $unitPrice = (float) ($it['unit_price'] ?? 0.00);

            if ($qty <= 0) {
                throw new DomainException('La cantidad de cada ítem debe ser mayor a cero.');
            }
            if ($unitPrice < 0) {
                throw new DomainException('El precio unitario no puede ser negativo.');
            }

            $itemSubtotal = $qty * $unitPrice;
            $subtotal += $itemSubtotal;

            $itemsData[] = [
                'product_id' => $it['product_id'],
                'product_name' => $it['product_name'],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $itemSubtotal,
            ];
        }

        $discountAmount = (float) ($data['discount_amount'] ?? 0.00);
        if ($discountAmount < 0) {
            throw new DomainException('El descuento no puede ser negativo.');
        }
        if ($discountAmount > $subtotal) {
            throw new DomainException('El descuento no puede ser superior al subtotal.');
        }

        $totalAmount = $subtotal - $discountAmount;
        $quoteNumber = $this->repository->getNextCorrelativeNumber();

        $validDays = (int) ($data['valid_days'] ?? 7);
        $validUntil = Carbon::now()->addDays($validDays)->toDateString();

        $quoteData = [
            'quote_number' => $quoteNumber,
            'seller_id' => $sellerId,
            'client_id' => $data['client_id'] ?? null,
            'client_name' => trim($data['client_name'] ?? 'Cliente General'),
            'client_phone' => trim($data['client_phone'] ?? '') ?: null,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'valid_until' => $validUntil,
            'status' => 'active',
            'notes' => $data['notes'] ?? null,
        ];

        return $this->repository->save($quoteData, $itemsData);
    }
}
