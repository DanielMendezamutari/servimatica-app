<?php

namespace Tests\Feature\Product;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\CompanySettingModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductWarrantyManagementTest extends TestCase
{
    use RefreshDatabase;

    private function ownerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_owner_can_create_product_with_warranty_days(): void
    {
        $headers = $this->ownerHeaders();
        $category = CategoryModel::first();

        $response = $this->postJson('/api/products', [
            'name' => 'Monitor Gamer ASUS 144Hz',
            'categoryId' => $category->id,
            'costPrice' => 1200.00,
            'salePrice' => 1650.00,
            'condition' => 'nuevo',
            'warrantyDays' => 365,
        ], $headers);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Monitor Gamer ASUS 144Hz')
            ->assertJsonPath('data.warrantyDays', 365)
            ->assertJsonPath('data.warranty_days', 365);

        $productId = $response->json('data.id');
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'warranty_days' => 365,
        ]);
    }

    public function test_owner_can_update_product_warranty_days(): void
    {
        $headers = $this->ownerHeaders();
        $category = CategoryModel::first();

        $createRes = $this->postJson('/api/products', [
            'name' => 'Mouse Inalámbrico Logitech',
            'categoryId' => $category->id,
            'costPrice' => 80.00,
            'salePrice' => 120.00,
            'condition' => 'nuevo',
            'warrantyDays' => 30,
        ], $headers)->assertCreated();

        $productId = $createRes->json('data.id');

        $updateRes = $this->putJson("/api/products/{$productId}", [
            'name' => 'Mouse Inalámbrico Logitech M170',
            'categoryId' => $category->id,
            'costPrice' => 80.00,
            'salePrice' => 120.00,
            'condition' => 'nuevo',
            'warrantyDays' => 180,
        ], $headers);

        $updateRes->assertOk()
            ->assertJsonPath('data.warrantyDays', 180);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'warranty_days' => 180,
        ]);
    }

    public function test_negative_warranty_days_are_rejected(): void
    {
        $headers = $this->ownerHeaders();
        $category = CategoryModel::first();

        $response = $this->postJson('/api/products', [
            'name' => 'Producto Invalido',
            'categoryId' => $category->id,
            'costPrice' => 50.00,
            'salePrice' => 80.00,
            'warrantyDays' => -10,
        ], $headers);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['warrantyDays']);
    }

    public function test_owner_can_update_company_warranty_terms(): void
    {
        $headers = $this->ownerHeaders();

        $response = $this->postJson('/api/company-settings', [
            'trade_name' => 'Servimática PC',
            'branch_name' => 'Sucursal Central',
            'city' => 'Beni — Bolivia',
            'address' => 'Av. 6 de Agosto',
            'mobile' => '77889900',
            'warranty_terms' => 'Garantía oficial de 1 año contra defectos de fábrica. No cubre descargas eléctricas.',
        ], $headers);

        $response->assertOk()
            ->assertJsonPath('data.warranty_terms', 'Garantía oficial de 1 año contra defectos de fábrica. No cubre descargas eléctricas.');

        $this->assertDatabaseHas('company_settings', [
            'id' => 1,
            'warranty_terms' => 'Garantía oficial de 1 año contra defectos de fábrica. No cubre descargas eléctricas.',
        ]);
    }
}
