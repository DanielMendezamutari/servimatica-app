<?php

namespace App\Application\Sale;

use App\Domain\Quote\QuoteRepositoryInterface;
use App\Domain\Sale\Sale;
use DomainException;

final readonly class ConvertQuoteToSaleUseCase
{
    public function __construct(
        private QuoteRepositoryInterface $quoteRepository,
        private ProcessSaleUseCase $processSaleUseCase
    ) {}

    public function execute(int $quoteId, int $cashShiftId, string $paymentMethod, ?float $cashTendered, int $sellerId): Sale
    {
        $quote = $this->quoteRepository->findById($quoteId);

        if ($quote === null) {
            throw new DomainException('La cotización indicada no existe.');
        }

        if ($quote->status === 'converted') {
            throw new DomainException('Esta cotización ya fue convertida a venta anteriormente.');
        }

        $items = [];
        foreach ($quote->items as $item) {
            $items[] = [
                'product_id' => $item->productId,
                'product_name' => $item->productName,
                'quantity' => $item->quantity,
                'unit_price' => $item->unitPrice,
            ];
        }

        $saleData = [
            'quote_id' => $quote->id,
            'client_id' => $quote->clientId,
            'client_name' => $quote->clientName,
            'client_nit_ci' => null,
            'cash_shift_id' => $cashShiftId,
            'payment_method' => $paymentMethod,
            'discount_amount' => $quote->discountAmount,
            'cash_tendered' => $cashTendered,
            'items' => $items,
        ];

        return $this->processSaleUseCase->execute($saleData, $sellerId);
    }
}
