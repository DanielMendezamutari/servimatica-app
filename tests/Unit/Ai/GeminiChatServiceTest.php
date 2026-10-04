<?php

namespace Tests\Unit\Ai;

use App\Application\Ai\GeminiCatalogContextBuilder;
use App\Application\Ai\GeminiChatService;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\CompanySettingModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiChatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CompanySettingModel::create([
            'trade_name' => 'Servimática PC',
            'legal_name' => 'Servimática SRL',
            'nit' => '12345678',
            'city' => 'Santa Cruz de la Sierra',
            'address' => 'Comercial Chiriguano pasillo 2 local # 333',
            'phone' => '33445566',
            'mobile' => '67369293',
            'currency_code' => 'BOB',
            'currency_symbol' => 'Bs.',
        ]);

        $cat = CategoryModel::create([
            'name' => 'Laptops',
            'description' => 'Portátiles',
            'status' => 'active',
        ]);

        ProductModel::create([
            'category_id' => $cat->id,
            'name' => 'Laptop Gamer Asus Rog',
            'description' => 'RTX 4060 16GB RAM',
            'condition' => 'nuevo',
            'sku' => 'LAP-ROG-001',
            'cost_price' => 5000.00,
            'sale_price' => 7500.00,
            'stock' => 5,
            'min_stock' => 1,
            'warranty_days' => 365,
            'status' => 'active',
            'gallery_images' => ['products/gallery/1.webp', 'products/gallery/2.webp'],
        ]);
    }

    public function test_catalog_context_builder_includes_active_products_and_protects_costs(): void
    {
        $builder = new GeminiCatalogContextBuilder();
        $prompt = $builder->buildSystemPrompt();

        $this->assertStringContainsString('Laptop Gamer Asus Rog', $prompt);
        $this->assertStringContainsString('Bs. 7,500.00', $prompt);
        $this->assertStringContainsString('Comercial Chiriguano pasillo 2 local # 333', $prompt);
        $this->assertStringContainsString('365 días de garantía', $prompt);
        $this->assertStringContainsString('catalogo?product=', $prompt);
        $this->assertStringContainsString('Cuenta con fotos multi-ángulo 360°', $prompt);

        // Seguridad: NUNCA debe contener el costo de compra (5000.00)
        $this->assertStringNotContainsString('5000', $prompt);
        $this->assertStringNotContainsString('cost_price', $prompt);
    }

    public function test_detects_human_agent_request(): void
    {
        $builder = new GeminiCatalogContextBuilder();
        $service = new GeminiChatService($builder);

        $this->assertTrue($service->detectHumanRequest('Por favor quiero hablar con un asesor'));
        $this->assertTrue($service->detectHumanRequest('necesito hablar con una persona'));
        $this->assertFalse($service->detectHumanRequest('¿Cuánto cuesta la laptop rog?'));

        $response = $service->reply('Quiero hablar con un asesor humano');
        $this->assertTrue($response['is_human_requested']);
        $this->assertStringContainsString('asesores de tienda', $response['reply']);
    }

    public function test_reply_handles_gemini_api_response(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '¡Hola! Te recomiendo la *Laptop Gamer Asus Rog* por *Bs. 7,500.00*.'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'totalTokenCount' => 140,
                ],
            ], 200),
        ]);

        config(['services.gemini.api_key' => 'fake-api-key']);

        $builder = new GeminiCatalogContextBuilder();
        $service = new GeminiChatService($builder);

        $res = $service->reply('¿Qué laptop potente tienen?');

        $this->assertFalse($res['is_human_requested']);
        $this->assertStringContainsString('Laptop Gamer Asus Rog', $res['reply']);
        $this->assertEquals(140, $res['tokens_used']);
    }
}
