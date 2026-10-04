<?php

namespace Tests\Feature\Quote;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\CompanySettingModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteDynamicCompanyPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_quote_renders_dynamic_company_information(): void
    {
        $this->seed();

        // 1. Configurar datos personalizados en CompanySettings
        $setting = CompanySettingModel::firstOrCreate(['id' => 1]);
        $setting->update([
            'trade_name' => 'Servimática Riberalta',
            'legal_name' => 'Servimática Norte SRL',
            'tax_id' => '9876543210',
            'slogan' => 'Tecnología de punta en la Amazonía',
            'branch_name' => 'Sucursal Riberalta Central',
            'city' => 'Riberalta, Beni — Bolivia',
            'address' => 'Av. Gabriel René Moreno #123',
            'mobile' => '78901234',
            'phone' => '38521111',
            'email' => 'riberalta@servimatica.com.bo',
            'default_quote_terms' => "Garantía de 12 meses en hardware.\nPrecios en Bolivianos válidos por 7 días.\nSujeto a disponibilidad.",
        ]);

        // 2. Crear datos de prueba para cotización
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        $headers = ['Authorization' => "Bearer $token"];

        $cat = CategoryModel::first();
        $product = ProductModel::create([
            'name' => 'Laptop HP ProBook',
            'category_id' => $cat->id,
            'sku' => 'LAP-HP-001',
            'cost_price' => 3500.00,
            'sale_price' => 4500.00,
            'stock' => 5,
            'min_stock' => 1,
            'status' => 'active',
        ]);

        $quoteRes = $this->postJson('/api/quotes', [
            'client_name' => 'Alcaldía de Riberalta',
            'client_phone' => '71122334',
            'discount_amount' => 100.00,
            'valid_days' => 7,
            'notes' => 'Cotización oficial para licitación',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 2,
                    'unit_price' => 4500.00,
                ],
            ],
        ], $headers)->assertCreated();

        $quoteId = $quoteRes->json('data.id');

        // 3. Consultar vista de impresión
        $response = $this->get("/api/quotes/{$quoteId}/print", $headers);


        $response->assertOk();
        $content = $response->getContent();

        // Verificar datos dinámicos inyectados
        $this->assertStringContainsString('Servimática Riberalta', $content);
        $this->assertStringContainsString('Riberalta, Beni — Bolivia', $content);
        $this->assertStringContainsString('Av. Gabriel René Moreno #123', $content);
        $this->assertStringContainsString('78901234', $content);
        $this->assertStringContainsString('9876543210', $content);
        $this->assertStringContainsString('Tecnología de punta en la Amazonía', $content);
        $this->assertStringContainsString('Garantía de 12 meses en hardware.', $content);
        $this->assertStringContainsString('Precios en Bolivianos válidos por 7 días.', $content);
        $this->assertStringContainsString('Garantía Técnica', $content);
        $this->assertStringContainsString('Cuentas Bancarias y Medios de Pago Habilitados', $content);
        $this->assertStringContainsString('logo_servimatica.png', $content);
        $this->assertStringNotContainsString('Trinidad, Beni — Bolivia', $content);
    }
}
