<?php

namespace Tests\Feature\Report;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\SupplierModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductKardexTest extends TestCase
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
            'name' => 'Cajero Vendedor',
            'username' => 'cajero1',
            'email' => 'cajero1@servimatica.com',
            'password' => 'password123',
            'pin' => '8888',
            'role' => 'vendedor',
        ], $headers);

        $token = $this->postJson('/api/auth/login', ['login' => 'cajero1', 'pin' => '8888'])->json('accessToken');
        return $this->cachedSellerHeaders = ['Authorization' => "Bearer $token"];
    }

    private function setupProductWithMovements(array $headers): int
    {
        $cat = CategoryModel::firstOrCreate(['name' => 'Componentes'], ['status' => 'active']);
        $product = ProductModel::create([
            'category_id' => $cat->id,
            'name' => 'Memoria RAM DDR4 16GB',
            'sku' => 'RAM-16GB-TEST',
            'cost_price' => 100.00,
            'sale_price' => 180.00,
            'stock' => 0,
            'status' => 'active',
        ]);

        // 1. Registrar movimiento inicial de stock: +5 unidades
        $this->postJson("/api/products/{$product->id}/stock", [
            'type' => 'in',
            'quantity' => 5,
            'reason' => 'Inventario Inicial',
        ], $headers);

        // 2. Proveedor y compra de 10 unidades a 130 Bs. cada una
        $supplier = SupplierModel::create([
            'name' => 'Mayorista Tech SRL',
            'contact_name' => 'Contacto Mayorista',
            'phone' => '70011223',
            'status' => 'active',
        ]);

        $this->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'FAC-9921',
            'purchase_date' => now()->format('Y-m-d'),
            'payment_condition' => 'contado',
            'payment_method' => 'efectivo',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => 130.00,
                    'new_sale_price' => 180.00,
                ],
            ],
        ], $headers)->assertCreated();

        // 3. Abrir turno de caja y realizar una venta POS de 3 unidades
        $shiftRes = $this->postJson('/api/cash-shifts/open', ['opening_amount' => 100.00], $headers)->assertCreated();
        $cashShiftId = $shiftRes->json('data.id');

        $this->postJson('/api/sales', [
            'cash_shift_id' => $cashShiftId,
            'payment_method' => 'efectivo',
            'client_name' => 'Cliente Kardex',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 180.00,
                ],
            ],
        ], $headers)->assertCreated();

        return $product->id;
    }

    public function test_owner_can_consult_kardex_with_complete_financial_valuation_and_cpp(): void
    {
        $headers = $this->ownerHeaders();
        $productId = $this->setupProductWithMovements($headers);

        $response = $this->getJson("/api/v1/kardex/{$productId}", $headers)
            ->assertOk()
            ->assertJsonPath('success', true);

        // Validar información del producto
        $response->assertJsonPath('data.product.name', 'Memoria RAM DDR4 16GB');
        $response->assertJsonPath('data.product.current_stock', 12); // 0 + 5 inicial + 10 compra - 3 venta = 12

        // Verificar que incluye datos financieros
        $this->assertNotNull($response->json('data.product.cost_price'));
        $this->assertNotNull($response->json('data.product.current_cpp'));

        $movements = $response->json('data.movements');
        $this->assertNotEmpty($movements);

        // Verificar que cada movimiento cuenta con bloque financiero para el dueño
        foreach ($movements as $m) {
            $this->assertArrayHasKey('financial', $m);
            $this->assertArrayHasKey('average_cost', $m['financial']);
            $this->assertArrayHasKey('balance_value', $m['financial']);
        }

        // Totales auditados
        $response->assertJsonPath('data.totals.final_balance_quantity', 12);
        $this->assertGreaterThan(0, $response->json('data.totals.final_balance_value'));
    }

    public function test_seller_sees_only_physical_kardex_without_financial_disclosure(): void
    {
        $ownerHeaders = $this->ownerHeaders();
        $productId = $this->setupProductWithMovements($ownerHeaders);

        $sellerHeaders = $this->sellerHeaders();

        $response = $this->getJson("/api/v1/kardex/{$productId}", $sellerHeaders)
            ->assertOk()
            ->assertJsonPath('success', true);

        // No debe divulgar costos ni saldos valorizados (Principio VI)
        $this->assertArrayNotHasKey('cost_price', $response->json('data.product'));
        $this->assertArrayNotHasKey('current_cpp', $response->json('data.product'));
        $this->assertArrayNotHasKey('total_value', $response->json('data.initial_balance'));
        $this->assertArrayNotHasKey('total_debit_amount', $response->json('data.totals'));
        $this->assertArrayNotHasKey('final_balance_value', $response->json('data.totals'));

        $movements = $response->json('data.movements');
        $this->assertNotEmpty($movements);

        foreach ($movements as $m) {
            $this->assertArrayHasKey('physical', $m);
            $this->assertArrayNotHasKey('financial', $m);
        }
    }

    public function test_owner_can_export_kardex_to_csv(): void
    {
        $headers = $this->ownerHeaders();
        $productId = $this->setupProductWithMovements($headers);

        $response = $this->get("/api/v1/kardex/{$productId}?export=csv", $headers);
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Memoria RAM DDR4 16GB', $content);
        $this->assertStringContainsString('Saldo Físico', $content);
    }
}
