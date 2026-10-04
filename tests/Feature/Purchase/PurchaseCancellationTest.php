<?php

namespace Tests\Feature\Purchase;

use App\Models\User;
use App\Infrastructure\Persistence\Eloquent\BrandModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SupplierModel;
use App\Infrastructure\Persistence\Eloquent\StockMovementModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PurchaseCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function getOwnerToken(): string
    {
        User::create([
            'name' => 'Dueño Test',
            'username' => 'dueno',
            'email' => 'dueno@servimatica.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('1234'),
            'role' => 'dueno',
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/auth/login', [
            'login' => 'dueno',
            'pin' => '1234',
        ])->assertOk();

        return $res->json('accessToken');
    }

    private function getSellerToken(): string
    {
        User::create([
            'name' => 'Vendedor Test',
            'username' => 'vendedor',
            'email' => 'vendedor@servimatica.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('5678'),
            'role' => 'vendedor',
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/auth/login', [
            'login' => 'vendedor',
            'pin' => '5678',
        ])->assertOk();

        return $res->json('accessToken');
    }

    public function test_owner_can_cancel_purchase_and_stock_is_restored(): void
    {
        $token = $this->getOwnerToken();
        $headers = ['Authorization' => "Bearer {$token}"];

        $supplier = SupplierModel::create([
            'name' => 'Intcomex Bolivia',
            'nit' => '3049582011',
            'phone' => '71122334',
            'is_active' => true,
        ]);

        $category = CategoryModel::create([
            'name' => 'Componentes',
            'status' => 'active',
        ]);

        $product = ProductModel::create([
            'name' => 'Memoria RAM DDR4 16GB Kingston',
            'sku' => 'RAM-DDR4-16G',
            'category_id' => $category->id,
            'cost_price' => 280.00,
            'sale_price' => 350.00,
            'stock' => 5,
            'min_stock' => 2,
            'status' => 'active',
        ]);

        // Receive purchase of 10 units (stock goes from 5 to 15)
        $purchaseRes = $this->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'FC-778899',
            'purchase_date' => '2026-10-03',
            'payment_condition' => 'contado',
            'payment_method' => 'transferencia',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => 275.00,
                ],
            ],
        ], $headers)->assertCreated();

        $purchaseId = $purchaseRes->json('data.id');
        $product->refresh();
        $this->assertEquals(15, $product->stock);

        // Cancel purchase with valid reason
        $cancelRes = $this->postJson("/api/purchases/{$purchaseId}/cancel", [
            'reason' => 'Factura duplicada por el distribuidor, devuelto al transporte',
        ], $headers)->assertOk();

        $cancelRes->assertJsonPath('data.status', 'cancelled');

        // Stock restored from 15 back to 5
        $product->refresh();
        $this->assertEquals(5, $product->stock);

        // Verify out movement audit log
        $outMovement = StockMovementModel::where('product_id', $product->id)
            ->where('type', 'out')
            ->first();

        $this->assertNotNull($outMovement);
        $this->assertEquals(10, $outMovement->quantity);
        $this->assertEquals(15, $outMovement->previous_stock);
        $this->assertEquals(5, $outMovement->new_stock);
        $this->assertStringContainsString('Factura duplicada', $outMovement->reason);
    }

    public function test_purchase_cancellation_is_rejected_when_stock_is_insufficient(): void
    {
        $token = $this->getOwnerToken();
        $headers = ['Authorization' => "Bearer {$token}"];

        $supplier = SupplierModel::create([
            'name' => 'TechZone Bolivia',
            'is_active' => true,
        ]);

        $category = CategoryModel::create([
            'name' => 'Periféricos',
            'status' => 'active',
        ]);

        $product = ProductModel::create([
            'name' => 'Mouse Gamer Logitech G203',
            'sku' => 'MOU-LOG-G203',
            'category_id' => $category->id,
            'cost_price' => 120.00,
            'sale_price' => 170.00,
            'stock' => 0,
            'min_stock' => 1,
            'status' => 'active',
        ]);

        // Purchase 5 units (stock becomes 5)
        $purchaseRes = $this->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'FC-5544',
            'purchase_date' => '2026-10-03',
            'payment_condition' => 'contado',
            'payment_method' => 'efectivo',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_cost' => 120.00,
                ],
            ],
        ], $headers)->assertCreated();

        $purchaseId = $purchaseRes->json('data.id');

        // Simulate sales or reduction of stock so only 2 remain
        $product->update(['stock' => 2]);

        // Trying to cancel 5 units when only 2 exist must fail with 422
        $cancelFail = $this->postJson("/api/purchases/{$purchaseId}/cancel", [
            'reason' => 'Error en factura',
        ], $headers)->assertStatus(422);

        $cancelFail->assertJsonFragment([
            'purchase' => ["No se puede anular la compra. El producto 'Mouse Gamer Logitech G203' solo tiene 2 unidades disponibles en inventario (se requieren 5 para la devolución)."]
        ]);

        // Stock must still be 2 (no negative stock allowed)
        $product->refresh();
        $this->assertEquals(2, $product->stock);
    }
}
