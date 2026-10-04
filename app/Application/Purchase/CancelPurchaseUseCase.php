<?php

namespace App\Application\Purchase;

use App\Domain\Purchase\Purchase;
use App\Domain\Purchase\PurchaseRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\StockMovementModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CancelPurchaseUseCase
{
    public function __construct(
        private PurchaseRepositoryInterface $purchaseRepository
    ) {}

    public function execute(int $id, int $cancelledBy, string $reason): Purchase
    {
        $purchase = $this->purchaseRepository->findById($id);

        if (!$purchase) {
            throw ValidationException::withMessages(['purchase' => 'La orden de compra especificada no existe.']);
        }

        if ($purchase->status === 'cancelled') {
            throw ValidationException::withMessages(['purchase' => 'Esta compra ya ha sido anulada previamente.']);
        }

        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages(['reason' => 'Debe ingresar el motivo de anulación.']);
        }

        return DB::transaction(function () use ($purchase, $id, $cancelledBy, $cleanReason) {
            // Validar suficiencia de stock físico para cada ítem antes de aplicar descuentos
            foreach ($purchase->items as $item) {
                /** @var ProductModel $product */
                $product = ProductModel::lockForUpdate()->find($item->productId);
                if (!$product) {
                    throw ValidationException::withMessages(['purchase' => "El producto {$item->productName} ya no existe en el catálogo."]);
                }

                if ($product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'purchase' => "No se puede anular la compra. El producto '{$product->name}' solo tiene {$product->stock} unidades disponibles en inventario (se requieren {$item->quantity} para la devolución)."
                    ]);
                }
            }

            // Aplicar descuento de stock y registrar movimientos de auditoría inmutable
            foreach ($purchase->items as $item) {
                /** @var ProductModel $product */
                $product = ProductModel::lockForUpdate()->find($item->productId);
                $previousStock = (int)$product->stock;
                $newStock = $previousStock - $item->quantity;

                $product->update(['stock' => $newStock]);

                StockMovementModel::create([
                    'product_id' => $product->id,
                    'user_id' => $cancelledBy,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reason' => "Anulación de Compra {$purchase->purchaseNumber} - Doc {$purchase->invoiceNumber}. Motivo: {$cleanReason}",
                ]);
            }

            return $this->purchaseRepository->cancel($id, $cancelledBy, $cleanReason);
        });
    }
}
