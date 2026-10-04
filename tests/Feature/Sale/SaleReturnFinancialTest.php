<?php

namespace Tests\Feature\Sale;

use App\Infrastructure\Persistence\Eloquent\CashShiftModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SaleItemModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SaleReturnFinancialTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    private function sellerHeaders(): array
    {
        $this->seed();
        $seller = User::create([
            'username' => 'juan',
            'name' => 'Vendedor Juan',
            'email' => 'juan@servimatica.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('4321'),
            'role' => 'vendedor',
            'status' => 'active',
        ]);

        $token = $this->postJson('/api/auth/login', ['login' => 'juan', 'pin' => '4321'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_cash_refund_updates_cash_shift_total_refunds(): void
    {
        $adminHeaders = $this->adminHeaders();
        $cat = CategoryModel::first();

        $product = ProductModel::create([
            'name' => 'Teclado Mecánico Redragon',
            'category_id' => $cat->id,
            'sku' => 'TEC-RED-01',
            'cost_price' => 120.00,
            'sale_price' => 200.00,
            'stock' => 10,
            'status' => 'active',
        ]);

        // Abrir turno de caja
        $openShiftRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 500.00,
        ], $adminHeaders)->assertStatus(201);
        $cashShiftId = $openShiftRes->json('data.id');

        // Vender 1 unidad
        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'efectivo',
            'client_name' => 'Cliente Teclado',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 200.00],
            ],
        ], $adminHeaders)->assertStatus(201);

        $saleId = $saleRes->json('data.id');
        $saleItem = SaleItemModel::where('sale_id', $saleId)->first();

        // Procesar devolución con reembolso en efectivo
        $returnRes = $this->postJson("/api/sales/{$saleId}/returns", [
            'resolution' => 'reembolso_efectivo',
            'reason' => 'Cliente desiste de la compra, caja cerrada',
            'cash_shift_id' => $cashShiftId,
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 1,
                    'condition' => 'stock_operativo',
                ],
            ],
        ], $adminHeaders)->assertStatus(201);

        $this->assertEquals('200.00', $returnRes->json('data.total_refund_amount'));

        // Verificar que el turno de caja acumuló el egreso por reembolso
        $shiftFresh = CashShiftModel::find($cashShiftId);
        $this->assertEquals('200.00', $shiftFresh->total_cash_refunds);
    }

    public function test_physical_exchange_1_to_1_deducts_operational_replacement_stock(): void
    {
        $adminHeaders = $this->adminHeaders();
        $cat = CategoryModel::first();

        // Stock inicial 5
        $product = ProductModel::create([
            'name' => 'Disco SSD Kingston 480GB',
            'category_id' => $cat->id,
            'sku' => 'SSD-KNG-480',
            'cost_price' => 150.00,
            'sale_price' => 220.00,
            'stock' => 5,
            'defective_stock' => 0,
            'status' => 'active',
        ]);

        $openShiftRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 100.00,
        ], $adminHeaders)->assertStatus(201);
        $cashShiftId = $openShiftRes->json('data.id');

        // Vender 1 unidad -> stock pasa a 4
        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'efectivo',
            'client_name' => 'Cliente SSD',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 220.00],
            ],
        ], $adminHeaders)->assertStatus(201);

        $saleId = $saleRes->json('data.id');
        $saleItem = SaleItemModel::where('sale_id', $saleId)->first();
        $this->assertEquals(4, (int) ProductModel::find($product->id)->stock);

        // Procesar cambio físico 1 a 1 por falla técnica
        // El disco fallado entra a defective_stock (0 -> 1)
        // Y el cambio físico descuenta 1 unidad operativa para entregar al cliente (4 -> 3)
        $this->postJson("/api/sales/{$saleId}/returns", [
            'resolution' => 'cambio_fisico',
            'reason' => 'Falla en sectores de arranque, cambio 1 a 1 inmediato',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 1,
                    'condition' => 'stock_defectuoso_rma',
                ],
            ],
        ], $adminHeaders)->assertStatus(201);

        $productFresh = ProductModel::find($product->id);
        $this->assertEquals(3, (int) $productFresh->stock);
        $this->assertEquals(1, (int) $productFresh->defective_stock);
    }

    public function test_seller_cannot_process_cash_refund(): void
    {
        $sellerHeaders = $this->sellerHeaders();
        $cat = CategoryModel::first();

        $product = ProductModel::create([
            'name' => 'Audífonos Bluetooth Sony',
            'category_id' => $cat->id,
            'sku' => 'AUD-SNY-01',
            'cost_price' => 80.00,
            'sale_price' => 130.00,
            'stock' => 5,
            'status' => 'active',
        ]);

        // Abrir caja con el vendedor
        $openShiftRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 100.00,
        ], $sellerHeaders)->assertStatus(201);
        $cashShiftId = $openShiftRes->json('data.id');

        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'efectivo',
            'client_name' => 'Cliente Audífonos',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 130.00],
            ],
        ], $sellerHeaders)->assertStatus(201);

        $saleId = $saleRes->json('data.id');
        $saleItem = SaleItemModel::where('sale_id', $saleId)->first();

        // Vendedor intenta reembolso en efectivo -> debe rechazar con 422
        $res = $this->postJson("/api/sales/{$saleId}/returns", [
            'resolution' => 'reembolso_efectivo',
            'reason' => 'Intento de reembolso por vendedor',
            'cash_shift_id' => $cashShiftId,
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 1,
                    'condition' => 'stock_operativo',
                ],
            ],
        ], $sellerHeaders)->assertStatus(422);

        $this->assertStringContainsString('exclusiva del Dueño', $res->json('message'));
    }

    public function test_sale_return_receipt_renders_successfully(): void
    {
        $adminHeaders = $this->adminHeaders();
        $cat = CategoryModel::first();

        $product = ProductModel::create([
            'name' => 'Cámara Web Logitech C920',
            'category_id' => $cat->id,
            'sku' => 'CAM-LOGI-920',
            'cost_price' => 300.00,
            'sale_price' => 450.00,
            'stock' => 5,
            'status' => 'active',
        ]);

        $openShiftRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 100.00,
        ], $adminHeaders)->assertStatus(201);

        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $openShiftRes->json('data.id'),
            'payment_method' => 'efectivo',
            'client_name' => 'Cliente Camara',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 450.00],
            ],
        ], $adminHeaders)->assertStatus(201);

        $saleId = $saleRes->json('data.id');
        $saleItem = SaleItemModel::where('sale_id', $saleId)->first();

        $returnRes = $this->postJson("/api/sales/{$saleId}/returns", [
            'resolution' => 'nota_credito',
            'reason' => 'Cambio de opinión cliente',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 1,
                    'condition' => 'stock_operativo',
                ],
            ],
        ], $adminHeaders)->assertStatus(201);

        $returnId = $returnRes->json('data.id');

        // Test render térmico 80mm
        $thermalRes = $this->get("/api/sale-returns/{$returnId}/receipt?format=thermal");
        $thermalRes->assertStatus(200);
        $thermalRes->assertSee('COMPROBANTE DE DEVOLUCIÓN');
        $thermalRes->assertSee('DEV-');

        // Test render carta formal
        $letterRes = $this->get("/api/sale-returns/{$returnId}/receipt?format=letter");
        $letterRes->assertStatus(200);
        $letterRes->assertSee('NOTA DE DEVOLUCIÓN Y GARANTÍA');
    }
}
