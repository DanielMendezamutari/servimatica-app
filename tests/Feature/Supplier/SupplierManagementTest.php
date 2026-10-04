<?php

namespace Tests\Feature\Supplier;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    private function getOwnerToken(): string
    {
        User::create([
            'name' => 'Dueño Test',
            'username' => 'dueno',
            'email' => 'dueno@servimatica.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('1234'),
            'role' => 'dueno',
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/auth/login', [
            'login' => 'dueno',
            'pin' => '1234',
        ])->assertOk();

        return $res->json('accessToken');
    }

    private function getSellerToken(): string
    {
        User::create([
            'name' => 'Vendedor Test',
            'username' => 'vendedor',
            'email' => 'vendedor@servimatica.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('5678'),
            'role' => 'vendedor',
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/auth/login', [
            'login' => 'vendedor',
            'pin' => '5678',
        ])->assertOk();

        return $res->json('accessToken');
    }

    public function test_owner_can_crud_supplier_and_toggle_status(): void
    {
        $token = $this->getOwnerToken();
        $headers = ['Authorization' => "Bearer {$token}"];

        // 1. Create Supplier
        $createRes = $this->postJson('/api/suppliers', [
            'name' => 'Deltron Bolivia SRL',
            'nit' => '1029384019',
            'contact_name' => 'Lic. Mario Terán',
            'phone' => '77123456',
            'email' => 'ventas@deltron.bo',
            'city' => 'Santa Cruz',
            'address' => 'Av. Banzer 4to Anillo',
        ], $headers)->assertCreated();

        $createRes->assertJsonPath('data.name', 'Deltron Bolivia SRL');
        $createRes->assertJsonPath('data.is_active', true);
        $supplierId = $createRes->json('data.id');

        // 2. List Suppliers
        $listRes = $this->getJson('/api/suppliers', $headers)->assertOk();
        $listRes->assertJsonCount(1, 'data');
        $listRes->assertJsonPath('data.0.name', 'Deltron Bolivia SRL');

        // 3. Options list
        $optionsRes = $this->getJson('/api/suppliers/options', $headers)->assertOk();
        $optionsRes->assertJsonCount(1, 'data');
        $optionsRes->assertJsonPath('data.0.name', 'Deltron Bolivia SRL');

        // 4. Update Supplier
        $updateRes = $this->putJson("/api/suppliers/{$supplierId}", [
            'name' => 'Deltron Bolivia Mayorista',
            'nit' => '1029384019',
            'contact_name' => 'Lic. Mario Terán',
            'phone' => '77999999',
            'email' => 'ventas2@deltron.bo',
            'city' => 'Santa Cruz',
            'address' => 'Av. Cristo Redentor',
        ], $headers)->assertOk();

        $updateRes->assertJsonPath('data.name', 'Deltron Bolivia Mayorista');
        $updateRes->assertJsonPath('data.phone', '77999999');

        // 5. Toggle Status
        $toggleRes = $this->patchJson("/api/suppliers/{$supplierId}/toggle-status", [], $headers)->assertOk();
        $toggleRes->assertJsonPath('data.is_active', false);

        // Verify inactive is excluded from options
        $optionsAfterInactive = $this->getJson('/api/suppliers/options', $headers)->assertOk();
        $optionsAfterInactive->assertJsonCount(0, 'data');
    }

    public function test_seller_is_forbidden_from_managing_suppliers(): void
    {
        $ownerToken = $this->getOwnerToken();
        $ownerHeaders = ['Authorization' => "Bearer {$ownerToken}"];

        $supplier = $this->postJson('/api/suppliers', [
            'name' => 'Intcomex Bolivia',
            'phone' => '71122334',
        ], $ownerHeaders)->assertCreated();

        $supplierId = $supplier->json('data.id');

        $sellerToken = $this->getSellerToken();
        $sellerHeaders = ['Authorization' => "Bearer {$sellerToken}"];

        // Seller must receive 403 Forbidden
        $this->getJson('/api/suppliers', $sellerHeaders)->assertForbidden();
        $this->getJson('/api/suppliers/options', $sellerHeaders)->assertForbidden();
        $this->postJson('/api/suppliers', ['name' => 'Test'], $sellerHeaders)->assertForbidden();
        $this->putJson("/api/suppliers/{$supplierId}", ['name' => 'Test 2'], $sellerHeaders)->assertForbidden();
        $this->patchJson("/api/suppliers/{$supplierId}/toggle-status", [], $sellerHeaders)->assertForbidden();

        // Unauthenticated guest must receive 401 Unauthorized
        $this->getJson('/api/suppliers')->assertUnauthorized();
    }
}
