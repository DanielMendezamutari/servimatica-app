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

            // 1. Evaluación Hardware
            $isHwValid = false;
            $hwDaysRemaining = 0;
            $hwExpiryStr = $item->warrantyHardwareExpiresAt ?: $item->warrantyExpiresAt;
            if (!empty($hwExpiryStr)) {
                $expiry = Carbon::parse($hwExpiryStr)->endOfDay();
                $now = Carbon::now();
                if ($now->lessThanOrEqualTo($expiry)) {
                    $isHwValid = true;
                    $hwDaysRemaining = (int) ceil($now->floatDiffInDays($expiry, false));
                }
            }
            $hwDays = $item->warrantyHardwareDays ?: $item->warrantyDays;
            $hwStatus = $hwDays > 0 ? ($isHwValid ? 'active' : 'expired') : 'none';

            // 2. Evaluación Software
            $isSwValid = false;
            $swDaysRemaining = 0;
            if (!empty($item->warrantySoftwareExpiresAt)) {
                $expiry = Carbon::parse($item->warrantySoftwareExpiresAt)->endOfDay();
                $now = Carbon::now();
                if ($now->lessThanOrEqualTo($expiry)) {
                    $isSwValid = true;
                    $swDaysRemaining = (int) ceil($now->floatDiffInDays($expiry, false));
                }
            }
            $swDays = $item->warrantySoftwareDays;
            $swStatus = $swDays > 0 ? ($isSwValid ? 'active' : 'expired') : 'none';

            $itemsSummary[] = [
                'sale_item_id' => $item->id,
                'product_id' => $item->productId,
                'product_name' => $item->productName,
                'original_quantity' => $item->quantity,
                'returned_quantity' => $returnedQty,
                'remaining_quantity' => $remainingQty,
                'unit_price' => $item->unitPrice,
                // Retrocompatibilidad con vistas legacy
                'warranty_days' => $hwDays,
                'warranty_expires_at' => $hwExpiryStr,
                'is_warranty_valid' => $isHwValid,
                'days_remaining' => $hwDaysRemaining,
                'serial_number' => $item->serialNumber,
                // Nuevos campos duales autónomos
                'warranty_hardware_days' => $hwDays,
                'warranty_hardware_expires_at' => $hwExpiryStr,
                'is_hardware_warranty_valid' => $isHwValid,
                'hardware_days_remaining' => $hwDaysRemaining,
                'hardware_status' => $hwStatus,
                'warranty_software_days' => $swDays,
                'warranty_software_expires_at' => $item->warrantySoftwareExpiresAt,
                'is_software_warranty_valid' => $isSwValid,
                'software_days_remaining' => $swDaysRemaining,
                'software_status' => $swStatus,
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
