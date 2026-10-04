<?php

namespace App\Application\Sale;

use App\Domain\CashShift\CashShiftRepositoryInterface;
use App\Domain\Sale\SaleRepositoryInterface;
use App\Domain\Sale\SaleReturn;
use App\Domain\Sale\SaleReturnItem;
use App\Domain\Sale\SaleReturnRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\CashShiftModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SaleItemModel;
use App\Infrastructure\Persistence\Eloquent\SaleReturnItemModel;
use App\Infrastructure\Persistence\Eloquent\StockMovementModel;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class ProcessSaleReturnUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
        private SaleReturnRepositoryInterface $saleReturnRepository,
        private CashShiftRepositoryInterface $cashShiftRepository
    ) {}

    public function execute(int $saleId, array $data, int $userId, string $userRole): SaleReturn
    {
        $sale = $this->saleRepository->findById($saleId);

        if ($sale === null) {
            throw new DomainException('Venta no encontrada.');
        }

        if ($sale->status === 'cancelled') {
            throw new DomainException('No se pueden procesar devoluciones sobre una venta ya anulada.');
        }

        $resolution = $data['resolution'] ?? '';
        if (!in_array($resolution, ['cambio_fisico', 'reembolso_efectivo', 'nota_credito'], true)) {
            throw new DomainException('Resolución de devolución inválida.');
        }

        if ($resolution === 'reembolso_efectivo' && !in_array($userRole, ['dueno', 'owner', 'admin'], true)) {
            throw new DomainException('El reembolso de dinero en efectivo es una operación financiera exclusiva del Dueño/Administrador.');
        }

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new DomainException('Debe incluir al menos un producto a devolver.');
        }

        return DB::transaction(function () use ($sale, $saleId, $data, $items, $resolution, $userId) {
            $returnItems = [];
            $totalRefundAmount = 0.00;

            foreach ($items as $itemInput) {
                $saleItemId = (int) $itemInput['sale_item_id'];
                $qty = (int) ($itemInput['quantity'] ?? 0);
                $condition = $itemInput['condition'] ?? 'stock_operativo';
                $serialNumber = !empty($itemInput['serial_number']) ? trim($itemInput['serial_number']) : null;

                if ($qty <= 0) {
                    throw new DomainException('La cantidad a devolver debe ser mayor a 0.');
                }

                if (!in_array($condition, ['stock_operativo', 'stock_defectuoso_rma'], true)) {
                    throw new DomainException('Condición de mercadería no reconocida.');
                }

                /** @var SaleItemModel|null $saleItem */
                $saleItem = SaleItemModel::where('sale_id', $saleId)->where('id', $saleItemId)->first();
                if ($saleItem === null) {
                    throw new DomainException("El ítem #{$saleItemId} no pertenece a esta venta.");
                }

                $prevReturned = (int) SaleReturnItemModel::where('sale_item_id', $saleItemId)->sum('quantity');
                $availableQty = (int) $saleItem->quantity - $prevReturned;
                if ($qty > $availableQty) {
                    throw new DomainException("La cantidad solicitada ({$qty}) supera las unidades disponibles para devolución ({$availableQty}) de '{$saleItem->product_name}'.");
                }

                /** @var ProductModel|null $product */
                $product = ProductModel::where('id', $saleItem->product_id)->lockForUpdate()->first();
                if ($product === null) {
                    throw new DomainException("El producto '{$saleItem->product_name}' ya no existe en el catálogo.");
                }

                // Segregación de inventario según condición física
                if ($condition === 'stock_operativo') {
                    $prevStock = (int) $product->stock;
                    $product->stock = $prevStock + $qty;
                    $product->save();

                    $itemUnitCost = (float) $saleItem->unit_cost;

                    StockMovementModel::create([
                        'product_id' => $product->id,
                        'user_id' => $userId,
                        'type' => 'in',
                        'quantity' => $qty,
                        'previous_stock' => $prevStock,
                        'new_stock' => $product->stock,
                        'reason' => "Devolución a inventario vendible - Factura #{$sale->invoiceNumber}",
                        'unit_cost' => $itemUnitCost,
                        'total_cost' => round($qty * $itemUnitCost, 2),
                        'reference_type' => 'sale_return',
                        'reference_id' => $sale->id,
                    ]);
                } else {
                    // Defectuoso / Cuarentena / RMA
                    $product->defective_stock = (int) $product->defective_stock + $qty;
                    $product->save();

                    $itemUnitCost = (float) $saleItem->unit_cost;

                    StockMovementModel::create([
                        'product_id' => $product->id,
                        'user_id' => $userId,
                        'type' => 'in',
                        'quantity' => $qty,
                        'previous_stock' => (int) $product->stock,
                        'new_stock' => (int) $product->stock,
                        'reason' => "Ingreso a cuarentena técnica/defectuoso (RMA) - Factura #{$sale->invoiceNumber}",
                        'unit_cost' => $itemUnitCost,
                        'total_cost' => round($qty * $itemUnitCost, 2),
                        'reference_type' => 'sale_return',
                        'reference_id' => $sale->id,
                    ]);
                }

                // Si la resolución es cambio físico 1 a 1, descontamos 1 unidad del stock operativo para la reposición
                if ($resolution === 'cambio_fisico') {
                    if ((int) $product->stock < $qty) {
                        throw new DomainException("No hay stock operativo suficiente para realizar el cambio físico 1 a 1 de '{$product->name}'. Disponible: {$product->stock}, Solicitado: {$qty}");
                    }
                    $prevStock = (int) $product->stock;
                    $product->stock = $prevStock - $qty;
                    $product->save();

                    $replacementCost = (float) $product->cost_price;

                    StockMovementModel::create([
                        'product_id' => $product->id,
                        'user_id' => $userId,
                        'type' => 'out',
                        'quantity' => $qty,
                        'previous_stock' => $prevStock,
                        'new_stock' => $product->stock,
                        'reason' => "Entrega de reposición por cambio de garantía 1 a 1 - Factura #{$sale->invoiceNumber}",
                        'unit_cost' => $replacementCost,
                        'total_cost' => round($qty * $replacementCost, 2),
                        'reference_type' => 'warranty_replacement',
                        'reference_id' => $sale->id,
                    ]);
                }

                $itemSubtotal = $qty * (float) $saleItem->unit_price;
                $totalRefundAmount += $itemSubtotal;

                $returnItems[] = new SaleReturnItem(
                    id: null,
                    saleReturnId: 0,
                    saleItemId: $saleItemId,
                    productId: $product->id,
                    productName: $product->name,
                    quantity: $qty,
                    unitPrice: (float) $saleItem->unit_price,
                    subtotal: $itemSubtotal,
                    condition: $condition,
                    serialNumber: $serialNumber ?: $saleItem->serial_number
                );
            }

            // Manejo de egreso en caja chica si la resolución es reembolso_efectivo
            $cashShiftId = null;
            if ($resolution === 'reembolso_efectivo') {
                $cashShiftId = (int) ($data['cash_shift_id'] ?? 0);
                $shift = CashShiftModel::where('id', $cashShiftId)->where('status', 'open')->lockForUpdate()->first();
                if ($shift === null) {
                    throw new DomainException('Debe seleccionar un turno de caja abierto válido para procesar el egreso de dinero.');
                }
                $shift->total_cash_refunds = (float) $shift->total_cash_refunds + $totalRefundAmount;
                $shift->save();
            }

            $nextReturnNumber = $this->saleReturnRepository->getNextReturnNumber();

            $saleReturn = new SaleReturn(
                id: null,
                returnNumber: $nextReturnNumber,
                saleId: $saleId,
                clientId: $sale->clientId,
                clientName: $sale->clientName,
                userId: $userId,
                cashShiftId: $cashShiftId,
                resolution: $resolution,
                totalRefundAmount: $totalRefundAmount,
                reason: trim($data['reason']),
                status: 'completed',
                items: $returnItems
            );

            return $this->saleReturnRepository->save($saleReturn);
        });
    }
}
