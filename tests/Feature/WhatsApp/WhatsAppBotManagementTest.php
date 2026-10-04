<?php

namespace Tests\Feature\WhatsApp;

use App\Infrastructure\Persistence\Eloquent\WhatsAppConversationModel;
use App\Infrastructure\Persistence\Eloquent\WhatsAppMessageModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppBotManagementTest extends TestCase
{
    use RefreshDatabase;

    private function ownerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    private function sellerHeaders(): array
    {
        $headers = $this->ownerHeaders();
        $this->postJson('/api/users', [
            'name' => 'Vendedor Mostrador',
            'username' => 'mostrador',
            'email' => 'mostrador@servimatica.com',
            'password' => 'password123',
            'pin' => '5555',
            'role' => 'vendedor',
        ], $headers);

        $token = $this->postJson('/api/auth/login', ['login' => 'mostrador', 'pin' => '5555'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_owner_can_get_and_update_settings(): void
    {
        $headers = $this->ownerHeaders();

        // 1. Obtener settings iniciales
        $response = $this->getJson('/api/whatsapp-bot/settings', $headers);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'settings' => ['is_active', 'gemini_model', 'has_gemini_key', 'gateway_url'],
                'stats' => ['total_conversations', 'active_conversations', 'human_agent_conversations'],
            ]);

        // 2. Actualizar configuración
        $updateResponse = $this->postJson('/api/whatsapp-bot/settings', [
            'is_active' => false,
            'gemini_model' => 'gemini-1.5-pro',
            'gateway_url' => 'http://192.168.1.50:8080',
        ], $headers);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('settings.is_active', false)
            ->assertJsonPath('settings.gemini_model', 'gemini-1.5-pro');
    }

    public function test_owner_can_simulate_chat(): void
    {
        $headers = $this->ownerHeaders();

        $response = $this->postJson('/api/whatsapp-bot/simulate', [
            'message' => '¿Tienen laptops disponibles para entrega inmediata?',
        ], $headers);

        $response->assertStatus(200)
            ->assertJsonStructure(['reply', 'tokens_used', 'is_human_requested'])
            ->assertJsonPath('is_human_requested', false);

        $this->assertNotEmpty($response->json('reply'));
    }

    public function test_owner_can_list_conversations_and_reset_handoff(): void
    {
        $headers = $this->ownerHeaders();

        // Crear una conversación con handoff a humano
        $conv = WhatsAppConversationModel::create([
            'phone_number' => '59170011223',
            'customer_name' => 'María López',
            'status' => 'human_agent',
            'human_handoff_until' => now()->addHours(24),
            'last_message_at' => now(),
        ]);

        $conv->messages()->create([
            'direction' => 'inbound',
            'message' => 'Deseo hablar con un humano',
        ]);

        // Listar
        $listResponse = $this->getJson('/api/whatsapp-bot/conversations', $headers);
        $listResponse->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.phone_number', '59170011223')
            ->assertJsonPath('data.0.is_human_handoff_active', true);

        // Reset handoff
        $resetResponse = $this->postJson("/api/whatsapp-bot/conversations/{$conv->id}/reset-handoff", [], $headers);
        $resetResponse->assertStatus(200);

        $conv->refresh();
        $this->assertEquals('active', $conv->status);
        $this->assertNull($conv->human_handoff_until);
    }

    public function test_seller_cannot_access_bot_management(): void
    {
        $sellerHeaders = $this->sellerHeaders();

        $response = $this->getJson('/api/whatsapp-bot/settings', $sellerHeaders);
        $response->assertStatus(403);

        $simResponse = $this->postJson('/api/whatsapp-bot/simulate', ['message' => 'hola'], $sellerHeaders);
        $simResponse->assertStatus(403);
    }
}
