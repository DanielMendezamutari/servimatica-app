<?php

namespace Tests\Feature\Quote;

use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\CompanySettingModel;
use App\Infrastructure\Persistence\Eloquent\PaymentMethodModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteDynamicWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_message_is_persuasive_commercial_and_dynamically_populated_from_database(): void
    {
        $this->seed();

        // 1. Configurar datos personalizados en CompanySettings
        $setting = CompanySettingModel::firstOrCreate(['id' => 1]);
        $setting->update([
            'trade_name' => 'Servimática Trinidad Premium',
            'slogan' => 'Líderes en Tecnología y Soporte Técnico',
            'city' => 'Trinidad, Beni — Bolivia',
            'address' => 'Calle Gil Coimbra #120, Zona Central',
            'mobile' => '71234567',
        ]);

        // 2. Configurar Métodos de Pago
        PaymentMethodModel::create([
            'name' => 'Pago Simple QR',
            'type' => 'qr',
            'is_active' => true,
            'applies_to' => 'both',
            'sort_order' => 1,
        ]);

        PaymentMethodModel::create([
            'name' => 'Transferencia BNB',
            'type' => 'bank_transfer',
            'bank_name' => 'Banco Nacional de Bolivia',
            'account_number' => '450-01928374',
            'account_holder' => 'Servimática SRL',
            'is_active' => true,
            'applies_to' => 'both',
            'sort_order' => 2,
        ]);

        // 3. Crear Producto con 365 días de garantía
        $cat = CategoryModel::first();
        $product = ProductModel::create([
            'name' => 'Laptop Gamer ASUS ROG Strix',
            'category_id' => $cat->id,
            'sku' => 'LAP-ROG-001',
            'cost_price' => 7000.00,
            'sale_price' => 8500.00,
            'stock' => 5,
            'min_stock' => 1,
            'warranty_days' => 365,
            'status' => 'active',
        ]);

        // 4. Crear Cotización
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        $headers = ['Authorization' => "Bearer $token"];

        $quoteRes = $this->postJson('/api/quotes', [
            'client_name' => 'Jhoshua Yaune',
            'client_phone' => '71299887',
            'discount_amount' => 100.00,
            'valid_days' => 7,
            'notes' => 'Incluye mouse inalámbrico de cortesía',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 1,
                    'unit_price' => 8500.00,
                ],
            ],
        ], $headers)->assertCreated();

        $quoteId = $quoteRes->json('data.id');

        // 5. Consultar endpoint whatsapp-link
        $res = $this->getJson("/api/quotes/{$quoteId}/whatsapp-link", $headers)->assertOk();

        $whatsappLink = $res->json('whatsapp_link');
        $messageText = $res->json('message_text');
        $formattedMessage = $res->json('formatted_message');

        // Validar estructura de respuesta
        $this->assertNotEmpty($whatsappLink);
        $this->assertEquals($messageText, $formattedMessage);
        $this->assertStringContainsString('wa.me/59171299887', $whatsappLink);

        // Validar persuasión y datos dinámicos en el mensaje
        $this->assertStringContainsString('Servimática Trinidad Premium', $messageText);
        $this->assertStringContainsString('Jhoshua Yaune', $messageText);
        $this->assertStringContainsString('Laptop Gamer ASUS ROG Strix', $messageText);
        $this->assertStringContainsString('12 meses (1 año) de Garantía Oficial', $messageText);
        $this->assertStringContainsString('Configuración inicial y programas esenciales sin costo', $messageText);
        $this->assertStringContainsString('Banco Nacional de Bolivia', $messageText);
        $this->assertStringContainsString('Pago rápido con QR', $messageText);
        $publicToken = $quoteRes->json('data.public_token');
        $this->assertStringContainsString('/api/quotes/public/' . $publicToken . '/print', $messageText);
        $this->assertStringContainsString('¿Desea que se lo reservemos para entrega hoy mismo?', $messageText);
        $this->assertStringContainsString('Trinidad', $messageText);


        // Validar ausencia de símbolos corruptos Unicode de reemplazo (\u{FFFD})
        $this->assertStringNotContainsString("\u{FFFD}", $messageText);
        $this->assertStringNotContainsString("\u{FFFD}", $whatsappLink);
    }

    public function test_whatsapp_message_formats_dual_autonomous_warranties_cleanly(): void
    {
        $this->seed();

        $cat = CategoryModel::first();
        $product = ProductModel::create([
            'name' => 'Laptop HP Omen 16',
            'category_id' => $cat->id,
            'sku' => 'LAP-OMEN-16',
            'cost_price' => 8000.00,
            'sale_price' => 9980.00,
            'stock' => 3,
            'min_stock' => 1,
            'warranty_hardware_days' => 730,
            'warranty_software_days' => 90,
            'status' => 'active',
        ]);

        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        $headers = ['Authorization' => "Bearer $token"];

        $quoteRes = $this->postJson('/api/quotes', [
            'client_name' => 'Cliente Dual',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => 1,
                    'unit_price' => 9980.00,
                ],
            ],
        ], $headers)->assertCreated();

        $quoteId = $quoteRes->json('data.id');
        $res = $this->getJson("/api/quotes/{$quoteId}/whatsapp-link", $headers)->assertOk();
        $messageText = $res->json('message_text');

        $this->assertStringContainsString('HW: 24 meses (2 años) | Software: 3 meses', $messageText);
        $this->assertStringNotContainsString("\u{FFFD}", $messageText);
    }
}
