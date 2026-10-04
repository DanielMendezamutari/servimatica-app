<?php

namespace Tests\Feature\Purchase;

use App\Models\User;
use App\Infrastructure\Persistence\Eloquent\BrandModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SupplierModel;
use App\Infrastructure\Persistence\Eloquent\StockMovementModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PurchaseReceptionTest extends TestCase
{
    use RefreshDatabase;

    private function getOwnerToken(): string
    {
        User::create([
            'name' => 'Dueño Test',
            'username' => 'dueno',
            'email' => 'dueno@servimatica.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('1234'),
            'role' => 'dueno',
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/auth/login', [
            'login' => 'dueno',
            'pin' => '1234',
        ])->assertOk();

        return $res->json('accessToken');
    }

    private function getSellerToken(): string
    {
        User::create([
            'name' => 'Vendedor Test',
            'username' => 'vendedor',
            'email' => 'vendedor@servimatica.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('5678'),
            'role' => 'vendedor',
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/auth/login', [
            'login' => 'vendedor',
            'pin' => '5678',
        ])->assertOk();

        return $res->json('accessToken');
    }

    public function test_owner_can_receive_purchase_with_stock_increment_and_price_update(): void
    {
        $token = $this->getOwnerToken();
        $headers = ['Authorization' => "Bearer {$token}"];

        $supplier = SupplierModel::create([
            'name' => 'Deltron Bolivia SRL',
            'nit' => '1029384019',
            'phone' => '77123456',
            'is_active' => true,
        ]);

        $category = CategoryModel::create([
            'name' => 'Almacenamiento',
            'status' => 'active',
        ]);

        $brand = BrandModel::create([
            'name' => 'Kingston',
            'is_active' => true,
        ]);

        $product = ProductModel::create([
            'name' => 'Disco SSD Kingston 480GB',
            'sku' => 'SSD-KING-480',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'cost_price' => 200.00,
            'sale_price' => 280.00,
            'stock' => 2,
            'min_stock' => 1,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'FC-98432',
            'purchase_date' => '2026-10-03',
            'payment_condition' => 'contado',
            'payment_method' => 'transferencia',
            'notes' => 'Reposición de discos para ensamblaje',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_cost' => 250.00,
                    'new_sale_price' => 320.00,
                ],
            ],
        ], $headers)->assertCreated();

        $response->assertJsonPath('data.status', 'received');
        $response->assertJsonPath('data.purchase_number', 'COM-000001');
        $response->assertJsonPath('data.total_amount', '1250.00');

        // Verify product stock incremented from 2 to 7, cost updated to 250, and sale price to 320
        $product->refresh();
        $this->assertEquals(7, $product->stock);
        $this->assertEquals(250.00, (float)$product->cost_price);
        $this->assertEquals(320.00, (float)$product->sale_price);

        // Verify immutable audit log in stock_movements
        $movement = StockMovementModel::where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('in', $movement->type);
        $this->assertEquals(5, $movement->quantity);
        $this->assertEquals(2, $movement->previous_stock);
        $this->assertEquals(7, $movement->new_stock);
        $this->assertStringContainsString('FC-98432', $movement->reason);
    }

    public function test_seller_cannot_process_purchases(): void
    {
        $sellerToken = $this->getSellerToken();
        $sellerHeaders = ['Authorization' => "Bearer {$sellerToken}"];

        $this->postJson('/api/purchases', [
            'supplier_id' => 1,
            'invoice_number' => 'FC-1111',
            'purchase_date' => '2026-10-03',
            'payment_condition' => 'contado',
            'payment_method' => 'efectivo',
            'items' => [
                ['product_id' => 1, 'quantity' => 1, 'unit_cost' => 10],
            ],
        ], $sellerHeaders)->assertForbidden();
    }
}
