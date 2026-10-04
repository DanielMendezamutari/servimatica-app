<?php

namespace Tests\Feature\Dashboard;

use App\Infrastructure\Persistence\Eloquent\CashShiftModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SaleItemModel;
use App\Infrastructure\Persistence\Eloquent\SaleModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    private ?array $cachedOwnerHeaders = null;
    private ?array $cachedSellerHeaders = null;
    private User $ownerUser;
    private User $sellerUser;
    private ProductModel $productA;
    private ProductModel $productB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->ownerUser = User::where('role', 'dueno')->first();

        $cat = CategoryModel::firstOrCreate(['name' => 'Accesorios'], ['status' => 'active']);

        $this->productA = ProductModel::create([
            'name' => 'Cable HDMI 2.0 4K',
            'sku' => 'HDMI-01',
            'category_id' => $cat->id,
            'cost_price' => 20.00,
            'sale_price' => 50.00,
            'stock' => 10,
            'min_stock' => 5,
            'status' => 'active',
        ]);

        $this->productB = ProductModel::create([
            'name' => 'Pasta Térmica Arctic',
            'sku' => 'PASTA-01',
            'category_id' => $cat->id,
            'cost_price' => 15.00,
            'sale_price' => 40.00,
            'stock' => 2, // crítico (menor al mínimo 5)
            'min_stock' => 5,
            'status' => 'active',
        ]);
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
            'name' => 'Cajero Vendedor',
            'username' => 'cajero1',
            'email' => 'cajero1@servimatica.com',
            'password' => 'password123',
            'pin' => '8888',
            'role' => 'vendedor',
        ], $headers);

        $token = $this->postJson('/api/auth/login', ['login' => 'cajero1', 'pin' => '8888'])->json('accessToken');
        $this->sellerUser = User::where('username', 'cajero1')->first();

        return $this->cachedSellerHeaders = ['Authorization' => "Bearer $token"];
    }

    public function test_owner_can_consult_dashboard_summary_with_financial_and_operational_data(): void
    {
        $headers = $this->ownerHeaders();

        // Crear turno abierto hoy
        $shift = CashShiftModel::create([
            'user_id' => $this->ownerUser->id,
            'opening_amount' => 200.00,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        // Crear una venta hoy
        $sale = SaleModel::create([
            'invoice_number' => 'VTA-0001',
            'seller_id' => $this->ownerUser->id,
            'cash_shift_id' => $shift->id,
            'subtotal' => 100.00,
            'discount_amount' => 0.00,
            'total_amount' => 100.00,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        SaleItemModel::create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'product_name' => $this->productA->name,
            'product_sku' => $this->productA->sku,
            'quantity' => 2,
            'unit_cost' => 20.00,
            'unit_price' => 50.00,
            'subtotal' => 100.00,
        ]);

        $response = $this->getJson('/api/v1/dashboard/summary', $headers);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.period', 'today')
            ->assertJsonPath('data.kpis.total_sales_bs', 100)
            ->assertJsonPath('data.kpis.sales_count', 1)
            ->assertJsonPath('data.kpis.total_profit_bs', 60)
            ->assertJsonPath('data.cash_shift.has_active_shift', true)
            ->assertJsonPath('data.cash_shift.shift_id', $shift->id);

        $criticalStock = $response->json('data.critical_stock');
        $this->assertNotEmpty($criticalStock);
        $this->assertEquals('PASTA-01', $criticalStock[0]['sku']);
    }

    public function test_seller_receives_operational_kpis_without_financial_profit_disclosure(): void
    {
        $sellerHeaders = $this->sellerHeaders();

        $shift = CashShiftModel::create([
            'user_id' => $this->sellerUser->id,
            'opening_amount' => 100.00,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $sale = SaleModel::create([
            'invoice_number' => 'VTA-0002',
            'seller_id' => $this->sellerUser->id,
            'cash_shift_id' => $shift->id,
            'subtotal' => 50.00,
            'discount_amount' => 0.00,
            'total_amount' => 50.00,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        SaleItemModel::create([
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'product_name' => $this->productA->name,
            'product_sku' => $this->productA->sku,
            'quantity' => 1,
            'unit_cost' => 20.00,
            'unit_price' => 50.00,
            'subtotal' => 50.00,
        ]);

        $response = $this->getJson('/api/v1/dashboard/summary', $sellerHeaders);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kpis.total_sales_bs', 50)
            ->assertJsonPath('data.kpis.sales_count', 1)
            ->assertJsonPath('data.kpis.total_profit_bs', null)
            ->assertJsonPath('data.kpis.profit_margin_percentage', null);
    }

    public function test_period_filtering_updates_summary_metrics(): void
    {
        $headers = $this->ownerHeaders();

        $responseWeek = $this->getJson('/api/v1/dashboard/summary?period=this_week', $headers);

        $responseWeek->assertStatus(200)
            ->assertJsonPath('data.period', 'this_week');

        $responseMonth = $this->getJson('/api/v1/dashboard/summary?period=this_month', $headers);

        $responseMonth->assertStatus(200)
            ->assertJsonPath('data.period', 'this_month');
    }
}
