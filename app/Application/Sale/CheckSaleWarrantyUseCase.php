<?php

namespace App\Application\Sale;

use App\Domain\Sale\SaleRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\SaleReturnItemModel;
use Carbon\Carbon;
use DomainException;

final readonly class CheckSaleWarrantyUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository
    ) {}

    public function execute(int $saleId): array
    {
        $sale = $this->saleRepository->findById($saleId);

        if ($sale === null) {
            throw new DomainException('Venta no encontrada.');
        }

        $itemsSummary = [];

        foreach ($sale->items as $item) {
            $returnedQty = (int) SaleReturnItemModel::where('sale_item_id', $item->id)->sum('quantity');
            $remainingQty = max(0, $item->quantity - $returnedQty);

            $isValid = false;
            $daysRemaining = 0;

            if ($item->warrantyExpiresAt !== null) {
                $expiry = Carbon::parse($item->warrantyExpiresAt)->endOfDay();
                $now = Carbon::now();
                if ($now->lessThanOrEqualTo($expiry)) {
                    $isValid = true;
                    $daysRemaining = (int) ceil($now->floatDiffInDays($expiry, false));
                } else {
                    $isValid = false;
                    $daysRemaining = 0;
                }
            }

            $itemsSummary[] = [
                'sale_item_id' => $item->id,
                'product_id' => $item->productId,
                'product_name' => $item->productName,
                'original_quantity' => $item->quantity,
                'returned_quantity' => $returnedQty,
                'remaining_quantity' => $remainingQty,
                'unit_price' => $item->unitPrice,
                'warranty_days' => $item->warrantyDays,
                'warranty_expires_at' => $item->warrantyExpiresAt,
                'is_warranty_valid' => $isValid,
                'days_remaining' => $daysRemaining,
                'serial_number' => $item->serialNumber,
            ];
        }

        return [
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoiceNumber,
            'sale_date' => $sale->createdAt,
            'client_name' => $sale->clientName,
            'status' => $sale->status,
            'items' => $itemsSummary,
        ];
    }
}
