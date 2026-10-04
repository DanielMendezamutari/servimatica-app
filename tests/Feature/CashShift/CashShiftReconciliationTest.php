<?php

namespace Tests\Feature\CashShift;

use App\Infrastructure\Persistence\Eloquent\CashShiftModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\PaymentMethodModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashShiftReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function sellerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_cash_shift_reconciliation_separates_cash_and_digital_payments(): void
    {
        $headers = $this->sellerHeaders();
        $cat = CategoryModel::first();

        $product1 = ProductModel::create([
            'name' => 'Mouse USB',
            'category_id' => $cat->id,
            'sku' => 'MOU-01',
            'cost_price' => 30.00,
            'sale_price' => 50.00,
            'stock' => 10,
            'min_stock' => 1,
            'status' => 'active',
        ]);

        $product2 = ProductModel::create([
            'name' => 'Teclado Mecánico',
            'category_id' => $cat->id,
            'sku' => 'TEC-01',
            'cost_price' => 120.00,
            'sale_price' => 200.00,
            'stock' => 10,
            'min_stock' => 1,
            'status' => 'active',
        ]);

        // 1. Abrir caja con Bs. 100
        $openRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 100.00,
        ], $headers)->assertStatus(201);
        $shiftId = $openRes->json('data.id');

        $cashMethod = PaymentMethodModel::where('type', 'cash')->first();
        $qrMethod = PaymentMethodModel::where('type', 'qr')->first();

        // 2. Venta 1: Efectivo Bs. 50
        $this->postJson('/api/sales', [
            'cash_shift_id' => $shiftId,
            'payment_method_id' => $cashMethod->id,
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 1, 'unit_price' => 50.00],
            ],
        ], $headers)->assertStatus(201);

        // 3. Venta 2: QR Bs. 200
        $this->postJson('/api/sales', [
            'cash_shift_id' => $shiftId,
            'payment_method_id' => $qrMethod->id,
            'reference_number' => 'QR-REC-112233',
            'items' => [
                ['product_id' => $product2->id, 'quantity' => 1, 'unit_price' => 200.00],
            ],
        ], $headers)->assertStatus(201);

        // 4. Consultar turno actual: verificar desglose digital
        $currentRes = $this->getJson('/api/cash-shifts/current', $headers);
        $currentRes->assertStatus(200)
            ->assertJsonPath('is_open', true)
            ->assertJsonPath('data.total_cash_sales', '50.00')
            ->assertJsonPath('data.total_qr_sales', '200.00')
            ->assertJsonStructure([
                'data' => [
                    'digital_totals_by_method',
                ],
            ]);

        // 5. Cerrar caja con arqueo físico de Bs. 150 (Bs. 100 apertura + Bs. 50 venta efectivo)
        $closeRes = $this->postJson('/api/cash-shifts/close', [
            'closing_amount' => 150.00,
            'notes' => 'Cierre cuadrado con desglose digital',
        ], $headers);

        $closeRes->assertStatus(200)
            ->assertJsonPath('data.expected_amount', '150.00')
            ->assertJsonPath('data.closing_amount', '150.00')
            ->assertJsonPath('data.difference', '0.00')
            ->assertJsonStructure([
                'data' => [
                    'digital_totals_by_method',
                ],
            ]);
    }
}
