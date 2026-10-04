<?php

namespace Tests\Feature\Client;

use App\Infrastructure\Persistence\Eloquent\ClientModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientExportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Dueño Export',
            'username' => 'owner_export',
            'email' => 'owner_export@servimatica.com',
            'password' => bcrypt('password123'),
            'pin_code' => '1234',
            'role' => 'dueno',
            'is_active' => true,
        ]);

        $this->seller = User::create([
            'name' => 'Vendedor Export',
            'username' => 'seller_export',
            'email' => 'seller_export@servimatica.com',
            'password' => bcrypt('password123'),
            'pin_code' => '1234',
            'role' => 'vendedor',
            'is_active' => true,
        ]);

        ClientModel::create([
            'name' => 'Cliente Exportable 1',
            'nit_ci' => '11223344',
            'phone' => '71112233',
            'client_type' => 'final',
            'city' => 'Trinidad',
            'is_active' => true,
        ]);
    }

    public function test_owner_can_export_clients_to_csv(): void
    {
        $token = auth('api')->login($this->owner);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/api/v1/clients/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="clientes_servimatica_', $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        $this->assertStringContainsString('Cliente Exportable 1', $content);
        $this->assertStringContainsString('11223344', $content);
        $this->assertStringContainsString('https://wa.me/59171112233', $content);
    }

    public function test_seller_is_forbidden_from_exporting_clients(): void
    {
        $token = auth('api')->login($this->seller);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/clients/export');

        $response->assertStatus(403);
    }
}
