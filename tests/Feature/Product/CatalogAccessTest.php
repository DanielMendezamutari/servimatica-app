<?php

namespace Tests\Feature\Product;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogAccessTest extends TestCase
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
            'name' => 'Vendedor Mostrador',
            'username' => 'mostrador',
            'email' => 'mostrador@servimatica.com',
            'password' => 'password123',
            'pin' => '5555',
            'role' => 'vendedor',
        ], $headers);

        $token = $this->postJson('/api/auth/login', ['login' => 'mostrador', 'pin' => '5555'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_seller_sees_only_active_products_and_no_financial_data(): void
    {
        $ownerHeaders = $this->ownerHeaders();
        $catActive = CategoryModel::where('name', 'Laptops')->first();
        $catInactive = CategoryModel::where('name', 'Accesorios')->first();
        $catInactive->update(['status' => 'inactive']);

        // 1. Active product in active category
        $this->postJson('/api/products', [
            'name' => 'Laptop Gamer HP',
            'categoryId' => $catActive->id,
            'sku' => 'LAP-ACTIVE-01',
            'costPrice' => 3000.00,
            'salePrice' => 4000.00,
            'stock' => 5,
            'minStock' => 2,
        ], $ownerHeaders);

        // 2. Inactive product in active category
        $p2 = $this->postJson('/api/products', [
            'name' => 'Laptop Gamer Asus Inactiva',
            'categoryId' => $catActive->id,
            'sku' => 'LAP-INACT-02',
            'costPrice' => 2500.00,
            'salePrice' => 3500.00,
            'stock' => 2,
        ], $ownerHeaders)->json('data.id');
        $this->patchJson("/api/products/$p2/toggle-status", [], $ownerHeaders);

        // 3. Active product in inactive category
        $this->postJson('/api/products', [
            'name' => 'Mouse Pad Gigante',
            'categoryId' => $catInactive->id,
            'sku' => 'ACC-PAD-03',
            'costPrice' => 20.00,
            'salePrice' => 50.00,
            'stock' => 10,
        ], $ownerHeaders);

        // --- CHECK AS OWNER ---
        $ownerList = $this->getJson('/api/products', $ownerHeaders)->assertOk();
        $this->assertCount(3, $ownerList->json('data'));
        $firstProduct = $ownerList->json('data.0');
        $this->assertArrayHasKey('costPrice', $firstProduct);
        $this->assertArrayHasKey('minStock', $firstProduct);
        $this->assertArrayHasKey('status', $firstProduct);

        // --- CHECK AS SELLER ---
        $sellerHeaders = $this->sellerHeaders();
        $sellerList = $this->getJson('/api/products', $sellerHeaders)->assertOk();

        // Seller should ONLY see 1 product (the active one in the active category)
        $this->assertCount(1, $sellerList->json('data'));
        $sellerProduct = $sellerList->json('data.0');

        $this->assertEquals('Laptop Gamer HP', $sellerProduct['name']);
        $this->assertEquals('4000.00', $sellerProduct['salePrice']);
        $this->assertEquals(5, $sellerProduct['stock']);

        // Financial & sensitive fields MUST NOT be present
        $this->assertArrayNotHasKey('costPrice', $sellerProduct);
        $this->assertArrayNotHasKey('minStock', $sellerProduct);
        $this->assertArrayNotHasKey('status', $sellerProduct);
    }

    public function test_seller_can_search_and_filter_active_catalog(): void
    {
        $ownerHeaders = $this->ownerHeaders();
        $cat = CategoryModel::where('name', 'Laptops')->first();

        $this->postJson('/api/products', [
            'name' => 'MacBook Pro M2',
            'categoryId' => $cat->id,
            'sku' => 'APPLE-MBP-01',
            'costPrice' => 8000,
            'salePrice' => 10000,
            'stock' => 3,
        ], $ownerHeaders);

        $this->postJson('/api/products', [
            'name' => 'ThinkPad T14',
            'categoryId' => $cat->id,
            'sku' => 'LENOVO-T14-01',
            'costPrice' => 5000,
            'salePrice' => 6500,
            'stock' => 4,
        ], $ownerHeaders);

        $sellerHeaders = $this->sellerHeaders();

        // Search by name
        $searchRes = $this->getJson('/api/products?search=MacBook', $sellerHeaders)->assertOk();
        $this->assertCount(1, $searchRes->json('data'));
        $this->assertEquals('MacBook Pro M2', $searchRes->json('data.0.name'));

        // Search by SKU
        $skuRes = $this->getJson('/api/products?search=LENOVO', $sellerHeaders)->assertOk();
        $this->assertCount(1, $skuRes->json('data'));
        $this->assertEquals('ThinkPad T14', $skuRes->json('data.0.name'));
    }
}
