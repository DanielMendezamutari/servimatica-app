<?php

namespace Tests\Feature\Sale;

use App\Infrastructure\Persistence\Eloquent\CashShiftModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SaleItemModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleWarrantyTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function sellerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_pos_sale_persists_warranty_days_and_serial_number_and_calculates_expiration(): void
    {
        $headers = $this->sellerHeaders();
        $cat = CategoryModel::first();

        // 1. Crear producto con garantía base de 90 días
        $product = ProductModel::create([
            'name' => 'Tarjeta Gráfica ASUS RTX 4060',
            'category_id' => $cat->id,
            'sku' => 'GPU-ASUS-4060',
            'cost_price' => 2000.00,
            'sale_price' => 2500.00,
            'stock' => 10,
            'min_stock' => 1,
            'warranty_days' => 90,
            'status' => 'active',
        ]);

        // 2. Abrir turno de caja
        $openShiftRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 500.00,
        ], $headers)->assertStatus(201);
        $cashShiftId = $openShiftRes->json('data.id');

        // 3. Procesar venta especificando garantía personalizada de 180 días y S/N
        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'efectivo',
            'client_name' => 'Comprador Gamer',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 2500.00,
                    'warranty_days' => 180,
                    'serial_number' => 'SN-ASUS-998877',
                ],
            ],
        ], $headers)->assertStatus(201);

        $saleId = $saleRes->json('data.id');

        // 4. Verificar en base de datos
        $savedItem = SaleItemModel::where('sale_id', $saleId)->first();
        $this->assertNotNull($savedItem);
        $this->assertEquals(180, (int) $savedItem->warranty_days);
        $this->assertEquals('SN-ASUS-998877', $savedItem->serial_number);
        $this->assertNotNull($savedItem->warranty_expires_at);

        $expectedExpiry = now()->addDays(180)->format('Y-m-d');
        $this->assertStringStartsWith($expectedExpiry, $savedItem->warranty_expires_at->format('Y-m-d'));

        // 5. Verificar verificación en mostrador endpoint GET /api/sales/{id}/warranty-check
        $checkRes = $this->getJson("/api/sales/{$saleId}/warranty-check", $headers)->assertStatus(200);

        $this->assertTrue($checkRes->json('data.items.0.is_warranty_valid'));
        $this->assertEquals('SN-ASUS-998877', $checkRes->json('data.items.0.serial_number'));
        $this->assertGreaterThanOrEqual(179, (int) $checkRes->json('data.items.0.days_remaining'));
    }

    public function test_pos_sale_uses_catalog_default_warranty_days_if_not_explicitly_sent(): void
    {
        $headers = $this->sellerHeaders();
        $cat = CategoryModel::first();

        $product = ProductModel::create([
            'name' => 'Fuente de Poder EVGA 600W',
            'category_id' => $cat->id,
            'sku' => 'PSU-EVGA-600',
            'cost_price' => 300.00,
            'sale_price' => 450.00,
            'stock' => 5,
            'min_stock' => 1,
            'warranty_days' => 60,
            'status' => 'active',
        ]);

        $openShiftRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 100.00,
        ], $headers)->assertStatus(201);
        $cashShiftId = $openShiftRes->json('data.id');

        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'efectivo',
            'client_name' => 'Comprador Oficina',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 450.00,
                ],
            ],
        ], $headers)->assertStatus(201);

        $saleId = $saleRes->json('data.id');
        $savedItem = SaleItemModel::where('sale_id', $saleId)->first();
        $this->assertEquals(60, (int) $savedItem->warranty_days);
        $this->assertNotNull($savedItem->warranty_expires_at);
        $this->assertStringStartsWith(now()->addDays(60)->format('Y-m-d'), $savedItem->warranty_expires_at->format('Y-m-d'));
    }
}
