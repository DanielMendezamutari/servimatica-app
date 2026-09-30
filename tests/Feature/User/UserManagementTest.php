<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function ownerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    private function employee(): array
    {
        return ['name' => 'Carlos Pérez', 'username' => 'carlos', 'email' => 'carlos@tienda.com',
            'password' => 'carlos123', 'pin' => '0246', 'role' => 'vendedor'];
    }

    public function test_owner_registers_lists_and_toggles_employee(): void
    {
        $headers = $this->ownerHeaders();
        $response = $this->postJson('/api/users', $this->employee(), $headers)->assertCreated()
            ->assertJsonPath('data.status', 'active')->assertJsonMissingPath('data.password')->assertJsonMissingPath('data.pin_code');
        $id = $response->json('data.id');
        $this->assertTrue(Hash::check('0246', User::find($id)->pin_code));
        $this->getJson('/api/users', $headers)->assertOk()->assertJsonCount(2, 'data');
        $token = $this->postJson('/api/auth/login', ['login' => 'carlos', 'pin' => '0246'])->assertOk()->json('accessToken');
        $this->postJson('/api/auth/login', ['login' => 'carlos', 'password' => 'carlos123'])->assertOk();
        $this->patchJson("/api/users/$id/toggle-status", [], $headers)->assertOk()->assertJsonPath('data.status', 'inactive');
        $this->getJson('/api/auth/me', ['Authorization' => "Bearer $token"])->assertForbidden();
        $this->postJson('/api/auth/login', ['login' => 'carlos', 'pin' => '0246'])->assertForbidden();
        $this->patchJson("/api/users/$id/toggle-status", [], $headers)->assertOk()->assertJsonPath('data.status', 'active');
        $this->patchJson('/api/users/1/toggle-status', [], $headers)->assertUnprocessable();
    }

    public function test_validation_enforces_unique_identity_role_and_credentials(): void
    {
        $headers = $this->ownerHeaders();
        $this->postJson('/api/users', $this->employee(), $headers)->assertCreated();
        $this->postJson('/api/users', $this->employee(), $headers)->assertUnprocessable()->assertJsonValidationErrors(['username', 'email']);
        foreach (['pin' => '12ab', 'password' => '12345', 'role' => 'admin', 'username' => 'bad alias'] as $field => $value) {
            $data = array_replace($this->employee(), ['username' => 'otro', 'email' => 'otro@tienda.com'], [$field => $value]);
            $this->postJson('/api/users', $data, $headers)->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseCount('users', 2);
    }

    public function test_edit_preserves_or_resets_credentials_and_protects_owner_role(): void
    {
        $headers = $this->ownerHeaders();
        $id = $this->postJson('/api/users', $this->employee(), $headers)->json('data.id');
        $data = $this->employee();
        unset($data['password'], $data['pin']);
        $data['name'] = 'Carlos Actualizado';
        $this->putJson("/api/users/$id", $data, $headers)->assertOk()->assertJsonPath('data.name', 'Carlos Actualizado');
        $this->postJson('/api/auth/login', ['login' => 'carlos', 'pin' => '0246'])->assertOk();
        $this->putJson("/api/users/$id", $data + ['pin' => '2468', 'password' => 'nueva123'], $headers)->assertOk();
        $this->postJson('/api/auth/login', ['login' => 'carlos', 'pin' => '0246'])->assertUnauthorized();
        $this->postJson('/api/auth/login', ['login' => 'carlos', 'pin' => '2468'])->assertOk();
        $this->postJson('/api/auth/login', ['login' => 'carlos', 'password' => 'nueva123'])->assertOk();
        $this->putJson("/api/users/$id", array_replace($data, ['username' => 'admin']), $headers)->assertUnprocessable();
        $this->putJson('/api/users/1', ['name' => 'Dueño', 'username' => 'admin', 'email' => 'admin@servimatica.com', 'role' => 'vendedor'], $headers)->assertUnprocessable();
        $this->putJson('/api/users/999', $data, $headers)->assertNotFound();
    }

    public function test_seeding_again_does_not_reset_owner_credentials(): void
    {
        $this->seed();
        User::first()->update(['password' => Hash::make('changed123')]);
        $this->seed();
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('changed123', User::first()->password));
    }
}
