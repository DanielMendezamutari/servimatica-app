<?php

namespace Tests\Feature\Report;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryValuationTest extends TestCase
{
    use RefreshDatabase;

    private ?array $cachedOwnerHeaders = null;
    private ?array $cachedSellerHeaders = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function ownerHeaders(): array
    {
        if ($this->cachedOwnerHeaders !== null) {
            return $this->cachedOwnerHeaders;
        }

        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return $this->cachedOwnerHeaders = ['Authorization' => "Bearer $token"];
    }

    private function sellerHeaders(): array
    {
        if ($this->cachedSellerHeaders !== null) {
            return $this->cachedSellerHeaders;
        }

        $headers = $this->ownerHeaders();
        $this->postJson('/api/users', [
            'name' => 'Vendedor Test',
            'username' => 'vendedor2',
            'email' => 'vendedor2@servimatica.com',
            'password' => 'password123',
            'pin' => '6666',
            'role' => 'vendedor',
        ], $headers);

        $token = $this->postJson('/api/auth/login', ['login' => 'vendedor2', 'pin' => '6666'])->json('accessToken');
        return $this->cachedSellerHeaders = ['Authorization' => "Bearer $token"];
    }

    public function test_seller_cannot_access_inventory_valuation_forbidden_403(): void
    {
        $sellerHeaders = $this->sellerHeaders();

        $this->getJson('/api/v1/reports/inventory-valuation', $sellerHeaders)
            ->assertStatus(403);
    }

    public function test_owner_can_consult_inventory_valuation_report(): void
    {
        $headers = $this->ownerHeaders();

        $cat = CategoryModel::firstOrCreate(['name' => 'Almacenamiento'], ['status' => 'active']);
        $product = ProductModel::create([
            'category_id' => $cat->id,
            'name' => 'SSD NVMe 1TB Kingston',
            'sku' => 'SSD-1TB-TEST',
            'cost_price' => 400.00,
            'sale_price' => 550.00,
            'stock' => 10,
            'defective_stock' => 2,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/reports/inventory-valuation', $headers)
            ->assertOk()
            ->assertJsonPath('success', true);

        $summary = $response->json('data.summary');
        $this->assertGreaterThanOrEqual(10, $summary['total_sellable_units']);
        $this->assertGreaterThanOrEqual(2, $summary['total_defective_units']);
        $this->assertGreaterThanOrEqual(4000.00, $summary['sellable_valuation_bs']);
        $this->assertGreaterThanOrEqual(800.00, $summary['defective_valuation_bs']);
        $this->assertGreaterThanOrEqual(4800.00, $summary['total_inventory_valuation_bs']);

        $products = $response->json('data.products');
        $this->assertNotEmpty($products);

        $found = collect($products)->firstWhere('sku', 'SSD-1TB-TEST');
        $this->assertNotNull($found);
        $this->assertEquals(4000.00, $found['sellable_value_bs']);
        $this->assertEquals(800.00, $found['defective_value_bs']);
        $this->assertEquals(4800.00, $found['total_value_bs']);
    }

    public function test_owner_can_export_inventory_valuation_to_csv(): void
    {
        $headers = $this->ownerHeaders();

        $cat = CategoryModel::firstOrCreate(['name' => 'Monitores'], ['status' => 'active']);
        ProductModel::create([
            'category_id' => $cat->id,
            'name' => 'Monitor 24 IPS 144Hz',
            'sku' => 'MON-24-TEST',
            'cost_price' => 800.00,
            'sale_price' => 1100.00,
            'stock' => 4,
            'defective_stock' => 1,
            'status' => 'active',
        ]);

        $response = $this->get('/api/v1/reports/inventory-valuation?export=csv', $headers);
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('VALORACIÓN TOTAL DE INVENTARIO', $content);
        $this->assertStringContainsString('Monitor 24 IPS 144Hz', $content);
        $this->assertStringContainsString('Capital Total', $content);
    }
}
