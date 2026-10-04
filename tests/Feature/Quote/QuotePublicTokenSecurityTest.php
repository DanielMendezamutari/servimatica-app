<?php

namespace Tests\Feature\Quote;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\QuoteModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotePublicTokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_view_quote_by_uuid_token_without_authentication(): void
    {
        $this->seed();

        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        $headers = ['Authorization' => "Bearer $token"];

        $cat = CategoryModel::first();
        $product = ProductModel::create([
            'name' => 'Laptop Lenovo IdeaPad 3',
            'category_id' => $cat->id,
            'sku' => 'LAP-LEN-001',
            'cost_price' => 2800,
            'sale_price' => 3500,
            'stock' => 3,
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/quotes', [
            'client_name' => 'Carlos Cliente',
            'client_phone' => '78901234',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 1,
                    'unit_price' => 3500,
                ],
            ],
        ], $headers);

        $res->assertCreated();
        $publicToken = $res->json('data.public_token');
        $this->assertNotEmpty($publicToken);

        // 1. Consulta pública por UUID token (sin headers de auth)
        $publicRes = $this->getJson("/api/quotes/public/{$publicToken}");
        $publicRes->assertOk()
            ->assertJsonPath('data.quote.client_name', 'Carlos Cliente')
            ->assertJsonPath('data.quote.total_amount', '3500.00');

        // 2. Renderizado de vista de impresión / descarga pública
        $printRes = $this->get("/api/quotes/public/{$publicToken}/print");
        $printRes->assertOk();
        $printRes->assertSee('Laptop Lenovo IdeaPad 3');
        $printRes->assertSee('Carlos Cliente');
    }

    public function test_anonymous_access_to_sequential_quote_id_is_unauthorized(): void
    {
        $this->seed();

        $quote = QuoteModel::create([
            'quote_number' => 'PRF-000001',
            'seller_id' => 1,
            'client_name' => 'Privado',
            'subtotal' => 1000,
            'total_amount' => 1000,
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'active',
        ]);

        // Sin autenticación al endpoint interno secuencial -> debe retornar 401 Unauthorized
        $res = $this->getJson("/api/quotes/{$quote->id}/print");
        $res->assertUnauthorized();
    }

    public function test_invalid_uuid_token_returns_not_found(): void
    {
        $res = $this->getJson("/api/quotes/public/00000000-0000-0000-0000-000000000000");
        $res->assertNotFound();
    }

    public function test_whatsapp_quote_service_generates_public_token_link(): void
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        $headers = ['Authorization' => "Bearer $token"];

        $cat = CategoryModel::first();
        $product = ProductModel::create([
            'name' => 'Monitor Samsung 24',
            'category_id' => $cat->id,
            'sku' => 'MON-SAM-001',
            'cost_price' => 800,
            'sale_price' => 1100,
            'stock' => 4,
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/quotes', [
            'client_name' => 'Maria Lopez',
            'client_phone' => '71234567',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 1,
                    'unit_price' => 1100,
                ],
            ],
        ], $headers);


        $res->assertCreated();
        $quoteId = $res->json('data.id');
        $publicToken = $res->json('data.public_token');

        $whatsappRes = $this->getJson("/api/quotes/{$quoteId}/whatsapp-link", $headers);
        $whatsappRes->assertOk();

        $message = $whatsappRes->json('message_text');
        $this->assertStringContainsString("quotes/public/{$publicToken}", $message);
        $this->assertStringNotContainsString("quotes/{$quoteId}/print", $message);
    }
}

