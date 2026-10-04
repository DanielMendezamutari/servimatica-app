<?php

namespace Tests\Feature\Report;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitabilityReportTest extends TestCase
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
            'name' => 'Vendedor Prueba',
            'username' => 'vendedor1',
            'email' => 'vendedor1@servimatica.com',
            'password' => 'password123',
            'pin' => '7777',
            'role' => 'vendedor',
        ], $headers);

        $token = $this->postJson('/api/auth/login', ['login' => 'vendedor1', 'pin' => '7777'])->json('accessToken');
        return $this->cachedSellerHeaders = ['Authorization' => "Bearer $token"];
    }

    public function test_seller_cannot_access_profitability_report_forbidden_403(): void
    {
        $sellerHeaders = $this->sellerHeaders();

        $this->getJson('/api/v1/reports/profitability', $sellerHeaders)
            ->assertStatus(403);
    }

    public function test_owner_can_consult_profitability_report_with_kpis_and_ranking(): void
    {
        $headers = $this->ownerHeaders();

        $cat = CategoryModel::firstOrCreate(['name' => 'Periféricos'], ['status' => 'active']);
        $productA = ProductModel::create([
            'category_id' => $cat->id,
            'name' => 'Mouse Gamer RGB',
            'sku' => 'MOU-GAM-001',
            'cost_price' => 100.00,
            'sale_price' => 150.00,
            'stock' => 10,
            'status' => 'active',
        ]);

        $productB = ProductModel::create([
            'category_id' => $cat->id,
            'name' => 'Teclado Mecánico',
            'sku' => 'TEC-MEC-001',
            'cost_price' => 200.00,
            'sale_price' => 300.00,
            'stock' => 5,
            'status' => 'active',
        ]);

        // Abrir turno de caja
        $shiftRes = $this->postJson('/api/cash-shifts/open', ['opening_amount' => 100.00], $headers)->assertCreated();
        $cashShiftId = $shiftRes->json('data.id');

        // Venta 1: 2 unidades de Mouse A (@ 150 = 300 Bs.)
        $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'efectivo',
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 2, 'unit_price' => 150.00],
            ],
        ], $headers)->assertCreated();

        // Venta 2: 1 unidad de Teclado B (@ 300 = 300 Bs.)
        $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'efectivo',
            'items' => [
                ['product_id' => $productB->id, 'quantity' => 1, 'unit_price' => 300.00],
            ],
        ], $headers)->assertCreated();

        // Consultar reporte de rentabilidad
        $response = $this->getJson('/api/v1/reports/profitability', $headers)
            ->assertOk()
            ->assertJsonPath('success', true);

        // Ventas totales = 600, Costo COGS = (2*100) + (1*200) = 400, Utilidad = 200, Margen = 33.33%
        $kpis = $response->json('data.kpis');
        $this->assertEquals(600.00, $kpis['total_sales']);
        $this->assertEquals(400.00, $kpis['total_cogs']);
        $this->assertEquals(200.00, $kpis['gross_profit']);
        $this->assertEquals(33.33, $kpis['profit_margin_percentage']);
        $this->assertEquals(2, $kpis['transactions_count']);
        $this->assertEquals(3, $kpis['items_sold_count']);

        // Timeline
        $timeline = $response->json('data.timeline');
        $this->assertNotEmpty($timeline);
        $this->assertEquals(600.00, $timeline[0]['sales']);

        // Top products
        $topProducts = $response->json('data.top_products');
        $this->assertCount(2, $topProducts);
    }

    public function test_owner_can_export_profitability_to_csv(): void
    {
        $headers = $this->ownerHeaders();

        $cat = CategoryModel::firstOrCreate(['name' => 'Periféricos'], ['status' => 'active']);
        $product = ProductModel::create([
            'category_id' => $cat->id,
            'name' => 'Auriculares Gamer 7.1',
            'sku' => 'AUR-GAM-001',
            'cost_price' => 80.00,
            'sale_price' => 120.00,
            'stock' => 8,
            'status' => 'active',
        ]);

        $shiftRes = $this->postJson('/api/cash-shifts/open', ['opening_amount' => 100.00], $headers)->assertCreated();
        $this->postJson('/api/sales', [
            'cash_shift_id' => $shiftRes->json('data.id'),
            'payment_method' => 'efectivo',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 120.00],
            ],
        ], $headers)->assertCreated();

        $response = $this->get('/api/v1/reports/profitability?export=csv', $headers);
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('REPORTE DE RENTABILIDAD Y UTILIDAD', $content);
        $this->assertStringContainsString('Auriculares Gamer 7.1', $content);
        $this->assertStringContainsString('Utilidad', $content);
    }
}
