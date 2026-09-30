<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_login_with_email_or_alias_and_password_or_pin(): void
    {
        $this->seed();
        foreach (['admin', 'admin@servimatica.com'] as $login) {
            foreach ([['password' => 'password'], ['pin' => '1234']] as $credential) {
                $response = $this->postJson('/api/auth/login', ['login' => $login] + $credential);
                $response->assertOk()->assertJsonPath('userData.role', 'dueno')
                    ->assertJsonPath('expiresIn', 28800)->assertJsonPath('userAbilityRules.0.subject', 'all');
                $token = $response->json('accessToken');
                $claims = json_decode(base64_decode(strtr(explode('.', $token)[1], '-_', '+/')), true);
                $this->assertSame(28800, $claims['exp'] - $claims['iat']);
                $this->getJson('/api/auth/me', ['Authorization' => "Bearer $token"])->assertOk()
                    ->assertJsonMissingPath('userData.password')->assertJsonMissingPath('userData.pin_code');
            }
        }
        $this->assertTrue(Hash::check('1234', User::first()->pin_code));
    }

    public function test_invalid_credentials_and_invalid_shapes_are_rejected(): void
    {
        $this->seed();
        $this->postJson('/api/auth/login', ['login' => 'admin', 'password' => 'bad'])->assertUnauthorized();
        $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '9999'])->assertUnauthorized();
        foreach ([[], ['pin' => '123'], ['pin' => '12ab'], ['pin' => 1234], ['pin' => '1234', 'password' => 'password']] as $fields) {
            $this->postJson('/api/auth/login', ['login' => 'admin'] + $fields)->assertUnprocessable();
        }
    }

    public function test_inactive_user_cannot_login_by_either_method(): void
    {
        $this->seed();
        User::first()->update(['status' => 'inactive']);
        foreach ([['password' => 'password'], ['pin' => '1234']] as $credential) {
            $this->postJson('/api/auth/login', ['login' => 'admin'] + $credential)->assertForbidden();
        }
    }

    public function test_logout_invalidates_token_and_expired_tokens_are_rejected(): void
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        $headers = ['Authorization' => "Bearer $token"];
        $this->postJson('/api/auth/logout', [], $headers)->assertOk();
        $this->getJson('/api/auth/me', $headers)->assertUnauthorized();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        $this->travel(481)->minutes();
        $this->getJson('/api/auth/me', ['Authorization' => "Bearer $token"])->assertUnauthorized();
        $this->travelBack();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/login', ['login' => 'unknown', 'pin' => '9999'])->assertUnauthorized();
        }
        $this->postJson('/api/auth/login', ['login' => 'unknown', 'pin' => '9999'])->assertStatus(429);
    }
}
