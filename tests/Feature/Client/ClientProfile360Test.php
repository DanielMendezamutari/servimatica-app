<?php

namespace Tests\Feature\Client;

use App\Infrastructure\Persistence\Eloquent\CashShiftModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ClientModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\QuoteModel;
use App\Infrastructure\Persistence\Eloquent\SaleItemModel;
use App\Infrastructure\Persistence\Eloquent\SaleModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientProfile360Test extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ClientModel $client;
    private CashShiftModel $cashShift;
    private ProductModel $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Vendedor 360',
            'username' => 'seller_360',
            'email' => 'seller_360@servimatica.com',
            'password' => bcrypt('password123'),
            'pin_code' => '1234',
            'role' => 'vendedor',
            'is_active' => true,
        ]);

        $this->client = ClientModel::create([
            'name' => 'Corporación del Oriente SA',
            'nit_ci' => '1029384700',
            'phone' => '71239988',
            'client_type' => 'empresa',
            'city' => 'Trinidad',
            'is_active' => true,
        ]);

        $this->cashShift = CashShiftModel::create([
            'user_id' => $this->user->id,
            'opening_amount' => 500.00,
            'status' => 'open',
            'opened_at' => Carbon::now(),
        ]);

        $category = CategoryModel::create([
            'name' => 'Laptops',
            'slug' => 'laptops',
            'is_active' => true,
        ]);

        $this->product = ProductModel::create([
            'name' => 'Laptop HP ProBook',
            'sku' => 'LAP-HP-001',
            'category_id' => $category->id,
            'cost_price' => 1000.00,
            'sale_price' => 1500.00,
            'stock' => 10,
            'min_stock' => 2,
            'warranty_days' => 365,
            'status' => 'active',
        ]);
    }

    public function test_can_fetch_client_360_detail_with_accumulated_stats(): void
    {
        // 2 ventas completadas
        $sale1 = SaleModel::create([
            'invoice_number' => 'V-2026-00001',
            'seller_id' => $this->user->id,
            'client_id' => $this->client->id,
            'cash_shift_id' => $this->cashShift->id,
            'client_name' => $this->client->name,
            'payment_method' => 'efectivo',
            'subtotal' => 1500.00,
            'total_amount' => 1500.00,
            'status' => 'completed',
        ]);

        $sale2 = SaleModel::create([
            'invoice_number' => 'V-2026-00002',
            'seller_id' => $this->user->id,
            'client_id' => $this->client->id,
            'cash_shift_id' => $this->cashShift->id,
            'client_name' => $this->client->name,
            'payment_method' => 'qr',
            'subtotal' => 2500.00,
            'total_amount' => 2500.00,
            'status' => 'completed',
        ]);

        // 1 cotización
        QuoteModel::create([
            'quote_number' => 'COT-2026-00001',
            'seller_id' => $this->user->id,
            'client_id' => $this->client->id,
            'client_name' => $this->client->name,
            'subtotal' => 3000.00,
            'total_amount' => 3000.00,
            'valid_until' => Carbon::now()->addDays(7),
            'status' => 'active',
        ]);

        // Artículos con garantía: uno vigente (365 días) y uno expirado (hace 10 días con 5 días de garantía)
        SaleItemModel::create([
            'sale_id' => $sale1->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'serial_number' => 'HP-889922',
            'quantity' => 1,
            'unit_cost' => 1000.00,
            'unit_price' => 1500.00,
            'subtotal' => 1500.00,
            'warranty_days' => 365,
            'warranty_expires_at' => Carbon::now()->addDays(365)->toDateString(),
        ]);

        $token = auth('api')->login($this->user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/clients/{$this->client->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Corporación del Oriente SA')
            ->assertJsonPath('data.stats.total_spent_bs', 4000)
            ->assertJsonPath('data.stats.sales_count', 2)
            ->assertJsonPath('data.stats.quotes_count', 1)
            ->assertJsonPath('data.stats.active_warranties_count', 1);
    }

    public function test_can_fetch_client_sales_and_warranties_history(): void
    {
        $sale = SaleModel::create([
            'invoice_number' => 'V-2026-00010',
            'seller_id' => $this->user->id,
            'client_id' => $this->client->id,
            'cash_shift_id' => $this->cashShift->id,
            'client_name' => $this->client->name,
            'payment_method' => 'efectivo',
            'subtotal' => 850.00,
            'total_amount' => 850.00,
            'status' => 'completed',
        ]);

        SaleItemModel::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'serial_number' => 'SAM-992211',
            'quantity' => 1,
            'unit_cost' => 500.00,
            'unit_price' => 850.00,
            'subtotal' => 850.00,
            'warranty_days' => 180,
            'warranty_expires_at' => Carbon::now()->addDays(180)->toDateString(),
        ]);

        $token = auth('api')->login($this->user);

        // Ventas
        $salesRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/clients/{$this->client->id}/sales");

        $salesRes->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.ticket_code', 'V-2026-00010')
            ->assertJsonPath('data.0.total', 850);

        // Garantías
        $warrantyRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/clients/{$this->client->id}/warranties");

        $warrantyRes->assertStatus(200)
            ->assertJsonPath('data.0.product_name', 'Laptop HP ProBook')
            ->assertJsonPath('data.0.serial_number', 'SAM-992211')
            ->assertJsonPath('data.0.warranty_days', 180)
            ->assertJsonPath('data.0.is_valid', true);
    }
}
