<?php

namespace Tests\Feature\Quote;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteManagementTest extends TestCase
{
    use RefreshDatabase;

    private function ownerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_seller_creates_quote_without_affecting_stock_and_generates_whatsapp(): void
    {
        $headers = $this->ownerHeaders();
        $cat = CategoryModel::first();

        // 1. Crear 2 productos con stock
        $p1 = ProductModel::create([
            'name' => 'Monitor Gamer ASUS 24"',
            'category_id' => $cat->id,
            'sku' => 'MON-ASUS-24',
            'cost_price' => 800.00,
            'sale_price' => 1200.00,
            'stock' => 10,
            'min_stock' => 2,
            'status' => 'active',
        ]);

        $p2 = ProductModel::create([
            'name' => 'Teclado Mecánico RGB',
            'category_id' => $cat->id,
            'sku' => 'TEC-RGB-001',
            'cost_price' => 150.00,
            'sale_price' => 250.00,
            'stock' => 15,
            'min_stock' => 3,
            'status' => 'active',
        ]);

        $initialStock1 = (int) $p1->stock;
        $initialStock2 = (int) $p2->stock;

        // 2. Crear cliente
        $clientRes = $this->postJson('/api/clients', [
            'name' => 'Empresa Andina SRL',
            'nit_ci' => '1029384756',
            'phone' => '77012345',
            'email' => 'compras@andina.bo',
        ], $headers)->assertCreated();

        $clientId = $clientRes->json('data.id');

        // 3. Emitir Proforma / Cotización (Subtotal: 2*1200 + 1*250 = 2650. Descuento: 50. Total: 2600)
        $quoteRes = $this->postJson('/api/quotes', [
            'client_id' => $clientId,
            'client_name' => 'Empresa Andina SRL',
            'client_phone' => '77012345',
            'discount_amount' => 50.00,
            'valid_days' => 10,
            'notes' => 'Precios especiales por volumen',
            'items' => [
                [
                    'product_id' => $p1->id,
                    'product_name' => $p1->name,
                    'quantity' => 2,
                    'unit_price' => (float) $p1->sale_price,
                ],
                [
                    'product_id' => $p2->id,
                    'product_name' => $p2->name,
                    'quantity' => 1,
                    'unit_price' => (float) $p2->sale_price,
                ],
            ],
        ], $headers)->assertCreated();

        $quoteId = $quoteRes->json('data.id');
        $this->assertStringStartsWith('PRF-', $quoteRes->json('data.quote_number'));
        $this->assertStringContainsString('wa.me/59177012345', $quoteRes->json('data.whatsapp_link'));
        $this->assertEquals('2600.00', $quoteRes->json('data.total_amount'));

        // 4. VERIFICACIÓN CRÍTICA: El stock de ambos productos debe permanecer intacto
        $p1Fresh = ProductModel::find($p1->id);
        $p2Fresh = ProductModel::find($p2->id);

        $this->assertEquals($initialStock1, (int) $p1Fresh->stock, 'El stock del producto 1 no debió descontarse por una proforma.');
        $this->assertEquals($initialStock2, (int) $p2Fresh->stock, 'El stock del producto 2 no debió descontarse por una proforma.');

        // 5. Consultar detalle de la proforma
        $showRes = $this->getJson("/api/quotes/{$quoteId}", $headers)->assertOk();
        $this->assertCount(2, $showRes->json('data.items'));
        $this->assertEquals('50.00', $showRes->json('data.discount_amount'));

        // 6. Consultar enlace de WhatsApp
        $waRes = $this->getJson("/api/quotes/{$quoteId}/whatsapp-link", $headers)->assertOk();
        $this->assertStringContainsString('wa.me/59177012345', $waRes->json('whatsapp_link'));
        $this->assertStringContainsString('SERVIMÁTICA', $waRes->json('message_text'));

        // 7. Consultar vista de impresión formal
        $printRes = $this->get("/api/quotes/{$quoteId}/print", $headers)->assertOk();
        $this->assertStringContainsString('COTIZACIÓN / PROFORMA', $printRes->getContent());
        $this->assertStringContainsString('Empresa Andina SRL', $printRes->getContent());
    }
}
