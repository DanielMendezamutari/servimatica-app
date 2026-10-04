<?php

namespace Tests\Feature\Sale;

use App\Infrastructure\Persistence\Eloquent\CashShiftModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSaleTest extends TestCase
{
    use RefreshDatabase;

    private function sellerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_pos_sale_atomic_stock_decrement_and_cash_shift_update(): void
    {
        $headers = $this->sellerHeaders();
        $cat = CategoryModel::first();

        // 1. Crear producto con stock 5
        $product = ProductModel::create([
            'name' => 'Mouse Inalámbrico Logitech',
            'category_id' => $cat->id,
            'sku' => 'MOU-LOGI-01',
            'cost_price' => 50.00,
            'sale_price' => 85.00,
            'stock' => 5,
            'min_stock' => 1,
            'status' => 'active',
        ]);

        // 2. Intentar vender sin haber abierto caja debe rebotar
        $failSale = $this->postJson('/api/sales', [
            'cash_shift_id' => 9999,
            'payment_method' => 'efectivo',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 85.00],
            ],
        ], $headers)->assertStatus(422);

        $this->assertStringContainsString('caja', $failSale->json('message'));

        // 3. Abrir turno de caja con Bs. 100.00
        $openShiftRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 100.00,
        ], $headers)->assertStatus(201);

        $cashShiftId = $openShiftRes->json('data.id');

        // 4. Procesar venta en efectivo: 2 unidades @ 85.00 = 170.00, efectivo entregado 200.00, vuelto 30.00
        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'efectivo',
            'client_name' => 'Comprador Mostrador',
            'discount_amount' => 0.00,
            'cash_tendered' => 200.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 85.00,
                ],
            ],
        ], $headers)->assertStatus(201);

        $saleId = $saleRes->json('data.id');
        $this->assertStringStartsWith('VTA-', $saleRes->json('data.invoice_number'));
        $this->assertEquals('170.00', $saleRes->json('data.total_amount'));
        $this->assertEquals('200.00', $saleRes->json('data.cash_tendered'));
        $this->assertEquals('30.00', $saleRes->json('data.change_due'));

        // 5. VERIFICACIÓN CRÍTICA: Stock decrementado exactamente a 3
        $productFresh = ProductModel::find($product->id);
        $this->assertEquals(3, (int) $productFresh->stock);

        // 6. VERIFICACIÓN CRÍTICA: Acumulador de efectivo en caja actualizado a 170.00
        $shiftFresh = CashShiftModel::find($cashShiftId);
        $this->assertEquals('170.00', $shiftFresh->total_cash_sales);
        $this->assertEquals('0.00', $shiftFresh->total_qr_sales);

        // 7. Venta con QR: 1 unidad @ 85.00 = 85.00
        $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'qr',
            'client_name' => 'Cliente Pago QR',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 85.00,
                ],
            ],
        ], $headers)->assertStatus(201);

        // Stock ahora debe ser 2
        $this->assertEquals(2, (int) ProductModel::find($product->id)->stock);

        // Acumulador QR en caja actualizado a 85.00
        $shiftFresh->refresh();
        $this->assertEquals('85.00', $shiftFresh->total_qr_sales);

        // 8. Verificar emisión de ticket térmico 80mm
        $receiptRes = $this->get("/api/sales/{$saleId}/receipt", $headers)->assertOk();
        $this->assertStringContainsString('COMPROBANTE DE VENTA', $receiptRes->getContent());
        $this->assertStringContainsString('Mouse Inalámbrico Logitech', $receiptRes->getContent());
    }
}
