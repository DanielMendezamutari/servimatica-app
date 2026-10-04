<?php

namespace Tests\Feature\Product;

use App\Infrastructure\Persistence\Eloquent\BrandModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\DeviceModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedCatalogTest extends TestCase
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

    public function test_can_create_product_with_brand_model_subfamily_and_condition(): void
    {
        $headers = $this->ownerHeaders();

        // 1. Crear categoría raíz y subfamilia
        $parent = CategoryModel::create(['name' => 'Computadoras', 'description' => 'PCs']);
        $sub = CategoryModel::create(['name' => 'Portátiles', 'parent_id' => $parent->id]);

        // 2. Crear marca y modelo
        $brand = BrandModel::create(['name' => 'Lenovo', 'status' => 'active']);
        $model = DeviceModel::create(['brand_id' => $brand->id, 'name' => 'ThinkPad E14', 'status' => 'active']);

        // 3. Crear producto
        $response = $this->postJson('/api/products', [
            'name' => 'Lenovo ThinkPad E14 Gen 4',
            'categoryId' => $parent->id,
            'subfamilyId' => $sub->id,
            'brandId' => $brand->id,
            'productModelId' => $model->id,
            'condition' => 'open_box',
            'costPrice' => 4500.00,
            'salePrice' => 5800.00,
            'stock' => 3,
            'minStock' => 1,
        ], $headers);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Lenovo ThinkPad E14 Gen 4');
        $response->assertJsonPath('data.condition', 'open_box');
        $response->assertJsonPath('data.brandId', $brand->id);
        $response->assertJsonPath('data.brandName', 'Lenovo');
        $response->assertJsonPath('data.productModelName', 'ThinkPad E14');
        $response->assertJsonPath('data.subfamilyName', 'Portátiles');

        $this->assertDatabaseHas('products', [
            'name' => 'Lenovo ThinkPad E14 Gen 4',
            'brand_id' => $brand->id,
            'product_model_id' => $model->id,
            'subfamily_id' => $sub->id,
            'condition' => 'open_box',
        ]);
    }

    public function test_can_filter_products_by_brand_and_condition(): void
    {
        $headers = $this->ownerHeaders();
        $cat = CategoryModel::firstOrCreate(['name' => 'Accesorios']);
        $brand1 = BrandModel::create(['name' => 'Logitech']);
        $brand2 = BrandModel::create(['name' => 'Razer']);

        $this->postJson('/api/products', [
            'name' => 'Mouse Logitech G502',
            'categoryId' => $cat->id,
            'brandId' => $brand1->id,
            'condition' => 'nuevo',
            'costPrice' => 200,
            'salePrice' => 350,
            'stock' => 10,
        ], $headers);

        $this->postJson('/api/products', [
            'name' => 'Mouse Razer DeathAdder Usado',
            'categoryId' => $cat->id,
            'brandId' => $brand2->id,
            'condition' => 'usado',
            'costPrice' => 100,
            'salePrice' => 180,
            'stock' => 2,
        ], $headers);

        // Filtro por marca
        $resBrand = $this->getJson("/api/products?brand_id={$brand1->id}", $headers);
        $resBrand->assertOk();
        $this->assertCount(1, $resBrand->json('data'));
        $this->assertEquals('Mouse Logitech G502', $resBrand->json('data.0.name'));

        // Filtro por condición
        $resCond = $this->getJson('/api/products?condition=usado', $headers);
        $resCond->assertOk();
        $this->assertCount(1, $resCond->json('data'));
        $this->assertEquals('Mouse Razer DeathAdder Usado', $resCond->json('data.0.name'));
    }

    public function test_owner_can_export_products_to_excel_with_financials(): void
    {
        $headers = $this->ownerHeaders();
        $cat = CategoryModel::firstOrCreate(['name' => 'Audio']);
        $this->postJson('/api/products', [
            'name' => 'Audífonos Sony WH-1000XM5',
            'categoryId' => $cat->id,
            'condition' => 'nuevo',
            'costPrice' => 2100,
            'salePrice' => 2800,
            'stock' => 4,
        ], $headers);

        $response = $this->get('/api/products/export-excel', $headers);

        $response->assertOk();
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response->baseResponse);
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('inventario_servimatica_', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    public function test_seller_can_export_products_to_excel_without_cost_disclosure(): void
    {
        $ownerHeaders = $this->ownerHeaders();
        $cat = CategoryModel::firstOrCreate(['name' => 'Video']);
        $this->postJson('/api/products', [
            'name' => 'Monitor Dell 27 4K',
            'categoryId' => $cat->id,
            'condition' => 'reacondicionado',
            'costPrice' => 1800,
            'salePrice' => 2600,
            'stock' => 5,
        ], $ownerHeaders);

        $sellerHeaders = $this->sellerHeaders();
        $response = $this->get('/api/products/export-excel', $sellerHeaders);

        $response->assertOk();
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response->baseResponse);
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('inventario_servimatica_', $disposition);
    }
}
