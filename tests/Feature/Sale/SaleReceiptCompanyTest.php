<?php

namespace Tests\Feature\Sale;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\CompanySettingModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleReceiptCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_receipt_renders_dynamic_company_settings(): void
    {
        $this->seed();

        // 1. Configurar datos personalizados en CompanySettings
        $setting = CompanySettingModel::firstOrCreate(['id' => 1]);
        $setting->update([
            'trade_name' => 'Servimática Sucursal Central',
            'legal_name' => 'Servimática Norte SRL',
            'tax_id' => '1234567890',
            'branch_name' => 'Sucursal Riberalta',
            'city' => 'Riberalta, Beni — Bolivia',
            'address' => 'Calle Sucre #250',
            'mobile' => '76543210',
            'phone' => '38522222',
            'receipt_footer_message' => 'Garantía legal Servimática 90 días en mano de obra.',
        ]);

        // 2. Login de administrador
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        $headers = ['Authorization' => "Bearer $token"];

        $cat = CategoryModel::first();
        $product = ProductModel::create([
            'name' => 'Memoria RAM DDR4 8GB',
            'category_id' => $cat->id,
            'sku' => 'RAM-DDR4-8GB',
            'cost_price' => 120.00,
            'sale_price' => 180.00,
            'stock' => 10,
            'min_stock' => 2,
            'status' => 'active',
        ]);

        $openShift = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 100.00,
        ], $headers)->assertCreated();
        $shiftId = $openShift->json('data.id');

        // 3. Crear venta
        $saleRes = $this->postJson('/api/sales', [
            'cash_shift_id' => $shiftId,
            'client_name' => 'Carlos Mendoza',
            'client_nit_ci' => '4567890',
            'payment_method' => 'efectivo',
            'cash_tendered' => 200.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 180.00,
                ],
            ],
        ], $headers)->assertCreated();

        $saleId = $saleRes->json('data.id');

        // 4. Consultar ticket térmico de 80mm
        $response = $this->get("/api/sales/{$saleId}/receipt");

        $response->assertOk();
        $content = $response->getContent();

        // 5. Validar presencia de datos institucionales dinámicos
        $this->assertStringContainsString('Servimática Sucursal Central', $content);
        $this->assertStringContainsString('Riberalta, Beni — Bolivia', $content);
        $this->assertStringContainsString('Calle Sucre #250', $content);
        $this->assertStringContainsString('1234567890', $content);
        $this->assertStringContainsString('Garantía legal Servimática 90 días en mano de obra.', $content);
    }
}
