<?php

namespace Tests\Feature\Warranty;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SaleItemModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutonomousHardwareSoftwareWarrantyTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_product_creation_and_update_with_autonomous_hardware_and_software_warranties(): void
    {
        $headers = $this->adminHeaders();
        $cat = CategoryModel::first();

        // 1. Crear producto con 730 días de Hardware y 180 días de Software
        $createRes = $this->postJson('/api/products', [
            'name' => 'Laptop Gamer Legion 5 Pro',
            'categoryId' => $cat->id,
            'sku' => 'LAP-LEGION-001',
            'costPrice' => 7000.00,
            'salePrice' => 8500.00,
            'stock' => 5,
            'condition' => 'nuevo',
            'warranty_hardware_days' => 730,
            'warranty_software_days' => 180,
        ], $headers)->assertStatus(201);

        $productId = $createRes->json('data.id');
        $this->assertEquals(730, $createRes->json('data.warranty_hardware_days'));
        $this->assertEquals(180, $createRes->json('data.warranty_software_days'));
        $this->assertEquals(730, $createRes->json('data.warranty_days')); // Fallback

        // 2. Actualizar producto a 365 días Hardware y 90 días Software
        $updateRes = $this->putJson("/api/products/{$productId}", [
            'name' => 'Laptop Gamer Legion 5 Pro Updated',
            'categoryId' => $cat->id,
            'costPrice' => 7000.00,
            'salePrice' => 8500.00,
            'condition' => 'nuevo',
            'warranty_hardware_days' => 365,
            'warranty_software_days' => 90,
        ], $headers)->assertStatus(200);

        $this->assertEquals(365, $updateRes->json('data.warranty_hardware_days'));
        $this->assertEquals(90, $updateRes->json('data.warranty_software_days'));
    }

    public function test_pos_sale_persists_independent_hardware_and_software_warranties(): void
    {
        $headers = $this->adminHeaders();
        $cat = CategoryModel::first();

        $product = ProductModel::create([
            'name' => 'Laptop ASUS ROG Strix G16',
            'category_id' => $cat->id,
            'sku' => 'LAP-ROG-001',
            'cost_price' => 8000.00,
            'sale_price' => 9800.00,
            'stock' => 3,
            'min_stock' => 1,
            'warranty_hardware_days' => 730,
            'warranty_software_days' => 90,
            'status' => 'active',
        ]);

        $openShiftRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 500.00,
        ], $headers)->assertStatus(201);
        $cashShiftId = $openShiftRes->json('data.id');

        // Procesar venta con 730 días de Hardware, 30 días de Software y S/N
        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'efectivo',
            'client_name' => 'Cliente VIP',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 9800.00,
                    'warranty_hardware_days' => 730,
                    'warranty_software_days' => 30,
                    'serial_number' => 'ROG-SN-991122',
                ],
            ],
        ], $headers)->assertStatus(201);

        $saleId = $saleRes->json('data.id');

        // Verificar en BD
        $item = SaleItemModel::where('sale_id', $saleId)->first();
        $this->assertNotNull($item);
        $this->assertEquals(730, $item->warranty_hardware_days);
        $this->assertEquals(30, $item->warranty_software_days);
        $this->assertEquals('ROG-SN-991122', $item->serial_number);
        $this->assertEquals(now()->addDays(730)->toDateString(), Carbon::parse($item->warranty_hardware_expires_at)->toDateString());
        $this->assertEquals(now()->addDays(30)->toDateString(), Carbon::parse($item->warranty_software_expires_at)->toDateString());
    }

    public function test_warranty_check_endpoint_evaluates_hardware_and_software_autonomously(): void
    {
        $headers = $this->adminHeaders();
        $cat = CategoryModel::first();

        $product = ProductModel::create([
            'name' => 'Laptop HP Omen 16',
            'category_id' => $cat->id,
            'sku' => 'LAP-OMEN-001',
            'cost_price' => 6000.00,
            'sale_price' => 7500.00,
            'stock' => 2,
            'status' => 'active',
        ]);

        $openShift = $this->postJson('/api/cash-shifts/open', ['opening_amount' => 500.00], $headers)->json('data.id');

        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $openShift,
            'payment_method' => 'efectivo',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 7500.00,
                    'warranty_hardware_days' => 365,
                    'warranty_software_days' => 15,
                    'serial_number' => 'OMEN-SN-7788',
                ],
            ],
        ], $headers)->assertStatus(201);

        $saleId = $saleRes->json('data.id');

        $checkRes = $this->getJson("/api/sales/{$saleId}/warranty-check", $headers)->assertStatus(200);

        $itemCheck = $checkRes->json('data.items.0');
        $this->assertEquals(365, $itemCheck['warranty_hardware_days']);
        $this->assertEquals(15, $itemCheck['warranty_software_days']);
        $this->assertEquals('active', $itemCheck['hardware_status']);
        $this->assertEquals('active', $itemCheck['software_status']);
        $this->assertTrue($itemCheck['is_hardware_warranty_valid']);
        $this->assertTrue($itemCheck['is_software_warranty_valid']);
    }
}
