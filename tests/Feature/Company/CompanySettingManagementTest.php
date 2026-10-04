<?php

namespace Tests\Feature\Company;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanySettingManagementTest extends TestCase
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

    public function test_owner_can_get_company_settings(): void
    {
        $token = auth('api')->login($this->owner);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/company-settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'trade_name',
                'branch_name',
                'city',
                'address',
                'mobile',
                'logo_url',
            ]);
    }

    public function test_seller_cannot_manage_company_settings(): void
    {
        $token = auth('api')->login($this->seller);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/company-settings');

        $response->assertStatus(403);

        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/company-settings', [
                'trade_name' => 'Hack Store',
            ]);

        $updateResponse->assertStatus(403);
    }

    public function test_owner_can_update_company_settings_with_logo(): void
    {
        Storage::fake('public');

        $token = auth('api')->login($this->owner);

        $logo = UploadedFile::fake()->image('logo.png', 400, 150);

        $payload = [
            'trade_name' => 'Servimática Riberalta',
            'legal_name' => 'Servimática Computación SRL',
            'tax_id' => '1029384756',
            'slogan' => 'Tecnología y Servicio Especializado',
            'branch_name' => 'Sucursal Central',
            'city' => 'Riberalta, Beni — Bolivia',
            'address' => 'Av. Plácido Méndez N° 450',
            'mobile' => '77123456',
            'phone' => '3-8524455',
            'email' => 'riberalta@servimatica.com',
            'default_quote_terms' => 'Validez: 7 días hábiles.',
            'receipt_footer_message' => 'Garantía oficial de 6 meses.',
            'logo' => $logo,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/company-settings', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'trade_name' => 'Servimática Riberalta',
                'city' => 'Riberalta, Beni — Bolivia',
                'address' => 'Av. Plácido Méndez N° 450',
                'mobile' => '77123456',
            ]);

        $this->assertDatabaseHas('company_settings', [
            'trade_name' => 'Servimática Riberalta',
            'city' => 'Riberalta, Beni — Bolivia',
            'mobile' => '77123456',
        ]);
    }

    public function test_public_can_get_company_info_without_token(): void
    {
        $response = $this->getJson('/api/company-settings/public');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'trade_name',
                'slogan',
                'branch_name',
                'city',
                'address',
                'mobile',
                'email',
                'logo_url',
            ]);
    }
}
