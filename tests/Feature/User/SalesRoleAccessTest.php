<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SalesRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_and_guest_cannot_manage_users(): void
    {
        User::create(['name' => 'Carlos', 'username' => 'carlos', 'email' => 'carlos@tienda.com',
            'password' => Hash::make('password'), 'pin_code' => Hash::make('2468'), 'role' => 'vendedor', 'status' => 'active']);
        $login = $this->postJson('/api/auth/login', ['login' => 'carlos', 'pin' => '2468'])->assertOk();
        $login->assertJsonPath('userAbilityRules', [
            ['action' => 'read', 'subject' => 'Dashboard'],
            ['action' => 'read', 'subject' => 'Product'],
            ['action' => 'manage', 'subject' => 'Pos'],
            ['action' => 'manage', 'subject' => 'Quote'],
            ['action' => 'manage', 'subject' => 'CashShift'],
            ['action' => 'read', 'subject' => 'Sale'],
            ['action' => 'manage', 'subject' => 'Client'],
        ]);
        $headers = ['Authorization' => 'Bearer '.$login->json('accessToken')];
        foreach ([['GET', '/api/users'], ['POST', '/api/users'], ['PUT', '/api/users/1'], ['PATCH', '/api/users/1/toggle-status']] as [$method, $url]) {
            $this->json($method, $url, [], $headers)->assertForbidden();
            $this->json($method, $url)->assertUnauthorized();
        }
        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer invalid'])->assertUnauthorized();
    }
}
