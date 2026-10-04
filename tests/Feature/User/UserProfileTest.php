<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    private function ownerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_owner_can_create_user_with_full_profile(): void
    {
        $headers = $this->ownerHeaders();

        $response = $this->postJson('/api/users', [
            'name' => 'Mario Valenzuela',
            'ci' => '7849201 LP',
            'username' => 'mario',
            'email' => 'mario@servimatica.com',
            'phone' => '77218392',
            'address' => 'Av. Arce #2133',
            'gender' => 'masculino',
            'sales_commission' => 3.5,
            'branch' => 'Sucursal 1 - Central',
            'password' => 'mario123',
            'pin' => '9876',
            'role' => 'vendedor',
        ], $headers);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Mario Valenzuela');
        $response->assertJsonPath('data.ci', '7849201 LP');
        $response->assertJsonPath('data.phone', '77218392');
        $response->assertJsonPath('data.gender', 'masculino');
        $response->assertJsonPath('data.sales_commission', 3.5);
        $response->assertJsonPath('data.branch', 'Sucursal 1 - Central');

        $this->assertDatabaseHas('users', [
            'username' => 'mario',
            'ci' => '7849201 LP',
            'phone' => '77218392',
            'gender' => 'masculino',
            'branch' => 'Sucursal 1 - Central',
        ]);
    }

    public function test_owner_can_update_user_profile(): void
    {
        $headers = $this->ownerHeaders();

        $created = $this->postJson('/api/users', [
            'name' => 'Elena Flores',
            'ci' => '5432109 SC',
            'username' => 'elena',
            'password' => 'elena123',
            'role' => 'vendedor',
        ], $headers);

        $userId = $created->json('data.id');

        $response = $this->putJson("/api/users/{$userId}", [
            'name' => 'Elena Flores de Mendez',
            'ci' => '5432109 SC',
            'username' => 'elena',
            'email' => 'elena@servimatica.com',
            'phone' => '69012345',
            'address' => 'Barrio Sirari',
            'gender' => 'femenino',
            'sales_commission' => 5.0,
            'branch' => 'Sucursal 2 - Equipetrol',
            'role' => 'vendedor',
        ], $headers);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Elena Flores de Mendez');
        $response->assertJsonPath('data.phone', '69012345');
        $response->assertJsonPath('data.gender', 'femenino');
        $this->assertEquals(5.0, (float) $response->json('data.sales_commission'));

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'name' => 'Elena Flores de Mendez',
            'phone' => '69012345',
            'gender' => 'femenino',
            'branch' => 'Sucursal 2 - Equipetrol',
        ]);
    }

    public function test_owner_can_export_users_to_excel(): void
    {
        $headers = $this->ownerHeaders();

        $this->postJson('/api/users', [
            'name' => 'Carlos Tester',
            'ci' => '9988776 CB',
            'username' => 'carlostest',
            'password' => 'carlos123',
            'role' => 'vendedor',
        ], $headers);

        $response = $this->get('/api/users/export-excel', $headers);

        $response->assertOk();
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response->baseResponse);
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('nomina_personal_servimatica_', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }
}
