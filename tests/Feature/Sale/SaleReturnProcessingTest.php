<?php

namespace Tests\Feature\Sale;

use App\Infrastructure\Persistence\Eloquent\CashShiftModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SaleItemModel;
use App\Infrastructure\Persistence\Eloquent\SaleReturnModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleReturnProcessingTest extends TestCase
{
    use RefreshDatabase;

    private function sellerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_sale_return_segregates_defective_stock_from_operational_stock(): void
    {
        $headers = $this->sellerHeaders();
        $cat = CategoryModel::first();

        // 1. Producto con stock inicial 10, defectuoso 0
        $product = ProductModel::create([
            'name' => 'Memoria RAM Kingston Fury 16GB',
            'category_id' => $cat->id,
            'sku' => 'RAM-KNG-16',
            'cost_price' => 200.00,
            'sale_price' => 300.00,
            'stock' => 10,
            'defective_stock' => 0,
            'min_stock' => 1,
            'warranty_days' => 180,
            'status' => 'active',
        ]);

        // 2. Abrir turno de caja y vender 3 unidades
        $openShiftRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 200.00,
        ], $headers)->assertStatus(201);
        $cashShiftId = $openShiftRes->json('data.id');

        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'efectivo',
            'client_name' => 'Cliente RAM',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 300.00,
                    'serial_number' => 'RAM-SN-001',
                ],
            ],
        ], $headers)->assertStatus(201);

        $saleId = $saleRes->json('data.id');
        $saleItem = SaleItemModel::where('sale_id', $saleId)->first();

        // El stock vendible ahora es 10 - 3 = 7
        $this->assertEquals(7, (int) ProductModel::find($product->id)->stock);

        // 3. Procesar devolución de 1 unidad con falla técnica (stock_defectuoso_rma) con nota de crédito
        $returnRes = $this->postJson("/api/sales/{$saleId}/returns", [
            'resolution' => 'nota_credito',
            'reason' => 'Presenta pantallazos azules continuos tras 2 semanas de uso',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 1,
                    'condition' => 'stock_defectuoso_rma',
                    'serial_number' => 'RAM-SN-001',
                ],
            ],
        ], $headers)->assertStatus(201);

        $this->assertStringStartsWith('DEV-', $returnRes->json('data.return_number'));
        $this->assertEquals('300.00', $returnRes->json('data.total_refund_amount'));

        // 4. Verificación CRÍTICA de segregación de inventario:
        // El stock vendible NO debe subir a 8, debe seguir en 7.
        // El stock defectuoso debe ser 1.
        $productFresh = ProductModel::find($product->id);
        $this->assertEquals(7, (int) $productFresh->stock);
        $this->assertEquals(1, (int) $productFresh->defective_stock);

        // 5. Devolución de 1 unidad nueva en perfectas condiciones (stock_operativo)
        $returnRes2 = $this->postJson("/api/sales/{$saleId}/returns", [
            'resolution' => 'nota_credito',
            'reason' => 'Cliente compró modelo equivocado, caja sellada',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 1,
                    'condition' => 'stock_operativo',
                ],
            ],
        ], $headers)->assertStatus(201);

        // Ahora el stock vendible SÍ debe aumentar a 8 (7 + 1 = 8), y defective_stock sigue en 1
        $productFresh2 = ProductModel::find($product->id);
        $this->assertEquals(8, (int) $productFresh2->stock);
        $this->assertEquals(1, (int) $productFresh2->defective_stock);
    }

    public function test_cannot_return_more_units_than_sold_or_already_returned(): void
    {
        $headers = $this->sellerHeaders();
        $cat = CategoryModel::first();

        $product = ProductModel::create([
            'name' => 'Cable HDMI 2.1 2m',
            'category_id' => $cat->id,
            'sku' => 'CAB-HDMI-01',
            'cost_price' => 15.00,
            'sale_price' => 35.00,
            'stock' => 10,
            'status' => 'active',
        ]);

        $openShiftRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 100.00,
        ], $headers)->assertStatus(201);

        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $openShiftRes->json('data.id'),
            'payment_method' => 'efectivo',
            'client_name' => 'Cliente Cable',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 35.00,
                ],
            ],
        ], $headers)->assertStatus(201);

        $saleId = $saleRes->json('data.id');
        $saleItem = SaleItemModel::where('sale_id', $saleId)->first();

        // Intentar devolver 5 unidades cuando solo compró 2 debe dar 422
        $this->postJson("/api/sales/{$saleId}/returns", [
            'resolution' => 'nota_credito',
            'reason' => 'Prueba exceso',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 5,
                    'condition' => 'stock_operativo',
                ],
            ],
        ], $headers)->assertStatus(422);
    }
}
