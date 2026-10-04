<?php

namespace Tests\Feature\Purchase;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\PaymentMethodModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SupplierModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private function ownerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_purchase_records_payment_method_and_reference(): void
    {
        $headers = $this->ownerHeaders();
        $cat = CategoryModel::first();

        $supplier = SupplierModel::create([
            'name' => 'Distribuidora Mayorista Santa Cruz',
            'nit' => '1029384756',
            'phone' => '71234567',
            'email' => 'ventas@distribuidora.bo',
            'is_active' => true,
        ]);

        $product = ProductModel::create([
            'name' => 'Memoria RAM DDR4 16GB Kingston',
            'category_id' => $cat->id,
            'sku' => 'RAM-KNG-16',
            'cost_price' => 200.00,
            'sale_price' => 280.00,
            'stock' => 5,
            'min_stock' => 2,
            'status' => 'active',
        ]);

        $bankMethod = PaymentMethodModel::where('type', 'bank_transfer')->first();
        $this->assertNotNull($bankMethod);

        $purchaseRes = $this->postJson('/api/purchases', [
            'invoice_number' => 'FAC-2026-999',
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-10-03',
            'payment_condition' => 'contado',
            'payment_method_id' => $bankMethod->id,
            'reference_number' => 'BCP-EGR-554433',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => 195.00,
                    'new_sale_price' => 280.00,
                ],
            ],
        ], $headers);

        $purchaseRes->assertStatus(201);
        $purchaseId = $purchaseRes->json('data.id');

        $this->assertDatabaseHas('purchases', [
            'id' => $purchaseId,
            'payment_method_id' => $bankMethod->id,
            'reference_number' => 'BCP-EGR-554433',
        ]);

        $showRes = $this->getJson("/api/purchases/{$purchaseId}", $headers);
        $showRes->assertStatus(200)
            ->assertJsonPath('data.payment_method_id', $bankMethod->id)
            ->assertJsonPath('data.reference_number', 'BCP-EGR-554433');
    }
}
