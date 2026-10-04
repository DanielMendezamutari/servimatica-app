<?php

namespace App\Application\Sale;

use App\Domain\Sale\Sale;
use App\Domain\Sale\SaleRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\StockMovementModel;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class CancelSaleUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository
    ) {}

    public function execute(int $saleId, int $ownerUserId, string $reason): Sale
    {
        $sale = $this->saleRepository->findById($saleId);

        if ($sale === null) {
            throw new DomainException('La venta solicitada no existe.');
        }

        if ($sale->status === 'cancelled') {
            throw new DomainException('Esta venta ya fue anulada previamente.');
        }

        if (trim($reason) === '') {
            throw new DomainException('Debe especificar un motivo para anular la venta.');
        }

        return DB::transaction(function () use ($sale, $ownerUserId, $reason) {
            // Reposición atómica de stock de cada producto
            foreach ($sale->items as $item) {
                /** @var ProductModel|null $product */
                $product = ProductModel::where('id', $item->productId)->lockForUpdate()->first();

                if ($product !== null) {
                    $prevStock = (int) $product->stock;
                    $newStock = $prevStock + $item->quantity;
                    $product->stock = $newStock;
                    $product->save();

                    StockMovementModel::create([
                        'product_id' => $product->id,
                        'user_id' => $ownerUserId,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'previous_stock' => $prevStock,
                        'new_stock' => $newStock,
                        'reason' => "Reposición por anulación de venta {$sale->invoiceNumber}: {$reason}",
                        'unit_cost' => $item->unitCost,
                        'total_cost' => round($item->quantity * $item->unitCost, 2),
                        'reference_type' => 'sale_cancellation',
                        'reference_id' => $sale->id,
                    ]);
                }
            }

            // Anular venta e invalidar comisión en base de datos
            $this->saleRepository->cancel($sale->id, $ownerUserId, $reason);

            return $this->saleRepository->findById($sale->id);
        });
    }
}
