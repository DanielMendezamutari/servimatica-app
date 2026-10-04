<?php

namespace Tests\Feature\Audit;

use App\Infrastructure\Persistence\Eloquent\LoginLogModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginAuditTest extends TestCase
{
    use RefreshDatabase;

    private function ownerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_successful_login_is_audited(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/login', [
            'login' => 'admin',
            'pin' => '1234',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('login_logs', [
            'attempted_username' => 'admin',
            'status' => 'success',
        ]);
    }

    public function test_failed_credentials_login_is_audited(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/login', [
            'login' => 'admin',
            'pin' => '9999',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseHas('login_logs', [
            'attempted_username' => 'admin',
            'status' => 'failed_credentials',
        ]);
    }

    public function test_inactive_user_login_is_audited_as_inactive(): void
    {
        $headers = $this->ownerHeaders();

        // Crear usuario
        $createUserRes = $this->postJson('/api/users', [
            'name' => 'Empleado Inactivo',
            'username' => 'inactivotest',
            'email' => 'inactivo@servimatica.com',
            'password' => 'pass123',
            'pin' => '4444',
            'role' => 'vendedor',
        ], $headers);

        $userId = $createUserRes->json('data.id');

        // Desactivar usuario
        $this->patchJson("/api/users/{$userId}/toggle-status", [], $headers);

        // Intento de login con cuenta inactiva
        $loginRes = $this->postJson('/api/auth/login', [
            'login' => 'inactivotest',
            'pin' => '4444',
        ]);

        $loginRes->assertStatus(403);

        $this->assertDatabaseHas('login_logs', [
            'attempted_username' => 'inactivotest',
            'status' => 'failed_inactive_user',
        ]);
    }

    public function test_owner_can_list_audit_logs(): void
    {
        $headers = $this->ownerHeaders();

        // Provocar un fallo
        $this->postJson('/api/auth/login', [
            'login' => 'desconocido',
            'pin' => '1111',
        ]);

        $response = $this->getJson('/api/audit/logins', $headers);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'attempted_username',
                    'status',
                    'status_label',
                    'ip_address',
                    'user_agent',
                    'created_at',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }

    public function test_seller_cannot_access_audit_logs(): void
    {
        $ownerHeaders = $this->ownerHeaders();

        $this->postJson('/api/users', [
            'name' => 'Vendedor Test',
            'username' => 'vendedortest',
            'email' => 'vendedortest@servimatica.com',
            'password' => 'pass123',
            'pin' => '7777',
            'role' => 'vendedor',
        ], $ownerHeaders);

        $token = $this->postJson('/api/auth/login', ['login' => 'vendedortest', 'pin' => '7777'])->json('accessToken');
        $sellerHeaders = ['Authorization' => "Bearer $token"];

        $response = $this->getJson('/api/audit/logins', $sellerHeaders);
        $response->assertForbidden();
    }
}
