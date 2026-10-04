<?php

namespace App\Application\Sale;

use App\Domain\CashShift\CashShiftRepositoryInterface;
use App\Domain\Quote\QuoteRepositoryInterface;
use App\Domain\Sale\Sale;
use App\Domain\Sale\SaleRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\StockMovementModel;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class ProcessSaleUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
        private CashShiftRepositoryInterface $cashShiftRepository,
        private QuoteRepositoryInterface $quoteRepository
    ) {}

    public function execute(array $data, int $sellerId): Sale
    {
        $cashShiftId = (int) ($data['cash_shift_id'] ?? 0);
        $shift = $this->cashShiftRepository->findById($cashShiftId);

        if ($shift === null || $shift->status !== 'open') {
            throw new DomainException('No hay un turno de caja abierto válido para procesar la venta. Debe abrir caja primero.');
        }

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new DomainException('La venta debe incluir al menos un producto.');
        }

        $paymentMethodId = !empty($data['payment_method_id']) ? (int) $data['payment_method_id'] : null;
        $referenceNumber = !empty($data['reference_number']) ? trim($data['reference_number']) : null;
        $paymentMethodModel = null;

        if ($paymentMethodId !== null) {
            $paymentMethodModel = \App\Infrastructure\Persistence\Eloquent\PaymentMethodModel::find($paymentMethodId);
            if ($paymentMethodModel === null || !$paymentMethodModel->is_active) {
                throw new DomainException('La forma de pago seleccionada no existe o está inactiva.');
            }

            if ($paymentMethodModel->requires_reference && empty($referenceNumber)) {
                throw new DomainException("La forma de pago '{$paymentMethodModel->name}' exige obligatoriamente registrar el número de comprobante o referencia.");
            }

            $paymentMethod = match ($paymentMethodModel->type) {
                'cash' => 'efectivo',
                'qr' => 'qr',
                default => 'transferencia',
            };
        } else {
            $paymentMethod = $data['payment_method'] ?? 'efectivo';
            if (!in_array($paymentMethod, ['efectivo', 'qr', 'transferencia'], true)) {
                throw new DomainException('Método de pago no reconocido.');
            }
        }

        return DB::transaction(function () use ($data, $items, $cashShiftId, $paymentMethod, $paymentMethodId, $referenceNumber, $sellerId) {
            $subtotal = 0.00;
            $itemsData = [];
            $stockMovementsToCreate = [];

            foreach ($items as $it) {
                $productId = (int) $it['product_id'];
                $qty = (int) ($it['quantity'] ?? 1);

                if ($qty <= 0) {
                    throw new DomainException('La cantidad debe ser mayor a 0.');
                }

                /** @var ProductModel|null $product */
                $product = ProductModel::where('id', $productId)->lockForUpdate()->first();

                if ($product === null || $product->status !== 'active') {
                    throw new DomainException("El producto solicitado no está disponible o ha sido desactivado.");
                }

                if ((int) $product->stock < $qty) {
                    throw new DomainException("Stock insuficiente para '{$product->name}'. Disponible: {$product->stock}, Solicitado: {$qty}");
                }

                // Descuento atómico de stock
                $prevStock = (int) $product->stock;
                $newStock = $prevStock - $qty;
                $product->stock = $newStock;
                $product->save();

                $unitCost = (float) $product->cost_price;

                // Auditoría de movimiento de inventario con costo y trazabilidad
                $stockMovementsToCreate[] = [
                    'product_id' => $product->id,
                    'user_id' => $sellerId,
                    'type' => 'out',
                    'quantity' => $qty,
                    'previous_stock' => $prevStock,
                    'new_stock' => $newStock,
                    'reason' => 'Venta en mostrador POS',
                    'unit_cost' => $unitCost,
                    'total_cost' => round($qty * $unitCost, 2),
                    'reference_type' => 'sale',
                ];

                $unitPrice = isset($it['unit_price']) ? (float) $it['unit_price'] : (float) $product->sale_price;
                $itemSubtotal = $qty * $unitPrice;
                $subtotal += $itemSubtotal;

                $warrantyDays = isset($it['warranty_days']) ? (int) $it['warranty_days'] : (int) ($product->warranty_days ?? 0);
                $warrantyExpiresAt = $warrantyDays > 0 ? now()->addDays($warrantyDays)->format('Y-m-d H:i:s') : null;
                $serialNumber = !empty($it['serial_number']) ? trim($it['serial_number']) : null;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? '',
                    'quantity' => $qty,
                    'unit_cost' => (float) $product->cost_price, // Preservado confidencialmente
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'warranty_days' => $warrantyDays,
                    'warranty_expires_at' => $warrantyExpiresAt,
                    'serial_number' => $serialNumber,
                ];
            }

            $discountAmount = (float) ($data['discount_amount'] ?? 0.00);
            if ($discountAmount < 0) {
                throw new DomainException('El monto de descuento no puede ser negativo.');
            }
            if ($discountAmount > $subtotal) {
                throw new DomainException('El descuento no puede superar el total de los productos.');
            }

            $totalAmount = $subtotal - $discountAmount;

            $cashTendered = null;
            $changeDue = 0.00;

            if ($paymentMethod === 'efectivo') {
                $cashTendered = isset($data['cash_tendered']) ? (float) $data['cash_tendered'] : $totalAmount;
                if ($cashTendered < $totalAmount) {
                    throw new DomainException('El monto entregado en efectivo es menor al total a cobrar.');
                }
                $changeDue = $cashTendered - $totalAmount;
            } else {
                $cashTendered = $totalAmount;
                $changeDue = 0.00;
            }

            // Comisión del vendedor (usando columna sales_commission de users)
            $seller = User::find($sellerId);
            $commissionRate = (float) ($seller?->sales_commission ?? $seller?->commission_rate ?? 0.00);
            $commissionAmount = round($totalAmount * ($commissionRate / 100), 2);

            $invoiceNumber = $this->saleRepository->getNextInvoiceNumber();

            $quoteId = !empty($data['quote_id']) ? (int) $data['quote_id'] : null;

            $saleData = [
                'invoice_number' => $invoiceNumber,
                'quote_id' => $quoteId,
                'seller_id' => $sellerId,
                'client_id' => !empty($data['client_id']) ? (int) $data['client_id'] : null,
                'client_name' => trim($data['client_name'] ?? 'Cliente Mostrador') ?: 'Cliente Mostrador',
                'client_nit_ci' => trim($data['client_nit_ci'] ?? '') ?: null,
                'cash_shift_id' => $cashShiftId,
                'payment_method' => $paymentMethod,
                'payment_method_id' => $paymentMethodId,
                'reference_number' => $referenceNumber,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'cash_tendered' => $cashTendered,
                'change_due' => $changeDue,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'status' => 'completed',
            ];

            $sale = $this->saleRepository->save($saleData, $itemsData);

            foreach ($stockMovementsToCreate as $sm) {
                $sm['reference_id'] = $sale->id;
                $sm['reason'] = "Venta POS {$sale->invoiceNumber}";
                StockMovementModel::create($sm);
            }

            // Acumular venta en la caja
            $this->cashShiftRepository->recordSale($cashShiftId, $totalAmount, $paymentMethod);

            // Si vino de una cotización, marcar la cotización como convertida
            if ($quoteId !== null) {
                $this->quoteRepository->updateStatus($quoteId, 'converted');
            }

            return $sale;
        });
    }
}
