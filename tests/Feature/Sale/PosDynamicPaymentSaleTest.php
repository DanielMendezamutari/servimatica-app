<?php

namespace Tests\Feature\Sale;

use App\Infrastructure\Persistence\Eloquent\CashShiftModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\PaymentMethodModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosDynamicPaymentSaleTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_sale_with_method_requiring_reference_fails_without_it(): void
    {
        $headers = $this->authHeaders();
        $cat = CategoryModel::first();

        $product = ProductModel::create([
            'name' => 'Cable HDMI 4K',
            'category_id' => $cat->id,
            'sku' => 'CAB-HDMI-01',
            'cost_price' => 20.00,
            'sale_price' => 45.00,
            'stock' => 10,
            'min_stock' => 2,
            'status' => 'active',
        ]);

        $openShift = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 100.00,
        ], $headers)->assertStatus(201);
        $shiftId = $openShift->json('data.id');

        // Buscar método QR que exige comprobante
        $qrMethod = PaymentMethodModel::where('type', 'qr')->first();
        $this->assertNotNull($qrMethod);

        // Venta sin referencia debe fallar
        $res = $this->postJson('/api/sales', [
            'cash_shift_id' => $shiftId,
            'payment_method_id' => $qrMethod->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 45.00],
            ],
        ], $headers);

        $res->assertStatus(422);
    }

    public function test_sale_with_payment_method_and_reference_succeeds(): void
    {
        $headers = $this->authHeaders();
        $cat = CategoryModel::first();

        $product = ProductModel::create([
            'name' => 'Adaptador USB Tipo C a RJ45',
            'category_id' => $cat->id,
            'sku' => 'ADP-USB-01',
            'cost_price' => 30.00,
            'sale_price' => 60.00,
            'stock' => 10,
            'min_stock' => 2,
            'status' => 'active',
        ]);

        $openShift = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 100.00,
        ], $headers)->assertStatus(201);
        $shiftId = $openShift->json('data.id');

        $qrMethod = PaymentMethodModel::where('type', 'qr')->first();

        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $shiftId,
            'payment_method_id' => $qrMethod->id,
            'reference_number' => 'BNB-TRF-98765432',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 60.00],
            ],
        ], $headers);

        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('data.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'payment_method_id' => $qrMethod->id,
            'reference_number' => 'BNB-TRF-98765432',
        ]);

        // Consultar detalle
        $showRes = $this->getJson("/api/sales/{$saleId}", $headers);
        $showRes->assertStatus(200)
            ->assertJsonPath('data.payment_method_id', $qrMethod->id)
            ->assertJsonPath('data.reference_number', 'BNB-TRF-98765432');
    }
}
