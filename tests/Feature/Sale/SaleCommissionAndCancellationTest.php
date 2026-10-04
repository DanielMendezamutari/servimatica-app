<?php

namespace Tests\Feature\Sale;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\StockMovementModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCommissionAndCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function ownerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    private function createSellerWithCommission(float $commissionRate): array
    {
        $ownerHeaders = $this->ownerHeaders();
        $createRes = $this->postJson('/api/users', [
            'name' => 'Carlos Vendedor',
            'username' => 'carlosv',
            'email' => 'carlos@servimatica.com',
            'password' => 'password123',
            'pin' => '5555',
            'role' => 'vendedor',
        ], $ownerHeaders)->assertCreated();

        $sellerId = $createRes->json('data.id');

        // Actualizar sales_commission
        $seller = User::find($sellerId);
        $seller->sales_commission = $commissionRate;
        $seller->save();

        $token = $this->postJson('/api/auth/login', ['login' => 'carlosv', 'pin' => '5555'])->json('accessToken');

        return [
            'headers' => ['Authorization' => "Bearer $token"],
            'user' => $seller,
            'ownerHeaders' => $ownerHeaders,
        ];
    }

    public function test_sale_commission_and_owner_cancellation_with_stock_restoration(): void
    {
        $setup = $this->createSellerWithCommission(3.00); // 3% de comisión
        $sellerHeaders = $setup['headers'];
        $ownerHeaders = $setup['ownerHeaders'];
        $seller = $setup['user'];

        $cat = CategoryModel::first();
        $product = ProductModel::create([
            'name' => 'Disco SSD Kingston 480GB',
            'category_id' => $cat->id,
            'sku' => 'SSD-KING-480',
            'cost_price' => 180.00,
            'sale_price' => 250.00,
            'stock' => 10,
            'min_stock' => 2,
            'status' => 'active',
        ]);

        // 1. Vendedor abre turno de caja
        $shiftRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 50.00,
        ], $sellerHeaders)->assertStatus(201);

        $shiftId = $shiftRes->json('data.id');

        // 2. Venta de 2 unidades @ 250 = Bs. 500.00 en efectivo
        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $shiftId,
            'payment_method' => 'efectivo',
            'client_name' => 'Cliente Corporativo',
            'cash_tendered' => 500.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 250.00,
                ],
            ],
        ], $sellerHeaders)->assertStatus(201);

        $saleId = $saleRes->json('data.id');

        // Comisión esperada: 500 * 0.03 = 15.00 Bs.
        $this->assertEquals('15.00', $saleRes->json('data.commission_amount'));
        $this->assertEquals('3.00', $saleRes->json('data.commission_rate'));

        // Stock bajó a 8
        $this->assertEquals(8, (int) ProductModel::find($product->id)->stock);

        // 3. Dueño consulta reporte de comisiones
        $repRes = $this->getJson('/api/sales/commissions-report', $ownerHeaders)->assertOk();
        $this->assertCount(1, $repRes->json('data'));
        $this->assertEquals('15.00', $repRes->json('data.0.total_commission_amount'));
        $this->assertEquals('500.00', $repRes->json('data.0.total_sales_amount'));

        // 4. Vendedor intenta anular la venta -> 403 Forbidden
        $this->postJson("/api/sales/{$saleId}/cancel", [
            'reason' => 'Error de digitación',
        ], $sellerHeaders)->assertStatus(403);

        // 5. Dueño anula la venta
        $cancelRes = $this->postJson("/api/sales/{$saleId}/cancel", [
            'reason' => 'Cliente devolvió el producto y canceló la compra',
        ], $ownerHeaders)->assertOk();

        $this->assertEquals('cancelled', $cancelRes->json('data.status'));
        $this->assertEquals('0.00', $cancelRes->json('data.commission_amount'));

        // 6. VERIFICACIÓN CRÍTICA: Stock del producto REPUESTO exactamente a 10
        $productFresh = ProductModel::find($product->id);
        $this->assertEquals(10, (int) $productFresh->stock, 'El stock debió reponerse a 10 tras anular la venta.');

        // Movimiento de reposición registrado en stock_movements
        $lastMovement = StockMovementModel::where('product_id', $product->id)->latest('id')->first();
        $this->assertEquals('in', $lastMovement->type);
        $this->assertEquals(2, $lastMovement->quantity);
        $this->assertEquals(10, $lastMovement->new_stock);

        // 7. En el reporte de comisiones ya no debe aparecer la venta cancelada
        $repAfter = $this->getJson('/api/sales/commissions-report', $ownerHeaders)->assertOk();
        $this->assertEmpty($repAfter->json('data'));
    }
}
