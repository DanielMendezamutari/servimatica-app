<?php

namespace Tests\Feature\WhatsApp;

use App\Domain\WhatsApp\WhatsAppGatewayInterface;
use App\Infrastructure\Persistence\Eloquent\BrandModel;
use App\Infrastructure\Persistence\Eloquent\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Persistence\Eloquent\WhatsAppConversationModel;
use App\Infrastructure\Persistence\Eloquent\WhatsAppMessageModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Gateway mock o fake
        $gatewayMock = $this->createMock(WhatsAppGatewayInterface::class);
        $gatewayMock->method('sendMessage')->willReturn(true);
        $this->app->instance(WhatsAppGatewayInterface::class, $gatewayMock);
    }

    public function test_webhook_handshake_verification(): void
    {
        $response = $this->get('/api/webhooks/whatsapp?hub_challenge=123456');
        $response->assertStatus(200);
        $this->assertEquals('123456', $response->getContent());

        $statusResponse = $this->getJson('/api/webhooks/whatsapp');
        $statusResponse->assertStatus(200)
            ->assertJsonPath('status', 'online');
    }

    public function test_ignores_outgoing_message_from_self(): void
    {
        $response = $this->postJson('/api/webhooks/whatsapp', [
            'data' => [
                'key' => [
                    'remoteJid' => '59178901234@s.whatsapp.net',
                    'fromMe' => true,
                ],
                'message' => [
                    'conversation' => 'Hola, te habla el bot',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ignored_self_message');

        $this->assertEquals(0, WhatsAppConversationModel::count());
    }

    public function test_ignores_group_message(): void
    {
        $response = $this->postJson('/api/webhooks/whatsapp', [
            'data' => [
                'key' => [
                    'remoteJid' => '1234567890-987654@g.us',
                    'fromMe' => false,
                ],
                'message' => [
                    'conversation' => 'Hola a todo el grupo',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ignored_group_message');
    }

    public function test_processes_inbound_message_and_creates_records(): void
    {
        $response = $this->postJson('/api/webhooks/whatsapp', [
            'data' => [
                'key' => [
                    'remoteJid' => '59178901234@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'pushName' => 'Carlos Cliente',
                'message' => [
                    'conversation' => 'Hola, qué precio tiene una laptop para diseño gráfico?',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'processed')
            ->assertJsonPath('result.replied', true);

        $conversation = WhatsAppConversationModel::where('phone_number', '59178901234')->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('Carlos Cliente', $conversation->customer_name);
        $this->assertEquals('active', $conversation->status);

        // Debería existir el mensaje de entrada y el mensaje de salida
        $this->assertCount(2, $conversation->messages);
        $this->assertEquals('inbound', $conversation->messages[0]->direction);
        $this->assertEquals('outbound', $conversation->messages[1]->direction);
    }

    public function test_handles_human_handoff_when_customer_requests_human(): void
    {
        $response = $this->postJson('/api/webhooks/whatsapp', [
            'data' => [
                'key' => [
                    'remoteJid' => '59178901234@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'pushName' => 'Carlos Cliente',
                'message' => [
                    'conversation' => 'Por favor, quiero hablar con un asesor humano en tienda',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'processed')
            ->assertJsonPath('result.is_human_requested', true);

        $conversation = WhatsAppConversationModel::where('phone_number', '59178901234')->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('human_agent', $conversation->status);
        $this->assertTrue($conversation->isHumanHandoffActive());

        // Mensaje subsiguiente durante handoff no debe responder automáticamente
        $secondResponse = $this->postJson('/api/webhooks/whatsapp', [
            'data' => [
                'key' => [
                    'remoteJid' => '59178901234@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'message' => [
                    'conversation' => 'Sigo esperando al asesor',
                ],
            ],
        ]);

        $secondResponse->assertStatus(200)
            ->assertJsonPath('result.replied', false)
            ->assertJsonPath('result.reason', 'human_agent_active');
    }
}
