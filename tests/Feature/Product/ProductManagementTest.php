<?php

namespace Tests\Feature\Product;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
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

    public function test_owner_creates_product_with_custom_and_auto_generated_sku(): void
    {
        $headers = $this->ownerHeaders();
        $category = CategoryModel::where('name', 'Laptops')->first();

        // 1. Create with custom SKU
        $responseCustom = $this->postJson('/api/products', [
            'name' => 'Laptop HP 15-ef2xxx',
            'description' => 'Ryzen 5, 8GB RAM, 256GB SSD',
            'categoryId' => $category->id,
            'sku' => 'LAP-HP-001',
            'costPrice' => 2500.00,
            'salePrice' => 3200.00,
            'stock' => 3,
            'minStock' => 2,
        ], $headers)->assertCreated()
            ->assertJsonPath('data.sku', 'LAP-HP-001')
            ->assertJsonPath('data.costPrice', '2500.00')
            ->assertJsonPath('data.salePrice', '3200.00')
            ->assertJsonPath('data.stock', 3)
            ->assertJsonPath('data.minStock', 2)
            ->assertJsonPath('data.status', 'active');

        // 2. Create with auto-generated SKU (empty/omitted sku)
        $responseAuto = $this->postJson('/api/products', [
            'name' => 'Laptop Dell Inspiron 3525',
            'categoryId' => $category->id,
            'costPrice' => 2800.00,
            'salePrice' => 3500.00,
        ], $headers)->assertCreated();

        $autoSku = $responseAuto->json('data.sku');
        $this->assertNotEmpty($autoSku);
        $this->assertStringStartsWith('LAP-', $autoSku);
    }

    public function test_sku_must_be_unique_and_validated(): void
    {
        $headers = $this->ownerHeaders();
        $category = CategoryModel::where('name', 'Laptops')->first();

        $this->postJson('/api/products', [
            'name' => 'Laptop Test 1',
            'categoryId' => $category->id,
            'sku' => 'UNIQUE-SKU',
            'costPrice' => 1000,
            'salePrice' => 1500,
        ], $headers)->assertCreated();

        // Duplicate SKU (case insensitive)
        $this->postJson('/api/products', [
            'name' => 'Laptop Test 2',
            'categoryId' => $category->id,
            'sku' => 'unique-sku',
            'costPrice' => 1000,
            'salePrice' => 1500,
        ], $headers)->assertUnprocessable()
            ->assertJsonValidationErrors('sku');
    }

    public function test_owner_updates_product_and_stock_cannot_be_modified_via_put(): void
    {
        $headers = $this->ownerHeaders();
        $category = CategoryModel::where('name', 'Laptops')->first();

        $res = $this->postJson('/api/products', [
            'name' => 'Laptop Original',
            'categoryId' => $category->id,
            'sku' => 'LAP-ORIG',
            'costPrice' => 2000,
            'salePrice' => 2600,
            'stock' => 5,
            'minStock' => 1,
        ], $headers)->assertCreated();

        $id = $res->json('data.id');

        // Update product attempting to change stock to 99
        $this->putJson("/api/products/$id", [
            'name' => 'Laptop Modificada',
            'categoryId' => $category->id,
            'sku' => 'LAP-ORIG',
            'costPrice' => 2100,
            'salePrice' => 2700,
            'stock' => 99,
            'minStock' => 3,
        ], $headers)->assertOk()
            ->assertJsonPath('data.name', 'Laptop Modificada')
            ->assertJsonPath('data.costPrice', '2100.00')
            ->assertJsonPath('data.salePrice', '2700.00')
            ->assertJsonPath('data.minStock', 3)
            ->assertJsonPath('data.stock', 5); // Stock remains 5!

        $this->assertEquals(5, ProductModel::find($id)->stock);
    }

    public function test_owner_toggles_product_status(): void
    {
        $headers = $this->ownerHeaders();
        $category = CategoryModel::where('name', 'Laptops')->first();

        $res = $this->postJson('/api/products', [
            'name' => 'Laptop Status Test',
            'categoryId' => $category->id,
            'sku' => 'LAP-STATUS',
            'costPrice' => 1000,
            'salePrice' => 1200,
        ], $headers)->assertCreated();

        $id = $res->json('data.id');

        $this->patchJson("/api/products/$id/toggle-status", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->patchJson("/api/products/$id/toggle-status", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_seller_cannot_create_or_modify_products(): void
    {
        $sellerHeaders = $this->sellerHeaders();

        $this->postJson('/api/products', [
            'name' => 'Hack Product',
            'categoryId' => 1,
            'costPrice' => 100,
            'salePrice' => 200,
        ], $sellerHeaders)->assertForbidden();

        $this->putJson('/api/products/1', [
            'name' => 'Hack Product',
            'categoryId' => 1,
            'costPrice' => 100,
            'salePrice' => 200,
        ], $sellerHeaders)->assertForbidden();

        $this->patchJson('/api/products/1/toggle-status', [], $sellerHeaders)
            ->assertForbidden();
    }
}
