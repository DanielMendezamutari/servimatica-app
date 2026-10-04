<?php

namespace Tests\Feature\Client;

use App\Infrastructure\Persistence\Eloquent\ClientModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Dueño Test',
            'username' => 'owner_crm',
            'email' => 'owner_crm@servimatica.com',
            'password' => bcrypt('password123'),
            'pin_code' => '1234',
            'role' => 'dueno',
            'is_active' => true,
        ]);

        $this->seller = User::create([
            'name' => 'Vendedor Test',
            'username' => 'seller_crm',
            'email' => 'seller_crm@servimatica.com',
            'password' => bcrypt('password123'),
            'pin_code' => '1234',
            'role' => 'vendedor',
            'is_active' => true,
        ]);
    }

    public function test_can_list_and_paginate_clients_with_search(): void
    {
        ClientModel::create([
            'name' => 'Comercial Mamoré',
            'nit_ci' => '1029384756',
            'phone' => '71234567',
            'client_type' => 'empresa',
            'city' => 'Trinidad',
            'is_active' => true,
        ]);

        ClientModel::create([
            'name' => 'Carlos Perez',
            'nit_ci' => '4928172',
            'phone' => '68901234',
            'client_type' => 'final',
            'city' => 'Trinidad',
            'is_active' => true,
        ]);

        $token = auth('api')->login($this->seller);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/clients?search=Mamoré');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Comercial Mamoré')
            ->assertJsonPath('data.0.client_type', 'empresa')
            ->assertJsonPath('data.0.whatsapp_url', 'https://wa.me/59171234567');
    }

    public function test_seller_and_owner_can_create_client_with_crm_fields(): void
    {
        $token = auth('api')->login($this->seller);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/clients', [
                'name' => 'Tecnología Beni SRL',
                'nit_ci' => '9988776655',
                'phone' => '77334455',
                'email' => 'contacto@tecnobeni.bo',
                'address' => 'Plaza Principal #10',
                'client_type' => 'mayorista',
                'city' => 'Trinidad',
                'notes' => 'Cliente preferencial con crédito a 15 días',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Tecnología Beni SRL')
            ->assertJsonPath('data.client_type', 'mayorista')
            ->assertJsonPath('data.client_type_label', 'Técnico / Mayorista')
            ->assertJsonPath('data.whatsapp_url', 'https://wa.me/59177334455');

        $this->assertDatabaseHas('clients', [
            'name' => 'Tecnología Beni SRL',
            'nit_ci' => '9988776655',
            'client_type' => 'mayorista',
        ]);
    }

    public function test_can_update_client_details(): void
    {
        $client = ClientModel::create([
            'name' => 'Cliente Antiguo',
            'phone' => '71112233',
            'client_type' => 'final',
        ]);

        $token = auth('api')->login($this->owner);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/clients/{$client->id}", [
                'name' => 'Cliente Actualizado SRL',
                'phone' => '72223344',
                'client_type' => 'empresa',
                'city' => 'Santa Cruz',
                'notes' => 'Sede central en Santa Cruz',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Cliente Actualizado SRL')
            ->assertJsonPath('data.client_type', 'empresa');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Cliente Actualizado SRL',
            'client_type' => 'empresa',
            'city' => 'Santa Cruz',
        ]);
    }

    public function test_owner_can_toggle_client_status_and_seller_is_forbidden(): void
    {
        $client = ClientModel::create([
            'name' => 'Cliente Inactivable',
            'is_active' => true,
        ]);

        $sellerToken = auth('api')->login($this->seller);
        $ownerToken = auth('api')->login($this->owner);

        // Vendedor intentando cambiar estado -> 403
        $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->patchJson("/api/v1/clients/{$client->id}/toggle-status")
            ->assertStatus(403);

        // Dueño cambiando estado -> 200
        $this->withHeader('Authorization', "Bearer {$ownerToken}")
            ->patchJson("/api/v1/clients/{$client->id}/toggle-status")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse($client->fresh()->is_active);
    }
}
