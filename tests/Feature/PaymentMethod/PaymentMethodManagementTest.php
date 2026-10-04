<?php

namespace Tests\Feature\PaymentMethod;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentMethodManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Dueño',
            'username' => 'dueno',
            'email' => 'dueno@servimatica.com',
            'password' => Hash::make('password'),
            'role' => 'dueno',
            'status' => 'active',
            'pin_code' => '1234',
        ]);

        $this->seller = User::create([
            'name' => 'Vendedor',
            'username' => 'vendedor',
            'email' => 'vendedor@servimatica.com',
            'password' => Hash::make('password'),
            'role' => 'vendedor',
            'status' => 'active',
            'pin_code' => '5678',
        ]);
    }

    public function test_owner_can_list_payment_methods(): void
    {
        $token = auth('api')->login($this->owner);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/payment-methods');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_seller_cannot_manage_payment_methods(): void
    {
        $token = auth('api')->login($this->seller);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/payment-methods');

        $response->assertStatus(403);

        $responsePost = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payment-methods', [
                'name' => 'Banco Ficticio',
                'type' => 'bank_transfer',
            ]);

        $responsePost->assertStatus(403);
    }

    public function test_owner_can_create_payment_method_with_qr_image(): void
    {
        Storage::fake('public');
        $token = auth('api')->login($this->owner);

        $file = UploadedFile::fake()->image('qr-union.png', 300, 300);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payment-methods', [
                'name' => 'QR Simple Banco Unión',
                'type' => 'qr',
                'bank_name' => 'Banco Unión',
                'account_number' => '10000012345678',
                'account_holder' => 'Servimática S.R.L.',
                'requires_reference' => true,
                'applies_to' => 'sales',
                'sort_order' => 1,
                'qr_image' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'QR Simple Banco Unión')
            ->assertJsonPath('data.type', 'qr')
            ->assertJsonPath('data.requires_reference', true);

        $this->assertDatabaseHas('payment_methods', [
            'name' => 'QR Simple Banco Unión',
            'bank_name' => 'Banco Unión',
            'requires_reference' => true,
        ]);
    }

    public function test_owner_can_update_and_toggle_status_of_payment_method(): void
    {
        $token = auth('api')->login($this->owner);

        $createRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payment-methods', [
                'name' => 'Transferencia BCP',
                'type' => 'bank_transfer',
                'bank_name' => 'BCP',
                'account_number' => '201-998877',
            ]);

        $id = $createRes->json('data.id');

        // Update
        $updateRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/payment-methods/{$id}", [
                'name' => 'Transferencia BCP Modificada',
                'type' => 'bank_transfer',
                'bank_name' => 'Banco de Crédito',
                'account_number' => '201-998877-0-1',
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Transferencia BCP Modificada');

        // Toggle
        $toggleRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/payment-methods/{$id}/toggle-status");

        $toggleRes->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('payment_methods', [
            'id' => $id,
            'is_active' => false,
        ]);
    }

    public function test_seller_and_owner_can_fetch_payment_options_for_sales(): void
    {
        $token = auth('api')->login($this->seller);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/payment-methods/options?context=sales');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }
}
