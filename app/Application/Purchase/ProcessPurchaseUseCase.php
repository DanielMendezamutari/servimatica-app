<?php

namespace App\Application\Purchase;

use App\Domain\Purchase\Purchase;
use App\Domain\Purchase\PurchaseRepositoryInterface;
use App\Domain\Supplier\SupplierRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\StockMovementModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ProcessPurchaseUseCase
{
    public function __construct(
        private PurchaseRepositoryInterface $purchaseRepository,
        private SupplierRepositoryInterface $supplierRepository
    ) {}

    public function execute(array $data): Purchase
    {
        $supplierId = (int)$data['supplier_id'];
        $supplier = $this->supplierRepository->findById($supplierId);

        if (!$supplier) {
            throw ValidationException::withMessages(['supplier_id' => 'El proveedor especificado no existe.']);
        }

        if (!$supplier->isActive) {
            throw ValidationException::withMessages(['supplier_id' => 'El proveedor seleccionado se encuentra inactivo.']);
        }

        $itemsInput = $data['items'] ?? [];
        if (empty($itemsInput)) {
            throw ValidationException::withMessages(['items' => 'Debe ingresar al menos un producto en la compra.']);
        }

        return DB::transaction(function () use ($data, $supplier, $itemsInput) {
            $preparedItems = [];
            $stockMovementsToCreate = [];
            $totalAmount = 0.0;
            $invoiceNum = trim($data['invoice_number']);

            foreach ($itemsInput as $index => $itemData) {
                $productId = (int)$itemData['product_id'];
                $quantity = (int)$itemData['quantity'];
                $unitCost = (float)$itemData['unit_cost'];
                $newSalePrice = !empty($itemData['new_sale_price']) ? (float)$itemData['new_sale_price'] : null;

                if ($quantity <= 0) {
                    throw ValidationException::withMessages(["items.{$index}.quantity" => 'La cantidad ingresada debe ser mayor a cero.']);
                }

                if ($unitCost < 0) {
                    throw ValidationException::withMessages(["items.{$index}.unit_cost" => 'El costo unitario no puede ser negativo.']);
                }

                /** @var ProductModel $product */
                $product = ProductModel::lockForUpdate()->find($productId);
                if (!$product) {
                    throw ValidationException::withMessages(["items.{$index}.product_id" => 'El producto especificado no existe.']);
                }

                $previousCost = $product->cost_price !== null ? (float)$product->cost_price : null;
                $previousSalePrice = $product->sale_price !== null ? (float)$product->sale_price : null;
                $previousStock = (int)$product->stock;
                $newStock = $previousStock + $quantity;
                $itemSubtotal = round($quantity * $unitCost, 2);
                $totalAmount += $itemSubtotal;

                // Actualizar stock, costo de reposición y precio de venta (si aplica)
                $productUpdates = [
                    'stock' => $newStock,
                    'cost_price' => $unitCost,
                ];

                if ($newSalePrice !== null && $newSalePrice > 0) {
                    $productUpdates['sale_price'] = $newSalePrice;
                }

                $product->update($productUpdates);

                $stockMovementsToCreate[] = [
                    'product_id' => $product->id,
                    'user_id' => (int)$data['user_id'],
                    'type' => 'in',
                    'quantity' => $quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reason' => "Compra Proveedor {$supplier->name} - Doc {$invoiceNum}",
                    'unit_cost' => $unitCost,
                    'total_cost' => $itemSubtotal,
                    'reference_type' => 'purchase',
                ];

                $preparedItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'subtotal' => $itemSubtotal,
                    'previous_cost' => $previousCost,
                    'previous_sale_price' => $previousSalePrice,
                    'new_sale_price' => $newSalePrice,
                ];
            }

            $paymentMethodId = !empty($data['payment_method_id']) ? (int)$data['payment_method_id'] : null;
            $referenceNumber = !empty($data['reference_number']) ? trim($data['reference_number']) : null;
            $paymentMethod = $data['payment_method'] ?? 'transferencia';

            if ($paymentMethodId !== null) {
                $pm = \App\Infrastructure\Persistence\Eloquent\PaymentMethodModel::find($paymentMethodId);
                if ($pm) {
                    $paymentMethod = match ($pm->type) {
                        'cash' => 'efectivo',
                        'bank_transfer' => 'transferencia',
                        default => 'otro',
                    };
                }
            }

            $purchaseData = [
                'purchase_number' => $this->purchaseRepository->generateNextPurchaseNumber(),
                'invoice_number' => trim($data['invoice_number']),
                'supplier_id' => $supplier->id,
                'user_id' => (int)$data['user_id'],
                'purchase_date' => $data['purchase_date'],
                'payment_condition' => $data['payment_condition'] ?? 'contado',
                'payment_method' => $paymentMethod,
                'payment_method_id' => $paymentMethodId,
                'reference_number' => $referenceNumber,
                'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
                'subtotal' => $totalAmount,
                'total_amount' => $totalAmount,
                'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            ];

            $purchase = $this->purchaseRepository->save($purchaseData, $preparedItems);

            foreach ($stockMovementsToCreate as $sm) {
                $sm['reference_id'] = $purchase->id;
                StockMovementModel::create($sm);
            }

            return $purchase;
        });
    }
}
