<?php

namespace Tests\Feature\Stock;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private function ownerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    private function sellerHeaders(): array
    {
        $headers = $this->ownerHeaders();
        $this->postJson('/api/users', [
            'name' => 'Vendedor Test',
            'username' => 'vendedor',
            'email' => 'vendedor@servimatica.com',
            'password' => 'password123',
            'pin' => '9999',
            'role' => 'vendedor',
        ], $headers);

        $token = $this->postJson('/api/auth/login', ['login' => 'vendedor', 'pin' => '9999'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    private function createProduct(int $stock = 3): int
    {
        $cat = CategoryModel::where('name', 'Laptops')->first();
        $prod = ProductModel::create([
            'category_id' => $cat->id,
            'name' => 'Laptop Stock Test',
            'sku' => 'LAP-STK-001',
            'cost_price' => 2000,
            'sale_price' => 2500,
            'stock' => $stock,
            'min_stock' => 1,
            'status' => 'active',
        ]);
        return $prod->id;
    }

    public function test_owner_adjusts_stock_in_and_out_with_audit_trail(): void
    {
        $headers = $this->ownerHeaders();
        $productId = $this->createProduct(3);

        // 1. In: +5
        $resIn = $this->postJson("/api/products/$productId/stock", [
            'type' => 'in',
            'quantity' => 5,
            'reason' => 'Recepción de mercadería importada',
        ], $headers)->assertOk()
            ->assertJsonPath('data.previousStock', 3)
            ->assertJsonPath('data.newStock', 8)
            ->assertJsonPath('data.movement.type', 'in')
            ->assertJsonPath('data.movement.quantity', 5)
            ->assertJsonPath('data.movement.userName', 'Administrador Dueño');

        $this->assertEquals(8, ProductModel::find($productId)->stock);

        // 2. Out: -2
        $resOut = $this->postJson("/api/products/$productId/stock", [
            'type' => 'out',
            'quantity' => 2,
            'reason' => 'Merma / daño en exhibición',
        ], $headers)->assertOk()
            ->assertJsonPath('data.previousStock', 8)
            ->assertJsonPath('data.newStock', 6)
            ->assertJsonPath('data.movement.type', 'out');

        $this->assertEquals(6, ProductModel::find($productId)->stock);

        // 3. Stock history
        $historyRes = $this->getJson("/api/products/$productId/stock-history", $headers)->assertOk();
        $this->assertCount(2, $historyRes->json('data'));
        $this->assertEquals(6, $historyRes->json('product.currentStock'));
    }

    public function test_cannot_reduce_stock_below_zero(): void
    {
        $headers = $this->ownerHeaders();
        $productId = $this->createProduct(5);

        // Attempt to withdraw 10
        $this->postJson("/api/products/$productId/stock", [
            'type' => 'out',
            'quantity' => 10,
            'reason' => 'Retiro excesivo',
        ], $headers)->assertUnprocessable();

        $this->assertEquals(5, ProductModel::find($productId)->stock);
    }

    public function test_stock_history_filters_by_type(): void
    {
        $headers = $this->ownerHeaders();
        $productId = $this->createProduct(10);

        $this->postJson("/api/products/$productId/stock", [
            'type' => 'in',
            'quantity' => 2,
            'reason' => 'Ingreso 1',
        ], $headers)->assertOk();

        $this->postJson("/api/products/$productId/stock", [
            'type' => 'out',
            'quantity' => 1,
            'reason' => 'Egreso 1',
        ], $headers)->assertOk();

        // Filter type=out
        $res = $this->getJson("/api/products/$productId/stock-history?type=out", $headers)->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('out', $res->json('data.0.type'));
    }

    public function test_seller_cannot_adjust_stock_or_view_history(): void
    {
        $sellerHeaders = $this->sellerHeaders();
        $productId = $this->createProduct(5);

        $this->postJson("/api/products/$productId/stock", [
            'type' => 'in',
            'quantity' => 1,
            'reason' => 'Intento de vendedor',
        ], $sellerHeaders)->assertForbidden();

        $this->getJson("/api/products/$productId/stock-history", $sellerHeaders)
            ->assertForbidden();
    }
}
